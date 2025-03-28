<?php 
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$borrower_id = $_SESSION['user_id']; // Logged-in borrower
$loan_id = filter_input(INPUT_POST, 'loanid', FILTER_VALIDATE_INT);

if (!$loan_id) {
    die("Invalid loan ID.");
}

// Database connection
$host = "localhost";
$dbname = "mfp_database";
$username = "root";
$password = "";
$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch lender ID and requested loan amount
$sql = "SELECT lender_id, requested_loan_amount FROM loan_application WHERE loanid = ? AND borrower_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $loan_id, $borrower_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $loan = $result->fetch_assoc();
    $lender_id = $loan['lender_id'];
    $loan_amount = $loan['requested_loan_amount'];

    // Insert payment request
    $insert_sql = "INSERT INTO payments (lender_id, borrower_id, amount, status) VALUES (?, ?, ?, 'Pending')";
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("iid", $lender_id, $borrower_id, $loan_amount);

    if ($stmt->execute()) {
        echo "Payment request sent successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
} else {
    echo "Loan application not found.";
}

$conn->close();
?>
