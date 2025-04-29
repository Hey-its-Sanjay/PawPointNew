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

// Process form submission
$message = "";
$message_type = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate and update settings
    foreach($_POST as $key => $value){
        // Skip non-setting fields
        if($key == 'submit') continue;
        
        // Update setting in database
        if(update_setting($key, $value)){
            $message = "Settings updated successfully.";
            $message_type = "success";
        } else {
            $message = "Error updating settings.";
            $message_type = "danger";
        }
    }
}

// Get all settings
$settings = get_all_settings();

// Group settings by type
$grouped_settings = [];
foreach($settings as $setting){
    $type = $setting['setting_type'];
    if(!isset($grouped_settings[$type])){
        $grouped_settings[$type] = [];
    }
    $grouped_settings[$type][] = $setting;
}

// Include header
include_once "header.php";
?>

<div class="container-fluid">
    <?php if(!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h2>System Settings</h2>
            <p class="text-muted">Configure system-wide settings for PawPoint</p>
        </div>
        <div class="card-body">
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="general-tab" data-toggle="tab" href="#general" role="tab" aria-controls="general" aria-selected="true">General</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="contact-tab" data-toggle="tab" href="#contact" role="tab" aria-controls="contact" aria-selected="false">Contact Info</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="appointments-tab" data-toggle="tab" href="#appointments" role="tab" aria-controls="appointments" aria-selected="false">Appointments</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="appearance-tab" data-toggle="tab" href="#appearance" role="tab" aria-controls="appearance" aria-selected="false">Appearance</a>
                    </li>
                </ul>
                
                <div class="tab-content mt-4" id="settingsTabContent">
                    <!-- General Settings -->
                    <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="site_name">Site Name</label>
                                    <input type="text" class="form-control" id="site_name" name="site_name" value="<?php echo get_setting('site_name', 'PawPoint'); ?>">
                                    <small class="form-text text-muted">The name of your veterinary care system</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="site_description">Site Description</label>
                                    <input type="text" class="form-control" id="site_description" name="site_description" value="<?php echo get_setting('site_description', "Your Pet's Healthcare Companion"); ?>">
                                    <small class="form-text text-muted">A short tagline or description for your site</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="enable_doctor_registration">Enable Doctor Registration</label>
                                    <select class="form-control" id="enable_doctor_registration" name="enable_doctor_registration">
                                        <option value="1" <?php echo get_setting('enable_doctor_registration', '1') == '1' ? 'selected' : ''; ?>>Yes</option>
                                        <option value="0" <?php echo get_setting('enable_doctor_registration', '1') == '0' ? 'selected' : ''; ?>>No</option>
                                    </select>
                                    <small class="form-text text-muted">Allow doctors to register through the site</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="enable_patient_registration">Enable Patient Registration</label>
                                    <select class="form-control" id="enable_patient_registration" name="enable_patient_registration">
                                        <option value="1" <?php echo get_setting('enable_patient_registration', '1') == '1' ? 'selected' : ''; ?>>Yes</option>
                                        <option value="0" <?php echo get_setting('enable_patient_registration', '1') == '0' ? 'selected' : ''; ?>>No</option>
                                    </select>
                                    <small class="form-text text-muted">Allow patients to register through the site</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Contact Settings -->
                    <div class="tab-pane fade" id="contact" role="tabpanel" aria-labelledby="contact-tab">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="contact_email">Contact Email</label>
                                    <input type="email" class="form-control" id="contact_email" name="contact_email" value="<?php echo get_setting('contact_email', 'contact@pawpoint.com'); ?>">
                                    <small class="form-text text-muted">Primary contact email address</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="contact_phone">Contact Phone</label>
                                    <input type="text" class="form-control" id="contact_phone" name="contact_phone" value="<?php echo get_setting('contact_phone', '(123) 456-7890'); ?>">
                                    <small class="form-text text-muted">Primary contact phone number</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Clinic Address</label>
                            <textarea class="form-control" id="address" name="address" rows="3"><?php echo get_setting('address', '123 Pet Street, Animal City'); ?></textarea>
                            <small class="form-text text-muted">Physical address of the clinic</small>
                        </div>
                    </div>
                    
                    <!-- Appointments Settings -->
                    <div class="tab-pane fade" id="appointments" role="tabpanel" aria-labelledby="appointments-tab">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="appointment_interval">Appointment Interval (minutes)</label>
                                    <select class="form-control" id="appointment_interval" name="appointment_interval">
                                        <option value="15" <?php echo get_setting('appointment_interval', '30') == '15' ? 'selected' : ''; ?>>15</option>
                                        <option value="30" <?php echo get_setting('appointment_interval', '30') == '30' ? 'selected' : ''; ?>>30</option>
                                        <option value="45" <?php echo get_setting('appointment_interval', '30') == '45' ? 'selected' : ''; ?>>45</option>
                                        <option value="60" <?php echo get_setting('appointment_interval', '30') == '60' ? 'selected' : ''; ?>>60</option>
                                    </select>
                                    <small class="form-text text-muted">Time interval between appointments</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="appointment_days_advance">Days in Advance for Booking</label>
                                    <input type="number" class="form-control" id="appointment_days_advance" name="appointment_days_advance" value="<?php echo get_setting('appointment_days_advance', '30'); ?>" min="1" max="90">
                                    <small class="form-text text-muted">How many days in advance patients can book appointments</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="clinic_open_time">Clinic Open Time</label>
                                    <input type="time" class="form-control" id="clinic_open_time" name="clinic_open_time" value="<?php echo get_setting('clinic_open_time', '09:00'); ?>">
                                    <small class="form-text text-muted">When the clinic opens (24-hour format)</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="clinic_close_time">Clinic Close Time</label>
                                    <input type="time" class="form-control" id="clinic_close_time" name="clinic_close_time" value="<?php echo get_setting('clinic_close_time', '17:00'); ?>">
                                    <small class="form-text text-muted">When the clinic closes (24-hour format)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Appearance Settings -->
                    <div class="tab-pane fade" id="appearance" role="tabpanel" aria-labelledby="appearance-tab">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="primary_color">Primary Color</label>
                                    <input type="color" class="form-control" id="primary_color" name="primary_color" value="<?php echo get_setting('primary_color', '#4a7c59'); ?>">
                                    <small class="form-text text-muted">Primary color theme for the site</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="secondary_color">Secondary Color</label>
                                    <input type="color" class="form-control" id="secondary_color" name="secondary_color" value="<?php echo get_setting('secondary_color', '#2c3e50'); ?>">
                                    <small class="form-text text-muted">Secondary color theme for the site</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="logo_url">Logo URL</label>
                            <input type="text" class="form-control" id="logo_url" name="logo_url" value="<?php echo get_setting('logo_url', '../images/pawpoint-logo.png'); ?>">
                            <small class="form-text text-muted">URL to your site logo image</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="favicon_url">Favicon URL</label>
                            <input type="text" class="form-control" id="favicon_url" name="favicon_url" value="<?php echo get_setting('favicon_url', '../images/favicon.ico'); ?>">
                            <small class="form-text text-muted">URL to your site favicon</small>
                        </div>
                    </div>
                </div>
                
                <div class="form-group mt-4">
                    <button type="submit" name="submit" class="btn btn-primary">Save Settings</button>
                    <button type="reset" class="btn btn-secondary">Reset</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
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
    
    .nav-tabs {
        border-bottom: 1px solid #e9ecef;
    }
    
    .nav-tabs .nav-link {
        color: #495057;
        background-color: transparent;
        border-color: transparent;
    }
    
    .nav-tabs .nav-link.active {
        color: var(--primary-color);
        background-color: #fff;
        border-color: #e9ecef #e9ecef #fff;
        border-bottom: 2px solid var(--primary-color);
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-control {
        border-radius: 4px;
        border: 1px solid #ced4da;
        padding: 10px 12px;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }
    
    .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
    }
    
    .text-muted {
        color: #6c757d !important;
    }
    
    .btn {
        padding: 10px 20px;
        border-radius: 4px;
        font-weight: 500;
        transition: all 0.3s;
    }
    
    .btn-primary {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
        color: #fff;
    }
    
    .btn-primary:hover {
        background-color: #2980b9;
        border-color: #2980b9;
    }
    
    .btn-secondary {
        background-color: #6c757d;
        border-color: #6c757d;
        color: #fff;
    }
    
    .btn-secondary:hover {
        background-color: #5a6268;
        border-color: #545b62;
    }
</style>

<script>
$(document).ready(function(){
    // Initialize Bootstrap tabs
    $('#settingsTabs a').click(function(e){
        e.preventDefault();
        $(this).tab('show');
    });
    
    // Save active tab to local storage
    $('a[data-toggle="tab"]').on('shown.bs.tab', function(e){
        localStorage.setItem('activeSettingsTab', $(e.target).attr('href'));
    });
    
    // Go to previously selected tab on page reload
    var activeTab = localStorage.getItem('activeSettingsTab');
    if(activeTab){
        $('#settingsTabs a[href="' + activeTab + '"]').tab('show');
    }
});
</script>

<?php
// Include footer
include_once "footer.php";
?> 