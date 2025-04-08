<?php
// Database configuration
$host = "localhost";
$dbname = "mfp_database";
$username = "root";
$password = "";

// Create a connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
    
// Initialize response message
$response_message = "";

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($action === 'login') {
        // Login logic
        if (empty($email) || empty($password)) {
            $response_message = "Email and password are required.";
        } else {
            $sql = "SELECT * FROM users WHERE email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);

            if ($stmt->execute()) {
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();

                if ($user && password_verify($password, $user['password'])) {
                    session_start();
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['user_email'] = $user['email'];

                    if ($user['role'] === 'Admin') {
                        header("Location: admin_home.php");
                    } else if ($user['role'] === 'Lender') {
                        header("Location: lender_home.php");
                    }
                    
                    else {
                        header("Location: index.php");
                    }
                    exit();
                } else {
                    $response_message = "Invalid email or password.";
                }
            } else {
                $response_message = "Error: " . $stmt->error;
            }

            $stmt->close();
        }
    } elseif ($action === 'register') {
        $name = $_POST['name'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $role = $_POST['role'] ?? '';
        $certificate_blob = NULL;

        // Lender-specific fields
        $interest_rate = $_POST['interest_rate'] ?? 0;
        $available_funds = $_POST['available_funds'] ?? 0;
        $experience = $_POST['experience'] ?? 0;
        $rating = 0; // Default rating
    
        if (empty($name) || empty($email) || empty($password) || empty($confirm_password) || empty($role)) {
            $response_message = "All fields are required.";
        } elseif ($password !== $confirm_password) {
            $response_message = "Passwords do not match.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
            if ($role === "Lender" && isset($_FILES['non_criminal_cert']) && $_FILES['non_criminal_cert']['size'] > 0) {
                $file_type = $_FILES["non_criminal_cert"]["type"];
                $allowed_types = ["application/pdf", "image/png", "image/jpeg"];
    
                if (in_array($file_type, $allowed_types)) {
                    $certificate_blob = file_get_contents($_FILES["non_criminal_cert"]["tmp_name"]);
                } else {
                    $response_message = "Invalid file type. Only PDF, PNG, and JPEG allowed.";
                }
            }
    
            $sql = "INSERT INTO users (name, email, password, role, non_criminal_cert) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
    
            if ($stmt) {
                $stmt->bind_param("sssss", $name, $email, $hashed_password, $role, $certificate_blob);
                $stmt->send_long_data(4, $certificate_blob); // Send file as BLOB
    
                if ($stmt->execute()) {
                    $user_id = $stmt->insert_id;
    
                    if ($role === "borrower") {
                        $borrower_sql = "INSERT INTO borrower (user_id, name, email) VALUES (?, ?, ?)";
                        $borrower_stmt = $conn->prepare($borrower_sql);
    
                        if ($borrower_stmt) {
                            $borrower_stmt->bind_param("iss", $user_id, $name, $email);
                            $borrower_stmt->execute();
                            $borrower_stmt->close();
                        }
                    }
    
                    // Insert into lenders table if role is 'Lender'
                    if (strtolower($role) === "lender") {
                        
                        $lender_sql = "INSERT INTO lenders (id, name, interest_rate, available_funds, experience, rating) VALUES (?, ?, ?, ?, ?, ?)";
                        $lender_stmt = $conn->prepare($lender_sql);
                    
                        if ($lender_stmt) {
                            $available_funds = floatval($available_funds);

                            $lender_stmt->bind_param("isdiii", $user_id, $name, $interest_rate, $available_funds, $experience, $rating);
                            if (!$lender_stmt->execute()) {
                                $response_message = "Error inserting lender data: " . $lender_stmt->error;
                            }
                            $lender_stmt->close();
                        } else {
                            $response_message = "Error preparing lender statement: " . $conn->error;
                        }
                    }
    
                    $response_message = "Registration successful! You can now log in.";
                } else {
                    $response_message = "Error: " . $stmt->error;
                }
    
                $stmt->close();
            } else {
                $response_message = "Error preparing statement: " . $conn->error;
            }
        }
    }
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login and Registration</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="./style.css">
          <style>
            html, body {
        margin: 0;
        padding: 0;
        min-height: 100vh;
        background-image: url('./Assets/10814678.jpg');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        background-attachment: fixed;
        display: flex;
        align-items: flex-start;
        justify-content: center;
        overflow-x: hidden;
        padding-top: 50px; /* space from top */
        padding-bottom: 50px; /* space from bottom */
    }

    .login-container {
        width: 400px;
        padding: 20px;
        background: rgba(255, 255, 255, 0.9);
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    @media (max-width: 420px) {
        .login-container {
            width: 90%;
        }
    }
    </style>
</head>
<body>
<div class="login-container container mt-5">
    <h2 class="text-center">Login and Registration</h2>

    <?php if (!empty($response_message)): ?>
        <script>
            alert("<?= htmlspecialchars($response_message, ENT_QUOTES) ?>");
        </script>
        <div class="alert alert-info"> <?= htmlspecialchars($response_message) ?> </div>
    <?php endif; ?>

    <form id="auth-form" method="POST" action="login.php"  enctype="multipart/form-data">
        <input type="hidden" name="action" id="action" value="login">

        <div id="role-selection" class="mb-3" style="display: none;">
            <label for="role" class="form-label">Select Role</label>
            <select id="role" name="role" class="form-control">
                <option value="borrower">Borrower</option>
                <option value="lender">Lender</option>
            </select>
        </div>

        <div id="common-fields">
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
            </div>
        </div>

        <div id="registration-fields" style="display: none;">
            <div class="mb-3">
                <label for="name" class="form-label">Full Name</label>
                <input type="text" id="name" name="name" class="form-control" placeholder="Enter your full name">
            </div>
            <div class="mb-3">
                <label for="confirm-password" class="form-label">Confirm Password</label>
                <input type="password" id="confirm-password" name="confirm_password" class="form-control" placeholder="Confirm your password">
            </div>
        </div>

        <div id="lenderFields" style="display: none;">
            <div class="mb-3">
                <label for="interest rate" class="form-label">Interest Rate (%):</label>
                <input class="form-control" placeholder="Interest Rate (%)" type="number" name="interest_rate" step="0.1">
            </div>
            
            <div class="mb-3">
                <label for="availabe funds" class="form-label">Available Funds:</label>
                <input class="form-control" placeholder="Available Funds" name="available_funds" type="number">
                </div>

            <div class="mb-3">
                <label for="experience" class="form-label">Experience (Years):</label>
                <input class="form-control" placeholder="Experience (Years)" name="experience" type="number">
            </div>
        </div>
        <div id="cert-upload-field" style="display: none;">
            <label>Upload Non-Criminal Certificate:</label>
            <input type="file" name="non_criminal_cert">
        </div><br>
        <button type="submit" class="btn btn-primary w-100" id="submit-button">Login</button>
        <button type="button" class="btn btn-link link-underline-light w-100 mt-2" id="toggle-form">Switch to Registration</button>
    </form>
</div>

<script>
        document.querySelector('.alert')?.remove(); // Remove response message when switching
        const toggleFormButton = document.getElementById('toggle-form');
        const actionInput = document.getElementById('action');
        const submitButton = document.getElementById('submit-button');
        const roleSelection = document.getElementById('role-selection');
        const registrationFields = document.getElementById('registration-fields');
        const lenderFields = document.getElementById('lenderFields');
        const certUploadField = document.getElementById('cert-upload-field');

        toggleFormButton.addEventListener('click', () => {
            if (actionInput.value === 'login') {
                actionInput.value = 'register';
                submitButton.textContent = 'Register';
                toggleFormButton.textContent = 'Switch to Login';
                roleSelection.style.display = 'block';
                registrationFields.style.display = 'block';
            } else {
                actionInput.value = 'login';
                submitButton.textContent = 'Login';
                toggleFormButton.textContent = 'Switch to Registration';
                roleSelection.style.display = 'none';
                registrationFields.style.display = 'none';
                lenderFields.style.display = 'none'; // Hide lender fields when switching to login
                certUploadField.style.display = 'none'; // Hide certificate field
            }
        });

        // Ensure lender fields show when "Lender" is selected
        document.getElementById('role').addEventListener('change', function () {
            if (this.value === 'lender') {
                lenderFields.style.display = 'block';
                certUploadField.style.display = 'block';
            } else {
                lenderFields.style.display = 'none';
                certUploadField.style.display = 'none';
            }
        });
        
</script>

</body>
</html>
