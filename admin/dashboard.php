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

// Get some stats for dashboard
$stats = [];

// Total patients
$sql = "SELECT COUNT(*) as total FROM patients";
$result = mysqli_query($conn, $sql);
if($result){
    $row = mysqli_fetch_assoc($result);
    $stats['total_patients'] = $row['total'];
} else {
    $stats['total_patients'] = 0;
}

// Total doctors
$sql = "SELECT COUNT(*) as total FROM doctors";
$result = mysqli_query($conn, $sql);
if($result){
    $row = mysqli_fetch_assoc($result);
    $stats['total_doctors'] = $row['total'];
} else {
    $stats['total_doctors'] = 0;
}

// Appointments today
$today = date('Y-m-d');
$sql = "SELECT COUNT(*) as total FROM appointments WHERE DATE(appointment_date) = '$today'";
$result = mysqli_query($conn, $sql);
if($result){
    $row = mysqli_fetch_assoc($result);
    $stats['appointments_today'] = $row['total'];
} else {
    $stats['appointments_today'] = 0;
}

// Recent patients (last 5)
$recent_patients = [];
$sql = "SELECT p.id, p.name, p.pet_type, p.pet_name, p.created_at 
        FROM patients p 
        ORDER BY p.created_at DESC 
        LIMIT 5";
$result = mysqli_query($conn, $sql);
if($result && mysqli_num_rows($result) > 0){
    while($row = mysqli_fetch_assoc($result)){
        $recent_patients[] = $row;
    }
}

// Recent appointments (next 5 upcoming)
$upcoming_appointments = [];
$sql = "SELECT a.id, a.appointment_date, a.reason, 
        p.name as patient_name, p.name as owner_name, 
        d.name as doctor_name 
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        JOIN doctors d ON a.doctor_id = d.id
        WHERE a.appointment_date >= NOW()
        ORDER BY a.appointment_date
        LIMIT 5";
$result = mysqli_query($conn, $sql);
if($result && mysqli_num_rows($result) > 0){
    while($row = mysqli_fetch_assoc($result)){
        $upcoming_appointments[] = $row;
    }
}

// Include header
include_once "header.php";
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1>Admin Dashboard</h1>
        <p>Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</p>
    </div>
    
    <div class="dashboard-stats">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-paw"></i>
            </div>
            <div class="stat-details">
                <h3>Total Patients</h3>
                <p class="stat-number"><?php echo number_format($stats['total_patients']); ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-user-md"></i>
            </div>
            <div class="stat-details">
                <h3>Doctors</h3>
                <p class="stat-number"><?php echo number_format($stats['total_doctors']); ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="stat-details">
                <h3>Today's Appointments</h3>
                <p class="stat-number"><?php echo number_format($stats['appointments_today']); ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-cog"></i>
            </div>
            <div class="stat-details">
                <h3>System</h3>
                <p><a href="settings.php" class="link-btn">Manage Settings</a></p>
            </div>
        </div>
    </div>
    
    <div class="dashboard-panels">
        <div class="panel">
            <div class="panel-header">
                <h2>Recent Patients</h2>
                <a href="patients.php" class="view-all">View All</a>
            </div>
            <div class="panel-body">
                <?php if(count($recent_patients) > 0): ?>
                <table class="dashboard-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Pet Type</th>
                            <th>Pet Name</th>
                            <th>Registered</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recent_patients as $patient): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($patient['name']); ?></td>
                            <td><?php echo htmlspecialchars($patient['pet_type']); ?></td>
                            <td><?php echo htmlspecialchars($patient['pet_name']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($patient['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="no-data">No recent patients found.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="panel">
            <div class="panel-header">
                <h2>Upcoming Appointments</h2>
                <a href="appointments.php" class="view-all">View All</a>
            </div>
            <div class="panel-body">
                <?php if(count($upcoming_appointments) > 0): ?>
                <table class="dashboard-table">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($upcoming_appointments as $appointment): ?>
                        <tr>
                            <td><?php echo date('M j, Y H:i', strtotime($appointment['appointment_date'])); ?></td>
                            <td><?php echo htmlspecialchars($appointment['patient_name']); ?> 
                                <small>(<?php echo htmlspecialchars($appointment['owner_name']); ?>)</small>
                            </td>
                            <td><?php echo htmlspecialchars($appointment['doctor_name']); ?></td>
                            <td><?php echo htmlspecialchars($appointment['reason']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="no-data">No upcoming appointments found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="dashboard-quick-links">
        <h2>Quick Links</h2>
        <div class="quick-links-grid">
            <a href="patients.php" class="quick-link">
                <i class="fas fa-paw"></i>
                <span>Manage Patients</span>
            </a>
            <a href="doctors.php" class="quick-link">
                <i class="fas fa-user-md"></i>
                <span>Manage Doctors</span>
            </a>
            <a href="appointments.php" class="quick-link">
                <i class="fas fa-calendar-alt"></i>
                <span>Appointments</span>
            </a>
            <a href="settings.php" class="quick-link">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
            <a href="profile.php" class="quick-link">
                <i class="fas fa-user-circle"></i>
                <span>Your Profile</span>
            </a>
            <a href="reports.php" class="quick-link">
                <i class="fas fa-chart-bar"></i>
                <span>Reports</span>
            </a>
        </div>
    </div>
</div>

<style>
    .dashboard-container {
        padding: 20px;
    }
    
    .dashboard-header {
        margin-bottom: 30px;
    }
    
    .dashboard-header h1 {
        margin: 0;
        color: #2c3e50;
        font-size: 28px;
    }
    
    .dashboard-header p {
        color: #7f8c8d;
        font-size: 16px;
        margin-top: 5px;
    }
    
    .dashboard-stats {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        padding: 20px;
        display: flex;
        align-items: center;
    }
    
    .stat-icon {
        font-size: 24px;
        color: #3498db;
        margin-right: 15px;
        width: 50px;
        height: 50px;
        background-color: rgba(52, 152, 219, 0.1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .stat-details h3 {
        margin: 0;
        font-size: 16px;
        color: #7f8c8d;
    }
    
    .stat-number {
        font-size: 24px;
        font-weight: bold;
        color: #2c3e50;
        margin: 5px 0 0 0;
    }
    
    .link-btn {
        display: inline-block;
        background-color: #3498db;
        color: white;
        padding: 5px 10px;
        border-radius: 4px;
        text-decoration: none;
        font-size: 14px;
    }
    
    .dashboard-panels {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .panel {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        overflow: hidden;
    }
    
    .panel-header {
        background-color: #f8f9fa;
        padding: 15px 20px;
        border-bottom: 1px solid #e9ecef;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .panel-header h2 {
        margin: 0;
        font-size: 18px;
        color: #2c3e50;
    }
    
    .view-all {
        color: #3498db;
        text-decoration: none;
        font-size: 14px;
    }
    
    .panel-body {
        padding: 20px;
    }
    
    .dashboard-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .dashboard-table th {
        text-align: left;
        padding: 10px;
        border-bottom: 1px solid #e9ecef;
        color: #7f8c8d;
        font-weight: 600;
    }
    
    .dashboard-table td {
        padding: 10px;
        border-bottom: 1px solid #e9ecef;
    }
    
    .dashboard-table tbody tr:last-child td {
        border-bottom: none;
    }
    
    .no-data {
        color: #7f8c8d;
        text-align: center;
        padding: 20px;
    }
    
    .dashboard-quick-links {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        padding: 20px;
    }
    
    .dashboard-quick-links h2 {
        margin: 0 0 20px 0;
        font-size: 18px;
        color: #2c3e50;
    }
    
    .quick-links-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 15px;
    }
    
    .quick-link {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        padding: 15px;
        background-color: #f8f9fa;
        border-radius: 8px;
        text-decoration: none;
        color: #2c3e50;
        transition: all 0.2s ease;
    }
    
    .quick-link:hover {
        background-color: #e9ecef;
        transform: translateY(-3px);
    }
    
    .quick-link i {
        font-size: 24px;
        margin-bottom: 10px;
        color: #3498db;
    }
</style>

<?php
// Include footer
include_once "footer.php";
?>

<script>
    // Handle mobile menu toggle
    document.addEventListener('DOMContentLoaded', function() {
        // Mobile menu toggle
        const mobileToggle = document.querySelector('.mobile-menu-toggle');
        const sidebar = document.querySelector('.sidebar');
        
        if (mobileToggle && sidebar) {
            mobileToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
                document.body.classList.toggle('sidebar-active');
            });
            
            // Close sidebar when clicking outside
            document.addEventListener('click', function(event) {
                if (sidebar.classList.contains('active') && 
                    !sidebar.contains(event.target) && 
                    !mobileToggle.contains(event.target)) {
                    sidebar.classList.remove('active');
                    document.body.classList.remove('sidebar-active');
                }
            });
        }
        
        // User dropdown menu toggle
        const userMenuToggle = document.querySelector('.user-menu-toggle');
        const userDropdownMenu = document.querySelector('.user-dropdown-menu');
        
        if (userMenuToggle && userDropdownMenu) {
            userMenuToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                userDropdownMenu.style.display = userDropdownMenu.style.display === 'none' ? 'block' : 'none';
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (userDropdownMenu.style.display === 'block' && 
                    !userDropdownMenu.contains(e.target) && 
                    !userMenuToggle.contains(e.target)) {
                    userDropdownMenu.style.display = 'none';
                }
            });
        }
    });
</script>
</body>
</html> 