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
        $sql = "SELECT id, name, email, password FROM patients WHERE email = ?";
        
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
                    mysqli_stmt_bind_result($stmt, $id, $name, $email, $hashed_password);
                    if(mysqli_stmt_fetch($stmt)){
                        if(verify_password($password, $hashed_password)){
                            // Password is correct, so start a new session
                            session_start_if_not_started();
                            
                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["patient_id"] = $id;
                            $_SESSION["name"] = $name;
                            $_SESSION["email"] = $email;
                            $_SESSION["user_type"] = "patient";
                            
                            // Redirect user to dashboard
                            redirect("dashboard.php");
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
    <title>Patient Login - PawPoint</title>
    <link rel="stylesheet" href="../css/style.css">
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
        <div class="form-container">
            <h2>Pet Owner Login</h2>
            <p>Please fill in your credentials to login.</p>

            <?php 
            if(!empty($login_err)){
                echo '<div class="alert alert-danger">' . $login_err . '</div>';
            }        
            ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo $email; ?>" class="<?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>">
                    <span class="invalid-feedback"><?php echo $email_err; ?></span>
                </div>    
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="<?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                    <span class="invalid-feedback"><?php echo $password_err; ?></span>
                </div>
                <div class="form-group">
                    <input type="submit" class="btn btn-primary btn-block" value="Login">
                </div>
                <p>Don't have an account? <a href="register.php">Sign up now</a>.</p>
            </form>
        </div>
    </div>
    
    <footer>
        <p>&copy; <?php echo date("Y"); ?> PawPoint. All rights reserved.</p>
    </footer>
</body>
</html> 