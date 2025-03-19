<?php
if (isset($_GET['account_number'])) {
    $accountNumber = $_GET['account_number'];

    // Simulating fetching CIBIL score (Replace with actual DB/API)
    $cibilScore = rand(300, 900);

    echo json_encode(["credit_score" => $cibilScore]);
}
?>
