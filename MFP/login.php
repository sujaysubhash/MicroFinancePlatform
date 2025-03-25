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
                    $response_message = "Login successful! Welcome " . htmlspecialchars($user['name']) . ".";
                     // Start a session
                      session_start();
                      $_SESSION['user_id'] = $user['id'];
                      $_SESSION['user_name'] = $user['name'];
                      $_SESSION['user_id'] = $user['id'];
                      $_SESSION['user_name'] = $user['name'];
                      $_SESSION['user_role'] = $user['role']; // Store role in session
                      $_SESSION['user_email'] = $user['email'];
                      // Redirect to index.php
                     // Redirect based on role
                    if ($user['role'] === 'Admin') {
                        header("Location: admin_home.php"); // Redirect to admin dashboard
                    } else {
                        header("Location: index.php"); // Redirect to normal user dashboard
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
         // Registration logic
    $name = $_POST['name'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? '';

    if (empty($name) || empty($email) || empty($password) || empty($confirm_password) || empty($role)) {
        $response_message = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $response_message = "Passwords do not match.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert user into the users table
        $sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);

            if ($stmt->execute()) {
                $user_id = $stmt->insert_id; // Get the inserted user's ID

                // If the role is "borrower", also insert into the borrower table
                if ($role === "borrower") {
                    $borrower_sql = "INSERT INTO borrower (user_id, name, email) VALUES (?, ?, ?)";
                    $borrower_stmt = $conn->prepare($borrower_sql);
                    
                    if ($borrower_stmt) {
                        $borrower_stmt->bind_param("iss", $user_id, $name, $email);
                        $borrower_stmt->execute();
                        $borrower_stmt->close();
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
            body {
          margin: 0; /* Removes default margin */
          padding: 0; /* Removes default padding */
          height: 100vh; /* Sets height to 100% of the viewport */
          background-image: url('./Assets/10814678.jpg'); /* Path to your image */
          background-size: cover; /* Ensures the image covers the entire page */
          background-position: center; /* Centers the image */
          background-repeat: no-repeat; /* Prevents tiling of the image */
          background-attachment: fixed; /* Keeps the image fixed during scrolling */
          }

      .login-container {
          width: 400px;
          padding: 20px;
          background: rgba(255, 255, 255, 0.8); /* Semi-transparent white background */
          border-radius: 10px;
          box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
          position: absolute;
          top: 50%;
          left: 50%;
          transform: translate(-50%, -50%);
          }
    </style>
</head>
<body>
<div class="login-container container mt-5">
    <h2 class="text-center">Login and Registration</h2>

    <?php if (!empty($response_message)): ?>
        <div class="alert alert-info"> <?= htmlspecialchars($response_message) ?> </div>
    <?php endif; ?>

    <form id="auth-form" method="POST" action="login.php">
        <input type="hidden" name="action" id="action" value="login">

        <div id="role-selection" class="mb-3" style="display: none;">
            <label for="role" class="form-label">Select Role</label>
            <select id="role" name="role" class="form-control">
                <option value="borrower">Borrower</option>
                <option value="lender">Lender</option>
                <option value="admin">Administrator</option>
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

        <button type="submit" class="btn btn-primary w-100" id="submit-button">Login</button>
        <button type="button" class="btn btn-link w-100 mt-2" id="toggle-form">Switch to Registration</button>
    </form>
</div>

<script>
    const toggleFormButton = document.getElementById('toggle-form');
    const actionInput = document.getElementById('action');
    const submitButton = document.getElementById('submit-button');
    const roleSelection = document.getElementById('role-selection');
    const registrationFields = document.getElementById('registration-fields');

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
        }
    });
</script>

</body>
</html>
