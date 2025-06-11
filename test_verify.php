<?php
// This is a test page to help with verification issues
require_once "includes/functions.php";

$success_msg = $error_msg = "";

// If form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!empty($_POST["email"]) && !empty($_POST["token"])) {
        $email = sanitize_input($_POST["email"]);
        $token = sanitize_input($_POST["token"]);
        
        // Connect to database
        global $conn;
        
        // Find the user with this email
        $sql = "SELECT id, email, verification_token, token_expiry, email_verified FROM patients WHERE email = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    mysqli_stmt_bind_result($stmt, $id, $user_email, $verification_token, $token_expiry, $email_verified);
                    mysqli_stmt_fetch($stmt);
                    
                    if ($email_verified == 1) {
                        $success_msg = "Your email is already verified. You can now <a href='patient/login.php'>login</a>.";
                    } elseif ($token == $verification_token) {
                        // Verify the email
                        $update_sql = "UPDATE patients SET email_verified = 1, verification_token = NULL, token_expiry = NULL WHERE id = ?";
                        
                        if ($update_stmt = mysqli_prepare($conn, $update_sql)) {
                            mysqli_stmt_bind_param($update_stmt, "i", $id);
                            
                            if (mysqli_stmt_execute($update_stmt)) {
                                $success_msg = "Email verification successful! You can now <a href='patient/login.php'>login</a>.";
                            } else {
                                $error_msg = "Database error when updating verification status.";
                            }
                            
                            mysqli_stmt_close($update_stmt);
                        }
                    } else {
                        $error_msg = "Invalid verification token.";
                    }
                } else {
                    $error_msg = "No account found with that email address.";
                }
            } else {
                $error_msg = "Database query error.";
            }
            
            mysqli_stmt_close($stmt);
        }
    } else {
        $error_msg = "Please provide both email and token.";
    }
}

// Get the latest verification email
$email_files = glob("emails/verification_*.html");
$token_from_file = "";
$email_from_file = "";

if (!empty($email_files)) {
    // Sort by creation time, newest first
    usort($email_files, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    
    // Get the newest file
    $latest_file = $email_files[0];
    $email_content = file_get_contents($latest_file);
    
    // Extract token from the file
    if (preg_match('/token=([a-f0-9]+)&/i', $email_content, $matches)) {
        $token_from_file = $matches[1];
    }
    
    // Extract email from the file
    if (preg_match('/email=([^&"\']+)/i', $email_content, $matches)) {
        $email_from_file = urldecode($matches[1]);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Verification - PawPoint</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .test-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin: 20px auto;
            max-width: 600px;
        }
        .token-display {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            word-break: break-all;
            margin: 10px 0;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <header>
        <h1>PawPoint</h1>
        <p>Email Verification Test Tool</p>
    </header>
    
    <div class="container">
        <div class="test-container">
            <h2>Manual Email Verification</h2>
            
            <?php 
            if(!empty($success_msg)){
                echo '<div class="alert alert-success">' . $success_msg . '</div>';
            }
            
            if(!empty($error_msg)){
                echo '<div class="alert alert-danger">' . $error_msg . '</div>';
            }
            ?>
            
            <p>This page helps you verify an email if the verification link is not working correctly.</p>
            
            <?php if (!empty($email_from_file) && !empty($token_from_file)): ?>
                <div class="alert alert-info">
                    <p><strong>Latest verification email information:</strong></p>
                    <p>Email: <span class="token-display"><?php echo htmlspecialchars($email_from_file); ?></span></p>
                    <p>Token: <span class="token-display"><?php echo htmlspecialchars($token_from_file); ?></span></p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email_from_file); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="token">Verification Token:</label>
                    <input type="text" id="token" name="token" value="<?php echo htmlspecialchars($token_from_file); ?>" required>
                </div>
                
                <div class="form-group">
                    <input type="submit" class="btn btn-primary btn-block" value="Verify Email">
                </div>
            </form>
            
            <div class="links" style="margin-top: 20px; text-align: center;">
                <a href="patient/login.php" class="btn">Go to Login</a>
                <a href="index.php" class="btn">Return to Homepage</a>
            </div>
        </div>
    </div>
    
    <footer>
        <p>&copy; <?php echo date("Y"); ?> PawPoint. All rights reserved.</p>
    </footer>
</body>
</html> 