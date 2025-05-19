<?php
// Initialize the session
session_start();
 
// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["patient_id"])){
    echo json_encode(['success' => false, 'message' => 'Please log in to add items to cart']);
    exit;
}

require_once "../includes/functions.php";

// Set up response header
header('Content-Type: application/json');

// Check if product_id is provided
if(!isset($_POST['product_id']) || empty($_POST['product_id'])){
    echo json_encode(['success' => false, 'message' => 'Invalid product']);
    exit;
}

$product_id = (int)$_POST['product_id'];
$patient_id = $_SESSION['patient_id'];
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

// Function to get cart count
function getCartCount($conn, $patient_id) {
    $count_sql = "SELECT SUM(quantity) as total FROM cart WHERE patient_id = ?";
    $count_stmt = mysqli_prepare($conn, $count_sql);
    mysqli_stmt_bind_param($count_stmt, "i", $patient_id);
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
    $count_row = mysqli_fetch_assoc($count_result);
    return $count_row['total'] ?: 0;
}

// Validate product exists and has stock
$sql = "SELECT * FROM products WHERE id = ? AND status = 'active' AND stock > 0";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) == 1){
            $product = mysqli_fetch_array($result);
            
            // Check if item is already in cart
            $cart_sql = "SELECT id, quantity FROM cart WHERE patient_id = ? AND product_id = ?";
            if($cart_stmt = mysqli_prepare($conn, $cart_sql)){
                mysqli_stmt_bind_param($cart_stmt, "ii", $patient_id, $product_id);
                mysqli_stmt_execute($cart_stmt);
                $cart_result = mysqli_stmt_get_result($cart_stmt);
                
                if(mysqli_num_rows($cart_result) > 0){
                    // Update existing cart item
                    $cart_item = mysqli_fetch_array($cart_result);
                    $new_quantity = $cart_item['quantity'] + $quantity;
                    
                    // Check if new quantity exceeds stock
                    if($new_quantity > $product['stock']){
                        echo json_encode(['success' => false, 'message' => 'Not enough stock available']);
                        exit;
                    }
                    
                    $update_sql = "UPDATE cart SET quantity = ? WHERE id = ?";
                    if($update_stmt = mysqli_prepare($conn, $update_sql)){
                        mysqli_stmt_bind_param($update_stmt, "ii", $new_quantity, $cart_item['id']);
                        if(mysqli_stmt_execute($update_stmt)){
                            $cart_count = getCartCount($conn, $patient_id);
                            echo json_encode(['success' => true, 'message' => 'Cart updated successfully', 'cartCount' => $cart_count]);
                        } else {
                            echo json_encode(['success' => false, 'message' => 'Error updating cart']);
                        }
                    }
                } else {
                    // Add new cart item
                    if($quantity > $product['stock']){
                        echo json_encode(['success' => false, 'message' => 'Not enough stock available']);
                        exit;
                    }
                    
                    $insert_sql = "INSERT INTO cart (patient_id, product_id, quantity) VALUES (?, ?, ?)";
                    if($insert_stmt = mysqli_prepare($conn, $insert_sql)){
                        mysqli_stmt_bind_param($insert_stmt, "iii", $patient_id, $product_id, $quantity);
                        if(mysqli_stmt_execute($insert_stmt)){
                            $cart_count = getCartCount($conn, $patient_id);
                            echo json_encode(['success' => true, 'message' => 'Product added to cart', 'cartCount' => $cart_count]);
                        } else {
                            echo json_encode(['success' => false, 'message' => 'Error adding to cart']);
                        }
                    }
                }
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Product not available']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error checking product']);
    }
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['success' => false, 'message' => 'Error processing request']);
}
?>
