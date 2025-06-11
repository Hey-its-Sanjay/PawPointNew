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
        // Check if email exists and token is valid using new token table structure
        $stmt = $conn->prepare("
            SELECT prt.* 
            FROM password_reset_tokens prt
            JOIN patients p ON prt.user_id = p.id
            WHERE p.email = ? 
            AND prt.token = ? 
            AND prt.user_type = 'patient'
            AND prt.expires_at > NOW()
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
        // Get patient ID from email
        $stmt = $conn->prepare("SELECT id FROM patients WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $patient_id = $result->fetch_assoc()['id'];
            
            // Hash the new password
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password
            $stmt = $conn->prepare("UPDATE patients SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $password_hash, $patient_id);
            
            if ($stmt->execute()) {
                // Delete all used tokens for this patient
                $stmt = $conn->prepare("DELETE FROM password_reset_tokens WHERE user_id = ? AND user_type = 'patient'");
                $stmt->bind_param("i", $patient_id);
                $stmt->execute();
                
                $message = 'Your password has been reset successfully. You can now login with your new password.';
                $message_type = 'success';
                // Redirect to login page after 3 seconds
                header("refresh:3;url=../patient/login.php");
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
    <title>Reset Password - PawPoint</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .password-container {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
        }
        
        .form-container {
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
        
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
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
            width: 100%;
        }
        
        .btn-primary:hover {
            background-color: #3c6547;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        small {
            color: #666;
            font-size: 0.85em;
            margin-top: 5px;
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>PawPoint Veterinary Care</h1>
            <h2>Reset Password</h2>
        </header>
        
        <main>
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
                            <div class="password-container">
                                <input type="password" id="new_password" name="new_password" required minlength="8">
                                <i class="password-toggle fas fa-eye" onclick="togglePassword('new_password', this)"></i>
                            </div>
                            <small>Password must be at least 8 characters long</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm Password</label>
                            <div class="password-container">
                                <input type="password" id="confirm_password" name="confirm_password" required>
                                <i class="password-toggle fas fa-eye" onclick="togglePassword('confirm_password', this)"></i>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Reset Password</button>
                        </div>
                    </form>
                <?php elseif ($message_type === 'success'): ?>
                    <div class="form-links">
                        <a href="login.php" class="btn btn-primary">Go to Login</a>
                    </div>
                <?php else: ?>
                    <div class="form-links">
                        <a href="forgot-password.php" class="btn btn-primary">Request a new password reset</a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
        
        <footer>
            <p>&copy; <?php echo date('Y'); ?> PawPoint Veterinary Care. All rights reserved.</p>
        </footer>
    </div>

    <script>
        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>