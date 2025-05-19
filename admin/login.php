<?php
// Initialize the session
session_start();
 
// Check if the user is already logged in, if yes then redirect to dashboard
if(isset($_SESSION["loggedin"]) && isset($_SESSION["admin_id"])){
    header("location: dashboard.php");
    exit;
}
 
// Include config file
require_once "../includes/config.php";

// Define variables and initialize with empty values
$username = $password = "";
$username_err = $password_err = $login_err = "";
 
// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    // Check if username is empty
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter username.";
    } else{
        $username = trim($_POST["username"]);
    }
    
    // Check if password is empty
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter your password.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    // Validate credentials
    if(empty($username_err) && empty($password_err)){
        // Prepare a select statement
        $sql = "SELECT id, username, password FROM admins WHERE username = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            
            // Set parameters
            $param_username = $username;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Store result
                mysqli_stmt_store_result($stmt);
                
                // Check if username exists, if yes then verify password
                if(mysqli_stmt_num_rows($stmt) == 1){                    
                    // Bind result variables
                    mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password);
                    if(mysqli_stmt_fetch($stmt)){
                        if(password_verify($password, $hashed_password)){
                            // Password is correct, so start a new session
                            session_start();
                            
                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["admin_id"] = $id;
                            $_SESSION["username"] = $username;                            
                            
                            // Redirect user to dashboard page
                            header("location: dashboard.php");
                        } else{
                            // Password is not valid, display a generic error message
                            $login_err = "Invalid username or password.";
                        }
                    }
                } else{
                    // Username doesn't exist, display a generic error message
                    $login_err = "Invalid username or password.";
                }
            } else{
                $login_err = "Oops! Something went wrong. Please try again later.";
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
<head>    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - PawPoint</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Arial', sans-serif;
        }
        
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        
        .login-logo {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .login-logo img {
            max-width: 150px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: #4a5568;
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .login-header p {
            color: #718096;
            font-size: 16px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #4a5568;
            font-weight: 600;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
        }
        
        .form-control:focus {
            border-color: #4299e1;
            outline: none;
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
        }
        
        .alert {
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .alert-danger {
            background-color: #fed7d7;
            color: #c53030;
            border: 1px solid #fc8181;
        }
        
        .btn-primary {
            display: block;
            width: 100%;
            padding: 12px;
            background-color: #4299e1;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
        }
          .btn-primary:hover {
            background-color: #3182ce;
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
        
        .invalid-feedback {
            color: #e53e3e;
            font-size: 14px;
            margin-top: 5px;
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
            <li><a href="../doctor/login.php">Doctor Login</a></li>
            <li><a href="../patient/login.php">Patient Login</a></li>
        </ul>
    </nav>
    <div class="login-container">
        <div class="login-logo">
            <img src="../images/pawpoint.png" alt="PawPoint Logo">
        </div>
        
        <div class="login-header">
            <h1>Admin Login</h1>
            <p>Access the veterinary practice management portal</p>
        </div>
        
        <?php if(!empty($login_err)): ?>
            <div class="alert alert-danger"><?php echo $login_err; ?></div>
        <?php endif; ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                <?php if(!empty($username_err)): ?>
                    <span class="invalid-feedback"><?php echo $username_err; ?></span>
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
                <button type="submit" class="btn btn-primary">Login</button>
            </div>
        </form>
    </div>
    
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