<?php
require_once __DIR__ . "/../includes/config.php";

$sql = "SELECT * FROM admins";
$result = mysqli_query($conn, $sql);

if ($result) {
    while($row = mysqli_fetch_assoc($result)) {
        echo "Username: " . $row['username'] . "\n";
        echo "Stored Password Hash: " . $row['password'] . "\n";
    }
} else {
    echo "Error: " . mysqli_error($conn);
}

// Test password hash
$test_password = 'admin123';
$hash = password_hash($test_password, PASSWORD_DEFAULT);
echo "\nTest password hash for 'admin123': " . $hash;
?>
