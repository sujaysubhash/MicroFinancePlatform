<?php 
session_start();
include './db_connection.php'; 

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "User not logged in."]);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'];
$amount = floatval($_POST['amount']);

if ($amount <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid amount."]);
    exit;
}

$conn->begin_transaction();
try {
    // Fetch borrower's wallet balance
    $stmt = $conn->prepare("SELECT wallet_balance FROM borrower WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($wallet_balance);
    $stmt->fetch();
    $stmt->close();

    if ($action === "add") {
        // Add the amount to the wallet
        $wallet_balance += $amount;
        
        // Update borrower's wallet balance in the database
        $stmt = $conn->prepare("UPDATE borrower SET wallet_balance = ? WHERE user_id = ?");
        $stmt->bind_param("di", $wallet_balance, $user_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to update wallet balance.");
        }
    } elseif ($action === "deduct") {
        if ($wallet_balance < $amount) {
            throw new Exception("Insufficient balance.");
        }
        $wallet_balance -= $amount;

        // Check if borrower has an approved loan
        $stmt = $conn->prepare("
            SELECT lender_id FROM loan_application 
            WHERE borrower_id = ? AND status = 'approved' 
            ORDER BY loanid DESC LIMIT 1
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($lender_id);
        $stmt->fetch();
        $stmt->close();

        if (!$lender_id) {
            throw new Exception("No approved loan found for this borrower.");
        }

        // Fetch lender's wallet balance
        $stmt = $conn->prepare("SELECT wallet_balance FROM lenders WHERE id = ?");
        $stmt->bind_param("i", $lender_id);
        $stmt->execute();
        $stmt->bind_result($lender_wallet_balance);
        $stmt->fetch();
        $stmt->close();

        if ($lender_wallet_balance === null) {
            throw new Exception("Lender not found.");
        }

        // Update lender's wallet balance
        $new_lender_balance = $lender_wallet_balance + $amount;
        $stmt = $conn->prepare("UPDATE lenders SET wallet_balance = ? WHERE id = ?");
        $stmt->bind_param("di", $new_lender_balance, $lender_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to update lender's wallet.");
        }

        // Update borrower's wallet balance
        $stmt = $conn->prepare("UPDATE borrower SET wallet_balance = ? WHERE user_id = ?");
        $stmt->bind_param("di", $wallet_balance, $user_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to update borrower's wallet.");
        }
    } else {
        throw new Exception("Invalid action.");
    }

    $conn->commit();
    echo json_encode(["success" => true, "new_balance" => $wallet_balance]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
