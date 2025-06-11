<?php
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/email_functions.php';
require_once '../includes/functions.php';

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
        $stmt = $conn->prepare("SELECT id, name FROM doctors WHERE email = ? AND status = 'approved'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            // Email not found or doctor not approved, but don't reveal this for security
            $message = 'If your email is registered and your account is approved, you will receive password reset instructions shortly.';
            $message_type = 'success';
        } else {
            // Email exists and doctor is approved
            $doctor = $result->fetch_assoc();
            $doctor_id = $doctor['id'];
            $doctor_name = $doctor['name'];
            
            $token = bin2hex(random_bytes(32)); // Generate secure random token
            $expires = date('Y-m-d H:i:s', time() + 3600); // Token expires in 1 hour
            
            // Delete any existing tokens for this doctor
            $stmt = $conn->prepare("DELETE FROM password_reset_tokens WHERE user_id = ? AND user_type = 'doctor'");
            $stmt->bind_param("i", $doctor_id);
            $stmt->execute();
            
            // Insert new token
            $stmt = $conn->prepare("INSERT INTO password_reset_tokens (user_id, user_type, token, expires_at) VALUES (?, 'doctor', ?, ?)");
            $stmt->bind_param("iss", $doctor_id, $token, $expires);
            
            if ($stmt->execute()) {
                // Construct reset link using the helper function
                $reset_link = get_site_url() . "/doctor/reset-password.php?email=" . urlencode($email) . "&token=" . $token;
                
                // Send email with reset link
                if (send_password_reset_email($email, $reset_link, 'doctor', $doctor_name)) {
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
    <title>Forgot Password - Doctor Portal - PawPoint</title>
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
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .btn-primary {
            background-color: #4CAF50;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        
        .btn-primary:hover {
            background-color: #45a049;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        
        .alert-error {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        
        .form-links {
            text-align: center;
            margin-top: 20px;
        }
        
        .form-links a {
            color: #4CAF50;
            text-decoration: none;
        }
        
        .form-links a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .password-reset-container {
                margin: 20px;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="password-reset-container">
        <h2>Reset Password</h2>
        <p>Please enter your email address to reset your password.</p>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="forgot-password.php">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($email); ?>">
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Reset Password</button>
            </div>
        </form>
        
        <div class="form-links">
            <a href="login.php">Back to Login</a>
        </div>
    </div>
</body>
</html>
