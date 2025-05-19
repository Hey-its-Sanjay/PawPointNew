<?php
session_start();

// Check if admin is logged in
if(!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true){
    header("location: login.php");
    exit;
}

require_once "../includes/config.php";
require_once "../includes/functions.php";

// Handle review deletion
if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['review_id'])) {
    $review_id = intval($_POST['review_id']);
    
    // Update review status to deleted instead of actually deleting it
    $sql = "UPDATE reviews SET status = 'deleted' WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $review_id);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success_message'] = "Review has been deleted successfully.";
        } else {
            $_SESSION['error_message'] = "Error deleting review. Please try again.";
        }
        mysqli_stmt_close($stmt);
    }
    
    // Redirect to refresh the page
    header("Location: manage_reviews.php");
    exit;
}

// Get all reviews with related information
$sql = "SELECT r.*, 
        d.name as doctor_name, 
        p.name as patient_name, 
        p.pet_name,
        a.appointment_date
        FROM reviews r
        JOIN doctors d ON r.doctor_id = d.id
        JOIN patients p ON r.patient_id = p.id
        JOIN appointments a ON r.appointment_id = a.id
        WHERE r.status = 'active'
        ORDER BY r.created_at DESC";

$reviews = [];
if ($result = mysqli_query($conn, $sql)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $reviews[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reviews - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .reviews-container {
            margin: 20px;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .review-card {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }

        .review-info {
            flex: 1;
        }

        .review-actions {
            text-align: right;
        }

        .star-rating {
            color: #ffd700;
            margin: 10px 0;
        }

        .delete-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
        }

        .delete-btn:hover {
            background-color: #c82333;
        }

        .review-meta {
            color: #666;
            font-size: 0.9em;
            margin: 5px 0;
        }

        .review-text {
            margin-top: 10px;
            line-height: 1.6;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="reviews-container">
            <h2>Manage Reviews</h2>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?php 
                        echo $_SESSION['success_message'];
                        unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger">
                    <?php 
                        echo $_SESSION['error_message'];
                        unset($_SESSION['error_message']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (empty($reviews)): ?>
                <p>No reviews found.</p>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <div class="review-info">
                                <h3>Doctor: Dr. <?php echo htmlspecialchars($review['doctor_name']); ?></h3>
                                <div class="review-meta">
                                    <p>Patient: <?php echo htmlspecialchars($review['patient_name']); ?></p>
                                    <p>Pet: <?php echo htmlspecialchars($review['pet_name']); ?></p>
                                    <p>Appointment Date: <?php echo date('F j, Y', strtotime($review['appointment_date'])); ?></p>
                                    <p>Review Date: <?php echo date('F j, Y', strtotime($review['created_at'])); ?></p>
                                </div>
                            </div>
                            <div class="review-actions">
                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this review?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                    <button type="submit" class="delete-btn">
                                        <i class="fas fa-trash"></i> Delete Review
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="star-rating">
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

    <?php include '../includes/footer.php'; ?>
</body>
</html>
