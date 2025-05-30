<?php
// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["admin_id"])){
    header("location: login.php");
    exit;
}

// Include config file
require_once "../includes/functions.php";

// Define variables and initialize with empty values
$name = $age = $address = $speciality = $email = $password = $confirm_password = "";
$name_err = $age_err = $address_err = $speciality_err = $email_err = $password_err = $confirm_password_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    
    // Validate name
    if(empty(trim($_POST["name"]))){
        $name_err = "Please enter the doctor's name.";
    } else {
        $name = sanitize_input($_POST["name"]);
    }
    
    // Validate age
    if(empty(trim($_POST["age"]))){
        $age_err = "Please enter age.";
    } elseif(!is_numeric($_POST["age"]) || $_POST["age"] < 18) {
        $age_err = "Please enter a valid age (must be at least 18).";
    } else {
        $age = sanitize_input($_POST["age"]);
    }
    
    // Validate address
    if(empty(trim($_POST["address"]))){
        $address_err = "Please enter an address.";
    } else {
        $address = sanitize_input($_POST["address"]);
    }
    
    // Validate speciality
    if(empty(trim($_POST["speciality"]))){
        $speciality_err = "Please enter a speciality.";
    } else {
        $speciality = sanitize_input($_POST["speciality"]);
    }
    
    // Validate email
    if(empty(trim($_POST["email"]))){
        $email_err = "Please enter an email.";
    } else {
        $email = sanitize_input($_POST["email"]);
        
        // Check if email format is valid
        if(!validate_email($email)) {
            $email_err = "Please enter a valid email address.";
        } else {
            // Check if email is already registered
            if(check_email_exists($email, "doctors")) {
                $email_err = "This email is already registered.";
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
    if(empty($name_err) && empty($age_err) && empty($address_err) && empty($speciality_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err)){
        
        // Prepare an insert statement
        $sql = "INSERT INTO doctors (name, age, address, speciality, email, password) VALUES (?, ?, ?, ?, ?, ?)";
         
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "sissss", $param_name, $param_age, $param_address, $param_speciality, $param_email, $param_password);
            
            // Set parameters
            $param_name = $name;
            $param_age = $age;
            $param_address = $address;
            $param_speciality = $speciality;
            $param_email = $email;
            $param_password = hash_password($password);
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Redirect to manage doctors page with success message
                header("location: manage_doctors.php?msg=added");
                exit();
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
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
    <title>Add New Doctor - PawPoint Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .admin-header {
            background-color: #34495E;
        }
        .admin-nav {
            background-color: #2C3E50;
        }
        .admin-btn {
            background-color: #2C3E50;
        }
        .admin-btn:hover {
            background-color: #1A252F;
        }
        .back-btn {
            background-color: #7f8c8d;
            color: white;
            margin-right: 10px;
            padding: 10px 20px;
            text-decoration: none;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .invalid-feedback {
            color: #e74c3c;
            display: block;
            margin-top: 5px;
        }
        .is-invalid {
            border-color: #e74c3c !important;
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <h1>PawPoint Admin</h1>
        <p>Administration Portal</p>
    </header>
    
    <nav class="admin-nav">
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="manage_doctors.php">Manage Doctors</a></li>
            <li><a href="manage_patients.php">Manage Patients</a></li>
            <li><a href="settings.php">Settings</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>
    
    <div class="container">
        <h2>Add New Doctor</h2>
        <p>Please fill this form and submit to add a new doctor to the database.</p>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Name</label>
                <input type="text" name="name" value="<?php echo $name; ?>" class="<?php echo (!empty($name_err)) ? 'is-invalid' : ''; ?>">
                <span class="invalid-feedback"><?php echo $name_err; ?></span>
            </div>    
            
            <div class="form-group">
                <label>Age</label>
                <input type="number" name="age" value="<?php echo $age; ?>" class="<?php echo (!empty($age_err)) ? 'is-invalid' : ''; ?>">
                <span class="invalid-feedback"><?php echo $age_err; ?></span>
            </div>
            
            <div class="form-group">
                <label>Address</label>
                <textarea name="address" class="<?php echo (!empty($address_err)) ? 'is-invalid' : ''; ?>"><?php echo $address; ?></textarea>
                <span class="invalid-feedback"><?php echo $address_err; ?></span>
            </div>
            
            <div class="form-group">
                <label>Speciality</label>
                <input type="text" name="speciality" value="<?php echo $speciality; ?>" class="<?php echo (!empty($speciality_err)) ? 'is-invalid' : ''; ?>">
                <span class="invalid-feedback"><?php echo $speciality_err; ?></span>
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
                <a href="manage_doctors.php" class="back-btn">Cancel</a>
                <input type="submit" class="btn btn-primary admin-btn" value="Add Doctor">
            </div>
        </form>
    </div>
    
    <footer>
        <p>&copy; <?php echo date("Y"); ?> PawPoint. All rights reserved.</p>
    </footer>
</body>
</html> 