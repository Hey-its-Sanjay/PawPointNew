<?php
require_once __DIR__ . "/../includes/config.php";

$new_password = 'admin123';
$new_hash = password_hash($new_password, PASSWORD_DEFAULT);

$sql = "UPDATE admins SET password = ? WHERE username = 'admin'";
if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "s", $new_hash);
    if(mysqli_stmt_execute($stmt)) {
        echo "Admin password has been reset successfully.\n";
        echo "Username: admin\n";
        echo "Password: admin123\n";
    } else {
        echo "Error updating password: " . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt);
} else {
    echo "Error preparing statement: " . mysqli_error($conn);
}

mysqli_close($conn);
?>
