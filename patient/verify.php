<?php
// Include necessary files
require_once "../includes/functions.php";

// Initialize variables
$token = $verification_message = "";
$verification_success = false;

// Process verification
if(isset($_GET["token"]) && !empty(trim($_GET["token"]))) {
    $token = trim($_GET["token"]);
    
    // Verify token
    $result = verify_patient_email($token);
    
    switch($result) {
        case "success":
            $verification_success = true;
            $verification_message = "Your email has been successfully verified. You can now <a href='login.php'>login</a> to your account.";
            break;
            
        case "already_verified":
            $verification_success = true;
            $verification_message = "This email is already verified. You can <a href='login.php'>login</a> to your account.";
            break;
            
        case "expired":
            $verification_message = "This verification link has expired. Please <a href='resend_verification.php'>request a new verification email</a>.";
            break;
            
        case "invalid":
            $verification_message = "Invalid verification link. Please make sure you're using the most recent verification email we sent you.";
            break;
            
        default:
            $verification_message = "An error occurred during verification. Please try again or contact support.";
    }
} else {
    $verification_message = "No verification token provided. Please use the link sent to your email.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - PawPoint</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .verification-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            margin: 30px auto;
            max-width: 600px;
            padding: 30px;
            text-align: center;
        }
        .verification-icon {
            font-size: 60px;
            margin-bottom: 20px;
        }
        .success-icon {
            color: #27AE60;
        }
        .error-icon {
            color: #E74C3C;
        }
        .verification-message {
            font-size: 1.1rem;
            margin-bottom: 25px;
        }
        .success-message {
            color: #27AE60;
        }
        .error-message {
            color: #E74C3C;
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
            <li><a href="login.php">Patient Login</a></li>
        </ul>
    </nav>
    
    <div class="container">
        <div class="verification-container">
            <h2>Email Verification</h2>
            
            <div class="verification-icon <?php echo $verification_success ? 'success-icon' : 'error-icon'; ?>">
                <?php echo $verification_success ? '✓' : '⚠'; ?>
            </div>
            
            <div class="verification-message <?php echo $verification_success ? 'success-message' : 'error-message'; ?>">
                <?php echo $verification_message; ?>
            </div>
            
            <div class="verification-actions">
                <?php if($verification_success): ?>
                    <a href="login.php" class="btn btn-primary">Login Now</a>
                <?php else: ?>
                    <a href="resend_verification.php" class="btn btn-primary">Request New Verification</a>
                    <a href="../index.php" class="btn">Back to Home</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <footer>
        <p>&copy; <?php echo date("Y"); ?> PawPoint. All rights reserved.</p>
    </footer>
</body>
</html> 