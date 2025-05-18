<?php
// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["doctor_id"])){
    header("location: login.php");
    exit;
}

// Include config file
require_once "D:/xampp/htdocs/Vetcare/pawpoint/includes/functions.php";

// Get doctor's patients (patients who have had appointments with this doctor)
$patients = [];
$sql = "SELECT DISTINCT p.*, 
        (SELECT COUNT(*) FROM appointments a2 WHERE a2.patient_id = p.id AND a2.doctor_id = ?) as visit_count,
        (SELECT MAX(appointment_date) FROM appointments a3 WHERE a3.patient_id = p.id AND a3.doctor_id = ?) as last_visit
        FROM patients p
        INNER JOIN appointments a ON p.id = a.patient_id
        WHERE a.doctor_id = ?
        ORDER BY last_visit DESC";

if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "iii", $_SESSION["doctor_id"], $_SESSION["doctor_id"], $_SESSION["doctor_id"]);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while($row = mysqli_fetch_assoc($result)) {
        $patients[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// Search functionality
$search_term = "";
if(isset($_GET["search"]) && !empty(trim($_GET["search"]))) {
    $search_term = trim($_GET["search"]);
    $filtered_patients = array_filter($patients, function($patient) use ($search_term) {
        return (stripos($patient["name"], $search_term) !== false) || 
               (stripos($patient["email"], $search_term) !== false) ||
               (stripos($patient["pet_name"], $search_term) !== false);
    });
    $patients = $filtered_patients;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Patients - PawPoint</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .search-box {
            margin-bottom: 30px;
        }

        .search-box input[type="text"] {
            width: 300px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-right: 10px;
        }

        .search-box button {
            padding: 10px 20px;
            background-color: #4a7c59;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .search-box button:hover {
            background-color: #3e5c47;
        }

        .patients-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .patient-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .patient-card h3 {
            color: #4a7c59;
            margin-top: 0;
        }

        .patient-info {
            margin: 10px 0;
            color: #666;
        }

        .patient-info strong {
            color: #333;
        }

        .pet-details {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .view-history-btn {
            display: inline-block;
            padding: 8px 15px;
            background-color: #4a7c59;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 10px;
            font-size: 0.9em;
        }

        .view-history-btn:hover {
            background-color: #3e5c47;
        }

        .visit-count {
            display: inline-block;
            padding: 3px 8px;
            background-color: #e0e0e0;
            border-radius: 12px;
            font-size: 0.8em;
            margin-left: 10px;
        }

        @media (max-width: 768px) {
            .patients-grid {
                grid-template-columns: 1fr;
            }
            
            .search-box input[type="text"] {
                width: 100%;
                margin-bottom: 10px;
            }
            
            .search-box button {
                width: 100%;
            }
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
            <li><a href="profile.php">My Profile</a></li>
            <li><a href="appointments.php">Appointments</a></li>
            <li><a href="patients.php">Patients</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <h2>My Patients</h2>
        
        <div class="search-box">
            <form action="" method="GET">
                <input type="text" name="search" placeholder="Search by patient name, email, or pet name..." 
                       value="<?= htmlspecialchars($search_term) ?>">
                <button type="submit">Search</button>
                <?php if(!empty($search_term)): ?>
                    <a href="patients.php" style="margin-left: 10px;">Clear Search</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if(empty($patients)): ?>
            <?php if(!empty($search_term)): ?>
                <p>No patients found matching your search.</p>
            <?php else: ?>
                <p>You haven't seen any patients yet.</p>
            <?php endif; ?>
        <?php else: ?>
            <div class="patients-grid">
                <?php foreach($patients as $patient): ?>
                    <div class="patient-card">
                        <h3><?= htmlspecialchars($patient['name']) ?>
                            <span class="visit-count"><?= $patient['visit_count'] ?> visits</span>
                        </h3>
                        <div class="patient-info">
                            <p><strong>Email:</strong> <?= htmlspecialchars($patient['email']) ?></p>
                            <p><strong>Phone:</strong> <?= htmlspecialchars($patient['phone']) ?></p>
                            <p><strong>Last Visit:</strong> <?= date('M d, Y', strtotime($patient['last_visit'])) ?></p>
                        </div>                        <div class="pet-details">
                            <p><strong>Pet Name:</strong> <?= htmlspecialchars($patient['pet_name']) ?></p>
                            <p><strong>Pet Type:</strong> <?= htmlspecialchars($patient['pet_type']) ?></p>
                        </div>
                        <a href="patient_history.php?id=<?= $patient['id'] ?>" class="view-history-btn">View History</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <footer>
        <p>&copy; <?= date("Y") ?> PawPoint. All rights reserved.</p>
    </footer>
</body>
</html>
