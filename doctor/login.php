<?php
// Initialize the session
session_start();
 
// Check if the user is already logged in, if yes then redirect to dashboard
if(isset($_SESSION["doctor_id"])){
    header("location: dashboard.php");
    exit;
}
 
// Include config file
require_once "../includes/functions.php";
 
// Define variables and initialize with empty values
$email = $password = "";
$email_err = $password_err = $login_err = "";
 
// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    // Check if email is empty
    if(empty(trim($_POST["email"]))){
        $email_err = "Please enter your email.";
    } else{
        $email = sanitize_input($_POST["email"]);
    }
    
    // Check if password is empty
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter your password.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    // Validate credentials
    if(empty($email_err) && empty($password_err)){
        // Prepare a select statement
        $sql = "SELECT id, name, email, password, status FROM doctors WHERE email = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            
            // Set parameters
            $param_email = $email;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Store result
                mysqli_stmt_store_result($stmt);
                
                // Check if email exists, if yes then verify password
                if(mysqli_stmt_num_rows($stmt) == 1){                    
                    // Bind result variables
                    mysqli_stmt_bind_result($stmt, $id, $name, $email, $hashed_password, $status);
                    if(mysqli_stmt_fetch($stmt)){
                        if(verify_password($password, $hashed_password)){
                            // Check if the account has been approved
                            if($status == 'approved'){
                                // Password is correct, so start a new session
                                session_start_if_not_started();
                                
                                // Store data in session variables
                                $_SESSION["loggedin"] = true;
                                $_SESSION["doctor_id"] = $id;
                                $_SESSION["name"] = $name;
                                $_SESSION["email"] = $email;
                                $_SESSION["user_type"] = "doctor";
                                
                                // Redirect user to dashboard
                                redirect("dashboard.php");
                            } else if($status == 'pending') {
                                // Account is still pending approval
                                $login_err = "Your account is pending approval by the administrator.";
                            } else if($status == 'rejected') {
                                // Account has been rejected
                                $login_err = "Your account application has been rejected. Please contact the administrator for more information.";
                            } else {
                                // Other status
                                $login_err = "There's an issue with your account. Please contact the administrator.";
                            }
                        } else{
                            // Password is not valid, display a generic error message
                            $login_err = "Invalid email or password.";
                        }
                    }
                } else{
                    // Email doesn't exist, display a generic error message
                    $login_err = "Invalid email or password.";
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
    
    // Close connection
    mysqli_close($conn);
}
?>


 <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Login - PawPoint</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f9f9f9;
            color: #333;
        }

        header {
            background-color: #005b96;
            color: white;
            padding: 20px 0;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        header h1 {
            font-size: 2.5em;
        }

        header p {
            font-size: 1.1em;
            margin-top: 5px;
        }

        nav {
            background-color: #003f6b;
            padding: 10px 0;
            text-align: center;
        }

        nav ul {
            list-style: none;
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        nav ul li a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 5px;
        }

        nav ul li a:hover {
            background-color: #005b96;
        }

        .login-container {
            max-width: 500px;
            background-color: white;
            margin: 40px auto;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }

        .login-container h2 {
            margin-bottom: 15px;
            color: #005b96;
        }

        .login-container p {
            margin-bottom: 10px;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            font-size: 1em;
            border: 1px solid #ccc;
            border-radius: 6px;
            transition: border 0.3s;
        }

        .form-group input:focus {
            border-color: #005b96;
            outline: none;
        }

        .btn-primary {
            width: 100%;
            background-color: #005b96;
            color: white;
            padding: 12px;
            font-size: 1em;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .btn-primary:hover {
            background-color: #00477a;
        }

        .alert {
            padding: 10px 15px;
            margin-bottom: 15px;
            border-radius: 5px;
        }

        .alert-info {
            background-color: #e7f3fe;
            color: #31708f;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #842029;
        }

        .invalid-feedback {
            color: red;
            font-size: 0.9em;
        }

        footer {
            text-align: center;
            padding: 15px;
            font-size: 0.9em;
            background-color: #f0f0f0;
            color: #555;
            margin-top: 60px;
        }

        .password-field {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #718096;
        }
        
        .password-toggle:hover {
            color: #4a5568;
        }
    </style>
</head>
<body>
    <header>
        <h1>🐾 PawPoint</h1>
        <p>Your Pet's Healthcare Companion</p>
    </header>

    <nav>
        <ul>
            <li><a href="../index.php">Home</a></li>
            <li><a href="login.php">Doctor Login</a></li>
            <li><a href="../patient/login.php">Patient Login</a></li>
        </ul>
    </nav>

    <div class="login-container">
        <h2>Doctor Login</h2>
        <p>Please fill in your credentials to login.</p>
        <p class="alert alert-info">Note: Your account must be approved by an administrator before you can log in.</p>

        <?php 
        if (!empty($login_err)) {
            echo '<div class="alert alert-danger">' . $login_err . '</div>';
        }        
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>">
                <?php if(!empty($email_err)): ?>
                    <span class="invalid-feedback"><?php echo $email_err; ?></span>
                <?php endif; ?>
            </div>    
            
            <div class="form-group">
                <label>Password</label>
                <div class="password-field">
                    <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" id="passwordField">
                    <button type="button" class="password-toggle" onclick="togglePassword('passwordField')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <?php if(!empty($password_err)): ?>
                    <span class="invalid-feedback"><?php echo $password_err; ?></span>
                <?php endif; ?>
            </div>
              <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Login">
            </div>
            <p>Don't have an account? <a href="register.php">Sign up now</a>.</p>
            <p>Forgot your password? <a href="forgot-password.php">Reset it here</a>.</p>
        </form>
    </div>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> PawPoint. All rights reserved.</p>
    </footer>

    <script>
        function togglePassword(fieldId) {
            const passwordField = document.getElementById(fieldId);
            const toggleButton = passwordField.nextElementSibling;
            const icon = toggleButton.querySelector('i');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
