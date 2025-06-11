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
} elseif(isset($_SESSION["admin_id"])) {
    header("location: admin/dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PawPoint - Your Pet's Health Partner</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4a90e2;
            --secondary: #5cb85c;
            --accent: #f0ad4e;
            --dark: #2c3e50;
            --light: #ecf0f1;
            --white: #ffffff;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: var(--dark);
            background-color: var(--light);
        }
        
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Header */
        header {
            background-color: var(--white);
            box-shadow: var(--shadow);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
        }
        
        .logo {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
        }
        
        .logo span {
            color: var(--secondary);
        }
        
        nav ul {
            display: flex;
            list-style: none;
        }
        
        nav ul li {
            margin-left: 30px;
        }
        
        nav ul li a {
            text-decoration: none;
            color: var(--dark);
            font-weight: 500;
            transition: color 0.3s;
        }
        
        nav ul li a:hover {
            color: var(--primary);
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--primary);
            color: var(--white);
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #3a7bc8;
        }
        
        .btn-secondary {
            background-color: var(--secondary);
        }
        
        .btn-secondary:hover {
            background-color: #4a9d4a;
        }
        
        /* Hero Section */
        .hero {
            padding-top: 120px;
            padding-bottom: 60px;
            background: linear-gradient(135deg, #c9e5f9 0%, #e3f0fd 100%);
            text-align: center;
        }
        
        .hero-content {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 0;
        }
        
        .hero h1 {
            font-size: 48px;
            margin-bottom: 20px;
            color: var(--dark);
        }
        
        .hero p {
            font-size: 18px;
            margin-bottom: 30px;
            color: #5a6a7a;
        }
        
        .hero-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
        }
        
        .hero-image {
            max-width: 100%;
            margin-top: 40px;
            border-radius: 10px;
            box-shadow: var(--shadow);
        }
        
        /* Features */
        .features {
            padding: 80px 0;
            background-color: var(--white);
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 60px;
        }
        
        .section-title h2 {
            font-size: 36px;
            color: var(--dark);
            margin-bottom: 15px;
        }
        
        .section-title p {
            color: #5a6a7a;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .feature-card {
            background-color: var(--white);
            border-radius: 10px;
            box-shadow: var(--shadow);
            padding: 30px;
            transition: transform 0.3s;
            text-align: center;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
        }
        
        .feature-icon {
            font-size: 40px;
            color: var(--primary);
            margin-bottom: 20px;
        }
        
        .feature-card h3 {
            font-size: 22px;
            margin-bottom: 15px;
            color: var(--dark);
        }
        
        .feature-card p {
            color: #5a6a7a;
        }
        
        /* Services */
        .services {
            padding: 80px 0;
            background-color: #f8f9fa;
        }
        
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }
        
        .service-card {
            background-color: var(--white);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: transform 0.3s;
        }
        
        .service-card:hover {
            transform: translateY(-10px);
        }
        
        .service-image {
            height: 200px;
            overflow: hidden;
        }
        
        .service-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }
        
        .service-card:hover .service-image img {
            transform: scale(1.1);
        }
        
        .service-content {
            padding: 20px;
        }
        
        .service-content h3 {
            font-size: 22px;
            margin-bottom: 10px;
            color: var(--dark);
        }
        
        .service-content p {
            color: #5a6a7a;
            margin-bottom: 15px;
        }
        
        /* CTA Section */
        .cta {
            padding: 80px 0;
            background: linear-gradient(135deg, var(--primary) 0%, #3a7bc8 100%);
            color: var(--white);
            text-align: center;
        }
        
        .cta h2 {
            font-size: 36px;
            margin-bottom: 20px;
        }
        
        .cta p {
            font-size: 18px;
            margin-bottom: 30px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .cta .btn {
            background-color: var(--white);
            color: var(--primary);
            font-weight: 600;
        }
        
        .cta .btn:hover {
            background-color: var(--light);
        }
        
        /* Footer */
        footer {
            background-color: var(--dark);
            color: var(--light);
            padding: 60px 0 20px;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .footer-column h3 {
            font-size: 20px;
            margin-bottom: 20px;
            color: var(--white);
        }
        
        .footer-column ul {
            list-style: none;
        }
        
        .footer-column ul li {
            margin-bottom: 10px;
        }
        
        .footer-column ul li a {
            color: var(--light);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-column ul li a:hover {
            color: var(--primary);
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .social-icons {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        
        .social-icons a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--white);
            border-radius: 50%;
            margin: 0 10px;
            transition: all 0.3s;
        }
        
        .social-icons a:hover {
            background-color: var(--primary);
            transform: translateY(-5px);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                text-align: center;
            }
            
            nav ul {
                margin-top: 20px;
                justify-content: center;
            }
            
            nav ul li {
                margin: 0 15px;
            }
            
            .hero h1 {
                font-size: 36px;
            }
            
            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .hero-buttons .btn {
                margin-bottom: 10px;
            }
        }
        
        @media (max-width: 576px) {
            .section-title h2 {
                font-size: 28px;
            }
            
            nav ul {
                flex-wrap: wrap;
            }
            
            nav ul li {
                margin: 0 10px 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <img src="PawPoint/images/pawpoint.png" alt="PawPoint Logo" style="height: 60px; margin-right: 5px; vertical-align: middle;">
                    Paw<span>Point</span>
                </div>
                <nav>
                    <ul>
                        <li><a href="../index.php">Home</a></li>
                        <li><a href="PawPoint/patient/login.php">Patient</a></li>
                        <li><a href="PawPoint/doctor/login.php">Doctor</a></li>
                        <li><a href="PawPoint/admin/login.php">Admin</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>Your Pet's Health Is Our Priority</h1>
                <p>Professional veterinary care with a compassionate touch. We're dedicated to providing the best medical care for your beloved pets.</p>
                <div class="hero-buttons">
                    <a href="PawPoint/patient/register.php" class="btn">Register as Patient</a>
                    <a href="PawPoint/doctor/register.php" class="btn btn-secondary">Register as Doctor</a>
                </div>
            </div>
            <img src="https://images.unsplash.com/photo-1581888227599-779811939961?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" alt="Veterinarian with dog" class="hero-image">
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <div class="section-title">
                <h2>Why Choose PawPoint?</h2>
                <p>We provide comprehensive veterinary services with a focus on compassionate care and advanced medical technologies.</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <h3>Expert Veterinarians</h3>
                    <p>Our team consists of highly qualified and experienced veterinary professionals dedicated to your pet's wellbeing.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-clinic-medical"></i>
                    </div>
                    <h3>Modern Facilities</h3>
                    <p>State-of-the-art equipment and comfortable facilities to ensure your pet receives the best care possible.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h3>Compassionate Care</h3>
                    <p>We treat your pets with love and respect, providing gentle and attentive care at all times.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="services">
        <div class="container">
            <div class="section-title">
                <h2>Our Services</h2>
                <p>We offer a wide range of veterinary services to keep your pets healthy and happy.</p>
            </div>
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-image">
                        <img src="https://images.unsplash.com/photo-1576201836106-db1758fd1c97?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" alt="Pet Examination">
                    </div>
                    <div class="service-content">
                        <h3>Wellness Exams</h3>
                        <p>Regular check-ups to ensure your pet is healthy and to detect any potential health issues early.</p>
                        <a href="patient/register.php" class="btn">Book Now</a>
                    </div>
                </div>
                <div class="service-card">
                    <div class="service-image">
                        <img src="https://plus.unsplash.com/premium_photo-1674760950724-502d17bc80a9?w=500&auto=format&fit=crop&q=60&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MjV8fHZhY2NpbmF0aW9ufGVufDB8fDB8fHww">
                    </div>
                    <div class="service-content">
                        <h3>Vaccinations</h3>
                        <p>Keep your pet protected from common diseases with our comprehensive vaccination programs.</p>
                        <a href="patient/register.php" class="btn">Book Now</a>
                    </div>
                </div>
                <div class="service-card">
                    <div class="service-image">
                        <img src="https://images.unsplash.com/photo-1551884831-bbf3cdc6469e?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" alt="Surgical Procedures">
                    </div>
                    <div class="service-content">
                        <h3>Surgical Procedures</h3>
                        <p>Advanced surgical care performed by skilled veterinary surgeons in a safe environment.</p>
                        <a href="patient/register.php" class="btn">Book Now</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="container">
            <h2>Ready to Give Your Pet the Best Care?</h2>
            <p>Register today and join our veterinary family. Your pet deserves the highest quality of care, and we're here to provide it.</p>
            <a href="patient/login.php" class="btn">Login Now</a>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <h3>PawPoint</h3>
                    <p>Professional veterinary services with a heart. We're dedicated to the health and happiness of your pets.</p>
                </div>
                <div class="footer-column">
                    <h3>Services</h3>
                    <ul>
                        <li><a href="#">Wellness Exams</a></li>
                        <li><a href="#">Vaccinations</a></li>
                        <li><a href="#">Surgery</a></li>
                        <li><a href="#">Dental Care</a></li>
                        <li><a href="#">Emergency Care</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="../index.php">Home</a></li>
                        <li><a href="patient/login.php">Patient Login</a></li>
                        <li><a href="doctor/login.php">Doctor Login</a></li>
                        <li><a href="patient/register.php">Register</a></li>
                        <li><a href="admin/login.php">Admin</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Contact Us</h3>
                    <ul>
                        <li><i class="fas fa-map-marker-alt"></i> 123 Pet Street, Animal City</li>
                        <li><i class="fas fa-phone"></i> (123) 456-7890</li>
                        <li><i class="fas fa-envelope"></i> info@pawpoint.com</li>
                    </ul>
                </div>
            </div>
            <div class="social-icons">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-linkedin-in"></i></a>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date("Y"); ?> PawPoint. All Rights Reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html> 