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

// Set default report type and date range
$report_type = isset($_GET['type']) ? $_GET['type'] : 'appointment';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01'); // First day of current month
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-t'); // Last day of current month

// Initialize report data array
$report_data = [];
$chart_data = [];
$total_count = 0;

// Process report generation based on type
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['generate'])) {
    
    switch ($report_type) {
        case 'appointment':
            // Appointments report
            $sql = "SELECT a.id, a.appointment_date, a.reason, a.status,
                    p.name as patient_name, p.pet_name, p.pet_type,
                    d.name as doctor_name, d.speciality
                    FROM appointments a
                    JOIN patients p ON a.patient_id = p.id
                    JOIN doctors d ON a.doctor_id = d.id
                    WHERE DATE(a.appointment_date) BETWEEN ? AND ?
                    ORDER BY a.appointment_date DESC";
            
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "ss", $date_from, $date_to);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                while ($row = mysqli_fetch_assoc($result)) {
                    $report_data[] = $row;
                }
                
                $total_count = count($report_data);
                
                // Get appointment stats by status for chart
                $status_sql = "SELECT status, COUNT(*) as count 
                               FROM appointments 
                               WHERE DATE(appointment_date) BETWEEN ? AND ?
                               GROUP BY status";
                
                if ($status_stmt = mysqli_prepare($conn, $status_sql)) {
                    mysqli_stmt_bind_param($status_stmt, "ss", $date_from, $date_to);
                    mysqli_stmt_execute($status_stmt);
                    $status_result = mysqli_stmt_get_result($status_stmt);
                    
                    while ($status_row = mysqli_fetch_assoc($status_result)) {
                        $chart_data[] = [
                            'label' => ucfirst($status_row['status']),
                            'value' => $status_row['count']
                        ];
                    }
                    
                    mysqli_stmt_close($status_stmt);
                }
            }
            break;
            
        case 'patient':
            // Patients report
            $sql = "SELECT p.id, p.name, p.email, p.age, p.address, p.pet_name, p.pet_type, 
                   p.created_at, COUNT(a.id) as appointment_count
                   FROM patients p
                   LEFT JOIN appointments a ON p.id = a.patient_id
                   WHERE DATE(p.created_at) BETWEEN ? AND ?
                   GROUP BY p.id
                   ORDER BY p.created_at DESC";
            
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "ss", $date_from, $date_to);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                while ($row = mysqli_fetch_assoc($result)) {
                    $report_data[] = $row;
                }
                
                $total_count = count($report_data);
                
                // Get patient stats by pet type for chart
                $pet_type_sql = "SELECT pet_type, COUNT(*) as count 
                                FROM patients 
                                WHERE DATE(created_at) BETWEEN ? AND ?
                                GROUP BY pet_type";
                
                if ($pet_stmt = mysqli_prepare($conn, $pet_type_sql)) {
                    mysqli_stmt_bind_param($pet_stmt, "ss", $date_from, $date_to);
                    mysqli_stmt_execute($pet_stmt);
                    $pet_result = mysqli_stmt_get_result($pet_stmt);
                    
                    while ($pet_row = mysqli_fetch_assoc($pet_result)) {
                        $chart_data[] = [
                            'label' => $pet_row['pet_type'],
                            'value' => $pet_row['count']
                        ];
                    }
                    
                    mysqli_stmt_close($pet_stmt);
                }
            }
            break;
            
        case 'doctor':
            // Doctors report
            $sql = "SELECT d.id, d.name, d.email, d.speciality, d.status, 
                   d.created_at, COUNT(a.id) as appointment_count
                   FROM doctors d
                   LEFT JOIN appointments a ON d.id = a.doctor_id
                   WHERE DATE(d.created_at) BETWEEN ? AND ?
                   GROUP BY d.id
                   ORDER BY appointment_count DESC";
            
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "ss", $date_from, $date_to);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                while ($row = mysqli_fetch_assoc($result)) {
                    $report_data[] = $row;
                }
                
                $total_count = count($report_data);
                
                // Get doctor stats by speciality for chart
                $speciality_sql = "SELECT speciality, COUNT(*) as count 
                                  FROM doctors 
                                  WHERE DATE(created_at) BETWEEN ? AND ?
                                  GROUP BY speciality";
                
                if ($spec_stmt = mysqli_prepare($conn, $speciality_sql)) {
                    mysqli_stmt_bind_param($spec_stmt, "ss", $date_from, $date_to);
                    mysqli_stmt_execute($spec_stmt);
                    $spec_result = mysqli_stmt_get_result($spec_stmt);
                    
                    while ($spec_row = mysqli_fetch_assoc($spec_result)) {
                        $chart_data[] = [
                            'label' => $spec_row['speciality'],
                            'value' => $spec_row['count']
                        ];
                    }
                    
                    mysqli_stmt_close($spec_stmt);
                }
            }
            break;
            
        case 'daily':
            // Daily summary report
            $sql = "SELECT DATE(appointment_date) as date, 
                   COUNT(*) as total_appointments,
                   SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                   SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                   SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                   SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
                   FROM appointments
                   WHERE DATE(appointment_date) BETWEEN ? AND ?
                   GROUP BY DATE(appointment_date)
                   ORDER BY date ASC";
            
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "ss", $date_from, $date_to);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                while ($row = mysqli_fetch_assoc($result)) {
                    $report_data[] = $row;
                    
                    // Add to chart data
                    $chart_data[] = [
                        'date' => date('M d', strtotime($row['date'])),
                        'total' => (int)$row['total_appointments']
                    ];
                }
                
                $total_count = count($report_data);
            }
            break;
    }
    
    if ($stmt) {
        mysqli_stmt_close($stmt);
    }
}

// Encode chart data for JavaScript
$chart_json = json_encode($chart_data);

// Include header
include_once "header.php";
?>

<div class="reports-container">
    <div class="reports-header">
        <h1>Reports & Analytics</h1>
        <p>Generate and analyze various reports for your veterinary practice</p>
    </div>
    
    <div class="report-filters">
        <form method="GET" action="reports.php" class="report-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="type">Report Type</label>
                    <select name="type" id="type" class="form-control">
                        <option value="appointment" <?php echo $report_type == 'appointment' ? 'selected' : ''; ?>>Appointments Report</option>
                        <option value="patient" <?php echo $report_type == 'patient' ? 'selected' : ''; ?>>Patients Report</option>
                        <option value="doctor" <?php echo $report_type == 'doctor' ? 'selected' : ''; ?>>Doctors Report</option>
                        <option value="daily" <?php echo $report_type == 'daily' ? 'selected' : ''; ?>>Daily Summary</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="date_from">From Date</label>
                    <input type="date" name="date_from" id="date_from" class="form-control" value="<?php echo $date_from; ?>">
                </div>
                
                <div class="form-group">
                    <label for="date_to">To Date</label>
                    <input type="date" name="date_to" id="date_to" class="form-control" value="<?php echo $date_to; ?>">
                </div>
                
                <div class="form-group btn-group">
                    <button type="submit" name="generate" value="1" class="btn btn-primary">Generate Report</button>
                    <?php if (!empty($report_data)): ?>
                    <button type="button" id="export-csv" class="btn btn-secondary">Export CSV</button>
                    <button type="button" id="print-report" class="btn btn-secondary">Print</button>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
    
    <?php if (!empty($report_data)): ?>
    <div class="report-results">
        <div class="report-summary">
            <div class="summary-header">
                <h2><?php echo ucfirst($report_type); ?> Report</h2>
                <p><?php echo date('F j, Y', strtotime($date_from)); ?> - <?php echo date('F j, Y', strtotime($date_to)); ?></p>
                <p>Total Records: <?php echo $total_count; ?></p>
            </div>
            
            <div class="report-visualization">
                <canvas id="reportChart" width="400" height="200"></canvas>
            </div>
        </div>
        
        <div class="report-data">
            <h3>Detailed Report</h3>
            
            <?php if ($report_type == 'appointment'): ?>
            <!-- Appointments Report Table -->
            <table class="report-table" id="report-table">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Patient</th>
                        <th>Pet</th>
                        <th>Doctor</th>
                        <th>Reason</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($report_data as $item): ?>
                    <tr>
                        <td><?php echo date('M j, Y H:i', strtotime($item['appointment_date'])); ?></td>
                        <td><?php echo htmlspecialchars($item['patient_name']); ?></td>
                        <td><?php echo htmlspecialchars($item['pet_name']); ?> (<?php echo htmlspecialchars($item['pet_type']); ?>)</td>
                        <td><?php echo htmlspecialchars($item['doctor_name']); ?> <small>(<?php echo htmlspecialchars($item['speciality']); ?>)</small></td>
                        <td><?php echo htmlspecialchars($item['reason']); ?></td>
                        <td><span class="status-badge status-<?php echo $item['status']; ?>"><?php echo ucfirst($item['status']); ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php elseif ($report_type == 'patient'): ?>
            <!-- Patients Report Table -->
            <table class="report-table" id="report-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Age</th>
                        <th>Pet Name</th>
                        <th>Pet Type</th>
                        <th>Registration Date</th>
                        <th>Appointments</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($report_data as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td><?php echo htmlspecialchars($item['email']); ?></td>
                        <td><?php echo htmlspecialchars($item['age']); ?></td>
                        <td><?php echo htmlspecialchars($item['pet_name']); ?></td>
                        <td><?php echo htmlspecialchars($item['pet_type']); ?></td>
                        <td><?php echo date('M j, Y', strtotime($item['created_at'])); ?></td>
                        <td><?php echo $item['appointment_count']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php elseif ($report_type == 'doctor'): ?>
            <!-- Doctors Report Table -->
            <table class="report-table" id="report-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Speciality</th>
                        <th>Status</th>
                        <th>Registration Date</th>
                        <th>Appointments</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($report_data as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td><?php echo htmlspecialchars($item['email']); ?></td>
                        <td><?php echo htmlspecialchars($item['speciality']); ?></td>
                        <td><span class="status-badge status-<?php echo $item['status']; ?>"><?php echo ucfirst($item['status']); ?></span></td>
                        <td><?php echo date('M j, Y', strtotime($item['created_at'])); ?></td>
                        <td><?php echo $item['appointment_count']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php elseif ($report_type == 'daily'): ?>
            <!-- Daily Summary Report Table -->
            <table class="report-table" id="report-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Total Appointments</th>
                        <th>Confirmed</th>
                        <th>Completed</th>
                        <th>Cancelled</th>
                        <th>Pending</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($report_data as $item): ?>
                    <tr>
                        <td><?php echo date('M j, Y (D)', strtotime($item['date'])); ?></td>
                        <td><?php echo $item['total_appointments']; ?></td>
                        <td><?php echo $item['confirmed']; ?></td>
                        <td><?php echo $item['completed']; ?></td>
                        <td><?php echo $item['cancelled']; ?></td>
                        <td><?php echo $item['pending']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="no-report-data">
        <p>Select report parameters and click "Generate Report" to view data.</p>
    </div>
    <?php endif; ?>
</div>

<style>
    .reports-container {
        padding: 20px;
    }
    
    .reports-header {
        margin-bottom: 20px;
    }
    
    .reports-header h1 {
        margin: 0;
        color: #2c3e50;
        font-size: 28px;
    }
    
    .reports-header p {
        color: #7f8c8d;
        font-size: 16px;
        margin-top: 5px;
    }
    
    .report-filters {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .report-form .form-row {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        align-items: flex-end;
    }
    
    .form-group {
        flex: 1;
        min-width: 180px;
        margin-bottom: 0;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 5px;
        color: #4a5568;
        font-weight: 600;
    }
    
    .form-control {
        width: 100%;
        padding: 10px;
        border: 1px solid #e2e8f0;
        border-radius: 4px;
        font-size: 14px;
    }
    
    .btn-group {
        display: flex;
        gap: 10px;
    }
    
    .btn {
        padding: 10px 15px;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 600;
        border: none;
    }
    
    .btn-primary {
        background-color: #3498db;
        color: white;
    }
    
    .btn-secondary {
        background-color: #e9ecef;
        color: #2c3e50;
    }
    
    .report-results {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        padding: 20px;
    }
    
    .report-summary {
        margin-bottom: 30px;
        display: flex;
        flex-wrap: wrap;
    }
    
    .summary-header {
        flex: 1;
        min-width: 250px;
    }
    
    .summary-header h2 {
        margin: 0 0 10px 0;
        color: #2c3e50;
    }
    
    .summary-header p {
        margin: 5px 0;
        color: #7f8c8d;
    }
    
    .report-visualization {
        flex: 2;
        min-width: 300px;
        max-width: 100%;
        height: 250px;
    }
    
    .report-data h3 {
        margin: 0 0 15px 0;
        color: #2c3e50;
        padding-bottom: 10px;
        border-bottom: 1px solid #e9ecef;
    }
    
    .report-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .report-table th {
        background-color: #f8f9fa;
        padding: 12px;
        text-align: left;
        font-weight: 600;
        color: #4a5568;
        border-bottom: 2px solid #e9ecef;
    }
    
    .report-table td {
        padding: 12px;
        border-bottom: 1px solid #e9ecef;
    }
    
    .status-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .status-confirmed {
        background-color: #ebf8ff;
        color: #2b6cb0;
    }
    
    .status-completed {
        background-color: #f0fff4;
        color: #2f855a;
    }
    
    .status-cancelled {
        background-color: #fff5f5;
        color: #c53030;
    }
    
    .status-pending {
        background-color: #fffaf0;
        color: #c05621;
    }
    
    .status-approved {
        background-color: #f0fff4;
        color: #2f855a;
    }
    
    .status-rejected {
        background-color: #fff5f5;
        color: #c53030;
    }
    
    .no-report-data {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        padding: 30px;
        text-align: center;
        color: #7f8c8d;
    }
    
    @media (max-width: 768px) {
        .form-group {
            flex: 100%;
        }
        
        .btn-group {
            flex-direction: column;
            width: 100%;
        }
        
        .report-summary {
            flex-direction: column;
        }
        
        .report-table {
            display: block;
            overflow-x: auto;
        }
    }
</style>

<?php include_once "footer.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chart initialization
    const chartData = <?php echo $chart_json; ?>;
    if (chartData.length > 0) {
        const ctx = document.getElementById('reportChart').getContext('2d');
        
        // Determine chart type based on report type
        let chartConfig = {};
        const reportType = '<?php echo $report_type; ?>';
        
        if (reportType === 'daily') {
            // Line chart for daily summary
            const labels = chartData.map(item => item.date);
            const data = chartData.map(item => item.total);
            
            chartConfig = {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Total Appointments',
                        data: data,
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        borderWidth: 2,
                        tension: 0.1,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: 'Daily Appointment Trends'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            };
        } else {
            // Pie chart for other report types
            const labels = chartData.map(item => item.label);
            const data = chartData.map(item => item.value);
            
            // Generate random colors
            const backgroundColors = generateColors(labels.length);
            
            let title = '';
            switch(reportType) {
                case 'appointment':
                    title = 'Appointments by Status';
                    break;
                case 'patient':
                    title = 'Patients by Pet Type';
                    break;
                case 'doctor':
                    title = 'Doctors by Speciality';
                    break;
            }
            
            chartConfig = {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: backgroundColors,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                        },
                        title: {
                            display: true,
                            text: title
                        }
                    }
                }
            };
        }
        
        new Chart(ctx, chartConfig);
    }
    
    // Export to CSV functionality
    document.getElementById('export-csv').addEventListener('click', function() {
        exportTableToCSV('report-table', '<?php echo $report_type; ?>_report.csv');
    });
    
    // Print functionality
    document.getElementById('print-report').addEventListener('click', function() {
        window.print();
    });
    
    // Helper Functions
    function generateColors(count) {
        const colors = [
            'rgba(52, 152, 219, 0.7)',  // Blue
            'rgba(46, 204, 113, 0.7)',  // Green
            'rgba(231, 76, 60, 0.7)',   // Red
            'rgba(241, 196, 15, 0.7)',  // Yellow
            'rgba(155, 89, 182, 0.7)',  // Purple
            'rgba(52, 73, 94, 0.7)',    // Dark blue
            'rgba(230, 126, 34, 0.7)',  // Orange
            'rgba(26, 188, 156, 0.7)'   // Turquoise
        ];
        
        let result = [];
        for (let i = 0; i < count; i++) {
            result.push(colors[i % colors.length]);
        }
        return result;
    }
    
    function exportTableToCSV(tableId, filename) {
        let csv = [];
        const table = document.getElementById(tableId);
        const rows = table.querySelectorAll('tr');
        
        for (let i = 0; i < rows.length; i++) {
            const row = [], cols = rows[i].querySelectorAll('td, th');
            
            for (let j = 0; j < cols.length; j++) {
                // Get text content and remove any commas (to avoid CSV issues)
                let data = cols[j].textContent.replace(/,/g, ' ');
                // Remove line breaks
                data = data.replace(/(\r\n|\n|\r)/gm, ' ');
                row.push('"' + data + '"');
            }
            
            csv.push(row.join(','));
        }
        
        // Download CSV file
        downloadCSV(csv.join('\n'), filename);
    }
    
    function downloadCSV(csv, filename) {
        const csvFile = new Blob([csv], {type: 'text/csv'});
        const downloadLink = document.createElement('a');
        
        downloadLink.download = filename;
        downloadLink.href = window.URL.createObjectURL(csvFile);
        downloadLink.style.display = 'none';
        
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
    }
});
</script> 