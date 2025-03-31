<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$host = "localhost";
$dbname = "mfp_database";
$username = "root";
$password = "";
$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $borrower_id = $_SESSION['user_id'];
    $loan_id = $_POST['loan_id'];
    $lender_id = $_POST['lender_id'];
    $amount = $_POST['amount'];

    $conn->begin_transaction();

    try {
        // Fetch borrower's wallet balance
        $stmt = $conn->prepare("SELECT wallet_balance FROM borrower WHERE user_id = ?");
        $stmt->bind_param("i", $borrower_id);
        $stmt->execute();
        $stmt->bind_result($borrower_wallet);
        $stmt->fetch();
        $stmt->close();

        if ($borrower_wallet < $amount) {
            echo "<script>alert('Insufficient balance!'); window.location.href='borrower_payment-history.php';</script>";
            exit();
        }

        // Deduct from borrower
        $stmt = $conn->prepare("UPDATE borrower SET wallet_balance = wallet_balance - ? WHERE user_id = ?");
        $stmt->bind_param("di", $amount, $borrower_id);
        $stmt->execute();

        // Credit lender
        $stmt = $conn->prepare("UPDATE lenders SET wallet_balance = wallet_balance + ? WHERE id = ?");
        $stmt->bind_param("di", $amount, $lender_id);
        $stmt->execute();

        // Insert payment record
        $stmt = $conn->prepare("INSERT INTO payments (loan_id, lender_id, borrower_id, amount, status) VALUES (?, ?, ?, ?, 'Completed')");
        $stmt->bind_param("iiid", $loan_id, $lender_id, $borrower_id, $amount);
        $stmt->execute();

        // Count completed payments for this loan
        $stmt = $conn->prepare("SELECT COUNT(*) FROM payments WHERE borrower_id = ? AND loan_id = ? AND status = 'Completed'");
        $stmt->bind_param("ii", $borrower_id, $loan_id);
        $stmt->execute();
        $stmt->bind_result($paid_months);
        $stmt->fetch();
        $stmt->close();

        // Update loan status if all payments are made
        $stmt = $conn->prepare("SELECT loan_duration FROM loan_application WHERE loanid = ?");
        $stmt->bind_param("i", $loan_id);
        $stmt->execute();
        $stmt->bind_result($loan_duration);
        $stmt->fetch();
        $stmt->close();

        if ($paid_months >= $loan_duration) {
            $stmt = $conn->prepare("UPDATE loan_application SET status = 'Completed' WHERE loanid = ?");
            $stmt->bind_param("i", $loan_id);
            $stmt->execute();
        }

        $conn->commit();
        echo "<script>alert('Payment successful!'); window.location.href='borrower_payment-history.php';</script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Payment failed! Please try again.'); window.location.href='borrower_payment-history.php';</script>";
    }

    $stmt->close();
    $conn->close();
} else {
    echo "<script>alert('Invalid request!'); window.location.href='borrower_payment-history.php';</script>";
}
?>
