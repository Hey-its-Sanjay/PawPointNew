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
$name = $age = $address = $speciality = $email = "";
$name_err = $age_err = $address_err = $speciality_err = $email_err = "";
$success_msg = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Get hidden input value
    $id = trim($_POST["id"]);
    
    // Validate name
    if(empty(trim($_POST["name"]))){
        $name_err = "Please enter the doctor's name.";
    } else {
        $name = trim($_POST["name"]);
    }
    
    // Validate age
    if(empty(trim($_POST["age"]))){
        $age_err = "Please enter age.";
    } elseif(!is_numeric($_POST["age"]) || $_POST["age"] < 18) {
        $age_err = "Please enter a valid age (must be at least 18).";
    } else {
        $age = trim($_POST["age"]);
    }
    
    // Validate address
    if(empty(trim($_POST["address"]))){
        $address_err = "Please enter an address.";
    } else {
        $address = trim($_POST["address"]);
    }
    
    // Validate speciality
    if(empty(trim($_POST["speciality"]))){
        $speciality_err = "Please enter a speciality.";
    } else {
        $speciality = trim($_POST["speciality"]);
    }
    
    // Validate email
    if(empty(trim($_POST["email"]))){
        $email_err = "Please enter an email.";
    } else {
        $email = trim($_POST["email"]);
        
        // Check if email format is valid
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email_err = "Please enter a valid email address.";
        } else {
            // Check if email is already registered to another doctor
            $sql = "SELECT id FROM doctors WHERE email = ? AND id != ?";
            
            if($stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($stmt, "si", $param_email, $param_id);
                $param_email = $email;
                $param_id = $id;
                
                if(mysqli_stmt_execute($stmt)){
                    mysqli_stmt_store_result($stmt);
                    
                    if(mysqli_stmt_num_rows($stmt) > 0){
                        $email_err = "This email is already registered to another doctor.";
                    }
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
    
    // Check input errors before updating the database
    if(empty($name_err) && empty($age_err) && empty($address_err) && empty($speciality_err) && empty($email_err)){
        // Prepare an update statement
        $sql = "UPDATE doctors SET name=?, age=?, address=?, speciality=?, email=? WHERE id=?";
         
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "sisssi", $param_name, $param_age, $param_address, $param_speciality, $param_email, $param_id);
            
            $param_name = $name;
            $param_age = $age;
            $param_address = $address;
            $param_speciality = $speciality;
            $param_email = $email;
            $param_id = $id;
            
            if(mysqli_stmt_execute($stmt)){
                header("location: manage_doctors.php?msg=updated");
                exit();
            }
            mysqli_stmt_close($stmt);
        }
    }
} else {
    // Check if id parameter is present
    if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){
        $id = trim($_GET["id"]);
        
        // Get doctor data
        $sql = "SELECT * FROM doctors WHERE id = ?";
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "i", $param_id);
            $param_id = $id;
            
            if(mysqli_stmt_execute($stmt)){
                $result = mysqli_stmt_get_result($stmt);
                
                if(mysqli_num_rows($result) == 1){
                    $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
                    
                    $name = $row["name"];
                    $age = $row["age"];
                    $address = $row["address"];
                    $speciality = $row["speciality"];
                    $email = $row["email"];
                } else {
                    header("location: error.php");
                    exit();
                }
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        header("location: error.php");
        exit();
    }
}

// Close connection
mysqli_close($conn);
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Doctor - PawPoint Admin</title>
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
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
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
            display: inline-block;
        }
        .invalid-feedback {
            color: #e74c3c;
            display: block;
            margin-top: 5px;
        }
        .is-invalid {
            border-color: #e74c3c !important;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
        }
    </style>
</head>
<body>
    <?php include "header.php"; ?>
    
    <div class="container">
        <h2>Edit Doctor</h2>
        <p>Please edit the input values and submit to update the doctor information.</p>
        
        <form action="edit_doctor.php?id=<?php echo $id; ?>" method="post">
            <div class="form-group">
                <label>Name</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" class="form-control <?php echo (!empty($name_err)) ? 'is-invalid' : ''; ?>">
                <span class="invalid-feedback"><?php echo $name_err; ?></span>
            </div>    
            
            <div class="form-group">
                <label>Age</label>
                <input type="number" name="age" value="<?php echo htmlspecialchars($age); ?>" class="form-control <?php echo (!empty($age_err)) ? 'is-invalid' : ''; ?>">
                <span class="invalid-feedback"><?php echo $age_err; ?></span>
            </div>
            
            <div class="form-group">
                <label>Address</label>
                <textarea name="address" class="form-control <?php echo (!empty($address_err)) ? 'is-invalid' : ''; ?>"><?php echo htmlspecialchars($address); ?></textarea>
                <span class="invalid-feedback"><?php echo $address_err; ?></span>
            </div>
            
            <div class="form-group">
                <label>Speciality</label>
                <input type="text" name="speciality" value="<?php echo htmlspecialchars($speciality); ?>" class="form-control <?php echo (!empty($speciality_err)) ? 'is-invalid' : ''; ?>">
                <span class="invalid-feedback"><?php echo $speciality_err; ?></span>
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>">
                <span class="invalid-feedback"><?php echo $email_err; ?></span>
            </div>
            
            <input type="hidden" name="id" value="<?php echo $id; ?>"/>
            <div class="form-group">
                <a href="manage_doctors.php" class="back-btn">Cancel</a>
                <input type="submit" class="admin-btn" value="Update Doctor">
            </div>
        </form>
    </div>
    
    <?php include "footer.php"; ?>
</body>
</html>