<?php
session_start(); // Start session

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to login page if not logged in
    exit();
}

// Fetch user's details from session
$lender_id = $_SESSION['user_id']; 
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


// SQL query to fetch user email based on session user ID
$sql = "SELECT email FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $lender_id); // Bind user ID
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $_SESSION['user_email'] = $row['email']; // Store email in session
} else {
    $_SESSION['user_email'] = 'Not Found'; // Default if email is not found
}

$user_email = $_SESSION['user_email'];


// Fetch notifications for the logged-in borrower
$notifications = [];
$query1 = "SELECT n.type, n.message, n.created_at FROM notifications n 
          JOIN loan_application l ON n.loan_id = l.loanid 
          WHERE l.lender_id = ? 
          ORDER BY n.created_at DESC";

$stmt = $conn->prepare($query1);
$stmt->bind_param("i", $lender_id);
$stmt->execute();
$result1 = $stmt->get_result();
while ($row = $result1->fetch_assoc()) {
    $notifications[] = $row;
}

$stmt->close();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  // DB connection
  $conn = new mysqli("localhost", "root", "", "mfp_database");
  if ($conn->connect_error) {
      die("Connection failed: " . $conn->connect_error);
  }

  $user_id = $_SESSION['user_id'] ?? 0;
  $name = $_POST['name'] ?? '';
  $email = $_POST['email'] ?? '';
  $subject = $_POST['subject'] ?? '';
  $message = $_POST['message'] ?? '';

  if ($user_id && $name && $email && $subject && $message) {
      $stmt = $conn->prepare("INSERT INTO messages (user_id, name, email, subject, message) VALUES (?, ?, ?, ?, ?)");
      $stmt->bind_param("issss", $user_id, $name, $email, $subject, $message);
      $stmt->execute();

      $success = true;
  } else {
      $error = true;
  }
}


$conn->close();
?>



<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Lender Profile</title>
  <meta content="" name="description">
  <meta content="" name="keywords">

  <!-- Fevicon -->
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
                <a href="./lender_notification.php">Show all notifications</a>
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
              <a class="dropdown-item d-flex align-items-center" href="./lender_contact.php">
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


            <a href="./lender_active_loans.php">
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
        <ul id="charts-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
          <li>
            <a href="./lender_profile.php">
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
        <a class="nav-link active" href="./lender_contact.php">
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
  <section class="section contact">
  <div class="row">
    <div class="">
      <div class="card p-4">
      <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
      <div class="row gy-4">

            <div class="col-md-6">
              <input type="text" name="name" class="form-control" placeholder="Your Name" required>
            </div>

            <div class="col-md-6 ">
              <input type="email" class="form-control" name="email" placeholder="Your Email" required>
            </div>

            <div class="col-md-12">
              <input type="text" class="form-control" name="subject" placeholder="Subject" required>
            </div>

            <div class="col-md-12">
              <textarea class="form-control" name="message" rows="6" placeholder="Message" required></textarea>
            </div>

            <div class="col-md-12 text-center">
            <button type="submit" class="btn btn-primary btn-lg px-4 shadow-sm">
              <i class="bi bi-send-fill me-2"></i> Send Message
            </button>
            </div>
          </div>

          <!-- Success and error alerts -->
          <?php if (!empty($success)): ?>
            <div class="alert alert-success mt-3 text-center">
              Message sent successfully!
            </div>
          <?php elseif (!empty($error)): ?>
            <div class="alert alert-danger mt-3 text-center">
              Failed to send message. Please try again.
            </div>
          <?php endif; ?>

        </form>
      </div>
    </div>
  </div>
</section>
  </main><!-- End #main -->

  <!-- ======= Footer ======= -->
  <footer id="footer" class="footer">
    <div class="copyright">
      &copy; Copyright <strong><span>Micro Finance Platform</span></strong>. All Rights Reserved
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
        document.getElementById("updateForm").addEventListener("submit", function(event) {
            event.preventDefault(); // Prevent actual form submission
        
            // Display the success message
            document.getElementById("successMessage").style.display = "block";
        
            // Optionally, hide the message after a few seconds
            setTimeout(function() {
                document.getElementById("successMessage").style.display = "none";
            }, 2000);
        });
    </script>
      
</body>

</html>