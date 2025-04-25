<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - PawPoint Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .admin-header {
            background-color: #34495E;
        }
        .admin-nav {
            background-color: #2C3E50;
        }
        .error-container {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            color: #721c24;
            margin: 20px 0;
            padding: 20px;
            text-align: center;
        }
        .back-btn {
            background-color: #2C3E50;
            border: none;
            border-radius: 4px;
            color: white;
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            text-decoration: none;
        }
        .back-btn:hover {
            background-color: #1A252F;
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
        <div class="error-container">
            <h2>Error!</h2>
            <p>Sorry, an error occurred while processing your request.</p>
            <p>Please go back and try again.</p>
            <a href="dashboard.php" class="back-btn">Return to Dashboard</a>
        </div>
    </div>
    
    <footer>
        <p>&copy; <?php echo date("Y"); ?> PawPoint. All rights reserved.</p>
    </footer>
</body>
</html> 