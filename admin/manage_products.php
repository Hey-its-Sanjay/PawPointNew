<?php
// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["admin_id"])){
    header("location: login.php");
    exit;
}

require_once "../includes/functions.php";

// Process delete operation if confirmed
if(isset($_GET["action"]) && $_GET["action"] == "delete" && isset($_GET["id"]) && !empty($_GET["id"])){
    $id = $_GET["id"];
    
    // Get product image before deleting
    $sql = "SELECT image FROM products WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $param_id);
        $param_id = $id;
        
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            if($row = mysqli_fetch_array($result)){
                // Delete the image file if it exists
                if($row['image'] && file_exists("../uploads/products/" . $row['image'])){
                    unlink("../uploads/products/" . $row['image']);
                }
            }
        }
        mysqli_stmt_close($stmt);
    }
    
    // Delete the product
    $sql = "DELETE FROM products WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $param_id);
        $param_id = $id;
        
        if(mysqli_stmt_execute($stmt)){
            header("location: manage_products.php?msg=deleted");
            exit();
        }
        mysqli_stmt_close($stmt);
    }
}

// Check for success message
$success_msg = "";
if(isset($_GET["msg"])){
    switch($_GET["msg"]){
        case "added":
            $success_msg = "Product added successfully.";
            break;
        case "updated":
            $success_msg = "Product updated successfully.";
            break;
        case "deleted":
            $success_msg = "Product deleted successfully.";
            break;
    }
}

// Fetch all products
$products = array();
$sql = "SELECT * FROM products ORDER BY name";
$result = mysqli_query($conn, $sql);

if($result && mysqli_num_rows($result) > 0){
    while($row = mysqli_fetch_assoc($result)){
        $products[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - PawPoint Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .admin-header {
            background-color: #34495E;
        }
        .admin-nav {
            background-color: #2C3E50;
        }
        .admin-table {
            border-collapse: collapse;
            margin: 25px 0;
            width: 100%;
        }
        .admin-table th {
            background-color: #2C3E50;
            color: white;
            padding: 12px 15px;
            text-align: left;
        }
        .admin-table td {
            border-bottom: 1px solid #ddd;
            padding: 12px 15px;
        }
        .admin-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .admin-table tr:hover {
            background-color: #ddd;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        .action-btn {
            color: white;
            display: inline-block;
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 3px;
            font-size: 0.8rem;
        }
        .edit-btn {
            background-color: #3498db;
        }
        .btn-info {
            background-color: #17a2b8;
        }
        .delete-btn {
            background-color: #e74c3c;
        }
        .add-btn {
            background-color: #2ecc71;
            color: white;
            margin-bottom: 20px;
            padding: 10px 15px;
            text-decoration: none;
            display: inline-block;
            border-radius: 4px;
        }
        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
        }
        .status-badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .status-active {
            background-color: #2ecc71;
            color: white;
        }
        .status-inactive {
            background-color: #95a5a6;
            color: white;
        }
        .price {
            font-weight: bold;
            color: #2c3e50;
        }
    </style>
</head>
<body>
    <?php include "header.php"; ?>
    
    <div class="container">
        <h2>Manage Products</h2>
        
        <?php 
        if(!empty($success_msg)){
            echo '<div class="alert alert-success">' . $success_msg . '</div>';
        }
        ?>
        
        <a href="add_product.php" class="add-btn">Add New Product</a>
        
        <?php if(count($products) > 0): ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($products as $product): ?>
                        <tr>
                            <td>
                                <img src="<?php echo !empty($product['image']) ? '../uploads/products/'.$product['image'] : '../uploads/products/default-product.jpg'; ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                     class="product-image">
                            </td>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo htmlspecialchars($product['category']); ?></td>
                            <td class="price">₱<?php echo number_format($product['price'], 2); ?></td>
                            <td><?php echo $product['stock']; ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $product['status']; ?>">
                                    <?php echo ucfirst($product['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="view_product.php?id=<?php echo $product['id']; ?>" class="action-btn btn-info">View</a>
                                    <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="action-btn edit-btn">Edit</a>
                                    <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $product['id']; ?>, '<?php echo addslashes(htmlspecialchars($product['name'])); ?>')" class="action-btn delete-btn">Delete</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No products found in the database.</p>
        <?php endif; ?>
    </div>
    
    <script>
        function confirmDelete(id, name) {
            if(confirm("Are you sure you want to delete product: " + name + "?")) {
                window.location.href = "manage_products.php?action=delete&id=" + id;
            }
        }
    </script>
</body>
</html>
