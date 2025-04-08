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

    // First fetch borrower ID for the loan
    $loan_query = "SELECT borrower_id FROM loan_application WHERE loanid = ?";
    $stmt = $conn->prepare($loan_query);
    $stmt->bind_param("i", $loanid);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $loan = $result->fetch_assoc();
        $borrower_id = $loan['borrower_id'];
        $stmt->close();

        // Update the loan status to 'rejected'
        $sql = "UPDATE loan_application SET status = 'rejected' WHERE loanid = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $loanid);

        if ($stmt->execute()) {
            // Insert notification
            $message = "Your loan application (Loan ID: {$loanid}) has been rejected.";
            $type = "Loan Rejected";
            $status = "Unread";

            $notif_query = "INSERT INTO notifications (user_id, loan_id, message, type, status) VALUES (?, ?, ?, ?, ?)";
            $notif_stmt = $conn->prepare($notif_query);
            $notif_stmt->bind_param("iisss", $borrower_id, $loanid, $message, $type, $status);
            $notif_stmt->execute();
            $notif_stmt->close();

            echo "<script>alert('Loan request Rejected!'); window.location.href='loan_request.php';</script>";
        } else {
            echo "<script>alert('Error updating loan status'); window.history.back();</script>";
        }

        $stmt->close();
    } else {
        echo "<script>alert('Loan not found'); window.history.back();</script>";
    }
}

$conn->close();
?>
