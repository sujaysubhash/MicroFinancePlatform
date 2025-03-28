<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
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

// Check if loanid is set
if (isset($_POST['loanid'])) {
    $loanid = $_POST['loanid'];

    // Update the loan status to 'approved'
    $sql = "UPDATE loan_application SET status = 'approved' WHERE loanid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $loanid);
    
    if ($stmt->execute()) {
        echo "<script>alert('Loan request approved successfully!'); window.location.href='loan_request.php';</script>";
    } else {
        echo "<script>alert('Error updating loan status'); window.history.back();</script>";
    }
    
    $stmt->close();
}

$conn->close();
?>
