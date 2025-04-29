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
    $patient_id = $_GET['id'];
    
    // Validate patient_id
    if(!is_numeric($patient_id)) {
        $action_message = "Invalid patient ID.";
        $action_type = "danger";
    } else {
        // Perform the requested action
        switch($action) {
            case 'delete':
                // Check if the patient has appointments
                $check_sql = "SELECT COUNT(*) as count FROM appointments WHERE patient_id = ?";
                $has_appointments = false;
                
                if($check_stmt = mysqli_prepare($conn, $check_sql)) {
                    mysqli_stmt_bind_param($check_stmt, "i", $patient_id);
                    
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
                    $action_message = "Cannot delete patient with existing appointments. Please remove appointments first.";
                    $action_type = "warning";
                } else {
                    // Delete the patient
                    $sql = "DELETE FROM patients WHERE id = ?";
                    
                    if($stmt = mysqli_prepare($conn, $sql)) {
                        mysqli_stmt_bind_param($stmt, "i", $patient_id);
                        
                        if(mysqli_stmt_execute($stmt)) {
                            $action_message = "Patient deleted successfully.";
                            $action_type = "success";
                        } else {
                            $action_message = "Error deleting patient: " . mysqli_error($conn);
                            $action_type = "danger";
                        }
                        
                        mysqli_stmt_close($stmt);
                    }
                }
                break;
                
            case 'verify':
                // Verify patient's email
                $sql = "UPDATE patients SET email_verified = 1 WHERE id = ?";
                
                if($stmt = mysqli_prepare($conn, $sql)) {
                    mysqli_stmt_bind_param($stmt, "i", $patient_id);
                    
                    if(mysqli_stmt_execute($stmt)) {
                        $action_message = "Patient email verified successfully.";
                        $action_type = "success";
                    } else {
                        $action_message = "Error verifying patient email: " . mysqli_error($conn);
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

// Build the query
$sql = "SELECT * FROM patients";
$count_sql = "SELECT COUNT(*) as total FROM patients";

$params = [];
$types = "";

if(!empty($search)) {
    $sql .= " WHERE " . $search_field . " LIKE ?";
    $count_sql .= " WHERE " . $search_field . " LIKE ?";
    $params[] = "%" . $search . "%";
    $types .= "s";
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
    if(!empty($search)) {
        mysqli_stmt_bind_param($count_stmt, "s", $params[0]);
    }
    
    if(mysqli_stmt_execute($count_stmt)) {
        mysqli_stmt_store_result($count_stmt);
        mysqli_stmt_bind_result($count_stmt, $total_records);
        mysqli_stmt_fetch($count_stmt);
    }
    
    mysqli_stmt_close($count_stmt);
}

$total_pages = ceil($total_records / $records_per_page);

// Get patients data
$patients = [];
if($stmt = mysqli_prepare($conn, $sql)) {
    // Bind parameters
    if(!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    if(mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        
        while($row = mysqli_fetch_assoc($result)) {
            $patients[] = $row;
        }
    }
    
    mysqli_stmt_close($stmt);
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
                    <h2>Manage Patients</h2>
                </div>
                <div class="col-md-6">
                    <form class="float-right">
                        <div class="input-group">
                            <select name="search_field" class="form-control">
                                <option value="name" <?php echo $search_field == 'name' ? 'selected' : ''; ?>>Name</option>
                                <option value="email" <?php echo $search_field == 'email' ? 'selected' : ''; ?>>Email</option>
                                <option value="pet_name" <?php echo $search_field == 'pet_name' ? 'selected' : ''; ?>>Pet Name</option>
                                <option value="pet_type" <?php echo $search_field == 'pet_type' ? 'selected' : ''; ?>>Pet Type</option>
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
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Pet Name</th>
                            <th>Pet Type</th>
                            <th>Registered</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($patients) > 0): ?>
                            <?php foreach($patients as $patient): ?>
                                <tr>
                                    <td><?php echo $patient['id']; ?></td>
                                    <td><?php echo htmlspecialchars($patient['name']); ?></td>
                                    <td><?php echo htmlspecialchars($patient['email']); ?></td>
                                    <td><?php echo htmlspecialchars($patient['pet_name']); ?></td>
                                    <td><?php echo htmlspecialchars($patient['pet_type']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($patient['created_at'])); ?></td>
                                    <td>
                                        <?php if($patient['email_verified']): ?>
                                            <span class="badge badge-success">Verified</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">Unverified</span>
                                            <a href="?action=verify&id=<?php echo $patient['id']; ?>" class="btn btn-sm btn-outline-success ml-2" onclick="return confirm('Are you sure you want to verify this patient?')">
                                                <i class="fas fa-check"></i> Verify
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="edit_patient.php?id=<?php echo $patient['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="view_patient.php?id=<?php echo $patient['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="?action=delete&id=<?php echo $patient['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this patient? This action cannot be undone.')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">No patients found.</td>
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
                                <a class="page-link" href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) . '&search_field=' . urlencode($search_field) : ''; ?>">First</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) . '&search_field=' . urlencode($search_field) : ''; ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        for($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) . '&search_field=' . urlencode($search_field) : ''; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) . '&search_field=' . urlencode($search_field) : ''; ?>">Next</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) . '&search_field=' . urlencode($search_field) : ''; ?>">Last</a>
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
    
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
        line-height: 1.5;
        border-radius: 0.2rem;
    }
    
    .input-group {
        width: 350px;
    }
</style>

<?php
// Include footer
include_once "footer.php";
?> 