<?php
// Initialize the session
session_start();

// Include config file
require_once "../includes/functions.php";

// Define variables
$email = $token = "";
$success_msg = $error_msg = "";

// Check if token and email are provided in the URL
if(isset($_GET["token"]) && !empty($_GET["token"]) && isset($_GET["email"]) && !empty($_GET["email"])) {
    $token = sanitize_input($_GET["token"]);
    $email = sanitize_input($_GET["email"]);
    
    // Prepare a select statement
    $sql = "SELECT id, email, verification_token, token_expiry, email_verified FROM patients WHERE email = ?";
    
    if($stmt = mysqli_prepare($conn, $sql)) {
        // Bind variables to the prepared statement as parameters
        mysqli_stmt_bind_param($stmt, "s", $param_email);
        
        // Set parameters
        $param_email = $email;
        
        // Attempt to execute the prepared statement
        if(mysqli_stmt_execute($stmt)) {
            // Store result
            mysqli_stmt_store_result($stmt);
            
            // Check if email exists
            if(mysqli_stmt_num_rows($stmt) == 1) {
                // Bind result variables
                mysqli_stmt_bind_result($stmt, $id, $user_email, $verification_token, $token_expiry, $email_verified);
                
                if(mysqli_stmt_fetch($stmt)) {
                    // Check if email is already verified
                    if($email_verified == 1) {
                        $success_msg = "Your email is already verified. You can now <a href='login.php'>login</a>.";
                    } 
                    // Check if token matches
                    else if($verification_token == $token) {
                        // Check if token is expired
                        $now = new DateTime();
                        $expiry = new DateTime($token_expiry);
                        
                        if($now > $expiry) {
                            $error_msg = "The verification link has expired. Please <a href='resend_verification.php?email=" . urlencode($email) . "'>request a new link</a>.";
                        } else {
                            // Update user to verified status
                            $update_sql = "UPDATE patients SET email_verified = 1, verification_token = NULL, token_expiry = NULL WHERE id = ?";
                            
                            if($update_stmt = mysqli_prepare($conn, $update_sql)) {
                                // Bind variables to the prepared statement as parameters
                                mysqli_stmt_bind_param($update_stmt, "i", $id);
                                
                                // Attempt to execute the prepared statement
                                if(mysqli_stmt_execute($update_stmt)) {
                                    $success_msg = "Your email has been verified successfully! You can now <a href='login.php'>login</a>.";
                                } else {
                                    $error_msg = "Something went wrong. Please try again later.";
                                }
                                
                                // Close statement
                                mysqli_stmt_close($update_stmt);
                            }
                        }
                    } else {
                        $error_msg = "Invalid verification token. Please <a href='resend_verification.php?email=" . urlencode($email) . "'>request a new link</a>.";
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
    
    // Close connection
    mysqli_close($conn);
} else {
    $error_msg = "Invalid verification request. Please make sure you clicked on the correct link.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - PawPoint</title>
    <link rel="stylesheet" href="../css/style.css">    <style>
        @media (max-width: 768px) {
            .container {
                width: 95%;
                padding: 10px;
            }
            .verification-container {
                padding: 15px;
                margin: 20px auto;
            }
            .alert {
                padding: 10px;
                word-break: break-word;
            }
            a {
                display: inline-block;
                padding: 8px 0;
            }
            .verification-link {
                word-break: break-all;
                background: #f8f9fa;
                padding: 10px;
                border-radius: 4px;
                margin: 10px 0;
                font-size: 14px;
            }
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
        <div class="verification-container">
            <h2>Email Verification</h2>
            
            <?php 
            if(!empty($success_msg)){
                echo '<div class="alert alert-success">' . $success_msg . '</div>';
            }
            
            if(!empty($error_msg)){
                echo '<div class="alert alert-danger">' . $error_msg . '</div>';
            }
            ?>
            
            <div class="links">
                <a href="login.php">Go to Login</a>
                <a href="../index.php">Return to Homepage</a>
            </div>
        </div>
    </div>
    
    <footer>
        <p>&copy; <?php echo date("Y"); ?> PawPoint. All rights reserved.</p>
    </footer>
</body>
</html> 