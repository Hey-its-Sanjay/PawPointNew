<?php
// Start session and include required files
session_start();
require_once "../includes/functions.php";

// Check if user is logged in
if (!isset($_SESSION["patient_id"])) {
    header("Location: login.php");
    exit();
}

// Get patient information
$patient_id = $_SESSION["patient_id"];
$query = "SELECT * FROM patients WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
$patient = $result->fetch_assoc();
$stmt->close();

// Check if symptoms table exists
$table_exists = false;
$check_table_sql = "SHOW TABLES LIKE 'symptoms'";
$table_result = $conn->query($check_table_sql);
if ($table_result && $table_result->num_rows > 0) {
    $table_exists = true;
}

$symptoms = [];
if ($table_exists) {
    // Get all symptoms from database
    $query = "SELECT * FROM symptoms ORDER BY name ASC";
    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $symptoms[] = $row;
        }
    }
}

// Process form submission
$diagnosis_result = null;
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["check_symptoms"]) && $table_exists) {
    // Get selected symptoms
    if (isset($_POST["symptoms"]) && is_array($_POST["symptoms"]) && count($_POST["symptoms"]) > 0) {
        $selected_symptoms = $_POST["symptoms"];
        
        // Get all diseases and their symptom mappings
        $query = "SELECT d.id, d.name, d.description, d.treatment, 
                 SUM(sdm.weight) as total_weight, COUNT(sdm.symptom_id) as symptom_count
                 FROM diseases d
                 JOIN symptom_disease_mapping sdm ON d.id = sdm.disease_id
                 WHERE sdm.symptom_id IN (" . implode(",", array_map("intval", $selected_symptoms)) . ")
                 GROUP BY d.id
                 ORDER BY total_weight DESC, symptom_count DESC
                 LIMIT 3";
        
        $result = $conn->query($query);
        if ($result) {
            $possible_diseases = [];
            while ($row = $result->fetch_assoc()) {
                $possible_diseases[] = $row;
            }
            
            // Get selected symptom names for display
            $query = "SELECT name FROM symptoms WHERE id IN (" . implode(",", array_map("intval", $selected_symptoms)) . ")";
            $result = $conn->query($query);
            if ($result) {
                $selected_symptom_names = [];
                while ($row = $result->fetch_assoc()) {
                    $selected_symptom_names[] = $row["name"];
                }
                
                // Prepare diagnosis result
                $diagnosis_result = [
                    "selected_symptoms" => $selected_symptom_names,
                    "possible_diseases" => $possible_diseases
                ];
            }
        }
    } else {
        $error_message = "Please select at least one symptom.";
    }
}

// Page title
$page_title = "Symptom Checker";

// Include header
include "header.php";
?>

<style>
    .symptom-checker-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .symptoms-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .symptom-item {
        border: 1px solid #e0e0e0;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        transition: all 0.3s ease;
        cursor: pointer;
        background-color: white;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    
    .symptom-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .symptom-item img {
        width: 120px;
        height: 120px;
        object-fit: cover;
        border-radius: 10px;
        margin-bottom: 15px;
        display: block;
        margin-left: auto;
        margin-right: auto;
        margin-bottom: 10px;
        background-color: #eee;
    }
    
    .symptom-item label {
        display: block;
        margin-top: 10px;
        cursor: pointer;
    }
    
    .symptom-item input[type="checkbox"] {
        margin-right: 5px;
    }
    
    .symptom-item.selected {
        background-color: #e8f4ea;
        border-color: #4a7c59;
    }
    
    .check-button {
        background-color: #4a7c59;
        color: white;
        border: none;
        padding: 12px 25px;
        border-radius: 5px;
        font-size: 16px;
        cursor: pointer;
        transition: background-color 0.3s ease;
        display: block;
        margin: 0 auto;
    }
    
    .check-button:hover {
        background-color: #3a6a49;
    }
    
    .results-container {
        margin-top: 30px;
        padding: 20px;
        border-radius: 8px;
        background-color: #f5f5f5;
    }
    
    .disease-card {
        background-color: white;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    .disease-card h3 {
        color: #4a7c59;
        margin-top: 0;
        border-bottom: 2px solid #e8f4ea;
        padding-bottom: 10px;
    }
    
    .disease-card p {
        margin-bottom: 15px;
        line-height: 1.6;
    }
    
    .section-title {
        color: #555;
        font-size: 14px;
        font-weight: bold;
        margin-bottom: 5px;
        text-transform: uppercase;
    }
    
    .disclaimer {
        background-color: #fff3cd;
        border-left: 4px solid #ffc107;
        padding: 15px;
        margin-top: 30px;
        border-radius: 4px;
    }
    
    .disclaimer h4 {
        color: #856404;
        margin-top: 0;
        margin-bottom: 10px;
    }
    
    .disclaimer p {
        margin-bottom: 0;
        color: #856404;
    }
    
    .selected-symptoms {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 20px;
    }
    
    .selected-symptom {
        background-color: #e8f4ea;
        color: #4a7c59;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 14px;
    }
    
    .setup-message {
        background-color: #f8d7da;
        color: #721c24;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 30px;
        border-left: 4px solid #721c24;
    }
    
    .setup-message h3 {
        margin-top: 0;
        color: #721c24;
    }
    
    .setup-message a {
        display: inline-block;
        margin-top: 10px;
        background-color: #721c24;
        color: white;
        padding: 8px 15px;
        border-radius: 5px;
        text-decoration: none;
        font-weight: bold;
    }
    
    .setup-message a:hover {
        background-color: #5a171c;
    }
     .symptom-item h3 {
        color: #4a7c59;
        margin: 0 0 10px 0;
        font-size: 16px;
    }

    .symptom-item p {
        font-size: 14px;
        color: #666;
        margin-bottom: 15px;
        line-height: 1.4;
    }

    .symptom-item:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    @media (max-width: 768px) {
        .symptoms-grid {
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        }
    }
</style>

<div class="symptom-checker-container">
    <h1>Pet Symptom Checker</h1>
    <p>Select the symptoms your pet is experiencing to get possible diagnoses. Remember, this tool is for informational purposes only and does not replace professional veterinary advice.</p>
    
    <?php if (!$table_exists): ?>
    <div class="setup-message">
        <h3>Setup Required</h3>
        <p>The Symptom Checker feature needs to be set up before you can use it. Please ask the administrator to run the setup script.</p>
        <p>Administrator: Please run the <strong>create_symptom_tables.php</strong> script in the includes directory to set up the necessary database tables.</p>
        <a href="../includes/create_symptom_tables.php" target="_blank">Run Setup Script</a>
    </div>
    <?php else: ?>
    
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <h2>Select Symptoms</h2>
        <div class="symptoms-grid">
            <?php foreach ($symptoms as $symptom): ?>
                <div class="symptom-item <?php echo (isset($_POST['symptoms']) && in_array($symptom['id'], $_POST['symptoms'])) ? 'selected' : ''; ?>">
                    <?php 
                        // Map symptom names to relevant medical images
                        $symptom_images = array(
                            'Vomiting' => 'https://img.freepik.com/free-photo/sick-pet-cat-dog-vomiting_87557-12480.jpg',
                            'Diarrhea' => 'https://img.freepik.com/free-photo/veterinarian-examining-dog_23-2149198216.jpg',
                            'Lethargy' => 'https://img.freepik.com/free-photo/sick-dog-lying-vet-s-office_23-2148981173.jpg',
                            'Loss of Appetite' => 'https://img.freepik.com/free-photo/close-up-dog-refusing-eat_23-2149165134.jpg',
                            'Coughing' => 'https://img.freepik.com/free-photo/veterinarian-examining-puppy-with-stethoscope_23-2148887380.jpg',
                            'Sneezing' => 'https://img.freepik.com/free-photo/closeup-cat-sneezing_23-2149194616.jpg',
                            'Fever' => 'https://img.freepik.com/free-photo/vet-examining-sick-dog_23-2149198229.jpg',
                            'Limping' => 'https://img.freepik.com/free-photo/dog-with-bandaged-leg_23-2148981158.jpg',
                            'Scratching' => 'https://img.freepik.com/free-photo/dog-scratching-itself_23-2148981147.jpg',
                            'Skin Issues' => 'https://img.freepik.com/free-photo/veterinarian-examining-dog-skin-condition_23-2149198238.jpg',
                            'Eye Problems' => 'https://img.freepik.com/free-photo/veterinarian-examining-dog-s-eyes_23-2148887358.jpg',
                            'Ear Problems' => 'https://img.freepik.com/free-photo/vet-cleaning-dogs-ears_23-2149198242.jpg',
                            'Dental Issues' => 'https://img.freepik.com/free-photo/veterinarian-examining-dogs-teeth_23-2149198227.jpg',
                            'Breathing Problems' => 'https://img.freepik.com/free-photo/veterinarian-examining-dog-with-stethoscope_23-2149198235.jpg',
                            'Weight Loss' => 'https://img.freepik.com/free-photo/veterinarian-weighing-dog-clinic_23-2149198231.jpg',
                            'Urinary Issues' => 'https://img.freepik.com/free-photo/veterinarian-examining-cat-clinic_23-2149198247.jpg',
                            // Default image for symptoms not in the list
                            'default' => 'https://img.freepik.com/free-photo/veterinarian-examining-dog-clinic_23-2149198212.jpg'
                        );
                        
                        // Get the appropriate image URL for the symptom, or use default if not found
                        $image_url = isset($symptom_images[$symptom['name']]) ? 
                                   $symptom_images[$symptom['name']] : 
                                   $symptom_images['default'];
                    ?>
                    <?php if (!empty($symptom['image']) && file_exists(__DIR__ . "/../uploads/symptoms/" . $symptom['image'])): ?>
                        <img src="../uploads/symptoms/<?php echo htmlspecialchars($symptom['image']); ?>" 
                             alt="<?php echo htmlspecialchars($symptom['name']); ?>">
                    <?php else: ?>
                        <img src="../assets/images/symptom-placeholder.jpg" 
                             alt="<?php echo htmlspecialchars($symptom['name']); ?>">
                    <?php endif; ?>
                    <div class="symptom-content">
                        <h3><?php echo $symptom['name']; ?></h3>
                        <p><?php echo $symptom['description']; ?></p>
                        <label>
                            <input type="checkbox" name="symptoms[]" value="<?php echo $symptom['id']; ?>" 
                                <?php echo (isset($_POST['symptoms']) && in_array($symptom['id'], $_POST['symptoms'])) ? 'checked' : ''; ?>>
                            Select this symptom
                        </label>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <button type="submit" name="check_symptoms" class="check-button">Check Symptoms</button>
    </form>
    
    <?php if ($diagnosis_result): ?>
    <div class="results-container">
        <h2>Diagnosis Results</h2>
        
        <h3>Selected Symptoms:</h3>
        <div class="selected-symptoms">
            <?php foreach ($diagnosis_result['selected_symptoms'] as $symptom): ?>
                <span class="selected-symptom"><?php echo $symptom; ?></span>
            <?php endforeach; ?>
        </div>
        
        <?php if (count($diagnosis_result['possible_diseases']) > 0): ?>
            <h3>Possible Conditions:</h3>
            <?php foreach ($diagnosis_result['possible_diseases'] as $disease): ?>
                <div class="disease-card">
                    <h3><?php echo $disease['name']; ?></h3>
                    
                    <div class="section-title">Description</div>
                    <p><?php echo $disease['description']; ?></p>
                    
                    <div class="section-title">Suggested Treatment</div>
                    <p><?php echo $disease['treatment']; ?></p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No matching conditions found based on the selected symptoms. Please consult with a veterinarian for proper diagnosis.</p>
        <?php endif; ?>
        
        <div class="disclaimer">
            <h4>Important Disclaimer</h4>
            <p>This symptom checker is provided for informational purposes only and is not a substitute for professional veterinary advice, diagnosis, or treatment. Always seek the advice of your veterinarian with any questions you may have regarding your pet's health condition. Never disregard professional veterinary advice or delay in seeking it because of something you have read on this website.</p>
        </div>
    </div>
    <?php endif; ?>
    
    <?php endif; ?> <!-- End of table_exists check -->
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Make the entire symptom item clickable to toggle checkbox
        const symptomItems = document.querySelectorAll('.symptom-item');
        
        symptomItems.forEach(item => {
            item.addEventListener('click', function(e) {
                // Don't toggle if clicking on the checkbox itself (it will toggle naturally)
                if (e.target.type !== 'checkbox') {
                    const checkbox = this.querySelector('input[type="checkbox"]');
                    checkbox.checked = !checkbox.checked;
                    
                    // Toggle selected class
                    this.classList.toggle('selected', checkbox.checked);
                }
            });
            
            // Add event listener to checkbox to toggle selected class
            const checkbox = item.querySelector('input[type="checkbox"]');
            checkbox.addEventListener('change', function() {
                item.classList.toggle('selected', this.checked);
            });
        });
    });
</script>

<?php
// Include footer
include "../includes/footer.php";
?>