<?php
// Initialize the session
session_start();

// Include config file
require_once "../includes/functions.php";

// Define variables and initialize with empty values
$email = $email_err = "";
$success_msg = $error_msg = "";

// Check if email is provided in the URL
if(isset($_GET["email"]) && !empty($_GET["email"])){
    $email = sanitize_input($_GET["email"]);
}

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST" || !empty($email)){
    
    // If form is submitted, get email from POST
    if($_SERVER["REQUEST_METHOD"] == "POST") {
        // Check if email is empty
        if(empty(trim($_POST["email"]))){
            $email_err = "Please enter your email.";
        } else{
            $email = sanitize_input($_POST["email"]);
        }
    }
    
    // If email is valid
    if(empty($email_err)){
        // Check if email exists in database and is not verified
        $sql = "SELECT id, name, email, email_verified FROM patients WHERE email = ?";
        
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
                    mysqli_stmt_bind_result($stmt, $id, $name, $user_email, $email_verified);
                    mysqli_stmt_fetch($stmt);
                    
                    // Check if email is already verified
                    if($email_verified == 1){
                        $error_msg = "Your email is already verified. You can now <a href='login.php'>login</a>.";
                    } else {
                        // Generate new verification token
                        $token = bin2hex(random_bytes(32));
                        
                        // Update verification token in database
                        $update_sql = "UPDATE patients SET verification_token = ?, token_expiry = DATE_ADD(NOW(), INTERVAL 24 HOUR) WHERE id = ?";
                        
                        if($update_stmt = mysqli_prepare($conn, $update_sql)){
                            // Bind variables to the prepared statement as parameters
                            mysqli_stmt_bind_param($update_stmt, "si", $token, $id);
                            
                            // Attempt to execute the prepared statement
                            if(mysqli_stmt_execute($update_stmt)){
                                // Send verification email using the function from functions.php
                                if(send_verification_email($email, $token)){
                                    $success_msg = "Verification email has been sent to $email. Please check your inbox and spam folder.";
                                } else {
                                    $error_msg = "Failed to send verification email. Please try again later.";
                                    // Save email locally for testing
                                    if (function_exists('save_verification_email_local')) {
                                        save_verification_email_local($email, $name, $token);
                                        $success_msg = "A verification email has been generated locally. Please contact the administrator.";
                                    }
                                }
                            } else {
                                $error_msg = "Something went wrong. Please try again later.";
                            }
                            
                            // Close statement
                            mysqli_stmt_close($update_stmt);
                        }
                    }
                } else {
                    $error_msg = "No account found with that email address.";
                }
            } else {
                $error_msg = "Oops! Something went wrong. Please try again later.";
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
    <title>Resend Verification Email - PawPoint</title>
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
            <h2>Resend Verification Email</h2>
            <p>Please enter your email address to receive a new verification link.</p>

            <?php 
            if(!empty($success_msg)){
                echo '<div class="alert alert-success">' . $success_msg . '</div>';
            }
            
            if(!empty($error_msg)){
                echo '<div class="alert alert-danger">' . $error_msg . '</div>';
            }
            ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo $email; ?>" class="<?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>">
                    <span class="invalid-feedback"><?php echo $email_err; ?></span>
                </div>
                <div class="form-group">
                    <input type="submit" class="btn btn-primary btn-block" value="Resend Verification Email">
                </div>
                <p>Remember your password? <a href="login.php">Login here</a>.</p>
                <p>Don't have an account? <a href="register.php">Sign up now</a>.</p>
            </form>
        </div>
    </div>
    
    <footer>
        <p>&copy; <?php echo date("Y"); ?> PawPoint. All rights reserved.</p>
    </footer>
</body>
</html> 