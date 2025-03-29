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

        $stmt = $conn->prepare("UPDATE borrower SET wallet_balance = wallet_balance - ? WHERE user_id = ?");
        $stmt->bind_param("di", $amount, $borrower_id);
        $stmt->execute();

        $stmt = $conn->prepare("UPDATE lenders SET wallet_balance = wallet_balance + ? WHERE id = ?");
        $stmt->bind_param("di", $amount, $lender_id);
        $stmt->execute();

        $stmt = $conn->prepare("UPDATE loan_application SET status = IF(requested_loan_amount <= (SELECT SUM(wallet_balance) FROM lenders WHERE id = ?), 'Completed', 'Pending') WHERE loanid = ?");
        $stmt->bind_param("ii", $lender_id, $loan_id);
        $stmt->execute();

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
