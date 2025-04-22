<?php
session_start(); // Start session

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to login page if not logged in
    exit();
}

// Fetch user's details from session
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Guest';
$user_role = $_SESSION['user_role'] ?? 'User'; // Default role if not set
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


// Assign lender_id from session user_id
$lender_id = $user_id; // Ensure lender_id is correctly assigned

// Fetch notifications for the logged-in lender
$notifications = [];
$query = "SELECT n.type, n.created_at FROM notifications n 
          JOIN loan_application l ON n.loan_id = l.loanid 
          WHERE l.lender_id = ? 
          ORDER BY n.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $lender_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}


//  active loans where lender_responded = 'true'
$sql = "SELECT loanid, borrower, requested_loan_amount, interest_rate, loan_duration 
        FROM loan_application 
        WHERE lender_id = ? AND lender_responded = 'true'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $lender_id);
$stmt->execute();
$result = $stmt->get_result();


?> 


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Lender Dashboard</title>
  <meta content="" name="description">
  <meta content="" name="keywords">

  <!-- Favicons -->
  <link href="./assets/img/logo.png" rel="icon">
  <link href="./assets/img/logo.png" rel="apple-touch-icon">

  <!-- Google Fonts -->
  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="assets/vendor/quill/quill.snow.css" rel="stylesheet">
  <link href="assets/vendor/quill/quill.bubble.css" rel="stylesheet">
  <link href="assets/vendor/remixicon/remixicon.css" rel="stylesheet">
  <link href="assets/vendor/simple-datatables/style.css" rel="stylesheet">

  <link href="assets/css/style.css" rel="stylesheet">

</head>

<body>

  <!-- ======= Header ======= -->
  <header id="header" class="header fixed-top d-flex align-items-center">

    <div class="d-flex align-items-center justify-content-between">
      <a href="./lender_home.php" class="logo d-flex align-items-center">
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
                <a href="#">Show all notifications</a>
            </li>
    </ul>

        </li><!-- End Notification Nav -->

        <li class="nav-item dropdown">

          <a class="nav-link nav-icon" href="#" data-bs-toggle="dropdown">
            <i class="bi bi-chat-left-text"></i>
            <span class="badge bg-success badge-number">3</span>
          </a><!-- End Messages Icon -->


        </li><!-- End Messages Nav -->

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
              <a class="dropdown-item d-flex align-items-center" href="./lender_profile.php">
                <i class="bi bi-person"></i>
                <span>My Profile</span>
              </a>
            </li>
            <li>
              <hr class="dropdown-divider">
            </li>

            <li>
              <a class="dropdown-item d-flex align-items-center" href="./lender_bank_details.php">
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
        <a class="nav-link active" data-bs-target="#components-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-menu-button-wide"></i><span>Investment</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="components-nav" class="nav-content active " data-bs-parent="#sidebar-nav">
          <li>


            <a href="./lender_active_loans.php" class="active">
              <i class="bi bi-circle"></i><span>Active Loans</span>
            </a>
          </li>

          <a href="./lender_page_amount_request.php" >
              <i class="bi bi-circle"></i><span>Amount Request</span>
            </a>
          </li>

          
        </ul>
      </li><!-- End Components Nav -->

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#forms-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-journal-text"></i><span>Transactions</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="forms-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a href="./add_money.php">
              <i class="bi bi-circle"></i><span>Add Money </span>
            </a>
          </li>
          <li>
            <a href="./lender_payment_history.php">
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
            <a href="./lender_bank_details.php">
              <i class="bi bi-circle"></i><span>Bank Details</span>
            </a>
          </li>

        </ul>
      </li><!-- End Charts Nav -->


      <li class="nav-heading">Pages</li>
     
      </li><!-- End Profile Page Nav -->


      <li class="nav-item">
        <a class="nav-link collapsed" href="./lender_faq.php">
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

  <div class="container py-5">
        <h2 class="mb-4">Hi, <?php echo htmlspecialchars($user_name); ?>!</h2>
        <h4 class="mb-3 text-primary">Your Active Loans</h4>

        <?php if ($result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Loan ID</th>
                            <th>Borrower Name</th>
                            <th>Requested Amount</th>
                            <th>Interest Rate (%)</th>
                            <th>Duration (months)</th>
                            <th>Contract</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?php echo $row['loanid']; ?></td>
        <td><?php echo htmlspecialchars($row['borrower']); ?></td>
        <td>₹<?php echo number_format($row['requested_loan_amount'], 2); ?></td>
        <td><?php echo $row['interest_rate']; ?></td>
        <td><?php echo $row['loan_duration']; ?></td>
        <td>
            <form action="contract.php" method="post" target="_blank">
                <input type="hidden" name="loanid" value="<?php echo $row['loanid']; ?>">
                <input type="hidden" name="borrower" value="<?php echo $row['borrower']; ?>">
                <input type="hidden" name="requested_loan_amount" value="<?php echo $row['requested_loan_amount']; ?>">
                <input type="hidden" name="interest_rate" value="<?php echo $row['interest_rate']; ?>">
                <input type="hidden" name="loan_duration" value="<?php echo $row['loan_duration']; ?>">
                <input type="hidden" name="lender" value="<?php echo $user_name; ?>">

                <button type="submit" class="btn btn-sm btn-primary">View Contract</button>
            </form>
        </td>
    </tr>
<?php endwhile; ?>

                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">No active loans found for you.</div>
        <?php endif; ?>

    </div>            
  </main>

  <!-- ======= Footer ======= -->
  <footer id="footer" class="footer">
    <div class="copyright">
      &copy; Copyright <strong><span>Micro Finance Platform</span></strong>. All Rights Reserved
    </div>
    <div class="credits">
    </div>
  </footer><!-- End Footer -->

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <script src="assets/vendor/apexcharts/apexcharts.min.js"></script>
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/chart.js/chart.umd.js"></script>
  <script src="assets/vendor/echarts/echarts.min.js"></script>
  <script src="assets/vendor/quill/quill.js"></script>
  <script src="assets/vendor/simple-datatables/simple-datatables.js"></script>
  <script src="assets/vendor/tinymce/tinymce.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>

  <script src="assets/js/main.js"></script>

  <script>
    document.addEventListener("DOMContentLoaded", function() {
        const fundedLoans = <?php echo json_encode($_SESSION['funded_loans'] ?? []); ?>;

        document.querySelectorAll(".fund-loan-btn").forEach(button => {
            const loanId = button.getAttribute("data-loanid");

            if (fundedLoans.includes(loanId)) {
                button.disabled = true;
                button.textContent = "Funded";
            }

            button.addEventListener("click", function(event) {
                event.preventDefault();
                button.disabled = true;
                button.textContent = "Funded";

                setTimeout(() => {
                    button.closest("form").submit();
                }, 500);
            });
        });
    });
</script>

</body>

</html>

<?php
// Close connection
$conn->close();
?>