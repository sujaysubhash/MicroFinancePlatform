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

// Fetch loan count from borrower table
$loan_count = 0;
$sql = "SELECT COUNT(*) AS total_loans FROM borrower WHERE user_id = ?";  
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($loan_count);
$stmt->fetch();
$stmt->close();


// Fetch total funded amount and count of lender responses
$funded_amount = 0;
$lender_responded_count = 0;
$sql = "SELECT SUM(funded_amount) AS total_funded, COUNT(lender_responded) AS total_lenders FROM borrower";
$result = $conn->query($sql);

if ($result && $row = $result->fetch_assoc()) {
    $funded_amount = $row['total_funded'] ?? 0;
    $lender_responded_count = $row['total_lenders'] ?? 0;
}



// Fetch wallet balance
$wallet_balance = 0;
$sql = "SELECT wallet_balance FROM borrower WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($wallet_balance);
$stmt->fetch();
$stmt->close();

// Fetch lenders data
$lenders = [];
$sql = "SELECT name, interest_rate, available_funds, experience FROM lenders";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $lenders[] = $row;
    }
}


$borrower_id = $user_id;
// Fetch notifications for the logged-in borrower
$notifications = [];
$query = "SELECT n.type, n.message, n.created_at FROM notifications n 
          JOIN loan_application l ON n.loan_id = l.loanid 
          WHERE l.borrower_id = ? 
          ORDER BY n.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $borrower_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}
$stmt->close();


// Fetch latest 5 news articles
$sql = "SELECT * FROM news_updates ORDER BY created_at DESC LIMIT 5";
$result = $conn->query($sql);


$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Dashboard -MFP</title>
  <meta content="" name="description">
  <meta content="" name="keywords">

  <!-- Favicons -->
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

  <link href="assets/css/style.css" rel="stylesheet">

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
        <a class="nav-link " href="./index.php">
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

          <a href="./repayment.php">
              <i class="bi bi-circle"></i><span>Repayments</span>
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
            <a href="./repayment.php">
              <i class="bi bi-circle"></i><span>Repayments</span>
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
  <!-- Wallet Balance on the Right Hand Side with Smaller Size -->
    <div class="container">
          <div class="row justify-content-end">
              <div class="col-lg-3">
                  <div class="card shadow-lg p-3 border-0 rounded-4 bg-white">
                      <div class="card-body text-center">
                          <h4 class="text-primary">Wallet Balance</h4>
                          <h2 class="fw-bold text-primary">₹<?php echo number_format($wallet_balance, 2); ?></h2>
                      </div>
                  </div>
              </div>
          </div>
      </div>
      <!-- Welcome Message Section -->
      <div class="pagetitle text-center" style="margin-top:-60px;">
          <h1 class="fw-bold text-primary">Welcome to Micro Finance Platform</h1>
          <p class="text-muted">Connecting borrowers and lenders directly with security and trust.</p>
      </div><!-- End Page Title -->

      <section class="section about">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow-lg p-2 border-0 rounded-4">
                    <div class="card-body text-center">
                        <h2 class="card-title text-primary">Who We Are</h2>
                        <p class="lead text-muted mb-2">
                            The <strong>Micro Finance Platform</strong> connects borrowers directly with lenders, removing intermediaries to ensure transparent, secure, and fair lending practices.
                            We aim to provide access to financial support for those who need it while offering investment opportunities for those who want to grow their wealth.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-3">
    <div class="col-lg-6">
        <div class="card shadow py-2 px-3 border-0 rounded-4 bg-light">
            <div class="card-body">
                <h3 class="card-title text-primary">Our Mission</h3>
                <p class="text-muted mb-1">
                    We connect borrowers and lenders directly, ensuring transparency and accessibility while providing financial support and investment opportunities.
                </p>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow py-2 px-3 border-0 rounded-4 bg-white">
            <div class="card-body">
                <h3 class="card-title text-primary">How It Works</h3>
                <ul class="list-unstyled text-muted mb-1">
                    <li><strong>Borrowers:</strong> Get short-term loans at fair rates without banks.</li>
                    <li><strong>Lenders:</strong> Invest directly and earn secured returns.</li>
                    <li><strong>Admin:</strong> Ensures platform security and smooth operation.</li>
                </ul>
            </div>
        </div>
    </div>
</div>


    <!-- News & Updates Centered at Bottom -->
    <!-- <section class="section dashboard mt-5">
        <div class="container">
            <div class="row mt-4 justify-content-center">
                <div class="col-lg-8">
                    <div class="card shadow p-4 border-0 rounded-4 bg-light">
                        <div class="card-body text-center">
                            <h4 class="text-primary">News &amp; Updates</h4>
                            <div class="news">
                                <?php
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        echo '<div class="post-item clearfix border-bottom pb-2 mb-2 d-flex flex-column align-items-center">
                                                <img src="' . $row["image_url"] . '" class="img-fluid rounded mb-2" alt="">
                                                <h5><a href="#" class="text-dark fw-bold">' . $row["title"] . '</a></h5>
                                                <p class="text-muted text-center">' . substr($row["content"], 0, 100) . '...</p>
                                              </div>';
                                    }
                                } else {
                                    echo '<p class="text-muted">No news available.</p>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>  -->

</main><!-- End #main -->


  <!-- ======= Footer ======= -->
  <footer id="footer" class="footer">
    <div class="copyright">
      &copy; Copyright <strong><span>Micro Finance Platform</span></strong>. All Rights Reserved
    </div>
    <div class="credits">
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