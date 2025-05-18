<?php
// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["patient_id"])){
    header("location: login.php");
    exit;
}

// Include config file
require_once "../includes/functions.php";

// Define variables and initialize with empty values
$name = $age = $address = $phone = $pet_name = $pet_type = $pet_details = "";
$name_err = $age_err = $address_err = $phone_err = $pet_name_err = $pet_type_err = $pet_details_err = $profile_picture_err = "";
$success_message = $error_message = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Check if it's the update profile form
    if(isset($_POST["update_profile"])) {
        
        // Validate name
        if(empty(trim($_POST["name"]))){
            $name_err = "Please enter your name.";
        } else {
            $name = trim($_POST["name"]);
        }
        
        // Validate age
        if(empty(trim($_POST["age"]))){
            $age_err = "Please enter your age.";
        } elseif(!is_numeric(trim($_POST["age"])) || intval(trim($_POST["age"])) < 1){
            $age_err = "Please enter a valid age.";
        } else {
            $age = trim($_POST["age"]);
        }
        
        // Validate address
        if(empty(trim($_POST["address"]))){
            $address_err = "Please enter your address.";
        } else {
            $address = trim($_POST["address"]);
        }
        
        // Validate phone (optional)
        if(!empty(trim($_POST["phone"]))) {
            // You can add more validation for phone if needed
            $phone = trim($_POST["phone"]);
        }
        
        // Validate pet name
        if(empty(trim($_POST["pet_name"]))){
            $pet_name_err = "Please enter your pet's name.";
        } else {
            $pet_name = trim($_POST["pet_name"]);
        }
        
        // Validate pet type
        if(empty(trim($_POST["pet_type"]))){
            $pet_type_err = "Please enter your pet's type (e.g. dog, cat).";
        } else {
            $pet_type = trim($_POST["pet_type"]);
        }
        
        // Pet details is optional
        $pet_details = trim($_POST["pet_details"]);
        
        // Check input errors before updating the database
        if(empty($name_err) && empty($age_err) && empty($address_err) && empty($phone_err) && 
           empty($pet_name_err) && empty($pet_type_err)) {
            
            // Prepare an update statement
            $sql = "UPDATE patients SET name = ?, age = ?, address = ?, phone = ?, pet_name = ?, pet_type = ?, pet_details = ? WHERE id = ?";
            
            if($stmt = mysqli_prepare($conn, $sql)){
                // Bind variables to the prepared statement as parameters
                mysqli_stmt_bind_param($stmt, "sisssssi", $name, $age, $address, $phone, $pet_name, $pet_type, $pet_details, $_SESSION["patient_id"]);
                
                // Attempt to execute the prepared statement
                if(mysqli_stmt_execute($stmt)){
                    $success_message = "Profile updated successfully!";
                } else{
                    $error_message = "Oops! Something went wrong. Please try again later.";
                }
                
                // Close statement
                mysqli_stmt_close($stmt);
            }
        }
    }
    // Check if it's the upload profile picture form
    elseif(isset($_POST["upload_picture"])) {
        
        // Check if a file was uploaded
        if(isset($_FILES["profile_picture"]) && $_FILES["profile_picture"]["error"] == 0) {
            
            $allowed_types = ["image/jpeg", "image/jpg", "image/png", "image/gif"];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            // Validate file type
            if(!in_array($_FILES["profile_picture"]["type"], $allowed_types)) {
                $profile_picture_err = "Only JPG, JPEG, PNG, and GIF files are allowed.";
            }
            // Validate file size
            elseif($_FILES["profile_picture"]["size"] > $max_size) {
                $profile_picture_err = "File size should not exceed 5MB.";
            }
            else {
                // Create uploads directory if it doesn't exist
                $upload_dir = "../uploads/profile_pictures/";
                if(!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Generate a unique filename
                $file_extension = pathinfo($_FILES["profile_picture"]["name"], PATHINFO_EXTENSION);
                $filename = "patient_" . $_SESSION["patient_id"] . "_" . time() . "." . $file_extension;
                $target_file = $upload_dir . $filename;
                
                // Upload the file
                if(move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
                    
                    // Update the database with the new profile picture
                    $sql = "UPDATE patients SET profile_picture = ? WHERE id = ?";
                    
                    if($stmt = mysqli_prepare($conn, $sql)) {
                        mysqli_stmt_bind_param($stmt, "si", $filename, $_SESSION["patient_id"]);
                        
                        if(mysqli_stmt_execute($stmt)) {
                            $success_message = "Profile picture updated successfully!";
                        } else {
                            $error_message = "Error updating profile picture in the database.";
                        }
                        
                        mysqli_stmt_close($stmt);
                    }
                } else {
                    $profile_picture_err = "Failed to upload the file.";
                }
            }
        } elseif($_FILES["profile_picture"]["error"] != 4) { // Error 4 means no file was uploaded
            $profile_picture_err = "Error uploading file: " . $_FILES["profile_picture"]["error"];
        } else {
            $profile_picture_err = "Please select a file to upload.";
        }
    }
}

// Fetch the current patient data
$sql = "SELECT * FROM patients WHERE id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $_SESSION["patient_id"]);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) == 1){
            $row = mysqli_fetch_assoc($result);
            
            // Set the variables with the current data if form wasn't submitted
            if($_SERVER["REQUEST_METHOD"] != "POST" || isset($_POST["upload_picture"])) {
                $name = $row["name"];
                $age = $row["age"];
                $address = $row["address"];
                $phone = $row["phone"];
                $pet_name = $row["pet_name"];
                $pet_type = $row["pet_type"];
                $pet_details = $row["pet_details"];
            }
            
            // Get profile picture
            $profile_picture = $row["profile_picture"];
            if(empty($profile_picture) || !file_exists("../uploads/profile_pictures/" . $profile_picture)) {
                $profile_picture = "default.jpg";
            }
        } else {
            // Redirect to login page if patient not found
            header("location: login.php");
            exit();
        }
    } else {
        echo "Oops! Something went wrong. Please try again later.";
    }
    
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - PawPoint</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .profile-container {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
        }
        
        .profile-sidebar {
            flex: 1;
            min-width: 250px;
            max-width: 300px;
        }
        
        .profile-content {
            flex: 3;
            min-width: 300px;
        }
        
        .profile-picture-container {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .profile-picture {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid #4a7c59;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        
        .btn-update {
            background-color: #4a7c59;
        }
        
        .btn-update:hover {
            background-color: #3e6b4a;
        }
        
        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
    </style>
</head>
<body>
    <header>
        <h1>PawPoint</h1>
        <p>Your Pet's Healthcare Companion</p>
    </header>
    
    <nav>
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="profile.php" class="active">My Profile</a></li>
            <li><a href="appointments.php">My Appointments</a></li>
            <li><a href="book_appointment.php">Book Appointment</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>
    
    <div class="container">
        <h2>My Profile</h2>
        
        <?php 
            if(!empty($success_message)){
                echo '<div class="alert alert-success">' . $success_message . '</div>';
            }
            if(!empty($error_message)){
                echo '<div class="alert alert-danger">' . $error_message . '</div>';
            }
        ?>
        
        <div class="profile-container">
            <div class="profile-sidebar">
                <div class="profile-picture-container">
                    <img src="../uploads/profile_pictures/<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile Picture" class="profile-picture">
                          <form action="/Vetcare/pawpoint/patient/profile.php" method="post" enctype="multipart/form-data" class="mt-3">
                        <div class="form-group">
                            <input type="file" name="profile_picture" class="form-control <?php echo (!empty($profile_picture_err)) ? 'is-invalid' : ''; ?>">
                            <span class="invalid-feedback"><?php echo $profile_picture_err; ?></span>
                        </div>
                        <div class="form-group">
                            <input type="submit" name="upload_picture" class="btn btn-update" value="Update Picture">
                        </div>
                    </form>
                </div>
            </div>
              <div class="profile-content">
                <form action="/Vetcare/pawpoint/patient/profile.php" method="post">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" class="form-control <?php echo (!empty($name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($name); ?>">
                        <span class="invalid-feedback"><?php echo $name_err; ?></span>
                    </div>
                    
                    <div class="form-group">
                        <label>Age</label>
                        <input type="number" name="age" class="form-control <?php echo (!empty($age_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($age); ?>">
                        <span class="invalid-feedback"><?php echo $age_err; ?></span>
                    </div>
                    
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" class="form-control <?php echo (!empty($address_err)) ? 'is-invalid' : ''; ?>"><?php echo htmlspecialchars($address); ?></textarea>
                        <span class="invalid-feedback"><?php echo $address_err; ?></span>
                    </div>
                    
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" name="phone" class="form-control <?php echo (!empty($phone_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($phone); ?>">
                        <span class="invalid-feedback"><?php echo $phone_err; ?></span>
                    </div>
                    
                    <div class="form-group">
                        <label>Pet Name</label>
                        <input type="text" name="pet_name" class="form-control <?php echo (!empty($pet_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($pet_name); ?>">
                        <span class="invalid-feedback"><?php echo $pet_name_err; ?></span>
                    </div>
                    
                    <div class="form-group">
                        <label>Pet Type</label>
                        <input type="text" name="pet_type" class="form-control <?php echo (!empty($pet_type_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($pet_type); ?>">
                        <span class="invalid-feedback"><?php echo $pet_type_err; ?></span>
                    </div>
                    
                    <div class="form-group">
                        <label>Pet Details (optional)</label>
                        <textarea name="pet_details" class="form-control <?php echo (!empty($pet_details_err)) ? 'is-invalid' : ''; ?>"><?php echo htmlspecialchars($pet_details); ?></textarea>
                        <span class="invalid-feedback"><?php echo $pet_details_err; ?></span>
                        <small class="form-text text-muted">Include any additional information about your pet like breed, color, medical history, etc.</small>
                    </div>
                    
                    <div class="form-group">
                        <input type="submit" name="update_profile" class="btn btn-update" value="Update Profile">
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <footer>
        <p>&copy; <?php echo date("Y"); ?> PawPoint. All rights reserved.</p>
    </footer>
</body>
</html> 