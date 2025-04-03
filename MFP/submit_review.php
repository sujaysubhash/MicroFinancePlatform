<?php
session_start(); // Start session
require 'db_connection.php'; // Include database connection

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to login page if not logged in
    exit();
}

// Fetch borrower ID from session
$borrower_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $lender_id = $_POST['lender_id'] ?? null;
    $rating = $_POST['rating'] ?? null;
    $review_text = $_POST['review'] ?? null;

    // Validate inputs
    if (!$lender_id || !$rating || empty(trim($review_text))) {
        echo "<script>
            alert('All fields are required!');
            window.location.href = 'borrower_payment_history.php';
        </script>";
        exit();
    }

    // Ensure rating is within valid range (1 to 5)
    if ($rating < 1 || $rating > 5) {
        echo "<script>
            alert('Invalid rating! Please select a value between 1 and 5.');
            window.location.href = 'borrower_payment_history.php';
        </script>";
        exit();
    }
    // Insert review into the database
    $sql = "INSERT INTO borrower_reviews (borrower_id, lender_id, review_text, rating, created_at, status) 
            VALUES (?, ?, ?, ?, NOW(), 'pending')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisd", $borrower_id, $lender_id, $review_text, $rating);
    $stmt->execute();
    $stmt->close();
    
    // Update the lender's rating (convert to int)
    $update_sql = "UPDATE lenders 
                   SET rating = (SELECT ROUND(AVG(rating)) FROM borrower_reviews WHERE lender_id = ?) 
                   WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ii", $lender_id, $lender_id);
    $update_stmt->execute();
    $update_stmt->close();
    

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("iisd", $borrower_id, $lender_id, $review_text, $rating);
        if ($stmt->execute()) {
            echo "<script>
                alert('Review submitted successfully!');
                window.location.href = 'borrower_payment-history.php';
            </script>";
        } else {
            echo "<script>
                alert('Error submitting review!');
                window.location.href = 'borrower_payment-history.php';
            </script>";
        }
        $stmt->close();
    } else {
        echo "<script>
            alert('Database error!');
            window.location.href = 'borrower_payment-history.php';
        </script>";
    }

    $conn->close();
}
?>
