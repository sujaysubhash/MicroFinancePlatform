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
    $stmt = $conn->prepare("SELECT wallet_balance FROM borrower WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($wallet_balance);
    $stmt->fetch();
    $stmt->close();

    if ($action === "add") {
        $wallet_balance += $amount;
    } elseif ($action === "deduct") {
        if ($wallet_balance < $amount) {
            throw new Exception("Insufficient balance.");
        }
        $wallet_balance -= $amount;
    } else {
        throw new Exception("Invalid action.");
    }

    $stmt = $conn->prepare("UPDATE borrower SET wallet_balance = ? WHERE user_id = ?");
    $stmt->bind_param("di", $wallet_balance, $user_id);
    $stmt->execute();
    
    $conn->commit();
    echo json_encode(["success" => true, "new_balance" => $wallet_balance]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>