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
require_once "../includes/email_functions.php";

// Process actions (confirm, complete, cancel)
$action_message = "";
$action_type = "";

if(isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $appointment_id = $_GET['id'];
    
    // Validate appointment_id
    if(!is_numeric($appointment_id)) {
        $action_message = "Invalid appointment ID.";
        $action_type = "danger";
    } else {
        // Get appointment details for email notifications
        $check_sql = "SELECT a.*, p.name as patient_name, p.email as patient_email, d.name as doctor_name 
                     FROM appointments a 
                     JOIN patients p ON a.patient_id = p.id 
                     JOIN doctors d ON a.doctor_id = d.id 
                     WHERE a.id = ?";
        $appointment_data = null;
        
        if($check_stmt = mysqli_prepare($conn, $check_sql)) {
            mysqli_stmt_bind_param($check_stmt, "i", $appointment_id);
            if(mysqli_stmt_execute($check_stmt)) {
                $result = mysqli_stmt_get_result($check_stmt);
                if($row = mysqli_fetch_assoc($result)) {
                    $appointment_data = $row;
                }
            }            mysqli_stmt_close($check_stmt);
        }
        
        if(!$appointment_data) {
            $action_message = "Appointment not found.";
            $action_type = "danger";
        } else {
            // Perform the requested action
            switch($action) {
                case 'cancel':
                    $sql = "UPDATE appointments SET status = 'cancelled' WHERE id = ?";
                    if($stmt = mysqli_prepare($conn, $sql)) {
                        mysqli_stmt_bind_param($stmt, "i", $appointment_id);
                        if(mysqli_stmt_execute($stmt)) {
                            $action_message = "Appointment cancelled successfully.";
                            $action_type = "success";
                            
                            // Send cancellation email
                            send_appointment_reject_email(
                                $appointment_data['patient_email'],
                                $appointment_data['patient_name'],
                                $appointment_data['appointment_date'],
                                $appointment_data['appointment_time'],
                                $appointment_data['doctor_name']
                            );
                        } else {
                            $action_message = "Error cancelling appointment.";
                            $action_type = "danger";
                        }
                        mysqli_stmt_close($stmt);
                    }
                    break;
                    
                case 'confirm':
                    $sql = "UPDATE appointments SET status = 'confirmed' WHERE id = ?";
                    if($stmt = mysqli_prepare($conn, $sql)) {
                        mysqli_stmt_bind_param($stmt, "i", $appointment_id);
                        if(mysqli_stmt_execute($stmt)) {
                            $action_message = "Appointment confirmed successfully.";
                            $action_type = "success";
                            
                            // Send confirmation email
                            send_appointment_accept_email(
                                $appointment_data['patient_email'],
                                $appointment_data['patient_name'],
                                $appointment_data['appointment_date'],
                                $appointment_data['appointment_time'],
                                $appointment_data['doctor_name']
                            );
                        } else {
                            $action_message = "Error confirming appointment.";
                            $action_type = "danger";
                        }
                        mysqli_stmt_close($stmt);
                    }
                    break;
                
                case 'complete':
                    $sql = "UPDATE appointments SET status = 'completed' WHERE id = ?";
                    if($stmt = mysqli_prepare($conn, $sql)) {
                        mysqli_stmt_bind_param($stmt, "i", $appointment_id);
                        if(mysqli_stmt_execute($stmt)) {
                            $action_message = "Appointment marked as completed.";
                            $action_type = "success";
                        } else {
                            $action_message = "Error completing appointment.";
                            $action_type = "danger";
                        }
                        mysqli_stmt_close($stmt);
                    }                    break;
            }
        }
    }
}

// Set up pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Get search parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$date = isset($_GET['date']) ? $_GET['date'] : '';

// Build the query
$sql = "SELECT a.*, p.name as patient_name, p.pet_name, p.pet_type, d.name as doctor_name 
        FROM appointments a
        LEFT JOIN patients p ON a.patient_id = p.id
        LEFT JOIN doctors d ON a.doctor_id = d.id
        WHERE 1=1";
$count_sql = "SELECT COUNT(*) as total FROM appointments a 
              LEFT JOIN patients p ON a.patient_id = p.id
              LEFT JOIN doctors d ON a.doctor_id = d.id
              WHERE 1=1";

$params = [];
$types = "";

if(!empty($search)) {
    $sql .= " AND (p.name LIKE ? OR d.name LIKE ? OR p.pet_name LIKE ?)";
    $count_sql .= " AND (p.name LIKE ? OR d.name LIKE ? OR p.pet_name LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
    $types .= "sss";
}

if(!empty($status)) {
    $sql .= " AND a.status = ?";
    $count_sql .= " AND a.status = ?";
    $params[] = $status;
    $types .= "s";
}

if(!empty($date)) {
    $sql .= " AND DATE(a.appointment_date) = ?";
    $count_sql .= " AND DATE(a.appointment_date) = ?";
    $params[] = $date;
    $types .= "s";
}

// Add sorting and pagination
$sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT ?, ?";
$params[] = $offset;
$params[] = $records_per_page;
$types .= "ii";

// Get total records for pagination
$total_records = 0;
if($count_stmt = mysqli_prepare($conn, $count_sql)) {
    if(!empty($params)) {
        $temp_types = substr($types, 0, -2); // Remove 'ii' for LIMIT params
        $temp_params = array_slice($params, 0, -2);
        if(!empty($temp_types)) {
            mysqli_stmt_bind_param($count_stmt, $temp_types, ...$temp_params);
        }
    }
    
    if(mysqli_stmt_execute($count_stmt)) {
        $result = mysqli_stmt_get_result($count_stmt);
        $row = mysqli_fetch_assoc($result);
        $total_records = $row['total'];
    }
    
    mysqli_stmt_close($count_stmt);
}

$total_pages = ceil($total_records / $records_per_page);

// Get appointments
$appointments = [];
if($stmt = mysqli_prepare($conn, $sql)) {
    if(!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    if(mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)) {
            $appointments[] = $row;
        }
    }
    
    mysqli_stmt_close($stmt);
}

// Get available statuses for filter
$statuses = ['pending', 'confirmed', 'completed', 'cancelled'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Appointments - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .container { 
            max-width: 1200px; 
            margin: 20px auto; 
            padding: 0 20px; 
        }
        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .filter-item {
            flex: 1;
            min-width: 200px;
        }
        .filter-item select, .filter-item input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }        .appointments-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .appointments-table th, 
        .appointments-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .appointments-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }
        .appointments-table tr:hover {
            background-color: #f5f5f5;
        }
        .pet-info {
            font-size: 0.9em;
        }
        .pet-type {
            color: #666;
            margin-top: 4px;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: 500;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d4edda; color: #155724; }
        .status-completed { background: #cce5ff; color: #004085; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .action-buttons {
            display: flex;
            gap: 5px;
        }        .btn-sm {
            padding: 5px 10px;
            font-size: 0.85em;
            border-radius: 3px;
            cursor: pointer;
            text-decoration: none;
            color: white;
            border: none;
            transition: opacity 0.2s;
        }
        .btn-sm:hover {
            opacity: 0.9;
        }
        .btn-confirm { background-color: #28a745; }
        .btn-complete { background-color: #007bff; }
        .btn-cancel { background-color: #dc3545; }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-danger { background: #f8d7da; color: #721c24; }
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }
        .pagination a {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #007bff;
        }
        .pagination a.active {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }
    </style>
</head>
<body>
    <?php include "header.php"; ?>

    <div class="container">
        <h2>Manage Appointments</h2>
        
        <?php if(!empty($action_message)): ?>
            <div class="alert alert-<?php echo $action_type; ?>">
                <?php echo $action_message; ?>
            </div>
        <?php endif; ?>

        <div class="filters">
            <div class="filter-item">
                <input type="text" id="search" placeholder="Search..." 
                       value="<?php echo htmlspecialchars($search); ?>"
                       onchange="updateFilters()">
            </div>
            <div class="filter-item">
                <select id="status" onchange="updateFilters()">
                    <option value="">All Statuses</option>
                    <?php foreach($statuses as $status_option): ?>
                        <option value="<?php echo $status_option; ?>" 
                                <?php echo $status === $status_option ? 'selected' : ''; ?>>
                            <?php echo ucfirst($status_option); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-item">
                <input type="date" id="date" 
                       value="<?php echo htmlspecialchars($date); ?>"
                       onchange="updateFilters()">
            </div>
        </div>

        <div class="table-responsive">
            <table class="appointments-table">
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Pet</th>
                        <th>Doctor</th>
                        <th>Date & Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($appointments) > 0): ?>
                        <?php foreach($appointments as $appointment): ?>
                            <tr>                                <td><?php echo htmlspecialchars($appointment['patient_name']); ?></td>
                                <td>
                                    <div class="pet-info">
                                        <?php echo htmlspecialchars($appointment['pet_name']); ?>
                                        <div class="pet-type">
                                            <?php echo htmlspecialchars($appointment['pet_type']); ?>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($appointment['doctor_name']); ?></td>
                                <td>
                                    <?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?>
                                    <br>
                                    <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $appointment['status']; ?>">
                                        <?php echo ucfirst($appointment['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if($appointment['status'] === 'pending'): ?>
                                            <a href="?action=confirm&id=<?php echo $appointment['id']; ?>" 
                                               class="btn-sm btn-confirm">Confirm</a>
                                        <?php endif; ?>
                                        
                                        <?php if($appointment['status'] === 'confirmed'): ?>
                                            <a href="?action=complete&id=<?php echo $appointment['id']; ?>" 
                                               class="btn-sm btn-complete">Complete</a>
                                        <?php endif; ?>
                                        
                                        <?php if($appointment['status'] !== 'cancelled' && $appointment['status'] !== 'completed'): ?>
                                            <a href="?action=cancel&id=<?php echo $appointment['id']; ?>" 
                                               class="btn-sm btn-cancel"
                                               onclick="return confirm('Are you sure you want to cancel this appointment?')">Cancel</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">No appointments found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if($total_pages > 1): ?>
            <div class="pagination">
                <?php if($page > 1): ?>
                    <a href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status) ? '&status=' . urlencode($status) : ''; ?><?php echo !empty($date) ? '&date=' . urlencode($date) : ''; ?>">First</a>
                    <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status) ? '&status=' . urlencode($status) : ''; ?><?php echo !empty($date) ? '&date=' . urlencode($date) : ''; ?>">Previous</a>
                <?php endif; ?>

                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);

                for($i = $start_page; $i <= $end_page; $i++):
                ?>
                    <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status) ? '&status=' . urlencode($status) : ''; ?><?php echo !empty($date) ? '&date=' . urlencode($date) : ''; ?>" 
                       class="<?php echo $i == $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status) ? '&status=' . urlencode($status) : ''; ?><?php echo !empty($date) ? '&date=' . urlencode($date) : ''; ?>">Next</a>
                    <a href="?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status) ? '&status=' . urlencode($status) : ''; ?><?php echo !empty($date) ? '&date=' . urlencode($date) : ''; ?>">Last</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function updateFilters() {
            const search = document.getElementById('search').value;
            const status = document.getElementById('status').value;
            const date = document.getElementById('date').value;
            
            let url = '?';
            if(search) url += 'search=' + encodeURIComponent(search) + '&';
            if(status) url += 'status=' + encodeURIComponent(status) + '&';
            if(date) url += 'date=' + encodeURIComponent(date) + '&';
            
            window.location.href = url.slice(0, -1); // Remove trailing & or ?
        }    </script>

    <?php include "footer.php"; ?>
</body>
</html> <?php ?>