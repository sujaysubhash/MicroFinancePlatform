<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email']) && isset($_POST['borrower']) && isset($_POST['loanid'])) {
    $to = $_POST['email'];
    $borrower = $_POST['borrower'];
    $loanId = $_POST['loanid'];
    $lender = $_POST['lender']; // optional
    $amount = $_POST['amount']; // optional

    $subject = "Final Payment Reminder for Loan ID: $loanId";

    $message = "
    Dear $borrower,

    This is a final reminder regarding your pending loan repayment (Loan ID: $loanId). As per the agreement, the due amount must be settled immediately.

    Failure to comply will result in legal action from the lender and platform, without further notice.

    Please consider this as a legal warning.

    Regards,
    $lender
    Micro Finance Platform (MFP)
    ";

    $headers = "From: no-reply@mfpplatform.com\r\n";
    $headers .= "Reply-To: support@mfpplatform.com\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    if (mail($to, $subject, $message, $headers)) {
        echo "Reminder email successfully sent to $borrower.";
    } else {
        echo "Failed to send email.";
    }
} else {
    echo "Invalid request.";
}
?>
