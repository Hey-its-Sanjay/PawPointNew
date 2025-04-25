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
            JOIN patients p ON prt.patient_id = p.id
            WHERE p.email = ? AND prt.token = ? AND prt.expires_at > NOW()
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
                // Delete used token
                $stmt = $conn->prepare("DELETE FROM password_reset_tokens WHERE patient_id = ?");
                $stmt->bind_param("i", $patient_id);
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
    <title>Reset Password - PawPoint</title>
    <link rel="stylesheet" href="../css/style.css">
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
                            <input type="password" id="new_password" name="new_password" required minlength="8">
                            <small>Password must be at least 8 characters long</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
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
                        <a href="forgot-password.php">Request a new password reset</a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
        
        <footer>
            <p>&copy; <?php echo date('Y'); ?> PawPoint Veterinary Care. All rights reserved.</p>
        </footer>
    </div>
</body>
</html> 