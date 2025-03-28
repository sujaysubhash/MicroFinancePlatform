<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "User not logged in."]);
    exit();
}

$user_id = $_SESSION['user_id'];  // Lender's ID

$host = "localhost";
$dbname = "mfp_database";
$username = "root";
$password = "";
$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed."]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'], $_POST['amount'])) {
    $action = $_POST['action'];
    $amount = floatval($_POST['amount']);

    // Get lender's wallet balance
    $stmt = $conn->prepare("SELECT wallet_balance FROM lenders WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($wallet_balance);
    $stmt->fetch();
    $stmt->close();

    if ($action === "add") {
        // Add money to wallet
        $new_balance = $wallet_balance + $amount;
        $stmt = $conn->prepare("UPDATE lenders SET wallet_balance = ? WHERE id = ?");
        $stmt->bind_param("di", $new_balance, $user_id);
        $stmt->execute();
        $stmt->close();

        echo json_encode(["success" => true, "new_balance" => $new_balance, "message" => "Money added successfully!"]);

    } elseif ($action === "deduct") {
        if ($amount > $wallet_balance) {
            echo json_encode(["success" => false, "message" => "Insufficient balance."]);
        } else {
            // Deduct money from wallet
            $new_balance = $wallet_balance - $amount;
            $stmt = $conn->prepare("UPDATE lenders SET wallet_balance = ? WHERE id = ?");
            $stmt->bind_param("di", $new_balance, $user_id);
            $stmt->execute();
            $stmt->close();

            echo json_encode(["success" => true, "new_balance" => $new_balance, "message" => "Amount deducted successfully!"]);
        }

    } elseif ($action === "fund_loan" && isset($_POST['loan_id'])) {
        $loan_id = intval($_POST['loan_id']);

        if ($amount > $wallet_balance) {
            echo json_encode(["success" => false, "message" => "Insufficient wallet balance!"]);
            exit();
        }

        // Get borrower's current funded amount and name
        $stmt = $conn->prepare("SELECT funded_amount, name FROM borrower WHERE loan_id = ?");
        $stmt->bind_param("i", $loan_id);
        $stmt->execute();
        $stmt->bind_result($funded_amount, $borrower_name);
        $stmt->fetch();
        $stmt->close();

        // Debugging step to check if the borrower name is fetched correctly
        if ($borrower_name === null || empty($borrower_name)) {
            echo json_encode(["success" => false, "message" => "Borrower name not found for Loan ID: $loan_id"]);
            exit();
        }

        // Begin transaction
        $conn->begin_transaction();

        try {
            // Update borrower's funded amount
            $newFundedAmount = $funded_amount + $amount;
            $stmt = $conn->prepare("UPDATE borrower SET funded_amount = ? WHERE loan_id = ?");
            $stmt->bind_param("di", $newFundedAmount, $loan_id);
            $stmt->execute();
            $stmt->close();

            // Deduct from lender's wallet
            $newWalletBalance = $wallet_balance - $amount;
            $stmt = $conn->prepare("UPDATE lenders SET wallet_balance = ? WHERE id = ?");
            $stmt->bind_param("di", $newWalletBalance, $user_id);
            $stmt->execute();
            $stmt->close();

            // Commit transaction
            $conn->commit();

            echo json_encode([
                "success" => true,
                "new_balance" => $newWalletBalance,
                "message" => "₹{$amount} successfully funded to Borrower: {$borrower_name}."
            ]);
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            echo json_encode(["success" => false, "message" => "Transaction failed. Please try again."]);
        }
    }
}

$conn->close();
?>
