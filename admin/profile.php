<?php
// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["admin_id"])){
    header("location: login.php");
    exit;
}

require_once "../includes/functions.php";

// Define variables and initialize with empty values
$new_username = $new_email = $current_password = $new_password = $confirm_password = "";
$username_err = $email_err = $current_password_err = $new_password_err = $confirm_password_err = "";
$success_msg = $error_msg = "";

// Get current admin data
$sql = "SELECT username, email FROM admins WHERE id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $_SESSION["admin_id"]);
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        if($row = mysqli_fetch_array($result)){
            $new_username = $row['username'];
            $new_email = $row['email'];
        }
    }
    mysqli_stmt_close($stmt);
}

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    
    if(isset($_POST["update_profile"])) {
        // Validate username
        if(empty(trim($_POST["username"]))){
            $username_err = "Please enter a username.";
        } else {
            // Check if username is already taken
            $sql = "SELECT id FROM admins WHERE username = ? AND id != ?";
            if($stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($stmt, "si", $_POST["username"], $_SESSION["admin_id"]);
                if(mysqli_stmt_execute($stmt)){
                    $result = mysqli_stmt_get_result($stmt);
                    if(mysqli_num_rows($result) > 0){
                        $username_err = "This username is already taken.";
                    } else {
                        $new_username = trim($_POST["username"]);
                    }
                }
                mysqli_stmt_close($stmt);
            }
        }
        
        // Validate email
        if(empty(trim($_POST["email"]))){
            $email_err = "Please enter an email.";
        } else {
            $new_email = trim($_POST["email"]);
        }
        
        // Check input errors before updating the database
        if(empty($username_err) && empty($email_err)){
            // Prepare an update statement
            $sql = "UPDATE admins SET username = ?, email = ? WHERE id = ?";
            
            if($stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($stmt, "ssi", $new_username, $new_email, $_SESSION["admin_id"]);
                if(mysqli_stmt_execute($stmt)){
                    $success_msg = "Profile updated successfully.";
                    $_SESSION["username"] = $new_username;
                } else{
                    $error_msg = "Something went wrong. Please try again later.";
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
    
    if(isset($_POST["change_password"])) {
        // Validate current password
        if(empty(trim($_POST["current_password"]))){
            $current_password_err = "Please enter your current password.";
        } else {
            $current_password = trim($_POST["current_password"]);
            // Verify current password
            $sql = "SELECT password FROM admins WHERE id = ?";
            if($stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($stmt, "i", $_SESSION["admin_id"]);
                if(mysqli_stmt_execute($stmt)){
                    $result = mysqli_stmt_get_result($stmt);
                    if($row = mysqli_fetch_array($result)){
                        if(!password_verify($current_password, $row['password'])){
                            $current_password_err = "Current password is incorrect.";
                        }
                    }
                }
                mysqli_stmt_close($stmt);
            }
        }
        
        // Validate new password
        if(empty(trim($_POST["new_password"]))){
            $new_password_err = "Please enter the new password.";     
        } elseif(strlen(trim($_POST["new_password"])) < 6){
            $new_password_err = "Password must have at least 6 characters.";
        } else{
            $new_password = trim($_POST["new_password"]);
        }
        
        // Validate confirm password
        if(empty(trim($_POST["confirm_password"]))){
            $confirm_password_err = "Please confirm the password.";     
        } else{
            $confirm_password = trim($_POST["confirm_password"]);
            if(empty($new_password_err) && ($new_password != $confirm_password)){
                $confirm_password_err = "Password did not match.";
            }
        }
        
        // Check input errors before updating the database
        if(empty($current_password_err) && empty($new_password_err) && empty($confirm_password_err)){
            // Prepare an update statement
            $sql = "UPDATE admins SET password = ? WHERE id = ?";
            
            if($stmt = mysqli_prepare($conn, $sql)){
                $param_password = password_hash($new_password, PASSWORD_DEFAULT);
                mysqli_stmt_bind_param($stmt, "si", $param_password, $_SESSION["admin_id"]);
                if(mysqli_stmt_execute($stmt)){
                    $success_msg = "Password updated successfully.";
                } else{
                    $error_msg = "Something went wrong. Please try again later.";
                }
                mysqli_stmt_close($stmt);
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
    <title>Admin Profile - PawPoint</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .admin-header {
            background-color: #34495E;
        }
        .admin-nav {
            background-color: #2C3E50;
        }
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
        }
        .profile-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .section-title {
            color: #2C3E50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498DB;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .form-control.is-invalid {
            border-color: #e74c3c;
        }
        .invalid-feedback {
            color: #e74c3c;
            font-size: 0.85em;
            margin-top: 5px;
        }
        .success-message {
            color: #27ae60;
            background-color: #dff0d8;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .error-message {
            color: #e74c3c;
            background-color: #f2dede;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .btn-primary {
            background-color: #3498db;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-primary:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <?php include "header.php"; ?>
    
    <div class="container">
        <?php if(!empty($success_msg)): ?>
            <div class="success-message"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        
        <?php if(!empty($error_msg)): ?>
            <div class="error-message"><?php echo $error_msg; ?></div>
        <?php endif; ?>
        
        <div class="profile-section">
            <h2 class="section-title">Update Profile</h2>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $new_username; ?>">
                    <span class="invalid-feedback"><?php echo $username_err; ?></span>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $new_email; ?>">
                    <span class="invalid-feedback"><?php echo $email_err; ?></span>
                </div>
                
                <div class="form-group">
                    <input type="submit" name="update_profile" class="btn-primary" value="Update Profile">
                </div>
            </form>
        </div>
        
        <div class="profile-section">
            <h2 class="section-title">Change Password</h2>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" class="form-control <?php echo (!empty($current_password_err)) ? 'is-invalid' : ''; ?>">
                    <span class="invalid-feedback"><?php echo $current_password_err; ?></span>
                </div>
                
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" class="form-control <?php echo (!empty($new_password_err)) ? 'is-invalid' : ''; ?>">
                    <span class="invalid-feedback"><?php echo $new_password_err; ?></span>
                </div>
                
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>">
                    <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
                </div>
                
                <div class="form-group">
                    <input type="submit" name="change_password" class="btn-primary" value="Change Password">
                </div>
            </form>
        </div>
    </div>
</body>
</html>
