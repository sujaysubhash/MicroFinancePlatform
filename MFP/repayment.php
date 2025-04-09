
<?php
session_start(); // Start session

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to login page if not logged in
    exit();
}

// Fetch user's name from session
$user_name = $_SESSION['user_name'] ?? 'Guest';
$user_role = $_SESSION['user_role'] ?? 'User'; // Default role if not set

// Database connection
$host = "localhost";
$dbname = "mfp_database";
$username = "root";
$password = "";
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch loan count from borrower table
$loan_count = 0;
$sql = "SELECT COUNT(*) AS total_loans FROM borrower";
$result = $conn->query($sql);

if ($result && $row = $result->fetch_assoc()) {
    $loan_count = $row['total_loans'];
}


// Fetch wallet balance for logged-in borrower
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT wallet_balance FROM borrower WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($wallet_balance);
    $stmt->fetch();
    $stmt->close();
} else {
    echo "User not logged in.";
    exit;
}


$borrower_id = $user_id;
// Fetch notifications for the logged-in borrower
$notifications = [];
$query1 = "SELECT n.type, n.message, n.created_at FROM notifications n 
          JOIN loan_application l ON n.loan_id = l.loanid 
          WHERE l.borrower_id = ? 
          ORDER BY n.created_at DESC";

$stmt = $conn->prepare($query1);
$stmt->bind_param("i", $borrower_id);
$stmt->execute();
$result1 = $stmt->get_result();
while ($row = $result1->fetch_assoc()) {
    $notifications[] = $row;
}
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Repayment</title>
  <meta content="" name="description">
  <meta content="" name="keywords">

  <!-- Fevicon -->
  <link href="./assets/img/logo.png" rel="icon">
  <link href="./assets/img/logo.png" rel="apple-touch-icon">


  <!-- Google Fonts -->
  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="assets/vendor/quill/quill.snow.css" rel="stylesheet">
  <link href="assets/vendor/quill/quill.bubble.css" rel="stylesheet">
  <link href="assets/vendor/remixicon/remixicon.css" rel="stylesheet">
  <link href="assets/vendor/simple-datatables/style.css" rel="stylesheet">

  <!-- Template Main CSS File -->
  <link href="assets/css/style.css" rel="stylesheet">
  <style>
    .sticky-aside {
      position: sticky;
      top: 80px;
    }
  </style>
</head>

<body>

  <!-- ======= Header ======= -->
<header id="header" class="header fixed-top d-flex align-items-center">

<div class="d-flex align-items-center justify-content-between">
  <a href="index.html" class="logo d-flex align-items-center">
    <img src="assets/img/logo.png" alt="">
    <span class="d-none d-lg-block">MFP</span>
  </a>
  <i class="bi bi-list toggle-sidebar-btn"></i>
</div><!-- End Logo -->

<div class="search-bar">
  <form class="search-form d-flex align-items-center" method="POST" action="#">
    <input type="text" name="query" placeholder="Search" title="Enter search keyword">
    <button type="submit" title="Search"><i class="bi bi-search"></i></button>
  </form>
</div><!-- End Search Bar -->

<nav class="header-nav ms-auto">
<ul class="d-flex align-items-center">
<li class="nav-item d-block d-lg-none">
    <a class="nav-link nav-icon search-bar-toggle " href="#">
        <i class="bi bi-search"></i>
    </a>
</li><!-- End Search Icon -->

<li class="nav-item dropdown">

<a class="nav-link nav-icon" href="#" data-bs-toggle="dropdown">
<i class="bi bi-bell"></i>
<span class="badge bg-primary badge-number">4</span>
</a><!-- End Notification Icon -->
<ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow notifications">
<li class="dropdown-header">
You have <?= count($notifications) ?> new notifications
<a href="./borrower_notification.php"><span class="badge rounded-pill bg-primary p-2 ms-2">View all</span></a>
</li>
<li>
<hr class="dropdown-divider">
</li>

<?php if (!empty($notifications)): ?>
<?php foreach ($notifications as $notification): ?>
  <li class="notification-item">
    <i class="bi bi-info-circle text-primary"></i>
    <div>
      <h4><?= htmlspecialchars($notification['type']) ?></h4>
      <p><?= date('F j, Y, g:i a', strtotime($notification['created_at'])) ?></p>
    </div>
  </li>
  <li>
    <hr class="dropdown-divider">
  </li>
<?php endforeach; ?>
<?php else: ?>
<li class="notification-item text-center">
  <p>No new notifications</p>
</li>
<?php endif; ?>

<li class="dropdown-footer">
<a href="./borrower_notification.php">Show all notifications</a>
</li>
</ul>


</li><!-- End Notification Nav -->

    <li class="nav-item dropdown pe-3">

      <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
<img src="./assets/img/person_profile.svg" alt="Profile" class="rounded-circle">
<span class="d-none d-md-block dropdown-toggle ps-2"><?= htmlspecialchars($user_name) ?></span>
      </a>

      <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
        <li class="dropdown-header">
          <h6><?= htmlspecialchars($user_name) ?></h6>
          <span><?= htmlspecialchars($user_role) ?></span>
          </li>
        <li>
          <hr class="dropdown-divider">
        </li>

        <li>
          <a class="dropdown-item d-flex align-items-center" href="./profile.php">
            <i class="bi bi-person"></i>
            <span>My Profile</span>
          </a>
        </li>
        <li>
          <hr class="dropdown-divider">
        </li>

        <li>
          <a class="dropdown-item d-flex align-items-center" href="./profile.php">
            <i class="bi bi-gear"></i>
            <span>Account Settings</span>
          </a>
        </li>
        <li>
          <hr class="dropdown-divider">
        </li>

        <li>
          <a class="dropdown-item d-flex align-items-center" href="./contact.php">
            <i class="bi bi-question-circle"></i>
            <span>Need Help?</span>
          </a>
        </li>
        <li>
          <hr class="dropdown-divider">
        </li>

        <li> 
        <a class="dropdown-item d-flex align-items-center" href="./logout.php">
          <i class="bi bi-box-arrow-right"></i>
          <span>Sign Out</span>
        </a>
        </li>

      </ul><!-- End Profile Dropdown Items -->
    </li><!-- End Profile Nav -->

  </ul>
</nav><!-- End Icons Navigation -->

</header><!-- End Header -->

  <!-- ======= Sidebar ======= -->
  <aside id="sidebar" class="sidebar">

    <ul class="sidebar-nav" id="sidebar-nav">

      <li class="nav-item">
        <a class="nav-link collapsed" href="./index.php">
          <i class="bi bi-grid"></i>
          <span>Dashboard</span>
        </a>
      </li><!-- End Dashboard Nav -->
      <li class="nav-item">
        <a class="nav-link collapsed" href="./notifications.php">
          <i class="bi bi-person"></i>
          <span>Notification</span>
        </a>
      </li><!-- End Profile Page Nav -->

      <li class="nav-item">
        <a class="nav-link collapsed" href="./lenders.php">
          <i class="bi bi-person"></i>
          <span>Lenders</span>
        </a>
      </li><!-- End Profile Page Nav -->

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#components-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-menu-button-wide"></i><span>Loans</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="components-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
      <li>

            <a href="./apply-loan.php">
              <i class="bi bi-circle"></i><span>Apply for loan</span>
            </a>
          </li>

          <a href="./loans.php">
              <i class="bi bi-circle"></i><span>My Loans</span>
            </a>
          </li>

          </li>
          
        </ul>
      </li><!-- End Components Nav -->

      <li class="nav-item">
        <a class="nav-link active" data-bs-target="#forms-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-journal-text"></i><span>Transactions</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="forms-nav" class="nav-content active " data-bs-parent="#sidebar-nav">
          <li>
          <a href="./repayment.php" class = "active">
            <i class="bi bi-circle"></i><span>Add Money</span>
          </a>
          </li>
          <li>
          <a href="./borrower_payment-history.php">
          <i class="bi bi-circle"></i><span>Payment History</span>
            </a>
          </li>
          
        </ul>
      </li><!-- End Forms Nav -->

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#charts-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-bar-chart"></i><span>Profile</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="charts-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a href="./profile.php">
              <i class="bi bi-circle"></i><span>Personal Details</span>
            </a>
          </li>
          <li>
            <a href="./borrower-bankdetails.php">
              <i class="bi bi-circle"></i><span>Bank Details</span>
            </a>
          </li>

        </ul>
      </li><!-- End Charts Nav -->


      <li class="nav-heading">Pages</li>
     
      </li><!-- End Profile Page Nav -->


      <li class="nav-item">
        <a class="nav-link collapsed" href="./faq.php">
          <i class="bi bi-question-circle"></i>
          <span>F.A.Q</span>
        </a>
      </li><!-- End F.A.Q Page Nav -->

      <li class="nav-item">
        <a class="nav-link collapsed" href="./contact.php">
          <i class="bi bi-envelope"></i>
          <span>Contact</span>
        </a>
      </li><!-- End Contact Page Nav -->

      <li class="nav-item">
        <a class="nav-link collapsed" href="./logout.php">
          <i class="bi bi-box-arrow-in-right"></i>
          <span>Logout</span>
        </a>
      </li><!-- End Login Page Nav -->

    </ul>

  </aside><!-- End Sidebar-->

  <main id="main" class="main">
  <div class="container my-5">
    <h2 class="text-center mb-4">Loan Repayment Portal - MFP</h2>
    <div class="row">
      <div class="col-lg-8 mb-4">
        <div class="card mb-4 shadow-sm">
          <div class="card-body">
            <h5 class="card-title">Auto Loan Payment</h5>
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="autoPaymentSwitch" onchange="toggleAutoPayment(this)">
              <label class="form-check-label" for="autoPaymentSwitch">Enable Auto Payment</label>
            </div>
            <p class="mt-2 text-muted">Auto payment will deduct loan amount from wallet at regular intervals.</p>
          </div>
        </div>
        
        <!-- <div class="card shadow-sm mb-4">
          <div class="card-body">
            <h5 class="card-title">Manual Loan Repayment</h5>
            <form id="manualPaymentForm">
              <div class="mb-3">
                <label for="repaymentAmount" class="form-label">Enter Amount to Pay</label>
                <input type="number" class="form-control" id="repaymentAmount" placeholder="₹ Amount" required>
              </div>
              <button type="button" class="btn btn-success" onclick="manualPayment()">Transfer to Lender</button>
            </form>
            <div id="paymentStatus" class="mt-3"></div>
          </div>
        </div> -->
        
        <div class="card shadow-sm mb-4">
          <div class="card-body">
            <h5 class="card-title">Add Money to Wallet</h5>
            <div class="input-group">
              <input type="number" id="addAmount" class="form-control" placeholder="Enter amount to add">
              <button class="btn btn-primary" onclick="addToWallet()">Add to Wallet</button>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-lg-4">
        <div class="card sticky-aside shadow-sm">
          <div class="card-body">
            <h5 class="card-title">Current Wallet Balance</h5>
            <p class="fs-4 text-success mb-0">₹ <span id="walletBalance"><?php echo number_format($wallet_balance, 2); ?></span></p>
            <p class="text-muted">This balance will be used for loan repayments.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

  <!-- ======= Footer ======= -->
  <footer id="footer" class="footer">
    <div class="copyright">
      &copy; Copyright <strong><span>Micro Finance Platform</span></strong>. All Rights Reserved
    </div>
    
  </footer><!-- End Footer -->

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/apexcharts/apexcharts.min.js"></script>
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/chart.js/chart.umd.js"></script>
  <script src="assets/vendor/echarts/echarts.min.js"></script>
  <script src="assets/vendor/quill/quill.js"></script>
  <script src="assets/vendor/simple-datatables/simple-datatables.js"></script>
  <script src="assets/vendor/tinymce/tinymce.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>

  <script>
function updateWalletDisplay(amount) {
    document.getElementById('walletBalance').innerText = parseFloat(amount).toFixed(2);
}

function addToWallet() {
    const amount = parseFloat(document.getElementById('addAmount').value);
    if (!isNaN(amount) && amount > 0) {
        fetch('update_wallet.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=add&amount=${amount}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateWalletDisplay(data.new_balance);
            } else {
                alert(data.message);
            }
        });
        document.getElementById('addAmount').value = '';
    }
}

function manualPayment() {
    const amount = parseFloat(document.getElementById('repaymentAmount').value);

    if (amount > 0) {
        fetch('update_wallet.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=deduct&amount=${amount}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateWalletDisplay(data.new_balance);
                document.getElementById('paymentStatus').innerHTML = `<div class="alert alert-success">₹${amount} successfully transferred.</div>`;
            } else {
                document.getElementById('paymentStatus').innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
            }
        });
    }
}


</script>

  <!-- Template Main JS File -->
  <script src="assets/js/main.js"></script>

</body>

</html>