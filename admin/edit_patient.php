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
$name = $email = $address = $pet_name = $pet_type = "";
$name_err = $email_err = $address_err = $pet_name_err = $pet_type_err = "";
$success_message = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    $id = trim($_POST["id"]);
    
    // Validate name
    if(empty(trim($_POST["name"]))){
        $name_err = "Please enter patient's name.";
    } else {
        $name = trim($_POST["name"]);
    }
    
    // Validate email
    if(empty(trim($_POST["email"]))){
        $email_err = "Please enter an email.";
    } else {
        // Prepare a select statement to check if email exists for other patients
        $sql = "SELECT id FROM patients WHERE email = ? AND id != ?";
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "si", $param_email, $id);
            $param_email = trim($_POST["email"]);
            
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $email_err = "This email is already taken.";
                } else{
                    $email = trim($_POST["email"]);
                }
            } else{
                $error = "Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    // Validate address
    if(empty(trim($_POST["address"]))){
        $address_err = "Please enter address.";     
    } else{
        $address = trim($_POST["address"]);
    }
    
    // Validate pet information
    if(empty(trim($_POST["pet_name"]))){
        $pet_name_err = "Please enter pet's name.";     
    } else{
        $pet_name = trim($_POST["pet_name"]);
    }
    
    if(empty(trim($_POST["pet_type"]))){
        $pet_type_err = "Please enter pet type.";     
    } else{
        $pet_type = trim($_POST["pet_type"]);
    }
    
    // Check input errors before updating the database
    if(empty($name_err) && empty($email_err) && empty($address_err) && empty($pet_name_err) && empty($pet_type_err)){
        // Prepare an update statement
        $sql = "UPDATE patients SET name=?, email=?, address=?, pet_name=?, pet_type=? WHERE id=?";
         
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "sssssi", $param_name, $param_email, $param_address, $param_pet_name, $param_pet_type, $param_id);
            
            // Set parameters
            $param_name = $name;
            $param_email = $email;
            $param_address = $address;
            $param_pet_name = $pet_name;
            $param_pet_type = $pet_type;
            $param_id = $id;
            
            if(mysqli_stmt_execute($stmt)){
                $success_message = "Patient updated successfully.";
            } else{
                $error = "Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }
} else {
    // Get patient data if id parameter is present
    if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){
        $id = trim($_GET["id"]);
        
        $sql = "SELECT * FROM patients WHERE id = ?";
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "i", $param_id);
            $param_id = $id;
            
            if(mysqli_stmt_execute($stmt)){
                $result = mysqli_stmt_get_result($stmt);
                
                if(mysqli_num_rows($result) == 1){
                    $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
                    
                    $name = $row["name"];
                    $email = $row["email"];
                    $address = $row["address"];
                    $pet_name = $row["pet_name"];
                    $pet_type = $row["pet_type"];                } else{
                    header("location: manage_patients.php");
                    exit();
                }
            } else{
                $error = "Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    } else{        header("location: manage_patients.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Patient - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .wrapper { width: 500px; margin: 30px auto; padding: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-control { 
            display: block;
            width: 100%;
            padding: 8px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .invalid-feedback { color: #dc3545; font-size: 0.9em; }
        .btn-primary {
            display: inline-block;
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
        }
        .btn-back {
            display: inline-block;
            padding: 10px 20px;
            background-color: #95a5a6;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-right: 10px;
        }
        .alert-success {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            background: #d4edda;
            color: #155724;
        }
    </style>
</head>
<body>
    <?php include "header.php"; ?>
    
    <div class="wrapper">
        <h2>Edit Patient</h2>
        
        <?php if(!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <input type="hidden" name="id" value="<?php echo $id; ?>"/>
            <div class="form-group">
                <label>Name</label>
                <input type="text" name="name" class="form-control <?php echo (!empty($name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $name; ?>">
                <span class="invalid-feedback"><?php echo $name_err; ?></span>
            </div>    
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>">
                <span class="invalid-feedback"><?php echo $email_err; ?></span>
            </div>
            <div class="form-group">
                <label>Address</label>
                <textarea name="address" class="form-control <?php echo (!empty($address_err)) ? 'is-invalid' : ''; ?>"><?php echo $address; ?></textarea>
                <span class="invalid-feedback"><?php echo $address_err; ?></span>
            </div>
            <div class="form-group">
                <label>Pet Name</label>
                <input type="text" name="pet_name" class="form-control <?php echo (!empty($pet_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $pet_name; ?>">
                <span class="invalid-feedback"><?php echo $pet_name_err; ?></span>
            </div>
            <div class="form-group">
                <label>Pet Type</label>
                <input type="text" name="pet_type" class="form-control <?php echo (!empty($pet_type_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $pet_type; ?>">
                <span class="invalid-feedback"><?php echo $pet_type_err; ?></span>
            </div>            <div class="form-group">
                <a href="manage_patients.php" class="btn-back">Back to List</a>
                <input type="submit" class="btn-primary" value="Update Patient">
            </div>
        </form>
    </div>    
    
    <?php include "footer.php"; ?>
</body>
</html>
