
<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Secure session handling
session_regenerate_id(true);

// Fetch user details
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Guest';
$user_role = $_SESSION['user_role'] ?? 'User';
$user_email = $_SESSION['user_email'] ?? 'user@gmail.com';

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

// Fetch loans related to lender
$sql = "SELECT la.loanid, u.name AS borrower_name, b.funded_amount, 
               la.requested_loan_amount, la.interest_rate, la.status, la.loan_duration, 
               b.wallet_balance, 
               (SELECT COUNT(*) FROM payments p 
                WHERE p.borrower_id = la.borrower_id AND p.status = 'Completed' 
                AND p.lender_id = la.lender_id AND p.loan_id = la.loanid) AS paid_months
        FROM loan_application la
        JOIN borrower b ON la.borrower_id = b.user_id
        JOIN users u ON la.borrower_id = u.id
        WHERE la.lender_id = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Query Preparation Failed: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$loans = [];
while ($row = $result->fetch_assoc()) {
    $total_repayable_amount = $row['requested_loan_amount'] + 
                              ($row['requested_loan_amount'] * $row['interest_rate'] / 100);
    $row['monthly_installment'] = $total_repayable_amount / $row['loan_duration'];
    $row['total_repayable_amount'] = $total_repayable_amount;
    $loans[] = $row;
}
$stmt->close();

// Fetch notifications
$notifications = [];
$query = "SELECT n.type, n.created_at FROM notifications n 
          JOIN loan_application l ON n.loan_id = l.loanid 
          WHERE l.lender_id = ? ORDER BY n.created_at DESC";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Notification Query Failed: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
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

  <title>Lender Payment History</title>
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
</li><!-- End Search Icon-->

<li class="nav-item dropdown">

  <a class="nav-link nav-icon" href="#" data-bs-toggle="dropdown">
    <i class="bi bi-bell"></i>
    <span class="badge bg-primary badge-number">4</span>
  </a><!-- End Notification Icon -->

  <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow notifications">
<li class="dropdown-header">
You have <?= count($notifications) ?> new notifications
<a href="./lender_notification.php"><span class="badge rounded-pill bg-primary p-2 ms-2">View all</span></a>
</li>
<li><hr class="dropdown-divider"></li>

<?php if (!empty($notifications)): ?>
<?php foreach ($notifications as $notification): ?>
    <li class="notification-item">
        <i class="bi bi-info-circle text-primary"></i>
        <div>
            <h4><?= htmlspecialchars($notification['type']) ?></h4>
            <p><?= date('F j, Y, g:i a', strtotime($notification['created_at'])) ?></p>
        </div>
    </li>
    <li><hr class="dropdown-divider"></li>
<?php endforeach; ?>
<?php else: ?>
<li class="notification-item text-center">
    <p>No new notifications</p>
</li>
<?php endif; ?>

<li class="dropdown-footer">
<a href="./lender_notification.php">Show all notifications</a>
</li>
    </ul>

        </li><!-- End Notification Nav -->

        <li class="nav-item dropdown">

          <a class="nav-link nav-icon" href="#" data-bs-toggle="dropdown">
            <i class="bi bi-chat-left-text"></i>
            <span class="badge bg-success badge-number">3</span>
          </a><!-- End Messages Icon -->

          <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow messages">
            <li class="dropdown-header">
              You have 3 new messages
              <a href="#"><span class="badge rounded-pill bg-primary p-2 ms-2">View all</span></a>
            </li>
            <li>
              <hr class="dropdown-divider">
            </li>

            <li class="message-item">
              <a href="#">
                <img src="assets/img/messages-1.jpg" alt="" class="rounded-circle">
                <div>
                  <h4>Maria Hudson</h4>
                  <p>Velit asperiores et ducimus soluta repudiandae labore officia est ut...</p>
                  <p>4 hrs. ago</p>
                </div>
              </a>
            </li>
            <li>
              <hr class="dropdown-divider">
            </li>

            <li class="message-item">
              <a href="#">
                <img src="assets/img/messages-2.jpg" alt="" class="rounded-circle">
                <div>
                  <h4>Anna Nelson</h4>
                  <p>Velit asperiores et ducimus soluta repudiandae labore officia est ut...</p>
                  <p>6 hrs. ago</p>
                </div>
              </a>
            </li>
            <li>
              <hr class="dropdown-divider">
            </li>

            <li class="message-item">
              <a href="#">
                <img src="assets/img/messages-3.jpg" alt="" class="rounded-circle">
                <div>
                  <h4>David Muldon</h4>
                  <p>Velit asperiores et ducimus soluta repudiandae labore officia est ut...</p>
                  <p>8 hrs. ago</p>
                </div>
              </a>
            </li>
            <li>
              <hr class="dropdown-divider">
            </li>

            <li class="dropdown-footer">
              <a href="#">Show all messages</a>
            </li>

          </ul><!-- End Messages Dropdown Items -->

        </li><!-- End Messages Nav -->

        <li class="nav-item dropdown pe-3">

          <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
    <img src="assets/img/profile-img.jpg" alt="Profile" class="rounded-circle">
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
        <a class="nav-link collapsed" href="./lender_home.php">
          <i class="bi bi-grid"></i>
          <span>Dashboard</span>
        </a>
      </li><!-- End Dashboard Nav -->
      <li class="nav-item">
        <a class="nav-link collapsed" href="./lender_notification.php">
          <i class="bi bi-person"></i>
          <span>Notification</span>
        </a>
      </li><!-- End Profile Page Nav -->

      <li class="nav-item">
        <a class="nav-link collapsed" href="loan_request.php">
          <i class="bi bi-person"></i>
          <span>Requests</span>
        </a>
      </li><!-- End Profile Page Nav -->

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#components-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-menu-button-wide"></i><span>Investment</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="components-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>


            <a href="./lender_payment_history.php">
              <i class="bi bi-circle"></i><span>Active Loans</span>
            </a>
          </li>

          <a href="./lender_page_amount_request.php">
              <i class="bi bi-circle"></i><span>Amount Request</span>
            </a>
          </li>
          
        </ul>
      </li><!-- End Components Nav -->

      <li class="nav-item">
        <a class="nav-link active" data-bs-target="#forms-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-journal-text"></i><span>Transactions</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="forms-nav" class="nav-content active" data-bs-parent="#sidebar-nav">
          <li>
            <a href="./add_money.php">
              <i class="bi bi-circle"></i><span>Add Money </span>
            </a>
          </li>
          <li>
            <a href="./lender_payment_history.php" class="active">
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
            <a href="">
              <i class="bi bi-circle"></i><span>Personal Details</span>
            </a>
          </li>
          <li>
            <a href="">
              <i class="bi bi-circle"></i><span>Bank Details</span>
            </a>
          </li>

        </ul>
      </li><!-- End Charts Nav -->


      <li class="nav-heading">Pages</li>
     
      </li><!-- End Profile Page Nav -->


      <li class="nav-item">
        <a class="nav-link collapsed" href="">
          <i class="bi bi-question-circle"></i>
          <span>F.A.Q</span>
        </a>
      </li><!-- End F.A.Q Page Nav -->

      <li class="nav-item">
        <a class="nav-link collapsed" href="">
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
<h2 class="text-center">Borrower Repayment History</h2>
  
  <?php if (!empty($loans)) : ?>
      <?php foreach ($loans as $loan) : ?>
          <div class="card mt-4">
              <div class="card-body">
                  <h5 class="card-title">Loan Repayment Summary - <?php echo htmlspecialchars($loan['borrower_name']); ?></h5>
                  <p><strong>Loan Amount:</strong> ₹<?php echo number_format($loan['requested_loan_amount'], 2); ?></p>
                  <p><strong>Interest Rate:</strong> <?php echo $loan['interest_rate']; ?>%</p>
                  <p><strong>Total Repayable Amount:</strong> ₹<?php echo number_format($loan['total_repayable_amount'], 2); ?></p>
                  <p><strong>Loan Duration:</strong> <?php echo $loan['loan_duration']; ?> Months</p>
                  <p><strong>Funded Amount:</strong> ₹<?php echo number_format($loan['funded_amount'], 2); ?></p>
                  <p><strong>Remaining Balance:</strong> ₹<?php echo number_format($loan['wallet_balance'], 2); ?></p>
                  
                  <?php
                  $paid_months = isset($loan['paid_months']) ? (int)$loan['paid_months'] : 0;
                  $loan_duration = isset($loan['loan_duration']) ? (int)$loan['loan_duration'] : 1;

                  $progress = ($loan_duration > 0) ? ($paid_months / $loan_duration) * 100 : 0;
                  $progress = max(0, min(100, $progress)); // Clamp between 0-100
                  ?>

                  
                  <div class="progress">
                      <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $progress; ?>%" aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100">
                          <?php echo round($progress); ?>% Paid
                      </div>
                  </div>
                        
              </div>
          </div>
          
          <table class="table table-bordered mt-4">
              <thead class="table-dark">
                  <tr>
                      <th>Month</th>
                      <th>Amount</th>
                      <th>Status</th>
                  </tr>
              </thead>
              <tbody>
                  <?php for ($i = 1; $i <= $loan['loan_duration']; $i++) : ?>
                      <tr>
                          <td>Month <?php echo $i; ?></td>
                          <td>₹<?php echo number_format($loan['monthly_installment'], 2); ?></td>
                          <td>
                              <?php if ($i <= $loan['paid_months']) : ?>
                                  <span class="badge bg-success">Paid</span>
                              <?php else : ?>
                                  <span class="badge bg-warning text-dark">Pending</span>
                              <?php endif; ?>
                          </td>
                      </tr>
                  <?php endfor; ?>
              </tbody>
          </table>
      <?php endforeach; ?>
  <?php else : ?>
      <p class="text-center mt-4">No repayment history available.</p>
  <?php endif; ?>
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


  <!-- Template Main JS File -->
  <script src="assets/js/main.js"></script>

</body>

</html>