<?php
include "db_connection.php"; // Ensure database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Fetch and sanitize input values
    $borrower_id = $_POST['borrower_id'] ?? null; // Assuming borrower ID is available in session
    $lender_id = $_POST['lender_id'] ?? null;
    $lender_name = $_POST['lender_name'] ?? '';
    $interest_rate = $_POST['interest_rate'] ?? '';
    $employment_status = $_POST['employment_status'] ?? '';
    $income = $_POST['income'] ?? '';
    $email = $_POST['email'] ?? '';

    // Debugging: Check if lender_id is received
    if (!$lender_id || empty($lender_id)) {
        echo json_encode(["success" => false, "message" => "Error: Lender ID is missing.", "debug" => $_POST]);
        exit();
    }

    if (!$borrower_id) {
        echo json_encode(["success" => false, "message" => "Error: Borrower ID is missing."]);
        exit();
    }

    // Insert into loan_application table
    $stmt = $conn->prepare("INSERT INTO loan_application (borrower_id, lender_id, lender_name, interest_rate, employment_status, income, email, status) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");

    $stmt->bind_param("iisssis", $borrower_id, $lender_id, $lender_name, $interest_rate, $employment_status, $income, $email);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Loan application submitted successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => "Database error: " . $stmt->error]);
    }

    

    $stmt->close();
    $conn->close();
}
?>
