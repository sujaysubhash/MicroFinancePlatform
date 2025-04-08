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
    $_SESSION['error_message'] = "Invalid request.";
    header("Location: lender_page_amount_request.php");
    exit();
}
$loan_id = intval($_POST['loanid']);

// Check if the loan was already funded by this lender
if (isset($_SESSION['funded_loans']) && in_array($loan_id, $_SESSION['funded_loans'])) {
    $_SESSION['error_message'] = "Loan already funded.";
    header("Location: lender_page_amount_request.php");
    exit();
}

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
$loan_query = "SELECT requested_loan_amount, borrower_id FROM loan_application WHERE loanid = ? AND status = 'approved'";
$stmt = $conn->prepare($loan_query);
$stmt->bind_param("i", $loan_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = "Loan not found or already funded.";
    header("Location: lender_page_amount_request.php");
    exit();
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
    $_SESSION['error_message'] = "Lender not found.";
    header("Location: lender_page_amount_request.php");
    exit();
}

$lender = $result->fetch_assoc();
$lender_balance = $lender['wallet_balance'];
$stmt->close();

// Check if lender has enough funds
if ($lender_balance < $loan_amount) {
    $_SESSION['error_message'] = "Insufficient funds in your wallet.";
    header("Location: lender_page_amount_request.php");
    exit();
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

// Update loan status to funded
$update_loan = "UPDATE loan_application SET status = 'funded' WHERE loanid = ?";
$stmt = $conn->prepare($update_loan);
$stmt->bind_param("i", $loan_id);
$stmt->execute();
$stmt->close();

//Lender responded updating to true.
$update_response = "UPDATE loan_application SET lender_responded = 'true' WHERE loanid = ?";
$stmt = $conn->prepare($update_response);
$stmt->bind_param("i", $loan_id);
$stmt->execute();
$stmt->close();


// Fetch borrower name
$borrower_query = "SELECT name FROM users WHERE id = ?";
$stmt = $conn->prepare($borrower_query);
$stmt->bind_param("i", $borrower_id);
$stmt->execute();
$result = $stmt->get_result();
$borrower = $result->fetch_assoc();
$borrower_name = $borrower['name'];
$stmt->close();

// Fetch lender name
$lender_query = "SELECT name FROM users WHERE id = ?";
$stmt = $conn->prepare($lender_query);
$stmt->bind_param("i", $lender_id);
$stmt->execute();
$result = $stmt->get_result();
$lender = $result->fetch_assoc();
$lender_name = $lender['name'];
$stmt->close();


// Insert notification
$notification_message = "Loan approved and funded to {$borrower_name} by {$lender_name}.";
$notification_type = "Loan Approved";
$notification_status = "Unread";

$insert_notification = "INSERT INTO notifications (user_id, loan_id, message, type, status) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($insert_notification);
$stmt->bind_param("iisss", $borrower_id, $loan_id, $notification_message, $notification_type, $notification_status);
$stmt->execute();
$stmt->close();


// **Generate Repayment Reminder Notification**
$due_date = date('d', strtotime('+6 months')); 

$repayment_message = "Loan Amount {$loan_amount} for the loan by {$lender_name} is due on {$due_date}th of the month.";
$repayment_type = "Repayment Reminder";
$repayment_status = "Unread";

$insert_repayment_notification = "INSERT INTO notifications (user_id, loan_id, message, type, status) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($insert_repayment_notification);
$stmt->bind_param("iisss", $borrower_id, $loan_id, $repayment_message, $repayment_type, $repayment_status);
$stmt->execute();
$stmt->close();

// Store the funded loan in the session
if (!isset($_SESSION['funded_loans'])) {
    $_SESSION['funded_loans'] = [];
}
$_SESSION['funded_loans'][] = $loan_id;

$conn->close();

// Redirect after funding
header("Location: lender_page_amount_request.php");
exit();
?>
