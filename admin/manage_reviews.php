<?php
session_start();

// Check if admin is logged in
if(!isset($_SESSION["loggedin"]) || !isset($_SESSION["admin_id"])){
    header("location: login.php");
    exit;
}

require_once "../includes/config.php";
require_once "../includes/functions.php";

// Handle review actions (delete, hide, restore, update)
if (isset($_POST['action']) && isset($_POST['review_id'])) {
    $review_id = intval($_POST['review_id']);
    $action = $_POST['action'];
    
    switch($action) {
        case 'delete':
            $sql = "UPDATE reviews SET status = 'deleted' WHERE id = ?";
            $message = "Review has been deleted successfully.";
            break;
        case 'hide':
            $sql = "UPDATE reviews SET status = 'hidden' WHERE id = ?";
            $message = "Review has been hidden successfully.";
            break;
        case 'restore':
            $sql = "UPDATE reviews SET status = 'active' WHERE id = ?";
            $message = "Review has been restored successfully.";
            break;
        case 'update':
            $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
            $review_text = isset($_POST['review_text']) ? trim($_POST['review_text']) : '';
            
            if($rating < 1 || $rating > 5) {
                $_SESSION['error_message'] = "Invalid rating value.";
                header("Location: manage_reviews.php");
                exit;
            }
            
            $sql = "UPDATE reviews SET rating = ?, review_text = ?, updated_at = NOW() WHERE id = ?";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "isi", $rating, $review_text, $review_id);
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['success_message'] = "Review updated successfully.";
                } else {
                    $_SESSION['error_message'] = "Error updating review.";
                }
                mysqli_stmt_close($stmt);
                header("Location: manage_reviews.php");
                exit;
            }
            break;
        default:
            $_SESSION['error_message'] = "Invalid action specified.";
            header("Location: manage_reviews.php");
            exit;
    }
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $review_id);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success_message'] = $message;
        } else {
            $_SESSION['error_message'] = "Error updating review. Please try again.";
        }
        mysqli_stmt_close($stmt);
    }
    
    header("Location: manage_reviews.php");
    exit;
}

// Handle filter and search
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'active';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$rating_filter = isset($_GET['rating']) ? intval($_GET['rating']) : 0;

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
        WHERE 1=1";

if ($status_filter !== 'all') {
    $sql .= " AND r.status = '$status_filter'";
}

if (!empty($search)) {
    $sql .= " AND (d.name LIKE '%$search%' OR p.name LIKE '%$search%' OR r.review_text LIKE '%$search%')";
}

if ($rating_filter > 0) {
    $sql .= " AND r.rating = $rating_filter";
}

$sql .= " ORDER BY r.created_at DESC";

$reviews = [];
if ($result = mysqli_query($conn, $sql)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $reviews[] = $row;
    }
}

// Get review statistics
$stats_sql = "SELECT 
    COUNT(*) as total_reviews,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_reviews,
    SUM(CASE WHEN status = 'hidden' THEN 1 ELSE 0 END) as hidden_reviews,
    SUM(CASE WHEN status = 'deleted' THEN 1 ELSE 0 END) as deleted_reviews,
    AVG(rating) as average_rating
    FROM reviews";
$stats = mysqli_fetch_assoc(mysqli_query($conn, $stats_sql));

include('header.php');
?>

<div class="container-fluid">
    <h2 class="mt-4">Manage Reviews</h2>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Reviews</h5>
                    <h2><?php echo $stats['total_reviews']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Active Reviews</h5>
                    <h2><?php echo $stats['active_reviews']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">Hidden Reviews</h5>
                    <h2><?php echo $stats['hidden_reviews']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Average Rating</h5>
                    <h2><?php echo number_format($stats['average_rating'], 1); ?> / 5.0</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row align-items-center">
                <div class="col-md-3 mb-2">
                    <label for="status">Status Filter:</label>
                    <select name="status" id="status" class="form-control" onchange="this.form.submit()">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Reviews</option>
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="hidden" <?php echo $status_filter === 'hidden' ? 'selected' : ''; ?>>Hidden</option>
                        <option value="deleted" <?php echo $status_filter === 'deleted' ? 'selected' : ''; ?>>Deleted</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label for="rating">Rating Filter:</label>
                    <select name="rating" id="rating" class="form-control" onchange="this.form.submit()">
                        <option value="0">All Ratings</option>
                        <?php for($i = 5; $i >= 1; $i--): ?>
                            <option value="<?php echo $i; ?>" <?php echo $rating_filter === $i ? 'selected' : ''; ?>>
                                <?php echo $i; ?> Stars
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-2">
                    <label for="search">Search:</label>
                    <div class="input-group">
                        <input type="text" name="search" id="search" class="form-control" placeholder="Search by doctor, patient, or review text..." value="<?php echo htmlspecialchars($search); ?>">
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="submit">Search</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Reviews Table -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Doctor</th>
                            <th>Patient</th>
                            <th>Rating</th>
                            <th>Review</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($reviews as $review): ?>
                            <tr>
                                <td><?php echo $review['id']; ?></td>
                                <td><?php echo htmlspecialchars($review['doctor_name']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($review['patient_name']); ?>
                                    <br>
                                    <small class="text-muted">Pet: <?php echo htmlspecialchars($review['pet_name']); ?></small>
                                </td>
                                <td>
                                    <?php
                                    for($i = 1; $i <= 5; $i++) {
                                        echo $i <= $review['rating'] ? '★' : '☆';
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($review['review_text']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($review['created_at'])); ?></td>
                                <td>
                                    <span class="badge badge-<?php
                                        echo $review['status'] === 'active' ? 'success' :
                                            ($review['status'] === 'hidden' ? 'warning' : 'danger');
                                    ?>">
                                        <?php echo ucfirst($review['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <!-- Edit Button - Opens Modal -->
                                    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#editModal<?php echo $review['id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <!-- Status Change Buttons -->
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                        <?php if($review['status'] === 'active'): ?>
                                            <button type="submit" name="action" value="hide" class="btn btn-warning btn-sm" onclick="return confirm('Are you sure you want to hide this review?')">
                                                <i class="fas fa-eye-slash"></i>
                                            </button>
                                        <?php elseif($review['status'] === 'hidden'): ?>
                                            <button type="submit" name="action" value="restore" class="btn btn-success btn-sm" onclick="return confirm('Are you sure you want to restore this review?')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if($review['status'] !== 'deleted'): ?>
                                            <button type="submit" name="action" value="delete" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this review?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editModal<?php echo $review['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editModalLabel<?php echo $review['id']; ?>" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editModalLabel<?php echo $review['id']; ?>">Edit Review</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <form method="POST" action="">
                                            <div class="modal-body">
                                                <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                                <input type="hidden" name="action" value="update">
                                                
                                                <div class="form-group">
                                                    <label>Doctor:</label>
                                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($review['doctor_name']); ?>" readonly>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label>Patient:</label>
                                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($review['patient_name']); ?>" readonly>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="rating<?php echo $review['id']; ?>">Rating:</label>
                                                    <select name="rating" id="rating<?php echo $review['id']; ?>" class="form-control" required>
                                                        <?php for($i = 5; $i >= 1; $i--): ?>
                                                            <option value="<?php echo $i; ?>" <?php echo $review['rating'] === $i ? 'selected' : ''; ?>>
                                                                <?php echo $i; ?> Stars
                                                            </option>
                                                        <?php endfor; ?>
                                                    </select>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="review_text<?php echo $review['id']; ?>">Review Text:</label>
                                                    <textarea name="review_text" id="review_text<?php echo $review['id']; ?>" class="form-control" rows="4" required><?php echo htmlspecialchars($review['review_text']); ?></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add necessary JavaScript -->
<script>
$(document).ready(function() {
    // Initialize any JavaScript features here
});
</script>

<?php include('footer.php'); ?>
