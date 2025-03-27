<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['id'])) {
        echo json_encode(["success" => false, "message" => "Lender ID missing"]);
        exit();
    }

    $lender_id = intval($data['id']);

    // Database configuration
    $host = "localhost";
    $dbname = "mfp_database";
    $username = "root";
    $password = "";

    // Create connection
    $conn = new mysqli($host, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        echo json_encode(["success" => false, "message" => "Database connection failed"]);
        exit();
    }

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Delete lender from the `lenders` table
        $stmt1 = $conn->prepare("DELETE FROM lenders WHERE id = ?");
        $stmt1->bind_param("i", $lender_id);
        $stmt1->execute();
        $stmt1->close();

        // Delete lender from the `users` table
        $stmt2 = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt2->bind_param("i", $lender_id);
        $stmt2->execute();
        $stmt2->close();

        // Commit transaction
        $conn->commit();
        echo json_encode(["success" => true]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["success" => false, "message" => "Error deleting lender"]);
    }

    $conn->close();
}
?>
