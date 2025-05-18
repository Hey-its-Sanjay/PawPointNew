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
    <title>Login - VetCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a2e0e6ad65.js" crossorigin="anonymous"></script>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Quicksand', sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(to right, #cbe9d8, #e0f7f1);
        }

        .login-container {
            max-width: 420px;
            margin: 80px auto;
            padding: 40px 30px;
            background-color: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .login-container img {
            width: 80px;
            margin-bottom: 15px;
        }

        .login-container h2 {
            color: #31725b;
            margin-bottom: 10px;
        }

        .login-container p {
            color: #666;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
            position: relative;
        }

        .form-group i {
            position: absolute;
            top: 12px;
            left: 10px;
            color: #31725b;
        }

        .form-group input {
            width: 100%;
            padding: 10px 10px 10px 35px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
        }

        .form-group input:focus {
            border-color: #31725b;
            outline: none;
            box-shadow: 0 0 6px rgba(49, 114, 91, 0.2);
        }

        .btn-primary {
            width: 100%;
            background-color: #31725b;
            color: white;
            border: none;
            padding: 12px;
            font-size: 16px;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-primary:hover {
            background-color: #285c4c;
        }

        .login-error {
            background-color: #ffe0e0;
            color: #a33;
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 5px;
        }

        .register-link {
            margin-top: 20px;
            font-size: 14px;
        }

        .register-link a {
            color: #31725b;
            text-decoration: none;
            font-weight: bold;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        footer {
            text-align: center;
            margin-top: 60px;
            color: #444;
            font-size: 14px;
        }

        @media (max-width: 480px) {
            .login-container {
                margin: 40px 20px;
            }
        }
    </style>
</head>
<body>
<nav style="background-color: #4a7c59; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; color: white;">
    <div style="font-size: 1.5em; font-weight: bold;">
    <div class="logo">
                    <img src="../images/pawpoint.png" alt="PawPoint Logo" style="height: 60px; margin-right: 5px; vertical-align: middle;">
                    Paw<span>Point</span>
                </div>
        
    </div>
    <div>
        <a href="../index.php" style="margin-right: 20px; text-decoration: none; color: white;">Home</a>
        <a href="login.php" style="margin-right: 20px; text-decoration: none; color: white;">Login</a>
        <a href="register.php" style="text-decoration: none; color: white;">Sign Up</a>
    </div>
</nav>

    <div class="login-container">
        <img src="../images/PawPoint.png" alt="Vet Logo" />
        <h2>Welcome to VetCare</h2>
        <p>Please login to continue caring for your furry friends</p>

        <?php if (!empty($login_err)): ?>
            <div class="login-error"> <?php echo $login_err; ?> </div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" id="email"
                    class="<?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>"
                    placeholder="Email Address" value="<?php echo $email; ?>">
                <?php if (!empty($email_err)): ?>
                    <div class="error"> <?php echo $email_err; ?> </div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" id="password"
                    class="<?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>"
                    placeholder="Password">
                <?php if (!empty($password_err)): ?>
                    <div class="error"> <?php echo $password_err; ?> </div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn-primary">Login</button>

            <div class="register-link">
                <p>Don't have an account? <a href="register.php">Register</a></p>
                <p><a href="forgot-password.php">Forgot password?</a></p>
            </div>
        </form>
    </div>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> VetCare. All rights reserved.</p>
    </footer>
</body>
</html>
