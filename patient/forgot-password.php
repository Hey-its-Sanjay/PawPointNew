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
        $stmt = $conn->prepare("SELECT id, name FROM patients WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            // Email not found, but don't reveal this for security
            $message = 'If your email is registered, you will receive password reset instructions shortly.';
            $message_type = 'success';
        } else {
            // Email exists, generate token and send reset email
            $patient = $result->fetch_assoc();
            $patient_id = $patient['id'];
            $patient_name = $patient['name'];
            
            $token = bin2hex(random_bytes(32)); // Generate secure random token
            $expires = date('Y-m-d H:i:s', time() + 3600); // Token expires in 1 hour
            
            // Delete any existing tokens for this patient
            $stmt = $conn->prepare("DELETE FROM password_reset_tokens WHERE user_id = ? AND user_type = 'patient'");
            $stmt->bind_param("i", $patient_id);
            $stmt->execute();
            
            // Insert new token
            $stmt = $conn->prepare("INSERT INTO password_reset_tokens (user_id, user_type, token, expires_at) VALUES (?, 'patient', ?, ?)");
            $stmt->bind_param("iss", $patient_id, $token, $expires);
            
            if ($stmt->execute()) {
                // Construct reset link using the helper function
                $reset_link = get_site_url() . "/patient/reset-password.php?email=" . urlencode($email) . "&token=" . $token;
                
                // Send email with reset link
                if (send_password_reset_email($email, $reset_link, 'patient', $patient_name)) {
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
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
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

        .form-container {
            text-align: center;
        }

        .form-container p {
            color: #666;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>PawPoint Veterinary Care</h1>
            <h2>Forgot Password</h2>
        </header>
        
        <main>
            <div class="password-reset-container">
                <div class="form-container">
                    <p>Please enter your email address to reset your password.</p>
                    
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo $message_type; ?>">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn-primary">Reset Password</button>
                        </div>
                    </form>
                    
                    <div class="links">
                        <p><a href="login.php">Back to Login</a></p>
                    </div>
                </div>
            </div>
        </main>
        
        <footer>
            <p>&copy; <?php echo date('Y'); ?> PawPoint Veterinary Care. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>