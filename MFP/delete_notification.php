<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo "error";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'])) {
    $notification_id = intval($_POST['id']);

    // Database connection
    $host = "localhost";
    $dbname = "mfp_database";
    $username = "root";
    $password = "";
    $conn = new mysqli($host, $username, $password, $dbname);

    if ($conn->connect_error) {
        echo "error";
        exit();
    }

    // Delete query
    $sql = "DELETE FROM notifications WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $notification_id);

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error";
    }

    $stmt->close();
    $conn->close();
} else {
    echo "error";
}
?>
