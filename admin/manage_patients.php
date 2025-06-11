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

// Process delete operation if confirmed
if(isset($_GET["action"]) && $_GET["action"] == "delete" && isset($_GET["id"]) && !empty($_GET["id"])){
    // Get hidden input value
    $id = $_GET["id"];
    
    // Delete the patient
    $sql = "DELETE FROM patients WHERE id = ?";
    
    if($stmt = mysqli_prepare($conn, $sql)){
        // Bind variables to the prepared statement as parameters
        mysqli_stmt_bind_param($stmt, "i", $param_id);
        
        // Set parameters
        $param_id = $id;
        
        // Attempt to execute the prepared statement
        if(mysqli_stmt_execute($stmt)){
            // Record deleted successfully. Redirect to this page
            header("location: manage_patients.php?msg=deleted");
            exit();
        } else{
            echo "Oops! Something went wrong. Please try again later.";
        }
        
        // Close statement
        mysqli_stmt_close($stmt);
    }
}

// Check for success message
$success_msg = "";
if(isset($_GET["msg"])){
    if($_GET["msg"] == "added"){
        $success_msg = "Patient added successfully.";
    } elseif($_GET["msg"] == "updated"){
        $success_msg = "Patient updated successfully.";
    } elseif($_GET["msg"] == "deleted"){
        $success_msg = "Patient deleted successfully.";
    }
}

// Fetch all patients
$patients = array();
$sql = "SELECT id, name, age, email FROM patients ORDER BY name";
$result = mysqli_query($conn, $sql);

if($result && mysqli_num_rows($result) > 0){
    while($row = mysqli_fetch_assoc($result)){
        $patients[] = $row;
    }
}
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Patients - PawPoint Admin</title>
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
        .admin-table {
            border-collapse: collapse;
            margin: 25px 0;
            width: 100%;
        }
        .admin-table th {
            background-color: #2C3E50;
            color: white;
            padding: 12px 15px;
            text-align: left;
        }
        .admin-table td {
            border-bottom: 1px solid #ddd;
            padding: 12px 15px;
        }
        .admin-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .admin-table tr:hover {
            background-color: #ddd;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        .action-btn {
            color: white;
            display: inline-block;
            margin-right: 5px;
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 3px;
            font-size: 0.8rem;
        }
        .edit-btn {
            background-color: #3498db;
        }
        .btn-info {
            background-color: #17a2b8;
        }
        .delete-btn {
            background-color: #e74c3c;
        }
        .add-btn {
            background-color: #2ecc71;
            color: white;
            margin-bottom: 20px;
            padding: 10px 15px;
            text-decoration: none;
            display: inline-block;
            border-radius: 4px;
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
        <h2>Manage Patients</h2>
        
        <?php 
        if(!empty($success_msg)){
            echo '<div class="alert alert-success">' . $success_msg . '</div>';
        }
        ?>
        
        <a href="add_patient.php" class="add-btn">Add New Patient</a>
        
        <?php if(count($patients) > 0): ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Age</th>
                        <th>Email</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($patients as $patient): ?>
                        <tr>
                            <td><?php echo $patient['id']; ?></td>
                            <td><?php echo htmlspecialchars($patient['name']); ?></td>
                            <td><?php echo $patient['age']; ?></td>
                            <td><?php echo htmlspecialchars($patient['email']); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="view_patient.php?id=<?php echo $patient['id']; ?>" class="action-btn btn-info">View</a>
                                    <a href="edit_patient.php?id=<?php echo $patient['id']; ?>" class="action-btn edit-btn">Edit</a>
                                    <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $patient['id']; ?>, '<?php echo addslashes(htmlspecialchars($patient['name'])); ?>')" class="action-btn delete-btn">Delete</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No patients found in the database.</p>
        <?php endif; ?>
    </div>
    
    <footer>
        <p>&copy; <?php echo date("Y"); ?> PawPoint. All rights reserved.</p>
    </footer>
    
    <script>
        function confirmDelete(id, name) {
            if(confirm("Are you sure you want to delete patient: " + name + "?")) {
                window.location.href = "manage_patients.php?action=delete&id=" + id;
            }
        }
    </script>
</body>
</html>