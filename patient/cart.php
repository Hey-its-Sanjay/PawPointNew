<?php
// Initialize the session
session_start();
 
// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["patient_id"])){
    header("location: login.php");
    exit;
}

require_once "../includes/functions.php";

$success_msg = "";
$error_msg = "";

// Handle quantity updates
if(isset($_POST['update_cart'])) {
    foreach($_POST['quantities'] as $cart_id => $quantity) {
        $quantity = (int)$quantity;
        if($quantity > 0) {
            // Verify stock availability
            $check_sql = "SELECT p.stock FROM cart c 
                         JOIN products p ON c.product_id = p.id 
                         WHERE c.id = ? AND c.patient_id = ?";
            
            if($check_stmt = mysqli_prepare($conn, $check_sql)){
                mysqli_stmt_bind_param($check_stmt, "ii", $cart_id, $_SESSION["patient_id"]);
                mysqli_stmt_execute($check_stmt);
                $check_result = mysqli_stmt_get_result($check_stmt);
                
                if($row = mysqli_fetch_assoc($check_result)) {
                    if($quantity <= $row['stock']) {
                        $update_sql = "UPDATE cart SET quantity = ? WHERE id = ? AND patient_id = ?";
                        if($update_stmt = mysqli_prepare($conn, $update_sql)){
                            mysqli_stmt_bind_param($update_stmt, "iii", $quantity, $cart_id, $_SESSION["patient_id"]);
                            mysqli_stmt_execute($update_stmt);
                        }
                    } else {
                        $error_msg = "Some items exceed available stock.";
                    }
                }
            }
        }
    }
    if(empty($error_msg)) {
        $success_msg = "Cart updated successfully.";
    }
}

// Handle item removal
if(isset($_GET['remove']) && !empty($_GET['remove'])) {
    $remove_sql = "DELETE FROM cart WHERE id = ? AND patient_id = ?";
    if($remove_stmt = mysqli_prepare($conn, $remove_sql)){
        mysqli_stmt_bind_param($remove_stmt, "ii", $_GET['remove'], $_SESSION["patient_id"]);
        if(mysqli_stmt_execute($remove_stmt)){
            $success_msg = "Item removed from cart.";
        }
    }
}

// Fetch cart items
$cart_items = array();
$total = 0;

$sql = "SELECT c.id, c.quantity, p.* FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.patient_id = ? AND p.status = 'active'
        ORDER BY p.name";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $_SESSION["patient_id"]);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while($row = mysqli_fetch_assoc($result)){
        $row['subtotal'] = $row['price'] * $row['quantity'];
        $total += $row['subtotal'];
        $cart_items[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - PawPoint</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .cart-container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
        }
        .cart-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .cart-table th {
            background-color: #34495E;
            color: white;
            padding: 12px;
            text-align: left;
        }
        .cart-table td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            vertical-align: middle;
        }
        .cart-table tr:hover {
            background-color: #f5f5f5;
        }
        .product-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
        }
        .quantity-input {
            width: 60px;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
        }
        .remove-btn {
            color: #e74c3c;
            text-decoration: none;
            font-size: 0.9em;
        }
        .remove-btn:hover {
            text-decoration: underline;
        }
        .update-cart {
            background-color: #3498db;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .update-cart:hover {
            background-color: #2980b9;
        }
        .cart-summary {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .cart-total {
            font-size: 1.2em;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        .checkout-btn {
            display: inline-block;
            background-color: #2ecc71;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
        }
        .checkout-btn:hover {
            background-color: #27ae60;
        }
        .checkout-btn.disabled {
            background-color: #95a5a6;
            cursor: not-allowed;
        }
        .empty-cart {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }
        .empty-cart p {
            margin-bottom: 20px;
        }
        .continue-shopping {
            display: inline-block;
            background-color: #3498db;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <header>
        <h1>PawPoint</h1>
        <p>Your Pet's Healthcare Companion</p>
    </header>     <nav>
        <?php
        // Get cart count
        $cart_count = 0;
        $cart_sql = "SELECT SUM(quantity) as total FROM cart WHERE patient_id = ?";
        if ($cart_stmt = mysqli_prepare($conn, $cart_sql)) {
            mysqli_stmt_bind_param($cart_stmt, "i", $_SESSION["patient_id"]);
            mysqli_stmt_execute($cart_stmt);
            $cart_result = mysqli_stmt_get_result($cart_stmt);
            if ($cart_row = mysqli_fetch_assoc($cart_result)) {
                $cart_count = $cart_row['total'] ?: 0;
            }
        }
        ?>
        <ul>
            <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> My Profile</a></li>
            <li><a href="appointments.php"><i class="fas fa-calendar"></i> My Appointments</a></li>
            <li><a href="book_appointment.php"><i class="fas fa-calendar-plus"></i> Book Appointment</a></li>
            <li><a href="products.php" class="active"><i class="fas fa-box"></i> Products</a></li>
            <li><a href="cart.php" style="position: relative;">
                <i class="fas fa-shopping-cart"></i> Cart
                <?php if ($cart_count > 0): ?>
                    <span class="cart-badge"><?php echo $cart_count; ?></span>
                <?php endif; ?>
            </a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>
    
    <div class="cart-container">
        <h2>Shopping Cart</h2>
        
        <?php if(!empty($success_msg)): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        
        <?php if(!empty($error_msg)): ?>
            <div class="alert alert-danger"><?php echo $error_msg; ?></div>
        <?php endif; ?>
        
        <?php if(count($cart_items) > 0): ?>
            <form method="post" action="">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($cart_items as $item): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 15px;">
                                        <img src="<?php echo !empty($item['image']) ? '../uploads/products/'.$item['image'] : '../uploads/products/default-product.jpg'; ?>" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                             class="product-img">
                                        <div>
                                            <strong><?php echo htmlspecialchars($item['name']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($item['category']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                <td>
                                    <input type="number" 
                                           name="quantities[<?php echo $item['id']; ?>]" 
                                           value="<?php echo $item['quantity']; ?>" 
                                           min="1" 
                                           max="<?php echo $item['stock']; ?>" 
                                           class="quantity-input">
                                </td>
                                <td>₱<?php echo number_format($item['subtotal'], 2); ?></td>
                                <td>
                                    <a href="cart.php?remove=<?php echo $item['id']; ?>" 
                                       class="remove-btn" 
                                       onclick="return confirm('Are you sure you want to remove this item?')">
                                        Remove
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div style="text-align: right;">
                    <button type="submit" name="update_cart" class="update-cart">Update Cart</button>
                </div>
                
                <div class="cart-summary">
                    <div class="cart-total">Total: ₱<?php echo number_format($total, 2); ?></div>
                    <a href="checkout.php" class="checkout-btn">
                        Proceed to Checkout
                    </a>
                </div>
            </form>
        <?php else: ?>
            <div class="empty-cart">
                <p>Your cart is empty</p>
                <a href="products.php" class="continue-shopping">Continue Shopping</a>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
    // Disable form submission when pressing Enter
    document.querySelector('form').addEventListener('keypress', function(e) {
        if (e.keyCode === 13) {
            e.preventDefault();
        }
    });
    </script>
</body>
</html>
