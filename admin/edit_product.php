<?php
// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["admin_id"])){
    header("location: login.php");
    exit;
}

require_once "../includes/functions.php";

// Define variables and initialize with empty values
$name = $description = $price = $category = $current_image = "";
$name_err = $description_err = $price_err = $category_err = $image_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    $id = trim($_POST["id"]);
    
    // Validate name
    if(empty(trim($_POST["name"]))){
        $name_err = "Please enter the product name.";
    } else {
        $name = trim($_POST["name"]);
    }
    
    // Validate description
    if(empty(trim($_POST["description"]))){
        $description_err = "Please enter the product description.";
    } else {
        $description = trim($_POST["description"]);
    }
    
    // Validate price
    if(empty(trim($_POST["price"]))){
        $price_err = "Please enter the price.";
    } elseif(!is_numeric($_POST["price"]) || $_POST["price"] <= 0){
        $price_err = "Please enter a valid price.";
    } else {
        $price = trim($_POST["price"]);
    }
    
    // Validate category
    if(empty(trim($_POST["category"]))){
        $category_err = "Please select a category.";
    } else {
        $category = trim($_POST["category"]);
    }
    
    // Handle image upload if new image is selected
    $image_name = $current_image;
    if(isset($_FILES["image"]) && $_FILES["image"]["error"] == 0){
        $allowed = ["jpg" => "image/jpg", "jpeg" => "image/jpeg", "png" => "image/png"];
        $filename = $_FILES["image"]["name"];
        $filetype = $_FILES["image"]["type"];
        $filesize = $_FILES["image"]["size"];
        
        // Verify file extension
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if(!array_key_exists($ext, $allowed)){
            $image_err = "Please upload a valid image file (JPG, JPEG, PNG).";
        }
        
        // Verify file size - 5MB maximum
        $maxsize = 5 * 1024 * 1024;
        if($filesize > $maxsize){
            $image_err = "Image size must be less than 5MB.";
        }
        
        // Verify MYME type of the file
        if(in_array($filetype, $allowed) && empty($image_err)){
            // Create upload directory if it doesn't exist
            if(!file_exists("../uploads/products/")){
                mkdir("../uploads/products/", 0777, true);
            }
            
            // Delete old image if exists
            if(!empty($current_image) && file_exists("../uploads/products/".$current_image)){
                unlink("../uploads/products/".$current_image);
            }
            
            // Generate unique filename
            $image_name = uniqid() . "." . $ext;
            $uploadfile = "../uploads/products/" . $image_name;
            
            // Move the file
            if(!move_uploaded_file($_FILES["image"]["tmp_name"], $uploadfile)){
                $image_err = "Error uploading the image.";
            }
        } elseif(!empty($_FILES["image"]["error"]) && $_FILES["image"]["error"] != 4) {
            $image_err = "Error: Invalid file type.";
        }
    }
    
    // Check input errors before updating the database
    if(empty($name_err) && empty($description_err) && empty($price_err) && empty($category_err) && empty($image_err)){
        // Prepare an update statement
        $sql = "UPDATE products SET name=?, description=?, price=?, category=?, image=?, stock=?, status=? WHERE id=?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            // Set parameters
            $param_name = $name;
            $param_description = $description;
            $param_price = $price;
            $param_category = $category;
            $param_image = $image_name;
            $param_stock = isset($_POST["stock"]) ? $_POST["stock"] : 0;
            $param_status = isset($_POST["status"]) ? $_POST["status"] : "active";
            $param_id = $id;
            
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "ssdssisi", $param_name, $param_description, $param_price, $param_category, $param_image, $param_stock, $param_status, $param_id);
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                header("location: manage_products.php?msg=updated");
                exit();
            }
            mysqli_stmt_close($stmt);
        }
    }
} else {
    // Check existence of id parameter before processing further
    if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){
        $id = trim($_GET["id"]);
        
        // Prepare a select statement
        $sql = "SELECT * FROM products WHERE id = ?";
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "i", $param_id);
            $param_id = $id;
            
            if(mysqli_stmt_execute($stmt)){
                $result = mysqli_stmt_get_result($stmt);
                
                if(mysqli_num_rows($result) == 1){
                    $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
                    
                    $name = $row["name"];
                    $description = $row["description"];
                    $price = $row["price"];
                    $category = $row["category"];
                    $current_image = $row["image"];
                    $stock = $row["stock"];
                    $status = $row["status"];
                } else{
                    header("location: error.php");
                    exit();
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    } else{
        header("location: error.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - PawPoint Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .admin-header {
            background-color: #34495E;
        }
        .admin-nav {
            background-color: #2C3E50;
        }
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .form-control.is-invalid {
            border-color: #e74c3c;
        }
        textarea.form-control {
            height: 150px;
            resize: vertical;
        }
        .invalid-feedback {
            color: #e74c3c;
            font-size: 0.85em;
            margin-top: 5px;
        }
        .btn-primary {
            background-color: #3498db;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-primary:hover {
            background-color: #2980b9;
        }
        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #95a5a6;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-right: 10px;
        }
        .back-btn:hover {
            background-color: #7f8c8d;
        }
        .current-image {
            max-width: 200px;
            margin: 10px 0;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <?php include "header.php"; ?>
    
    <div class="container">
        <h2>Edit Product</h2>
        <p>Please update the product details.</p>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            
            <div class="form-group">
                <label>Name</label>
                <input type="text" name="name" class="form-control <?php echo (!empty($name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $name; ?>">
                <span class="invalid-feedback"><?php echo $name_err; ?></span>
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control <?php echo (!empty($description_err)) ? 'is-invalid' : ''; ?>"><?php echo $description; ?></textarea>
                <span class="invalid-feedback"><?php echo $description_err; ?></span>
            </div>
            
            <div class="form-group">
                <label>Price (₱)</label>
                <input type="number" step="0.01" name="price" class="form-control <?php echo (!empty($price_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $price; ?>">
                <span class="invalid-feedback"><?php echo $price_err; ?></span>
            </div>
            
            <div class="form-group">
                <label>Category</label>
                <select name="category" class="form-control <?php echo (!empty($category_err)) ? 'is-invalid' : ''; ?>">
                    <option value="">Select Category</option>
                    <option value="Food" <?php echo ($category == "Food") ? "selected" : ""; ?>>Food</option>
                    <option value="Treats" <?php echo ($category == "Treats") ? "selected" : ""; ?>>Treats</option>
                    <option value="Toys" <?php echo ($category == "Toys") ? "selected" : ""; ?>>Toys</option>
                    <option value="Accessories" <?php echo ($category == "Accessories") ? "selected" : ""; ?>>Accessories</option>
                    <option value="Health" <?php echo ($category == "Health") ? "selected" : ""; ?>>Health & Wellness</option>
                    <option value="Grooming" <?php echo ($category == "Grooming") ? "selected" : ""; ?>>Grooming</option>
                </select>
                <span class="invalid-feedback"><?php echo $category_err; ?></span>
            </div>
            
            <div class="form-group">
                <label>Stock</label>
                <input type="number" name="stock" class="form-control" value="<?php echo $stock; ?>" min="0">
            </div>
            
            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="active" <?php echo ($status == "active") ? "selected" : ""; ?>>Active</option>
                    <option value="inactive" <?php echo ($status == "inactive") ? "selected" : ""; ?>>Inactive</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Current Image</label><br>
                <?php if(!empty($current_image) && file_exists("../uploads/products/".$current_image)): ?>
                    <img src="../uploads/products/<?php echo $current_image; ?>" alt="Current product image" class="current-image">
                <?php else: ?>
                    <p>No image currently set</p>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label>Change Product Image</label>
                <input type="file" name="image" class="form-control <?php echo (!empty($image_err)) ? 'is-invalid' : ''; ?>">
                <span class="invalid-feedback"><?php echo $image_err; ?></span>
                <small>Leave empty to keep current image</small>
            </div>
            
            <div class="form-group">
                <a href="manage_products.php" class="back-btn">Cancel</a>
                <input type="submit" class="btn-primary" value="Update Product">
            </div>
        </form>
    </div>
</body>
</html>
