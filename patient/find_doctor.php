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

// Initialize variables
$search_query = "";
$speciality_filter = "";
$doctors = [];

// Define available specialities
$specialities = [];
$speciality_sql = "SELECT DISTINCT speciality FROM doctors WHERE status = 'approved' ORDER BY speciality ASC";
$speciality_result = mysqli_query($conn, $speciality_sql);
if($speciality_result) {
    while($row = mysqli_fetch_assoc($speciality_result)) {
        $specialities[] = $row['speciality'];
    }
}

// Process search and filter
if($_SERVER["REQUEST_METHOD"] == "GET") {
    if(isset($_GET["search"]) && !empty(trim($_GET["search"]))) {
        $search_query = trim($_GET["search"]);
    }
    
    if(isset($_GET["speciality"]) && !empty(trim($_GET["speciality"]))) {
        $speciality_filter = trim($_GET["speciality"]);
    }
}

// Build the SQL query based on search and filter parameters
$sql = "SELECT id, name, age, speciality, profile_picture, bio, phone 
        FROM doctors 
        WHERE status = 'approved'";

if(!empty($search_query)) {
    $sql .= " AND (name LIKE ? OR speciality LIKE ? OR bio LIKE ?)";
}

if(!empty($speciality_filter)) {
    if(!empty($search_query)) {
        $sql .= " AND speciality = ?";
    } else {
        $sql .= " AND speciality = ?";
    }
}

$sql .= " ORDER BY name ASC";

// Prepare and execute the query
if($stmt = mysqli_prepare($conn, $sql)) {
    // Bind parameters based on search and filter conditions
    if(!empty($search_query) && !empty($speciality_filter)) {
        // Both search query and speciality filter
        $search_param = "%" . $search_query . "%";
        mysqli_stmt_bind_param($stmt, "ssss", $search_param, $search_param, $search_param, $speciality_filter);
    } elseif(!empty($search_query)) {
        // Only search query
        $search_param = "%" . $search_query . "%";
        mysqli_stmt_bind_param($stmt, "sss", $search_param, $search_param, $search_param);
    } elseif(!empty($speciality_filter)) {
        // Only speciality filter
        mysqli_stmt_bind_param($stmt, "s", $speciality_filter);
    }
    
    // Execute the query
    if(mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        
        // Fetch all doctors that match the criteria
        while($row = mysqli_fetch_assoc($result)) {
            $doctors[] = $row;
        }
    }
    
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find a Doctor - PawPoint</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .search-container {
            margin-bottom: 30px;
            padding: 20px;
            background-color: #f5f5f5;
            border-radius: 5px;
        }
        
        .search-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .search-input {
            flex: 3;
            min-width: 200px;
        }
        
        .filter-select {
            flex: 2;
            min-width: 150px;
        }
        
        .search-button {
            flex: 1;
            min-width: 100px;
            background-color: #4a7c59;
        }
        
        .search-button:hover {
            background-color: #3e6b4a;
        }
        
        .doctors-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .doctor-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .doctor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .doctor-header {
            padding: 15px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid #eee;
        }
        
        .doctor-image {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #4a7c59;
            margin-right: 15px;
        }
        
        .doctor-name {
            font-size: 1.2rem;
            margin: 0 0 5px;
        }
        
        .doctor-speciality {
            color: #4a7c59;
            font-weight: 600;
            margin: 0;
        }
        
        .doctor-body {
            padding: 15px;
        }
        
        .doctor-detail {
            margin-bottom: 8px;
        }
        
        .doctor-bio {
            margin-top: 12px;
            color: #666;
            font-size: 0.9rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .doctor-footer {
            background-color: #f9f9f9;
            padding: 12px 15px;
            text-align: center;
        }
        
        .btn-book {
            background-color: #4a7c59;
        }
        
        .btn-book:hover {
            background-color: #3e6b4a;
        }
        
        .no-results {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        
        @media (max-width: 768px) {
            .search-form {
                flex-direction: column;
            }
            
            .search-input, .filter-select, .search-button {
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
            <li><a href="find_doctor.php" class="active">Find Doctor</a></li>
            <li><a href="appointments.php">My Appointments</a></li>
            <li><a href="book_appointment.php">Book Appointment</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>
    
    <div class="container">
        <h2>Find a Doctor</h2>
        
        <div class="search-container">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="get" class="search-form">
                <input type="text" name="search" placeholder="Search by name, speciality..." class="form-control search-input" value="<?php echo htmlspecialchars($search_query); ?>">
                
                <select name="speciality" class="form-control filter-select">
                    <option value="">All Specialities</option>
                    <?php foreach($specialities as $speciality): ?>
                        <option value="<?php echo htmlspecialchars($speciality); ?>" <?php echo ($speciality_filter == $speciality) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($speciality); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <button type="submit" class="btn search-button">Search</button>
            </form>
        </div>
        
        <?php if(count($doctors) == 0): ?>
            <div class="no-results">
                <p>No doctors found. Please try a different search or filter.</p>
            </div>
        <?php else: ?>
            <div class="doctors-container">
                <?php foreach($doctors as $doctor): ?>
                    <div class="doctor-card">
                        <div class="doctor-header">
                            <?php 
                                $profile_img = $doctor['profile_picture'];
                                if(empty($profile_img) || !file_exists("../uploads/profile_pictures/" . $profile_img)) {
                                    $profile_img = "default.jpg";
                                }
                            ?>
                            <img src="../uploads/profile_pictures/<?php echo htmlspecialchars($profile_img); ?>" alt="Dr. <?php echo htmlspecialchars($doctor['name']); ?>" class="doctor-image">
                            <div>
                                <h3 class="doctor-name">Dr. <?php echo htmlspecialchars($doctor['name']); ?></h3>
                                <p class="doctor-speciality"><?php echo htmlspecialchars($doctor['speciality']); ?></p>
                            </div>
                        </div>
                        <div class="doctor-body">
                            <?php if(!empty($doctor['phone'])): ?>
                                <div class="doctor-detail">
                                    <strong>Phone:</strong> <?php echo htmlspecialchars($doctor['phone']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if(!empty($doctor['bio'])): ?>
                                <div class="doctor-bio">
                                    <?php echo htmlspecialchars($doctor['bio']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="doctor-footer">
                            <a href="book_appointment.php?doctor_id=<?php echo $doctor['id']; ?>" class="btn btn-book">Book Appointment</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <footer>
        <p>&copy; <?php echo date("Y"); ?> PawPoint. All rights reserved.</p>
    </footer>
</body>
</html> 