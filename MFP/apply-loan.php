<?php
session_start(); // Start session

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); 
    exit();
}

// Fetch user details
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Guest';
$user_email = $_SESSION['user_email'] ?? 'user@gmail.com';
$user_role = $_SESSION['user_role'] ?? 'User';

// Database configuration
$host = "localhost";
$dbname = "mfp_database";
$username = "root";
$password = "";

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch lenders
$sql = "SELECT id, name, interest_rate, available_funds, experience, rating FROM lenders";
$result = $conn->query($sql);

// Handle loan application form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['apply_loan'])) {
    $lender_id = $_POST['lender_id'];
    $employment_status = $_POST['employment_status'];
    $income = $_POST['income'];
    $credit_score = $_POST['credit_score'] ?? rand(300, 900); // Generate if not provided
    $loan_amount = $_POST['loan_amount']; // Fetch requested loan amount
    $pan_number = $_POST['pan_number'];

    // Ensure borrower exists in the borrower table
    $check_borrower = $conn->query("SELECT user_id FROM borrower WHERE user_id = '$user_id'");
    if ($check_borrower->num_rows == 0) {
        $insert_borrower = "INSERT INTO borrower (user_id, name, email, employment_status, income, credit_score, loan_status, funded_amount, loan_applied_count, wallet_balance) 
                            VALUES ('$user_id', '$user_name', '$user_email', '$employment_status', '$income', '$credit_score', 'pending', 0, 1, 0, ' $pan_number')";
        if (!$conn->query($insert_borrower)) {
            die("Error inserting borrower: " . $conn->error);
        }
    } else {
        // If borrower exists, update details
        $update_borrower = "UPDATE borrower SET employment_status='$employment_status', income='$income', 
                            credit_score='$credit_score', loan_status='pending', loan_applied_count = loan_applied_count + 1, pan_number = '$pan_number'
                             WHERE user_id='$user_id'";
        if (!$conn->query($update_borrower)) {
            die("Error updating borrower: " . $conn->error);
        }
    }

    // Fetch lender details
    $lender_query = $conn->query("SELECT name, interest_rate, available_funds FROM lenders WHERE id = '$lender_id'");
    if ($lender_query->num_rows > 0) {
        $lender = $lender_query->fetch_assoc();
        $lender_name = $lender['name'];
        $interest_rate = $lender['interest_rate'];
        $available_funds = $lender['available_funds'];

        // Ensure the loan amount is within the available funds
        if ($loan_amount > $available_funds) {
            echo "<script>alert('Loan amount exceeds available funds! Please enter a valid amount.'); window.location.href='apply-loan.php';</script>";
            exit();
        }

        // Insert into loan_application table (including requested_loan_amount)
        $loan_duration = $_POST['loan_duration']; // Fetch loan duration

        // Insert into loan_application table (including loan_duration)
        $insert_loan = "INSERT INTO loan_application (borrower_id, lender_id, borrower, lender_name, interest_rate, requested_loan_amount, loan_duration, status) 
                VALUES ('$user_id', '$lender_id', '$user_name', '$lender_name', '$interest_rate', '$loan_amount', '$loan_duration', 'pending')";
        
        if (!$conn->query($insert_loan)) {
            die("Error inserting loan: " . $conn->error);
        }
        
        $loan_id = $conn->insert_id;

        // Update borrower table with loan details
        $update_borrower = "UPDATE borrower SET loan_id='$loan_id' WHERE user_id='$user_id'";
        if (!$conn->query($update_borrower)) {
            die("Error updating borrower: " . $conn->error);
        }

        echo "<script>alert('Loan application submitted successfully!'); window.location.href='lenders.php';</script>";
    } else {
        echo "<script>alert('Invalid lender selected. Please try again.'); window.location.href='apply-loan.php';</script>";
    }
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

?>



<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Lenders</title>
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
    <a class="nav-link collapsed" href="./borrower_notification.php">
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
    <a class="nav-link active" data-bs-target="#components-nav" data-bs-toggle="collapse" href="#">
      <i class="bi bi-menu-button-wide"></i><span>Loans</span><i class="bi bi-chevron-down ms-auto"></i>
    </a>
    <ul id="components-nav" class="nav-content active " data-bs-parent="#sidebar-nav">
      <li>


        <a href="./apply-loan.php" class="active">
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
    <a class="nav-link collapsed" data-bs-target="#forms-nav" data-bs-toggle="collapse" href="#">
      <i class="bi bi-journal-text"></i><span>Transactions</span><i class="bi bi-chevron-down ms-auto"></i>
    </a>
    <ul id="forms-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
      <li>
        <a href="./repayment.php">
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

 <!-- ======= Main Content ======= -->
 <main id="main" class="main">
  <div class="pagetitle">
    <h1>Lenders</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item active">Lenders</li>
      </ol>
    </nav>
  </div>
  <section class="section">
    <div class="row">
      <?php
      if ($result->num_rows > 0) {
          while ($row = $result->fetch_assoc()) {
              ?>
              <div class="col-lg-4 col-md-6">
                <div class="card shadow-sm border-0 rounded">
                  <div class="card-body">
                    <h5 class="card-title text-primary"><?php echo htmlspecialchars($row['name']); ?></h5>
                    <p class="card-text">
                      <strong>Interest Rate:</strong> <?php echo htmlspecialchars($row['interest_rate']); ?>%<br>
                      <strong>Loan Amount:</strong> ₹<?php echo number_format($row['available_funds']); ?><br>
                      <strong>Experience:</strong> <?php echo htmlspecialchars($row['experience']); ?> years<br>
                      <strong>Rating:</strong> 
                      <?php for ($i = 0; $i < $row['rating']; $i++) { echo "⭐"; } ?>
                    </p>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#applyLoanModal<?php echo $row['id']; ?>">
                        Apply Loan
                    </button>
                  </div>
                </div>
              </div>
              <div class="modal fade" id="applyLoanModal<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="applyLoanModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="applyLoanModalLabel">Apply for Loan with <?php echo htmlspecialchars($row['name']); ?></h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <form method="post">
                        <input type="hidden" name="lender_id" value="<?php echo $row['id']; ?>">
                        
                        <div class="mb-3">
                          <label for="employment_status" class="form-label">Employment Status</label>
                          <select class="form-select" name="employment_status" required>
                            <option value="employed">Employed</option>
                            <option value="self-employed">Self-Employed</option>
                            <option value="unemployed">Unemployed</option>
                          </select>
                        </div>

                        <div class="mb-3">
                          <label for="income" class="form-label">Monthly Income (₹)</label>
                          <input type="number" class="form-control" name="income" required min="0">
                        </div>

                        <div class="mb-3">
                          <label for="credit_score" class="form-label">CIBIL Score</label>
                          <input type="number" class="form-control" name="credit_score" required min="300" max="900">
                        </div>

                        <div class="mb-3">
                          <label for="pan_number" class="form-label">PAN Number</label>
                          <input type="text" class="form-control" name="pan_number"  maxlength="10" minlength="10" pattern="[A-Z]{5}[0-9]{4}[A-Z]{1}" required>
                        </div>

                        <div class="mb-3">
                          <label for="available_funds" class="form-label">Available Funds</label>
                          <input type="text" class="form-control" id="available_funds_<?php echo $row['id']; ?>" value="<?php echo $row['available_funds']; ?>" readonly>
                        </div>

                        <div class="mb-3">
                          <label for="loan_amount" class="form-label">Loan Amount</label>
                          <input type="number" class="form-control" name="loan_amount" id="loan_amount_<?php echo $row['id']; ?>" 
                          min="1" max="<?php echo $row['available_funds']; ?>" required>
                        </div>
                        <div class="mb-3">
                          <label for="loan_duration" class="form-label">Loan Duration (Months)</label>
                          <input type="number" class="form-control" name="loan_duration" id="loan_duration_<?php echo $row['id']; ?>" 
                          min="1" max="60" required> <!-- Limit duration between 1 and 60 months -->
                        </div>

                        <button type="submit" name="apply_loan" class="btn btn-success">Submit Loan Application</button>
                      </form>
                    </div>
                  </div>
                </div>
              </div>
              <?php
          }
      } else {
          echo "<p>No lenders found.</p>";
      }
      $conn->close();
      ?>
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

  <!-- Vendor JS Files -->
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
    function validateForm() {
    const pan = document.getElementById("pan_number").value;
    const panRegex = /^[A-Z]{5}[0-9]{4}[A-Z]{1}$/;
    if (!panRegex.test(pan)) {
      alert("Invalid PAN number. It should be in format: AAAAA9999A");
      return false;
    }
    return true;
    }
  </script>
</body>

</html>