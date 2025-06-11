<?php
// Include database connection
require_once "config.php";
require_once "functions.php";

// Create uploads/symptoms directory if it doesn't exist
$symptoms_dir = "../uploads/symptoms";
if (!file_exists($symptoms_dir)) {
    mkdir($symptoms_dir, 0777, true);
}

// Check if the symptoms table already exists
$check_table_sql = "SHOW TABLES LIKE 'symptoms'";
$result = $conn->query($check_table_sql);

if ($result->num_rows == 0) {
    // Read and execute the SQL file
    $sql_file = file_get_contents("../symptom_checker.sql");
    
    // Execute multi query
    if ($conn->multi_query($sql_file)) {
        echo "<p>Symptom Checker tables created successfully!</p>";
        
        // Clear results to avoid issues with subsequent queries
        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->more_results() && $conn->next_result());
    } else {
        echo "<p>Error creating Symptom Checker tables: " . $conn->error . "</p>";
    }
} else {
    echo "<p>Symptom Checker tables already exist.</p>";
}

// Close the connection
$conn->close();

echo "<p>You can now <a href='../patient/symptom_checker.php'>access the Symptom Checker</a>.</p>";
?>