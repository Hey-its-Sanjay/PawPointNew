<?php
// Initialize session
session_start();

// Check if the user is logged in, if not redirect to login page
if(!isset($_SESSION["loggedin"]) || !isset($_SESSION["admin_id"])){
    header("location: login.php");
    exit;
}

// Include config and functions files
require_once "../includes/config.php";
require_once "../includes/functions.php";

// Process actions (delete, status change, etc.)
$action_message = "";
$action_type = "";

if(isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $doctor_id = $_GET['id'];
    
    // Validate doctor_id
    if(!is_numeric($doctor_id)) {
        $action_message = "Invalid doctor ID.";
        $action_type = "danger";
    } else {
        // Perform the requested action
        switch($action) {
            case 'delete':
                // Check if the doctor has appointments
                $check_sql = "SELECT COUNT(*) as count FROM appointments WHERE doctor_id = ?";
                $has_appointments = false;
                
                if($check_stmt = mysqli_prepare($conn, $check_sql)) {
                    mysqli_stmt_bind_param($check_stmt, "i", $doctor_id);
                    
                    if(mysqli_stmt_execute($check_stmt)) {
                        mysqli_stmt_store_result($check_stmt);
                        mysqli_stmt_bind_result($check_stmt, $count);
                        
                        if(mysqli_stmt_fetch($check_stmt)) {
                            $has_appointments = ($count > 0);
                        }
                    }
                    
                    mysqli_stmt_close($check_stmt);
                }
                  if($has_appointments) {
                    $action_message = "Cannot delete doctor with existing appointments. Please remove appointments first or deactivate the account instead.";
                    $action_type = "danger";
                    $action_type = "warning";
                } else {
                    // Delete the doctor
                    $sql = "DELETE FROM doctors WHERE id = ?";
                    
                    if($stmt = mysqli_prepare($conn, $sql)) {
                        mysqli_stmt_bind_param($stmt, "i", $doctor_id);
                        
                        if(mysqli_stmt_execute($stmt)) {
                            $action_message = "Doctor deleted successfully.";
                            $action_type = "success";
                        } else {
                            $action_message = "Error deleting doctor: " . mysqli_error($conn);
                            $action_type = "danger";
                        }
                        
                        mysqli_stmt_close($stmt);
                    }
                }
                break;
                
            case 'approve':
                // Approve doctor's application
                $sql = "UPDATE doctors SET status = 'approved' WHERE id = ?";
                
                if($stmt = mysqli_prepare($conn, $sql)) {
                    mysqli_stmt_bind_param($stmt, "i", $doctor_id);
                    
                    if(mysqli_stmt_execute($stmt)) {
                        $action_message = "Doctor application approved successfully.";
                        $action_type = "success";
                        
                        // Get doctor's email to send notification
                        $email_sql = "SELECT email, name FROM doctors WHERE id = ?";
                        if($email_stmt = mysqli_prepare($conn, $email_sql)) {
                            mysqli_stmt_bind_param($email_stmt, "i", $doctor_id);
                            
                            if(mysqli_stmt_execute($email_stmt)) {
                                $email_result = mysqli_stmt_get_result($email_stmt);
                                if($doctor_data = mysqli_fetch_assoc($email_result)) {
                                    // Send approval email (simplified for demo)
                                    $to = $doctor_data['email'];
                                    $subject = "Your PawPoint Doctor Application Has Been Approved";
                                    $message = "Hello " . $doctor_data['name'] . ",\n\nYour application to join PawPoint as a veterinary doctor has been approved. You can now log in to your account and start using the system.\n\nThank you,\nThe PawPoint Team";
                                    $headers = "From: noreply@pawpoint.com";
                                    
                                    // Uncomment to send real emails
                                    // mail($to, $subject, $message, $headers);
                                }
                            }
                            
                            mysqli_stmt_close($email_stmt);
                        }
                    } else {
                        $action_message = "Error approving doctor application: " . mysqli_error($conn);
                        $action_type = "danger";
                    }
                    
                    mysqli_stmt_close($stmt);
                }
                break;
                
            case 'reject':
                // Reject doctor's application
                $sql = "UPDATE doctors SET status = 'rejected' WHERE id = ?";
                
                if($stmt = mysqli_prepare($conn, $sql)) {
                    mysqli_stmt_bind_param($stmt, "i", $doctor_id);
                    
                    if(mysqli_stmt_execute($stmt)) {
                        $action_message = "Doctor application rejected.";
                        $action_type = "success";
                        
                        // Get doctor's email to send notification
                        $email_sql = "SELECT email, name FROM doctors WHERE id = ?";
                        if($email_stmt = mysqli_prepare($conn, $email_sql)) {
                            mysqli_stmt_bind_param($email_stmt, "i", $doctor_id);
                            
                            if(mysqli_stmt_execute($email_stmt)) {
                                $email_result = mysqli_stmt_get_result($email_stmt);
                                if($doctor_data = mysqli_fetch_assoc($email_result)) {
                                    // Send rejection email (simplified for demo)
                                    $to = $doctor_data['email'];
                                    $subject = "Your PawPoint Doctor Application Status";
                                    $message = "Hello " . $doctor_data['name'] . ",\n\nWe regret to inform you that your application to join PawPoint as a veterinary doctor has been rejected. If you have any questions, please contact our administrator.\n\nThank you,\nThe PawPoint Team";
                                    $headers = "From: noreply@pawpoint.com";
                                    
                                    // Uncomment to send real emails
                                    // mail($to, $subject, $message, $headers);
                                }
                            }
                            
                            mysqli_stmt_close($email_stmt);
                        }
                    } else {
                        $action_message = "Error rejecting doctor application: " . mysqli_error($conn);
                        $action_type = "danger";
                    }
                    
                    mysqli_stmt_close($stmt);
                }
                break;
                
            default:
                $action_message = "Unknown action requested.";
                $action_type = "warning";
                break;
        }
    }
}

// Set up pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Get search parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_field = isset($_GET['search_field']) ? $_GET['search_field'] : 'name';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build the query
$sql = "SELECT * FROM doctors";
$count_sql = "SELECT COUNT(*) as total FROM doctors";

$where_clauses = [];
$params = [];
$types = "";

if(!empty($search)) {
    $where_clauses[] = $search_field . " LIKE ?";
    $params[] = "%" . $search . "%";
    $types .= "s";
}

if(!empty($status_filter)) {
    $where_clauses[] = "status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if(!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
    $count_sql .= " WHERE " . implode(" AND ", $where_clauses);
}

// Add sorting
$sql .= " ORDER BY created_at DESC";

// Add pagination
$sql .= " LIMIT ?, ?";
$params[] = $offset;
$params[] = $records_per_page;
$types .= "ii";

// Get total records for pagination
$total_records = 0;
if($count_stmt = mysqli_prepare($conn, $count_sql)) {
    if(!empty($params) && count($params) > 2) {
        $count_types = substr($types, 0, -2); // Remove the 'ii' for limit parameters
        $count_params = array_slice($params, 0, -2); // Remove the limit parameters
        mysqli_stmt_bind_param($count_stmt, $count_types, ...$count_params);
    }
    
    if(mysqli_stmt_execute($count_stmt)) {
        mysqli_stmt_store_result($count_stmt);
        mysqli_stmt_bind_result($count_stmt, $total_records);
        mysqli_stmt_fetch($count_stmt);
    }
    
    mysqli_stmt_close($count_stmt);
}

$total_pages = ceil($total_records / $records_per_page);

// Get doctors data
$doctors = [];
if($stmt = mysqli_prepare($conn, $sql)) {
    // Bind parameters
    if(!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    if(mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        
        while($row = mysqli_fetch_assoc($result)) {
            $doctors[] = $row;
        }
    }
    
    mysqli_stmt_close($stmt);
}

// Get pending applications count
$pending_count = 0;
$pending_sql = "SELECT COUNT(*) as count FROM doctors WHERE status = 'pending'";
if($pending_stmt = mysqli_prepare($conn, $pending_sql)) {
    mysqli_stmt_execute($pending_stmt);
    mysqli_stmt_bind_result($pending_stmt, $pending_count);
    mysqli_stmt_fetch($pending_stmt);
    mysqli_stmt_close($pending_stmt);
}

// Include header
include_once "header.php";
?>

<div class="container-fluid">
    <?php if(!empty($action_message)): ?>
        <div class="alert alert-<?php echo $action_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $action_message; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h2>Manage Doctors</h2>
                    <?php if($pending_count > 0): ?>
                        <span class="badge badge-warning"><?php echo $pending_count; ?> pending application(s)</span>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <div class="float-right">
                        <a href="add_doctor.php" class="btn btn-success mb-2">
                            <i class="fas fa-plus"></i> Add New Doctor
                        </a>
                        <form>
                            <div class="input-group mt-2">
                                <select name="status" class="form-control">
                                    <option value="">All Status</option>
                                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                                <select name="search_field" class="form-control">
                                    <option value="name" <?php echo $search_field == 'name' ? 'selected' : ''; ?>>Name</option>
                                    <option value="email" <?php echo $search_field == 'email' ? 'selected' : ''; ?>>Email</option>
                                    <option value="speciality" <?php echo $search_field == 'speciality' ? 'selected' : ''; ?>>Speciality</option>
                                </select>
                                <input type="text" name="search" class="form-control" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Speciality</th>
                            <th>Registered</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($doctors) > 0): ?>
                            <?php foreach($doctors as $doctor): ?>
                                <tr>
                                    <td><?php echo $doctor['id']; ?></td>
                                    <td><?php echo htmlspecialchars($doctor['name']); ?></td>
                                    <td><?php echo htmlspecialchars($doctor['email']); ?></td>
                                    <td><?php echo htmlspecialchars($doctor['speciality']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($doctor['created_at'])); ?></td>
                                    <td>
                                        <?php if($doctor['status'] == 'approved'): ?>
                                            <span class="badge badge-success">Approved</span>
                                        <?php elseif($doctor['status'] == 'pending'): ?>
                                            <span class="badge badge-warning">Pending</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Rejected</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($doctor['status'] == 'pending'): ?>
                                            <a href="?action=approve&id=<?php echo $doctor['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Are you sure you want to approve this doctor?')">
                                                <i class="fas fa-check"></i> Approve
                                            </a>
                                            <a href="?action=reject&id=<?php echo $doctor['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to reject this doctor?')">
                                                <i class="fas fa-times"></i> Reject
                                            </a>
                                        <?php endif; ?>
                                        <a href="edit_doctor.php?id=<?php echo $doctor['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="view_doctor.php?id=<?php echo $doctor['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="?action=delete&id=<?php echo $doctor['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this doctor? This action cannot be undone.')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No doctors found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php if($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) . '&search_field=' . urlencode($search_field) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>">First</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) . '&search_field=' . urlencode($search_field) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        for($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) . '&search_field=' . urlencode($search_field) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) . '&search_field=' . urlencode($search_field) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>">Next</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) . '&search_field=' . urlencode($search_field) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>">Last</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .alert {
        margin-bottom: 20px;
    }
    
    .card {
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 30px;
    }
    
    .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #e9ecef;
        padding: 15px 20px;
    }
    
    .card-header h2 {
        margin: 0;
        font-size: 20px;
        display: inline-block;
        margin-right: 10px;
    }
    
    .card-body {
        padding: 20px;
    }
    
    .table th {
        background-color: #f8f9fa;
        font-weight: 600;
    }
    
    .table-striped tbody tr:nth-of-type(odd) {
        background-color: rgba(0,0,0,.02);
    }
    
    .badge {
        padding: 6px 10px;
        font-weight: 500;
        border-radius: 4px;
    }
    
    .badge-success {
        background-color: #28a745;
    }
    
    .badge-warning {
        background-color: #ffc107;
        color: #212529;
    }
    
    .badge-danger {
        background-color: #dc3545;
    }
    
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
        line-height: 1.5;
        border-radius: 0.2rem;
        margin-right: 5px;
    }
    
    .input-group {
        width: 500px;
    }
</style>

<?php
// Include footer
include_once "footer.php";
?> 