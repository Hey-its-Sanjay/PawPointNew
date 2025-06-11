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

// Get upcoming appointments
$upcoming_appointments = [];
$sql = "SELECT a.id, a.appointment_date, a.appointment_time, a.status, 
        d.name as doctor_name, d.speciality as doctor_speciality
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.id
        WHERE a.patient_id = ? AND a.status != 'cancelled' AND a.status != 'completed'
        AND (a.appointment_date > CURDATE() OR (a.appointment_date = CURDATE() AND a.appointment_time >= CURTIME()))
        ORDER BY a.appointment_date ASC, a.appointment_time ASC
        LIMIT 3";

if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $_SESSION["patient_id"]);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while($row = mysqli_fetch_assoc($result)) {
        $upcoming_appointments[] = $row;
    }
    
    mysqli_stmt_close($stmt);
}

// Get pet information if any
$pets = [];
$pet_sql = "SELECT pet_name, pet_type FROM patients WHERE id = ?";
if($pet_stmt = mysqli_prepare($conn, $pet_sql)) {
    mysqli_stmt_bind_param($pet_stmt, "i", $_SESSION["patient_id"]);
    mysqli_stmt_execute($pet_stmt);
    $pet_result = mysqli_stmt_get_result($pet_stmt);
    
    if($pet_row = mysqli_fetch_assoc($pet_result)) {
        if(!empty($pet_row['pet_name']) && !empty($pet_row['pet_type'])) {
            $pets[] = $pet_row;
        }
    }
    
    mysqli_stmt_close($pet_stmt);
}

// Get featured doctors (limited to 4)
$featured_doctors = [];
$doctor_sql = "SELECT id, name, speciality, profile_picture 
               FROM doctors 
               WHERE status = 'approved' 
               ORDER BY RAND() 
               LIMIT 4";
$doctor_result = mysqli_query($conn, $doctor_sql);
if($doctor_result) {
    while($doctor_row = mysqli_fetch_assoc($doctor_result)) {
        $featured_doctors[] = $doctor_row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - PawPoint</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .status-pending, .status-confirmed, .status-completed, .status-cancelled {
            border-radius: 4px;
            color: white;
            display: inline-block;
            font-size: 0.75rem;
            padding: 3px 8px;
            margin-left: 5px;
        }
        .status-pending {
            background-color: #F39C12;
        }
        .status-confirmed {
            background-color: #27AE60;
        }
        .status-completed {
            background-color: #3498DB;
        }
        .status-cancelled {
            background-color: #E74C3C;
        }
        .appointment-item {
            border-bottom: 1px solid #ddd;
            padding: 10px 0;
        }
        .appointment-item:last-child {
            border-bottom: none;
        }
        .pet-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            padding: 10px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }
        .pet-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #4a7c59;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            margin-right: 15px;
        }
        .pet-details {
            flex: 1;
        }
        .pet-name {
            font-weight: bold;
            font-size: 1.1rem;
        }
        .pet-type {
            color: #666;
        }
        
        /* Doctor grid styles */
        .featured-doctors {
            margin-top: 40px;
        }
        
        .doctors-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .doctor-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
            text-align: center;
        }
        
        .doctor-card:hover {
            transform: translateY(-5px);
        }
        
        .doctor-image-container {
            padding-top: 20px;
        }
        
        .doctor-image {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #4a7c59;
        }
        
        .doctor-info {
            padding: 15px;
        }
        
        .doctor-name {
            font-weight: bold;
            font-size: 1.1rem;
            margin-bottom: 5px;
        }
        
        .doctor-speciality {
            color: #4a7c59;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
        
        .doctor-link {
            display: inline-block;
            background-color: #4a7c59;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: background-color 0.3s ease;
        }
        
        .doctor-link:hover {
            background-color: #3e6b4a;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .see-all-link {
            color: #4a7c59;
            text-decoration: none;
            font-weight: 500;
        }
        
        .see-all-link:hover {
            text-decoration: underline;
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
            <li><a href="find_doctor.php">Find Doctor</a></li>
            <li><a href="appointments.php">My Appointments</a></li>
            <li><a href="products.php">Products</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>
    
    <div class="container">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION["name"]); ?>!</h2>
        <div class="alert alert-success">
            You have successfully logged in to your patient account.
        </div>
          
        
        
        <div style="display: flex; flex-wrap: wrap; justify-content: space-between; margin-top: 40px;">
            <div class="form-container" style="width: 100%; max-width: 48%; min-width: 300px; margin-bottom: 20px;">
                <h3>Your Pets</h3>
                <div style="margin-top: 20px;">
                    <?php if(count($pets) > 0): ?>
                        <?php foreach($pets as $pet): ?>
                            <div class="pet-item">
                                <div class="pet-icon">
                                    <?php echo substr($pet['pet_type'], 0, 1); ?>
                                </div>
                                <div class="pet-details">
                                    <div class="pet-name"><?= htmlspecialchars($pet['pet_name']) ?></div>
                                    <div class="pet-type"><?= htmlspecialchars($pet['pet_type']) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <a href="profile.php" class="btn btn-primary" style="margin-top: 15px;">Update Pet Info</a>
                    <?php else: ?>
                        <p>You have no registered pets yet.</p>
                        <a href="profile.php" class="btn btn-primary" style="margin-top: 15px;">Add a Pet</a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="form-container" style="width: 100%; max-width: 48%; min-width: 300px; margin-bottom: 20px;">
                <h3>Upcoming Appointments</h3>
                <div style="margin-top: 20px;">
                    <?php if(count($upcoming_appointments) > 0): ?>
                        <?php foreach($upcoming_appointments as $appointment): ?>
                            <div class="appointment-item">
                                <p>
                                    <strong>Dr. <?= htmlspecialchars($appointment['doctor_name']) ?></strong> 
                                    <small>(<?= htmlspecialchars($appointment['doctor_speciality']) ?>)</small>
                                </p>
                                <p>
                                    <?= date('l, M d, Y', strtotime($appointment['appointment_date'])) ?> 
                                    at <?= date('h:i A', strtotime($appointment['appointment_time'])) ?>
                                    <span class="status-<?= $appointment['status'] ?>"><?= ucfirst($appointment['status']) ?></span>
                                </p>
                            </div>
                        <?php endforeach; ?>
                        <a href="appointments.php" class="btn btn-primary" style="margin-top: 15px;">View All Appointments</a>
                    <?php else: ?>
                        <p>You have no upcoming appointments.</p>
                        <a href="book_appointment.php" class="btn btn-primary" style="margin-top: 15px;">Book an Appointment</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="featured-doctors">
            <div class="section-header">
                <h3>Our Veterinarians</h3>
                <a href="find_doctor.php" class="see-all-link">See All Doctors</a>
            </div>
            
            <div class="doctors-grid">
                <?php foreach($featured_doctors as $doctor): ?>
                    <div class="doctor-card">
                        <div class="doctor-image-container">
                            <?php 
                                $profile_img = $doctor['profile_picture'];
                                if(empty($profile_img) || !file_exists("../uploads/profile_pictures/" . $profile_img)) {
                                    $profile_img = "default.jpg";
                                }
                            ?>
                            <img src="../uploads/profile_pictures/<?php echo htmlspecialchars($profile_img); ?>" alt="Dr. <?php echo htmlspecialchars($doctor['name']); ?>" class="doctor-image">
                        </div>
                        <div class="doctor-info">
                            <div class="doctor-name">Dr. <?php echo htmlspecialchars($doctor['name']); ?></div>
                            <div class="doctor-speciality"><?php echo htmlspecialchars($doctor['speciality']); ?></div>
                            <a href="book_appointment.php?doctor_id=<?php echo $doctor['id']; ?>" class="doctor-link">Book Appointment</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="form-container" style="margin-top: 30px;">
            <h3>Quick Actions</h3>
            <div style="display: flex; flex-wrap: wrap; gap: 15px; margin-top: 20px;">
                <a href="find_doctor.php" class="btn btn-primary" style="flex: 1; min-width: 200px;">Find a Doctor</a>
                <a href="book_appointment.php" class="btn btn-primary" style="flex: 1; min-width: 200px;">Book Appointment</a>
                <a href="profile.php" class="btn btn-primary" style="flex: 1; min-width: 200px;">Update Profile</a>
            </div>
        </div>
    </div>
    
    <footer>
        <p>&copy; <?php echo date("Y"); ?> PawPoint. All rights reserved.</p>
    </footer>
</body>
</html> 