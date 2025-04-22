<?php
require 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['loanid'])) {
    // Get data from POST request
    $loanId = $_POST['loanid'];
    $lender = $_POST['lender'];
    $borrower = $_POST['borrower'];
    $requestedLoanAmount = $_POST['requested_loan_amount'];
    $interestRate = $_POST['interest_rate'];
    $loanDuration = $_POST['loan_duration'];

    // Get current date
    $contractDate = date("d-m-Y");

    // Bootstrap styled HTML content
    $html = "
    <!DOCTYPE html>
    <html>
    <head>
        <title>Loan Contract</title>
        <link rel='stylesheet' href='https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css'>

        <style>
            body { font-size: 14px; }
            .contract-box { padding: 30px; border: 1px solid #ccc; margin-top: 20px; }
        </style>
            <!-- Fevicon -->

    </head>
    
    <body>
        <div class='container contract-box'>
            <h2 class='text-center mb-4'>Loan Agreement</h2>
            <p><strong>Date:</strong> $contractDate</p>
            <p>This Loan Agreement is made between:</p>
            <ul>
                <li><strong>Lender:</strong> $lender </li>
                <li><strong>Borrower:</strong> $borrower</li>
            </ul>
            <p>Whereas the lender agrees to lend and the borrower agrees to borrow the following:</p>
            <ul>
                <li><strong>Loan Amount: </strong>". number_format($requestedLoanAmount, 2) ."</li>
                <li><strong>Interest Rate:</strong> $interestRate %</li>
                <li><strong>Duration:</strong> $loanDuration months</li>
            </ul>
            <p>Both parties agree to the terms outlined in the platform and are bound by this agreement.</p>
            <br>
            <p>The borrower is legally bound to repay the loan. Failure to do so will result in legal action by the lender and platform.</p>
            <br>
            <div class='row'>
                <div class='col-6 text-center'>
                <p>______________________<br><strong>Attested by:</strong><br><?php echo $lender; ?> (Lender: $lender)</p>
            </div>
            <div class='col-6 text-center'>
                <p>______________________<br><strong>Received by:</strong><br><?php echo $borrower; ?> (Borrower: $borrower)</p>
            </div>
            </div>
        </div>
    </body>
    </html>
    ";

    // Initialize DOMPDF
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);

    // Set paper size and orientation
    $dompdf->setPaper('A4', 'portrait');

    // Render the HTML as PDF
    $dompdf->render();

    // Output the PDF to browser or save it
    $dompdf->stream("loan_contract_$loanId.pdf", ["Attachment" => false]);
} else {
    echo "Invalid access!";
}
?>
