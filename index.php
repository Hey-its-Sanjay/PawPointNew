<?php
// Start the session
session_start();

// Check if user is already logged in
if(isset($_SESSION["doctor_id"])) {
    header("location: doctor/dashboard.php");
    exit;
} elseif(isset($_SESSION["patient_id"])) {
    header("location: patient/dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PawPoint - Veterinary Care Platform</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Custom styles for homepage */
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f9f9f9;
        }
        
        header {
            background-color: white;
            color: #444;
            padding: 15px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        header h1 {
            color: #3498DB;
            font-size: 2.2rem;
        }
        
        .hero-section {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 40px 0;
            flex-wrap: wrap;
        }
        
        .hero-content {
            flex: 1;
            min-width: 300px;
            padding-right: 30px;
        }
        
        .hero-content h2 {
            font-size: 2.5rem;
            color: #2C3E50;
            text-align: left;
            margin-bottom: 15px;
        }
        
        .hero-content p {
            font-size: 1.1rem;
            margin-bottom: 25px;
            color: #666;
        }
        
        .hero-image {
            flex: 1;
            min-width: 300px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .circle-image {
            width: 350px;
            height: 350px;
            border-radius: 50%;
            background-color: #FF7BAC;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .pet-image {
            width: 80%;
            height: auto;
            object-fit: cover;
        }
        
        .service-section {
            margin: 60px 0;
        }
        
        .service-section h3 {
            text-align: center;
            font-size: 1.8rem;
            color: #2C3E50;
            margin-bottom: 30px;
        }
        
        .service-cards {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .service-card {
            flex: 1;
            min-width: 280px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 20px;
        }
        
        .service-card img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .service-card h4 {
            color: #3498DB;
            margin-bottom: 10px;
        }
        
        .cta-section {
            background-color: #3498DB;
            color: white;
            padding: 50px 0;
            text-align: center;
            border-radius: 10px;
        }
        
        .cta-section h3 {
            font-size: 2rem;
            margin-bottom: 20px;
        }
        
        .cta-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .cta-btn {
            background-color: white;
            color: #3498DB;
            padding: 12px 25px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .cta-btn:hover {
            background-color: #f0f0f0;
            transform: translateY(-3px);
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>PawPoint</h1>
            <p>Your Pet's Healthcare Companion</p>
        </div>
    </header>
    
    <nav>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="doctor/login.php">Doctor Login</a></li>
            <li><a href="patient/login.php">Patient Login</a></li>
            <li><a href="admin/login.php">Admin</a></li>
        </ul>
    </nav>
    
    <div class="container">
        <section class="hero-section">
            <div class="hero-content">
                <h2>Vet Clinic</h2>
                <p>Optimal Health for Furry Friends. Comprehensive Veterinary Services.</p>
                <a href="patient/register.php" class="btn btn-primary">Book an Appointment</a>
            </div>
            <div class="hero-image">
                <div class="circle-image">
                    <img src="https://images.unsplash.com/photo-1560743641-3914f2c45636?ixlib=rb-1.2.1&auto=format&fit=crop&w=633&q=80" alt="Happy dog" class="pet-image">
                </div>
            </div>
        </section>
        
        <section class="service-section">
            <h3>Our Services</h3>
            <div class="service-cards">
                <div class="service-card">
                    <img src="https://images.unsplash.com/photo-1548767797-d8c844163c4c?ixlib=rb-1.2.1&auto=format&fit=crop&w=1351&q=80" alt="Veterinary checkup">
                    <h4>Wellness Exams</h4>
                    <p>Regular checkups to ensure your pet's optimal health and early detection of potential issues.</p>
                </div>
                <div class="service-card">
                    <img src="https://images.unsplash.com/photo-1583511655857-d19b40a7a54e?ixlib=rb-1.2.1&auto=format&fit=crop&w=1349&q=80" alt="Cute dog">
                    <h4>Vaccinations</h4>
                    <p>Essential vaccines to protect your pets from common and potentially serious diseases.</p>
                </div>
                <div class="service-card">
                    <img src="https://images.unsplash.com/photo-1511044568932-338cba0ad803?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" alt="Cat health check">
                    <h4>Dental Care</h4>
                    <p>Complete dental services to maintain your pet's oral health and prevent future problems.</p>
                </div>
            </div>
        </section>
        
        <section class="cta-section">
            <h3>Join Our Pawsitively Amazing Clinic</h3>
            <div class="cta-buttons">
                <a href="doctor/register.php" class="cta-btn">Register as Doctor</a>
                <a href="patient/register.php" class="cta-btn">Register as Patient</a>
            </div>
        </section>
    </div>
    
    <footer>
        <p>&copy; <?php echo date("Y"); ?> PawPoint. All rights reserved.</p>
    </footer>
</body>
</html> 