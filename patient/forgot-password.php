<?php
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/email_functions.php';

// Initialize variables
$email = '';
$message = '';
$message_type = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    // Validate email
    if (empty($email)) {
        $message = 'Please enter your email address.';
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $message_type = 'error';
    } else {
        // Check if email exists in the database
        $stmt = $conn->prepare("SELECT id FROM patients WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            // Email not found, but don't reveal this for security
            $message = 'If your email is registered, you will receive password reset instructions shortly.';
            $message_type = 'success';
        } else {
            // Email exists, generate token and send reset email
            $patient_id = $result->fetch_assoc()['id'];
            $token = bin2hex(random_bytes(32)); // Generate secure random token
            $expires = date('Y-m-d H:i:s', time() + 3600); // Token expires in 1 hour
            
            // Delete any existing tokens for this patient
            $stmt = $conn->prepare("DELETE FROM password_reset_tokens WHERE patient_id = ?");
            $stmt->bind_param("i", $patient_id);
            $stmt->execute();
            
            // Insert new token
            $stmt = $conn->prepare("INSERT INTO password_reset_tokens (patient_id, token, expires_at) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $patient_id, $token, $expires);
            
            if ($stmt->execute()) {
                // Construct reset link
                $reset_link = "http://{$_SERVER['HTTP_HOST']}/pawpoint/patient/reset-password.php?email=" . urlencode($email) . "&token=" . $token;
                
                // Send email with reset link
                if (send_password_reset_email($email, $reset_link)) {
                    $message = 'Password reset instructions have been sent to your email.';
                    $message_type = 'success';
                } else {
                    $message = 'Failed to send password reset email. Please try again later.';
                    $message_type = 'error';
                }
            } else {
                $message = 'An error occurred. Please try again.';
                $message_type = 'error';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - PawPoint</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .password-reset-container {
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
        
        .success-message {
            background-color: #d4edda;
            color: #155724;
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
        
        .links {
            text-align: center;
            margin-top: 20px;
        }
        
        .links a {
            color: #4a7c59;
            text-decoration: none;
        }
        
        .links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <?php include "../includes/header.php"; ?>
    
    <div class="container">
        <div class="password-reset-container">
            <h2>Forgot Password</h2>
            <p>Please enter your email address to reset your password.</p>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
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
                    <input type="submit" class="btn-primary" value="Reset Password">
                </div>
            </form>
            
            <div class="links">
                <p><a href="login.php">Back to Login</a></p>
            </div>
        </div>
    </div>
    
    <?php include "../includes/footer.php"; ?>
</body>
</html> 