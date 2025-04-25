<?php
// Include the necessary files
require_once "../includes/functions.php";

// Define the missing function
function save_verification_email_local($to_email, $name, $token) {
    // Create emails directory if it doesn't exist
    $email_dir = dirname(__FILE__) . '/../emails/';
    if (!file_exists($email_dir)) {
        mkdir($email_dir, 0777, true);
    }
    
    // Use direct IP for verification link to work better on mobile
    $ip_address = "192.168.1.88";
    $protocol = "http";
    $verification_link = $protocol . "://" . $ip_address . "/Vetcare/pawpoint/patient/verify_email.php?email=" . urlencode($to_email) . "&token=" . $token;
    
    // Create message
    $message = "
    <html>
    <head>
        <title>Verify Your Email</title>
    </head>
    <body>
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background-color: #3498DB; color: white; padding: 20px; text-align: center;'>
                <h1>PawPoint Veterinary Care</h1>
            </div>
            <div style='padding: 20px; border: 1px solid #ddd; background-color: #f9f9f9;'>
                <h2>Hello, $name!</h2>
                <p>Thank you for registering with PawPoint Veterinary Care. To complete your registration, please verify your email address by clicking the button below:</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='$verification_link' style='background-color: #3498DB; color: white; padding: 12px 25px; text-decoration: none; border-radius: 4px; font-weight: bold;'>Verify Email</a>
                </div>
                <p>Or copy and paste the following link into your browser:</p>
                <p><a href='$verification_link'>$verification_link</a></p>
                <p>This link will expire in 24 hours.</p>
                <p>If you did not sign up for a PawPoint account, please ignore this email.</p>
                <p>Thank you,<br>The PawPoint Team</p>
            </div>
            <div style='text-align: center; padding: 10px; color: #777; font-size: 12px;'>
                <p>&copy; " . date('Y') . " PawPoint Veterinary Care. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Save email to file
    $filename = $email_dir . 'verification_' . time() . '_' . md5($to_email) . '.html';
    file_put_contents($filename, $message);
    
    // Log message
    error_log("Verification email for $to_email saved to $filename");
    
    return true;
}

// Initialize variables
$name = $age = $address = $pet_name = $pet_type = $email = $password = $confirm_password = "";
$name_err = $age_err = $address_err = $pet_name_err = $pet_type_err = $email_err = $password_err = $confirm_password_err = "";
$success_message = "";
$email_setup_instructions = "";

// Process form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    
    // Validate name
    if(empty(trim($_POST["name"]))){
        $name_err = "Please enter your full name.";
    } else {
        $name = sanitize_input($_POST["name"]);
    }
    
    // Validate age
    if(empty(trim($_POST["age"]))){
        $age_err = "Please enter your age.";
    } elseif(!is_numeric($_POST["age"]) || $_POST["age"] <= 0) {
        $age_err = "Please enter a valid age.";
    } else {
        $age = sanitize_input($_POST["age"]);
    }
    
    // Validate address
    if(empty(trim($_POST["address"]))){
        $address_err = "Please enter your address.";
    } else {
        $address = sanitize_input($_POST["address"]);
    }
    
    // Validate pet name
    if(empty(trim($_POST["pet_name"]))){
        $pet_name_err = "Please enter your pet's name.";
    } else {
        $pet_name = sanitize_input($_POST["pet_name"]);
    }
    
    // Validate pet type
    if(empty(trim($_POST["pet_type"]))){
        $pet_type_err = "Please enter your pet's type (dog, cat, etc).";
    } else {
        $pet_type = sanitize_input($_POST["pet_type"]);
    }
    
    // Validate email
    if(empty(trim($_POST["email"]))){
        $email_err = "Please enter your email.";
    } else {
        $email = sanitize_input($_POST["email"]);
        
        // Check if email format is valid
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email_err = "Please enter a valid email address.";
        } else {
            // Check if email is already registered
            $sql = "SELECT id FROM patients WHERE email = ?";
            
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "s", $param_email);
                $param_email = $email;
                
                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_store_result($stmt);
                    
                    if (mysqli_stmt_num_rows($stmt) > 0) {
                        $email_err = "This email is already registered.";
                    }
                } else {
                    $general_err = "Oops! Something went wrong. Please try again later.";
                }
                
                mysqli_stmt_close($stmt);
            }
        }
    }
    
    // Validate password
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter a password.";     
    } elseif(strlen(trim($_POST["password"])) < 6){
        $password_err = "Password must have at least 6 characters.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    // Validate confirm password
    if(empty(trim($_POST["confirm_password"]))){
        $confirm_password_err = "Please confirm password.";     
    } else{
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($password_err) && ($password != $confirm_password)){
            $confirm_password_err = "Password did not match.";
        }
    }
    
    // Check input errors before inserting into database
    if(empty($name_err) && empty($age_err) && empty($address_err) && empty($pet_name_err) && empty($pet_type_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err) && empty($general_err)){
        
        // First check if pet columns exist
        $check_pet_columns = "SHOW COLUMNS FROM patients LIKE 'pet_name'";
        $result = mysqli_query($conn, $check_pet_columns);
        
        if(mysqli_num_rows($result) == 0) {
            // Pet columns don't exist, need to add them first
            $add_pet_columns = "ALTER TABLE patients 
                              ADD COLUMN pet_name VARCHAR(100) DEFAULT 'Not specified',
                              ADD COLUMN pet_type VARCHAR(100) DEFAULT 'Not specified'";
            mysqli_query($conn, $add_pet_columns);
        }
        
        // Check if email verification columns exist
        $check_email_verified = "SHOW COLUMNS FROM patients LIKE 'email_verified'";
        $result = mysqli_query($conn, $check_email_verified);
        
        if(mysqli_num_rows($result) == 0) {
            // Email verification columns don't exist, need to add them first
            $add_verification_columns = "ALTER TABLE patients 
                                       ADD COLUMN email_verified TINYINT(1) DEFAULT 0,
                                       ADD COLUMN verification_token VARCHAR(255) DEFAULT NULL,
                                       ADD COLUMN token_expiry DATETIME DEFAULT NULL";
            mysqli_query($conn, $add_verification_columns);
        }
        
        // Generate a verification token
        $token = generate_token();
        $token_expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        // Prepare an insert statement with all fields
        $sql = "INSERT INTO patients (name, age, address, pet_name, pet_type, email, password, verification_token, token_expiry) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
         
        if($stmt = mysqli_prepare($conn, $sql)){
            // Set parameters first to avoid null values
            $param_name = $name;
            $param_age = $age;
            $param_address = $address;
            $param_pet_name = $pet_name;
            $param_pet_type = $pet_type;
            $param_email = $email;
            $param_password = password_hash($password, PASSWORD_DEFAULT);
            $param_token = $token;
            $param_token_expiry = $token_expiry;
            
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "sisssssss", $param_name, $param_age, $param_address, $param_pet_name, $param_pet_type, $param_email, $param_password, $param_token, $param_token_expiry);
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Get the ID of the newly inserted patient
                $patient_id = mysqli_insert_id($conn);
                
                // Send verification email
                if(send_verification_email($email, $token)) {
                    // Show success message
                    $success_message = "Registration successful! Please check your email to verify your account.";
                    
                    // Clear form data
                    $name = $age = $address = $pet_name = $pet_type = $email = $password = $confirm_password = "";
                } else {
                    // Email sending failed, but account was created
                    // Save email locally for testing
                    save_verification_email_local($email, $name, $token);
                    $success_message = "Your account has been created, but we couldn't send a verification email. Please contact support.";
                    
                    // Show PHPMailer setup instructions
                    if(function_exists('get_email_setup_instructions')) {
                        $email_setup_instructions = get_email_setup_instructions();
                    }
                }
            } else{
                echo "Oops! Something went wrong. Please try again later. Error: " . mysqli_error($conn);
            }

            // Close statement
            mysqli_stmt_close($stmt);
        } else {
            echo "Prepare statement error: " . mysqli_error($conn);
        }
    }
    
    // Close connection
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Registration - PawPoint</title>
    <link rel="stylesheet" href="../css/style.css">
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
        <div class="form-container">
            <h2>Pet Owner Registration</h2>
            <p>Please fill in your details to create a patient account.</p>
            <p class="alert alert-info">Note: After registration, you'll need to verify your email address before you can log in.</p>
            
            <?php 
            if(!empty($success_message)){
                echo '<div class="alert alert-success">' . $success_message . '</div>';
            }
            
            if(!empty($email_setup_instructions)){
                echo '<div class="alert alert-warning">' . $email_setup_instructions . '</div>';
            }
            ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" value="<?php echo $name; ?>" class="<?php echo (!empty($name_err)) ? 'is-invalid' : ''; ?>">
                    <span class="invalid-feedback"><?php echo $name_err; ?></span>
                </div>    
                
                <div class="form-group">
                    <label>Your Age</label>
                    <input type="number" name="age" value="<?php echo $age; ?>" class="<?php echo (!empty($age_err)) ? 'is-invalid' : ''; ?>">
                    <span class="invalid-feedback"><?php echo $age_err; ?></span>
                </div>
                
                <div class="form-group">
                    <label>Your Address</label>
                    <textarea name="address" class="<?php echo (!empty($address_err)) ? 'is-invalid' : ''; ?>"><?php echo $address; ?></textarea>
                    <span class="invalid-feedback"><?php echo $address_err; ?></span>
                </div>
                
                <div class="form-group">
                    <label>Pet's Name</label>
                    <input type="text" name="pet_name" value="<?php echo $pet_name; ?>" class="<?php echo (!empty($pet_name_err)) ? 'is-invalid' : ''; ?>">
                    <span class="invalid-feedback"><?php echo $pet_name_err; ?></span>
                </div>
                
                <div class="form-group">
                    <label>Pet Type (e.g., Dog, Cat)</label>
                    <input type="text" name="pet_type" value="<?php echo $pet_type; ?>" class="<?php echo (!empty($pet_type_err)) ? 'is-invalid' : ''; ?>">
                    <span class="invalid-feedback"><?php echo $pet_type_err; ?></span>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo $email; ?>" class="<?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>">
                    <span class="invalid-feedback"><?php echo $email_err; ?></span>
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="<?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                    <span class="invalid-feedback"><?php echo $password_err; ?></span>
                </div>
                
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" class="<?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>">
                    <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
                </div>
                
                <div class="form-group">
                    <input type="submit" class="btn btn-primary btn-block" value="Register">
                </div>
                
                <p>Already have an account? <a href="login.php">Login here</a>.</p>
            </form>
        </div>
    </div>
    
    <footer>
        <p>&copy; <?php echo date("Y"); ?> PawPoint. All rights reserved.</p>
    </footer>
</body>
</html> 