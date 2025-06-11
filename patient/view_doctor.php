<?php
session_start();

// Include config and functions
require_once "../includes/config.php";
require_once "../includes/functions.php";

// Check if doctor ID is provided
if (!isset($_GET['id'])) {
    header("location: find_doctor.php");
    exit;
}

$doctor_id = intval($_GET['id']);

// Get doctor details
$sql = "SELECT d.*, 
        (SELECT AVG(rating) FROM reviews r WHERE r.doctor_id = d.id AND r.status = 'active') as average_rating,
        (SELECT COUNT(*) FROM reviews r WHERE r.doctor_id = d.id AND r.status = 'active') as review_count
        FROM doctors d 
        WHERE d.id = ? AND d.status = 'approved'";

$doctor = null;
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $doctor_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result->num_rows === 1) {
        $doctor = mysqli_fetch_assoc($result);
    } else {
        header("location: find_doctor.php");
        exit;
    }
    mysqli_stmt_close($stmt);
}

// Get doctor's reviews
$reviews = [];
$review_sql = "SELECT r.*, p.name as patient_name, p.pet_name, a.appointment_date
               FROM reviews r
               JOIN patients p ON r.patient_id = p.id
               JOIN appointments a ON r.appointment_id = a.id
               WHERE r.doctor_id = ? AND r.status = 'active'
               ORDER BY r.created_at DESC";

if ($stmt = mysqli_prepare($conn, $review_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $doctor_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $reviews[] = $row;
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Profile - PawPoint</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .doctor-profile {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .doctor-header {
            display: flex;
            align-items: start;
            margin-bottom: 30px;
        }

        .doctor-image {
            width: 200px;
            height: 200px;
            object-fit: cover;
            border-radius: 10px;
            margin-right: 30px;
        }

        .doctor-info {
            flex: 1;
        }

        .rating-summary {
            margin: 20px 0;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }

        .star-rating {
            color: #ffd700;
            font-size: 24px;
        }

        .reviews-section {
            margin-top: 30px;
        }

        .review-card {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .reviewer-info {
            font-weight: bold;
        }

        .review-date {
            color: #666;
            font-size: 0.9em;
        }

        .review-rating {
            color: #ffd700;
            margin: 10px 0;
        }

        .review-text {
            color: #333;
            line-height: 1.6;
        }

        .book-button {
            display: inline-block;
            background-color: #4a7c59;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            margin-top: 20px;
        }

        .book-button:hover {
            background-color: #3c6547;
        }
    </style>
</head>
<body>
    <?php include "header.php"; ?>

    <div class="container">
        <?php if ($doctor): ?>
            <div class="doctor-profile">
                <div class="doctor-header">
                    <img src="../uploads/profile_pictures/<?php 
                        echo !empty($doctor['profile_picture']) ? htmlspecialchars($doctor['profile_picture']) : 'default.jpg'; 
                    ?>" alt="Dr. <?php echo htmlspecialchars($doctor['name']); ?>" class="doctor-image">
                    
                    <div class="doctor-info">
                        <h2>Dr. <?php echo htmlspecialchars($doctor['name']); ?></h2>
                        <p><strong>Speciality:</strong> <?php echo htmlspecialchars($doctor['speciality']); ?></p>
                        
                        <div class="rating-summary">
                            <div class="star-rating">
                                <?php 
                                $average_rating = round($doctor['average_rating'], 1);
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $average_rating) {
                                        echo '<i class="fas fa-star"></i>';
                                    } elseif ($i - 0.5 <= $average_rating) {
                                        echo '<i class="fas fa-star-half-alt"></i>';
                                    } else {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                }
                                ?>
                                <span style="color: #333; font-size: 0.8em; margin-left: 10px;">
                                    <?php echo number_format($average_rating, 1); ?> 
                                    (<?php echo $doctor['review_count']; ?> reviews)
                                </span>
                            </div>
                        </div>

                        <a href="book_appointment.php?doctor_id=<?php echo $doctor_id; ?>" class="book-button">
                            Book Appointment
                        </a>
                    </div>
                </div>

                <div class="reviews-section">
                    <h3>Patient Reviews</h3>
                    <?php if (empty($reviews)): ?>
                        <p>No reviews yet.</p>
                    <?php else: ?>
                        <?php foreach ($reviews as $review): ?>
                            <div class="review-card">
                                <div class="review-header">
                                    <div class="reviewer-info">
                                        <?php echo htmlspecialchars($review['patient_name']); ?>
                                        <span style="color: #666;">
                                            (Pet: <?php echo htmlspecialchars($review['pet_name']); ?>)
                                        </span>
                                    </div>
                                    <div class="review-date">
                                        <?php echo date('F j, Y', strtotime($review['created_at'])); ?>
                                    </div>
                                </div>
                                <div class="review-rating">
                                    <?php
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $review['rating']) {
                                            echo '<i class="fas fa-star"></i>';
                                        } else {
                                            echo '<i class="far fa-star"></i>';
                                        }
                                    }
                                    ?>
                                </div>
                                <div class="review-text">
                                    <?php echo nl2br(htmlspecialchars($review['review_text'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include "../includes/footer.php"; ?>
</body>
</html>
