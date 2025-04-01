<?php
session_start(); // Start session

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to login page if not logged in
    exit();
}

// Fetch user's details from session
$user_name = $_SESSION['user_name'] ?? 'Guest';
$user_role = $_SESSION['user_role'] ?? 'User';
$user_email = $_SESSION['user_email'] ?? 'user@gmail.com';
$user_id = $_SESSION['user_id']; // Borrower's ID

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

// Fetch loan details from loan_application table
$loans = [];
$sql = "SELECT loanid, lender_id, lender_name, interest_rate, requested_loan_amount, status, borrower_id 
        FROM loan_application 
        WHERE borrower_id = ?";  // Filter loans by borrower ID

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id); // Bind the logged-in borrower ID
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $loans[] = $row;
    }
}

// Handle request amount button click
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['loanid'])) {
    $loan_id = $_POST['loanid'];
    
    // Fetch lender ID and requested loan amount from loan_application table
    $sql = "SELECT lender_id, requested_loan_amount FROM loan_application WHERE loanid = ? AND borrower_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $loan_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $loan = $result->fetch_assoc();
        $lender_id = $loan['lender_id'];
        $loan_amount = $loan['requested_loan_amount'];

        // Insert a new payment request
        $insert_sql = "INSERT INTO payments (lender_id, borrower_id, amount, status) VALUES (?, ?, ?, 'Pending')";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("iid", $lender_id, $user_id, $loan_amount);
        
        if ($stmt->execute()) {
            echo "<script>alert('Payment request sent successfully!');</script>";
        } else {
            echo "<script>alert('Error: " . $stmt->error . "');</script>";
        }
        
        $stmt->close();
    } else {
        echo "<script>alert('Loan application not found.');</script>";
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
        <a class="nav-link active" data-bs-target="#components-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-menu-button-wide"></i><span>Loans</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="components-nav" class="nav-content active " data-bs-parent="#sidebar-nav">
          <li>


            <a href="./apply-loan.php">
              <i class="bi bi-circle"></i><span>Apply for loan</span>
            </a>
          </li>

          <a href="./loans.php" class="active">
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

  <main id="main" class="main">
    <div class="card-body">
        <h5 class="card-title">Loan Applications <span>| Today</span></h5>

        <table class="table table-borderless datatable">
            <thead>
                <tr>
                    <th scope="col">Loan ID</th>
                    <th scope="col">Lender Name</th>
                    <th scope="col">Interest Rate</th>
                    <th scope="col">Status</th>
                    <th scope="col">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($loans)): ?>
                    <?php foreach ($loans as $loan): ?>
                        <tr>
                            <th scope="row"><a href="#">LN<?php echo $loan['loanid']; ?></a></th>
                            <td><?php echo htmlspecialchars($loan['lender_name']); ?></td>
                            <td><?php echo $loan['interest_rate']; ?>%</td>
                            <td>
                                <?php 
                                    $statusClass = '';
                                    switch ($loan['status']) {
                                        case 'approved': $statusClass = 'bg-success'; break;
                                        case 'pending': $statusClass = 'bg-warning'; break;
                                        case 'rejected': $statusClass = 'bg-danger'; break;
                                    }
                                ?>
                                <span class="badge <?php echo $statusClass; ?>"><?php echo ucfirst($loan['status']); ?></span>
                            </td>
                            <td>
                                <?php if ($loan['status'] === 'approved' && $loan['borrower_id'] === $user_id): ?>
                                  <form method="POST" class="d-inline request-form">
                                      <input type="hidden" name="loanid" value="<?php echo $loan['loanid']; ?>">
                                      <button type="submit" class="btn btn-primary btn-sm request-btn">
                                          Request Amount
                                      </button>
                                  </form>

                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">No loan applications found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
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

  <script>
     document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll(".request-form").forEach(form => {
            let button = form.querySelector(".request-btn");
            let loanId = form.querySelector("input[name='loanid']").value;

            // Check if this loan was already requested
            if (localStorage.getItem("requested_" + loanId)) {
                button.textContent = "Requested";
                button.classList.remove("btn-primary");
                button.classList.add("btn-success");
                button.disabled = true;
            }

            form.addEventListener("submit", function (event) {
                event.preventDefault(); // Prevent immediate form submission

                button.textContent = "Requested";
                button.classList.remove("btn-primary");
                button.classList.add("btn-success");
                button.disabled = true;

                // Store the request state in localStorage
                localStorage.setItem("requested_" + loanId, true);

                // submit the form after a short delay
                setTimeout(() => {
                    this.submit(); // Submit after updating UI
                }, 500);
            });
        });
    });
</script>


</body>

</html>