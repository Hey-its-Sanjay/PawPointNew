<?php
require_once '../includes/config.php';
require_once '../includes/db_connect.php';

// Initialize variables
$email = '';
$token = '';
$new_password = '';
$confirm_password = '';
$message = '';
$message_type = '';
$valid_token = false;

// Check if email and token are provided in URL
if (isset($_GET['email']) && isset($_GET['token'])) {
    $email = trim($_GET['email']);
    $token = trim($_GET['token']);
    
    // Validate email and token
    if (empty($email) || empty($token) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Invalid reset link. Please request a new password reset.';
        $message_type = 'error';
    } else {
        // Check if email exists and token is valid
        $stmt = $conn->prepare("
            SELECT prt.* FROM password_reset_tokens prt
            JOIN doctors d ON prt.user_id = d.id
            WHERE d.email = ? AND prt.token = ? AND prt.user_type = 'doctor' AND prt.expires_at > NOW()
        ");
        $stmt->bind_param("ss", $email, $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $valid_token = true;
        } else {
            $message = 'Invalid or expired reset link. Please request a new password reset.';
            $message_type = 'error';
        }
    }
} else {
    $message = 'Invalid reset link. Please request a new password reset.';
    $message_type = 'error';
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // Validate passwords
    if (empty($new_password)) {
        $message = 'Please enter a new password.';
        $message_type = 'error';
    } elseif (strlen($new_password) < 8) {
        $message = 'Password must be at least 8 characters long.';
        $message_type = 'error';
    } elseif (empty($confirm_password)) {
        $message = 'Please confirm your password.';
        $message_type = 'error';
    } elseif ($new_password !== $confirm_password) {
        $message = 'Passwords do not match.';
        $message_type = 'error';
    } else {
        // Get doctor ID from email
        $stmt = $conn->prepare("SELECT id FROM doctors WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $doctor_id = $result->fetch_assoc()['id'];
            
            // Hash the new password
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password
            $stmt = $conn->prepare("UPDATE doctors SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $password_hash, $doctor_id);
            
            if ($stmt->execute()) {
                // Delete used token
                $stmt = $conn->prepare("DELETE FROM password_reset_tokens WHERE user_id = ? AND user_type = 'doctor'");
                $stmt->bind_param("i", $doctor_id);
                $stmt->execute();
                
                $message = 'Your password has been reset successfully. You can now login with your new password.';
                $message_type = 'success';
                $valid_token = false; // Hide the form after successful reset
            } else {
                $message = 'Failed to update password. Please try again.';
                $message_type = 'error';
            }
        } else {
            $message = 'An error occurred. Please try again.';
            $message_type = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Doctor Portal - PawPoint</title>
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
        
        .password-toggle {
            position: relative;
        }
        
        .password-toggle .toggle-icon {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
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
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="form-container">
            <?php if ($valid_token): ?>
                <p>Please enter and confirm your new password.</p>
                
                <form method="POST" action="reset-password.php?email=<?php echo urlencode($email); ?>&token=<?php echo $token; ?>">
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <div class="password-toggle">
                            <input type="password" id="new_password" name="new_password" class="form-control" required minlength="8">
                            <span class="toggle-icon" onclick="togglePassword('new_password')">👁️</span>
                        </div>
                        <small>Password must be at least 8 characters long</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="password-toggle">
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                            <span class="toggle-icon" onclick="togglePassword('confirm_password')">👁️</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Reset Password</button>
                    </div>
                </form>
            <?php elseif ($message_type === 'success'): ?>
                <div class="form-links">
                    <p><a href="login.php">Click here to login</a></p>
                </div>
            <?php else: ?>
                <div class="form-links">
                    <p><a href="forgot-password.php">Request a new password reset</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function togglePassword(inputId) {
        const input = document.getElementById(inputId);
        input.type = input.type === 'password' ? 'text' : 'password';
    }
    </script>
</body>
</html>
