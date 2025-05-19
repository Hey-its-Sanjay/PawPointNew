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
$sql = "SELECT d.id, d.name, d.age, d.speciality, d.profile_picture, d.bio, d.phone,
        (SELECT AVG(rating) FROM reviews r WHERE r.doctor_id = d.id AND r.status = 'active') as average_rating,
        (SELECT COUNT(*) FROM reviews r WHERE r.doctor_id = d.id AND r.status = 'active') as review_count
        FROM doctors d
        WHERE d.status = 'approved'";

if(!empty($search_query)) {
    $sql .= " AND (d.name LIKE ? OR d.speciality LIKE ? OR d.bio LIKE ?)";
}

if(!empty($speciality_filter)) {
    if(!empty($search_query)) {
        $sql .= " AND d.speciality = ?";
    } else {
        $sql .= " AND d.speciality = ?";
    }
}

$sql .= " ORDER BY d.name ASC";

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
            display: flex;
            padding: 20px;
            margin-bottom: 20px;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            transition: transform 0.2s;
        }
        
        .doctor-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .doctor-image {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 20px;
        }
        
        .doctor-info {
            flex: 1;
        }
        
        .doctor-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 10px;
        }
        
        .doctor-name {
            font-size: 1.4em;
            color: #4a7c59;
            margin: 0;
        }
        
        .doctor-rating {
            color: #ffd700;
            font-size: 1.1em;
        }
        
        .doctor-speciality {
            color: #666;
            margin: 5px 0;
        }
        
        .doctor-bio {
            margin: 10px 0;
            color: #333;
            line-height: 1.6;
        }
        
        .doctor-actions {
            margin-top: 15px;
        }
        
        .btn-view-profile {
            background-color: #4a7c59;
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
        }
        
        .btn-book {
            background-color: #31725b;
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-view-profile:hover, .btn-book:hover {
            opacity: 0.9;
        }
        
        .review-count {
            color: #666;
            font-size: 0.9em;
            margin-left: 5px;
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
             <li><a href="products.php">Products</a></li>
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
        
        <div class="doctors-list">
            <?php if (empty($doctors)): ?>
                <p>No doctors found matching your criteria.</p>
            <?php else: ?>
                <?php foreach ($doctors as $doctor): ?>
                    <div class="doctor-card">
                        <img src="../uploads/profile_pictures/<?php 
                            echo !empty($doctor['profile_picture']) ? htmlspecialchars($doctor['profile_picture']) : 'default.jpg'; 
                            ?>" alt="Dr. <?php echo htmlspecialchars($doctor['name']); ?>" class="doctor-image">
                        
                        <div class="doctor-info">
                            <div class="doctor-header">
                                <h3 class="doctor-name">Dr. <?php echo htmlspecialchars($doctor['name']); ?></h3>
                                <div class="doctor-rating">
                                    <?php 
                                    $rating = round($doctor['average_rating'], 1);
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $rating) {
                                            echo '<i class="fas fa-star"></i>';
                                        } elseif ($i - 0.5 <= $rating) {
                                            echo '<i class="fas fa-star-half-alt"></i>';
                                        } else {
                                            echo '<i class="far fa-star"></i>';
                                        }
                                    }
                                    ?>
                                    <span class="review-count">
                                        <?php 
                                        echo $rating > 0 ? number_format($rating, 1) : 'No rating'; 
                                        ?> 
                                        (<?php echo $doctor['review_count']; ?> reviews)
                                    </span>
                                </div>
                            </div>
                            
                            <div class="doctor-speciality">
                                <?php echo htmlspecialchars($doctor['speciality']); ?>
                            </div>
                            
                            <?php if (!empty($doctor['bio'])): ?>
                                <div class="doctor-bio">
                                    <?php echo nl2br(htmlspecialchars($doctor['bio'])); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="doctor-actions">
                                <a href="view_doctor.php?id=<?php echo $doctor['id']; ?>" class="btn-view-profile">
                                    View Profile & Reviews
                                </a>
                                <a href="book_appointment.php?doctor_id=<?php echo $doctor['id']; ?>" class="btn-book">
                                    Book Appointment
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include "../includes/footer.php"; ?>
</body>
</html>