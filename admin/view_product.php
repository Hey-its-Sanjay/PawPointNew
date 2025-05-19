<?php
// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["admin_id"])){
    header("location: login.php");
    exit;
}

// Include config file
require_once "../includes/functions.php";

// Check existence of id parameter before processing further
if(!isset($_GET["id"]) || empty(trim($_GET["id"]))){
    header("location: error.php");
    exit();
}

// Prepare a select statement
$sql = "SELECT * FROM products WHERE id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    // Bind variables to the prepared statement as parameters
    mysqli_stmt_bind_param($stmt, "i", $param_id);
    
    // Set parameters
    $param_id = trim($_GET["id"]);
    
    // Attempt to execute the prepared statement
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) == 1){
            /* Fetch result row as an associative array */
            $product = mysqli_fetch_array($result, MYSQLI_ASSOC);
        } else {
            // URL doesn't contain valid id parameter
            header("location: error.php");
            exit();
        }
    } else {
        header("location: error.php");
        exit();
    }
    
    // Close statement
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Product - PawPoint Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .product-details {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .product-image {
            width: 100%;
            max-height: 400px;
            object-fit: contain;
            margin-bottom: 20px;
            border-radius: 8px;
            background-color: #f8f9fa;
            padding: 10px;
        }
        
        .product-info {
            margin-bottom: 20px;
        }
        
        .info-group {
            margin-bottom: 15px;
        }
        
        .info-label {
            font-weight: bold;
            color: #4a7c59;
            margin-bottom: 5px;
        }
        
        .info-value {
            color: #2c3e50;
            line-height: 1.5;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.9em;
            font-weight: bold;
        }
        
        .status-active {
            background-color: #27ae60;
            color: white;
        }
        
        .status-inactive {
            background-color: #e74c3c;
            color: white;
        }
        
        .stock-status {
            margin-top: 10px;
            font-weight: bold;
        }
        
        .in-stock { color: #27ae60; }
        .low-stock { color: #f39c12; }
        .out-of-stock { color: #e74c3c; }
        
        .action-buttons {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            color: white;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        
        .btn-edit {
            background-color: #3498db;
        }
        
        .btn-edit:hover {
            background-color: #2980b9;
        }
        
        .btn-back {
            background-color: #95a5a6;
        }
        
        .btn-back:hover {
            background-color: #7f8c8d;
        }
    </style>
</head>
<body>
    <?php include "header.php"; ?>
    
    <div class="product-details">
        <div class="product-info">
            <div class="info-group">
                <div class="info-label">Product Name</div>
                <div class="info-value"><?php echo htmlspecialchars($product["name"]); ?></div>
            </div>
            
            <?php if(!empty($product["image"])): ?>
            <img src="../uploads/products/<?php echo htmlspecialchars($product["image"]); ?>" 
                 alt="<?php echo htmlspecialchars($product["name"]); ?>"
                 class="product-image"
                 onerror="this.src='../uploads/products/default-product.jpg'">
            <?php endif; ?>
            
            <div class="info-group">
                <div class="info-label">Description</div>
                <div class="info-value"><?php echo nl2br(htmlspecialchars($product["description"])); ?></div>
            </div>
            
            <div class="info-group">
                <div class="info-label">Price</div>
                <div class="info-value">₱<?php echo number_format($product["price"], 2); ?></div>
            </div>
            
            <div class="info-group">
                <div class="info-label">Category</div>
                <div class="info-value"><?php echo htmlspecialchars($product["category"]); ?></div>
            </div>
            
            <div class="info-group">
                <div class="info-label">Status</div>
                <div class="info-value">
                    <span class="status-badge <?php echo $product["status"] == 'active' ? 'status-active' : 'status-inactive'; ?>">
                        <?php echo ucfirst($product["status"]); ?>
                    </span>
                </div>
            </div>
            
            <div class="info-group">
                <div class="info-label">Stock Information</div>
                <div class="info-value">
                    <?php echo $product["stock"]; ?> units available
                    <div class="stock-status <?php 
                        if($product["stock"] > 10) echo 'in-stock';
                        elseif($product["stock"] > 0) echo 'low-stock';
                        else echo 'out-of-stock';
                    ?>">
                        <?php 
                        if($product["stock"] > 10) echo 'In Stock';
                        elseif($product["stock"] > 0) echo 'Low Stock';
                        else echo 'Out of Stock';
                        ?>
                    </div>
                </div>
            </div>
            
            <div class="info-group">
                <div class="info-label">Added On</div>
                <div class="info-value"><?php echo date('F j, Y g:i A', strtotime($product["created_at"])); ?></div>
            </div>
            
            <div class="info-group">
                <div class="info-label">Last Updated</div>
                <div class="info-value"><?php echo date('F j, Y g:i A', strtotime($product["updated_at"])); ?></div>
            </div>
        </div>
        
        <div class="action-buttons">
            <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-edit">Edit Product</a>
            <a href="manage_products.php" class="btn btn-back">Back to List</a>
        </div>
    </div>
</body>
</html>
