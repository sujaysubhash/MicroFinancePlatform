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


$query = "SELECT COUNT(*) AS loan_count FROM loan_application WHERE lender_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $lender_id);
$stmt->execute();
$result = $stmt->get_result();

$loan_count = 0;
if ($row = $result->fetch_assoc()) {
    $loan_count = $row['loan_count'];
}

// Fetch total funded amount and count of lender responses
$funded_amount = 0;
$lender_responded_count = 0;
$sql = "SELECT SUM(funded_amount) AS total_funded, COUNT(lender_responded) AS total_lenders FROM borrower";
$result = $conn->query($sql);

if ($result && $row = $result->fetch_assoc()) {
    $funded_amount = $row['total_funded'] ?? 0;
    $lender_responded_count = $row['total_lenders'] ?? 0;
}


// Fetch lenders data
$lenders = [];
$sql = "SELECT name, interest_rate, available_funds, experience FROM lenders";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $lenders[] = $row;
    }
}

//Fetch news
$sql = "SELECT * FROM news_updates ORDER BY created_at DESC LIMIT 5";
$result = $conn->query($sql);

// Fetch wallet balance for lender
$lender_wallet_balance = 0;
$sql = "SELECT wallet_balance FROM lenders WHERE id = ?"; // Use 'id' instead of 'user_id'
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $lender_id);
$stmt->execute();
$stmt->bind_result($lender_wallet_balance);
$stmt->fetch();
$stmt->close();

//Lender responded
$query1 = "SELECT COUNT(*) AS responded_count 
          FROM loan_application 
          WHERE lender_id = ? AND status = 'approved' OR status='funded' ";

$stmt = $conn->prepare($query1);
$stmt->bind_param("i", $lender_id);
$stmt->execute();
$result = $stmt->get_result();

$responded_count = 0;
if ($row = $result->fetch_assoc()) {
    $responded_count = $row['responded_count'];
}

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
$stmt->close();


// Fetch latest 5 news articles
$sql = "SELECT * FROM news_updates ORDER BY created_at DESC LIMIT 5";
$result = $conn->query($sql);
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
              <a class="dropdown-item d-flex align-items-center" href="./borrower-bankdetails.php">
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
        <a class="nav-link " href="./lender_home.php">
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
        <ul id="charts-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
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
        <a class="nav-link collapsed" href="./lender_contact.php">
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

    <div class="pagetitle">
      <h1>Lender Dashboard</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="./lender_home.php">Home</a></li>
          <li class="breadcrumb-item active">Dashboard</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

    <section class="section dashboard">
      <div class="row">

        <!-- Left side columns -->
        <div class="col-lg-8">
          <div class="row">

            <!-- Sales Card -->
            <div class="col-xxl-4 col-md-6">
              <div class="card info-card sales-card">

                <div class="filter">
                  <a class="icon" href="#" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></a>
                  <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                    <li class="dropdown-header text-start">
                      <h6>Filter</h6>
                    </li>

                    <li><a class="dropdown-item" href="#">Today</a></li>
                    <li><a class="dropdown-item" href="#">This Month</a></li>
                    <li><a class="dropdown-item" href="#">This Year</a></li>
                  </ul>
                </div>

                <div class="card-body">
                  <h5 class="card-title">Responded <span>| Today</span></h5>

                  <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                      <i class="bi bi-cart"></i>
                    </div>
                    <div class="ps-3">
                      <h6><?php echo htmlspecialchars($responded_count); ?></h6> <!-- Dynamic Loan Count -->

                    </div>
                  </div>
                </div>

              </div>
            </div><!-- End Sales Card -->

            <div class="col-xxl-4 col-md-6">
              <div class="card info-card revenue-card">

                <div class="filter">
                  <a class="icon" href="#" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></a>
                  <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                    <li class="dropdown-header text-start">
                      <h6>Filter</h6>
                    </li>

                    <li><a class="dropdown-item" href="#">Today</a></li>
                    <li><a class="dropdown-item" href="#">This Month</a></li>
                    <li><a class="dropdown-item" href="#">This Year</a></li>
                  </ul>
                </div>

                <div class="card-body">
                  <h5 class="card-title">Wallet Balance</h5>

                  <div class="ps-3">
                  <h6>₹<?php echo number_format($lender_wallet_balance, 2); ?></h6>
                  </div>
                </div>

              </div>
            </div><!-- End Revenue Card -->

            <!-- Customers Card -->
            <div class="col-xxl-4 col-xl-12">

              <div class="card info-card customers-card">

                <div class="filter">
                  <a class="icon" href="#" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></a>
                  <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                    <li class="dropdown-header text-start">
                      <h6>Filter</h6>
                    </li>

                    <li><a class="dropdown-item" href="#">Today</a></li>
                    <li><a class="dropdown-item" href="#">This Month</a></li>
                    <li><a class="dropdown-item" href="#">This Year</a></li>
                  </ul>
                </div>

                <div class="card-body">
                  <h5 class="card-title">Loans <span>| This Year</span></h5>

                  <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                      <i class="bi bi-people"></i>
                    </div>
                    <div class="ps-3">
                    <h6><?php echo htmlspecialchars($loan_count) ?></h6>

                    </div>
                  </div>

                </div>
              </div>

            </div><!-- End Customers Card -->

              
            <div class="col-12">
              <div class="card top-selling overflow-auto">

                <div class="filter">
                  <a class="icon" href="#" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></a>
                  <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                    <li class="dropdown-header text-start">
                      <h6>Filter</h6>
                    </li>

                    <li><a class="dropdown-item" href="#">Today</a></li>
                    <li><a class="dropdown-item" href="#">This Month</a></li>
                    <li><a class="dropdown-item" href="#">This Year</a></li>
                  </ul>
                </div>

                <div class="card-body pb-0">
                  <h5 class="card-title">Top Rated <span>| This Month</span></h5>

                  <table class="table table-borderless">
                    <thead>
                      <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Interest</th>
                        <th scope="col">Funding Limit</th>
                        <th scope="col">Experience</th>
                      </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lenders as $lender): ?>
                          <tr>
                              <td><a href="#" class="text-primary fw-bold"><?php echo htmlspecialchars($lender['name']); ?></a></td>
                              <td><?php echo htmlspecialchars($lender['interest_rate']); ?></td>
                              <td class="fw-bold">₹<?php echo number_format($lender['available_funds']); ?></td>
                              <td><?php echo htmlspecialchars($lender['experience']); ?> years</td>
                          </tr>
                        <?php endforeach; ?>
                    </tbody>
                  </table>

                </div>

              </div>
            </div><!-- End Top Selling -->

          </div>
        </div><!-- End Left side columns -->

        <!-- Right side columns -->
        <div class="col-lg-4">


  
        <!-- News & Updates Traffic -->
        <div class="card">
            <div class="filter">
                <a class="icon" href="#" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></a>
                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                    <li class="dropdown-header text-start">
                        <h6>Filter</h6>
                    </li>
                    <li><a class="dropdown-item" href="#">Today</a></li>
                    <li><a class="dropdown-item" href="#">This Month</a></li>
                    <li><a class="dropdown-item" href="#">This Year</a></li>
                </ul>
            </div>

            <div class="card-body pb-0">
                <h5 class="card-title">News &amp; Updates <span>| Latest</span></h5>

                <div class="news">
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo '<div class="post-item clearfix">
                                    <img src="' . $row["image_url"] . '" alt="">
                                    <h4><a href="#">' . $row["title"] . '</a></h4>
                                    <p>' . substr($row["content"], 0, 100) . '...</p>
                                  </div>';
                        }
                    } else {
                        echo '<p>No news available.</p>';
                    }
                    ?>
                </div><!-- End sidebar recent posts -->
            </div>
        </div><!-- End News & Updates -->
                  

        </div><!-- End Right side columns -->

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

      </div>
    </section>

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

  <script src="assets/vendor/apexcharts/apexcharts.min.js"></script>
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/chart.js/chart.umd.js"></script>
  <script src="assets/vendor/echarts/echarts.min.js"></script>
  <script src="assets/vendor/quill/quill.js"></script>
  <script src="assets/vendor/simple-datatables/simple-datatables.js"></script>
  <script src="assets/vendor/tinymce/tinymce.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>

  <script src="assets/js/main.js"></script>

</body>

</html>