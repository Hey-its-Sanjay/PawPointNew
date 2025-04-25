<?php
// Initialize the session
session_start();
 
// Check if the user is already logged in, if yes then redirect to dashboard
if(isset($_SESSION["patient_id"])){
    header("location: dashboard.php");
    exit;
}
 
// Include config file
require_once "../includes/functions.php";
 
// Define variables and initialize with empty values
$email = $password = "";
$email_err = $password_err = $login_err = "";
$verification_message = "";
 
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
        $sql = "SELECT id, name, email, password, email_verified FROM patients WHERE email = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            
            // Set parameters
            $param_email = $email;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Store result
                mysqli_stmt_store_result($stmt);
                
                // Check if email exists
                if(mysqli_stmt_num_rows($stmt) == 1){                    
                    // Bind result variables
                    mysqli_stmt_bind_result($stmt, $id, $name, $db_email, $hashed_password, $email_verified);
                    if(mysqli_stmt_fetch($stmt)){
                        // Check if account is verified
                        if ($email_verified != 1) {
                            $login_err = "Please verify your email before logging in. <a href='resend_verification.php?email=".urlencode($email)."'>Resend verification email</a> or try <a href='../test_verify.php'>manual verification</a>.";
                        } 
                        // Verify password
                        elseif (password_verify($password, $hashed_password)) {
                            // Password is correct, start a new session
                            session_start();
                            
                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["patient_id"] = $id;
                            $_SESSION["name"] = $name;
                            $_SESSION["email"] = $db_email;
                            $_SESSION["user_type"] = "patient";
                            
                            // Redirect to dashboard
                            header("location: dashboard.php");
                            exit;
                        } else {
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
    <title>Login - PawPoint</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .login-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .error {
            color: #e74c3c;
            margin-top: 5px;
            font-size: 14px;
        }
        
        .login-error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .btn-primary {
            background-color: #4a7c59;
            color: white;
            border: none;
            padding: 12px 20px;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn-primary:hover {
            background-color: #3c6547;
        }
        
        .register-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .register-link a {
            color: #4a7c59;
            text-decoration: none;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <header>
        <h1>PawPoint</h1>
        <p>Your Pet's Healthcare Companion</p>
    </header>
    
    <nav>
        <ul>
            <li><a href="../index.php">Home</a></li>
            <li><a href="../doctor/login.php">Doctor Login</a></li>
            <li><a href="login.php">Patient Login</a></li>
        </ul>
    </nav>
    
    <div class="container">
        <div class="login-container">
            <h2>Patient Login</h2>
            <p>Please enter your credentials to login</p>
            
            <?php if (!empty($login_err)): ?>
                <div class="login-error"><?php echo $login_err; ?></div>
            <?php endif; ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>">
                    <?php if (!empty($email_err)): ?>
                        <div class="error"><?php echo $email_err; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                    <?php if (!empty($password_err)): ?>
                        <div class="error"><?php echo $password_err; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <input type="submit" class="btn-primary" value="Login">
                </div>
                
                <div class="register-link">
                    <p>Don't have an account? <a href="register.php">Register here</a></p>
                    <p><a href="forgot-password.php">Forgot password?</a></p>
                </div>
            </form>
        </div>
    </div>
    
    <footer>
        <p>&copy; <?php echo date("Y"); ?> PawPoint. All rights reserved.</p>
    </footer>
</body>
</html> 