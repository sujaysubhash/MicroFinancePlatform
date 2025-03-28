<?php
session_start();

// Check if the user is logged in and is a lender
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Lender') {
    die("Unauthorized access.");
}

// Fetch lender details
$lender_id = $_SESSION['user_id'];

// Check if loan ID is provided
if (!isset($_POST['loanid'])) {
    die("Invalid request.");
}
$loan_id = intval($_POST['loanid']);

// Database connection
$host = "localhost";
$dbname = "mfp_database";
$username = "root";
$password = "";
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch loan details
$loan_query = "SELECT requested_loan_amount, borrower_id FROM loan_application WHERE loanid = ? AND status = 'pending'";
$stmt = $conn->prepare($loan_query);
$stmt->bind_param("i", $loan_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Loan not found or already funded.");
}

$loan = $result->fetch_assoc();
$loan_amount = $loan['requested_loan_amount'];
$borrower_id = $loan['borrower_id'];
$stmt->close();

// Fetch lender wallet balance
$lender_query = "SELECT wallet_balance FROM lenders WHERE id = ?";
$stmt = $conn->prepare($lender_query);
$stmt->bind_param("i", $lender_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Lender not found.");
}

$lender = $result->fetch_assoc();
$lender_balance = $lender['wallet_balance'];
$stmt->close();

// Check if lender has enough funds
if ($lender_balance < $loan_amount) {
    die("Insufficient funds in your wallet.");
}

// Deduct funds from lender
$update_lender = "UPDATE lenders SET wallet_balance = wallet_balance - ? WHERE id = ?";
$stmt = $conn->prepare($update_lender);
$stmt->bind_param("di", $loan_amount, $lender_id);
$stmt->execute();
$stmt->close();

// Update borrower's funded amount
$update_borrower = "UPDATE borrower SET funded_amount = funded_amount + ? WHERE user_id = ?";
$stmt = $conn->prepare($update_borrower);
$stmt->bind_param("di", $loan_amount, $borrower_id);
$stmt->execute();
$stmt->close();

// Update loan status to approved
$update_loan = "UPDATE loan_application SET status = 'approved' WHERE loanid = ?";
$stmt = $conn->prepare($update_loan);
$stmt->bind_param("i", $loan_id);
$stmt->execute();
$stmt->close();

$conn->close();

echo "Loan successfully funded.";
header("Location: fund_loan.php"); // Redirect back to loan requests page
exit();
?>
