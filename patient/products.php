<?php
// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["patient_id"])){
    header("location: login.php");
    exit;
}

require_once "../includes/functions.php";

// Get filter parameters
$category = isset($_GET['category']) ? $_GET['category'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name_asc';

// Prepare the base SQL query
$sql = "SELECT * FROM products WHERE status = 'active'";

// Add category filter if selected
if(!empty($category)) {
    $sql .= " AND category = '" . mysqli_real_escape_string($conn, $category) . "'";
}

// Add search filter if provided
if(!empty($search)) {
    $sql .= " AND (name LIKE '%" . mysqli_real_escape_string($conn, $search) . "%' OR 
              description LIKE '%" . mysqli_real_escape_string($conn, $search) . "%')";
}

// Add sorting
switch($sort) {
    case 'price_asc':
        $sql .= " ORDER BY price ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY price DESC";
        break;
    case 'name_desc':
        $sql .= " ORDER BY name DESC";
        break;
    default:
        $sql .= " ORDER BY name ASC";
}

// Get products
$products = array();
$result = mysqli_query($conn, $sql);

if($result && mysqli_num_rows($result) > 0){
    while($row = mysqli_fetch_assoc($result)){
        $products[] = $row;
    }
}

// Get all categories for filter
$categories = array();
$cat_sql = "SELECT DISTINCT category FROM products WHERE status = 'active' ORDER BY category";
$cat_result = mysqli_query($conn, $cat_sql);

if($cat_result && mysqli_num_rows($cat_result) > 0){
    while($row = mysqli_fetch_assoc($cat_result)){
        $categories[] = $row['category'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - PawPoint</title>
    <link rel="stylesheet" href="../css/style.css">    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: #e74c3c;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
            font-weight: bold;
            min-width: 18px;
            text-align: center;
        }

        nav ul li a {
            position: relative;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        nav ul li a i {
            font-size: 16px;
        }

        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }

        .section {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        .section-title {
            color: #2C3E50;
            margin-bottom: 20px;
            font-size: 1.5rem;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498DB;
        }

        /* Product specific styles */
        .filters {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }

        .filter-group {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-box,
        .filter-select {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 0.9rem;
            min-width: 200px;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .product-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .product-card:hover {
            transform: translateY(-5px);
        }        .product-image-container {
            width: 100%;
            height: 200px;
            overflow: hidden;
            position: relative;
            background-color: #f8f9f9;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
        }

        .product-image {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
            display: block;
            margin: auto;
        }

        .product-card:hover .product-image {            transform: scale(1.1);
        }

        .product-info {
            padding: 15px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            cursor: pointer;
        }

        .product-description {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 10px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: #fff;
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            max-width: 600px;
            position: relative;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-close {
            position: absolute;
            right: 15px;
            top: 10px;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }

        .modal-close:hover {
            color: #333;
        }

        .modal-image {
            width: 100%;
            max-height: 300px;
            object-fit: contain;
            margin-bottom: 20px;
        }

        .product-name {
            font-weight: bold;
            font-size: 1.1rem;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .product-category {
            color: #4a7c59;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .product-price {
            font-size: 1.2rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .stock-status {
            font-size: 0.9rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .stock-status i {
            font-size: 1rem;
        }

        .in-stock { color: #4a7c59; }
        .low-stock { color: #f39c12; }
        .out-of-stock { color: #e74c3c; }

        .add-to-cart {
            width: 100%;
            padding: 8px 15px;
            background-color: #3498DB;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: bold;
            transition: background-color 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: auto;
        }

        .add-to-cart:hover {
            background-color: #3e6b4a;
        }

        .add-to-cart:disabled {
            background-color: #95a5a6;
            cursor: not-allowed;
        }

        @media (max-width: 768px) {
            .dashboard-container {
                padding: 10px;
            }

            .section {
                padding: 15px;
            }

            .filters {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-group {
                width: 100%;
            }

            .search-box,
            .filter-select {
                width: 100%;
                min-width: unset;
            }
        }
    </style>
</head>
<body>   <header>
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
        <div class="container">
        <div class="section">
            <h2 class="section-title">Our Products</h2>
            
            <div class="filters">
                <div class="filter-group">
                    <input type="text" 
                           class="search-box" 
                           placeholder="Search products..." 
                           value="<?php echo htmlspecialchars($search); ?>"
                           onchange="updateFilters()">
                    
                    <select class="filter-select" onchange="updateFilters()" id="category">
                        <option value="">All Categories</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" 
                                    <?php echo $category === $cat ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <select class="filter-select" onchange="updateFilters()" id="sort">
                        <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Name (A-Z)</option>
                        <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Name (Z-A)</option>
                        <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Price (Low to High)</option>
                        <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price (High to Low)</option>
                    </select>
                </div>
            </div>
            
            <?php if (empty($products)): ?>
            <div style="text-align: center; padding: 20px; color: #666;">
                <i class="fas fa-box-open" style="font-size: 3rem; color: #4a7c59; margin-bottom: 10px;"></i>
                <p>No products found. Try adjusting your search or filters.</p>
            </div>
            <?php else: ?>
            <div class="products-grid">
                <?php foreach($products as $product): ?>                    <div class="product-card">
                        <div class="product-image-container">
                            <?php 
                            $image_path = '../uploads/products/' . $product['image'];
                            $default_image = '../uploads/products/dogFood.png';
                            
                            // Ensure the uploads directory exists
                            if (!file_exists('../uploads/products/')) {
                                mkdir('../uploads/products/', 0777, true);
                            }
                            
                            // Create default product image if it doesn't exist
                            if (!file_exists($default_image)) {
                                copy('../uploads/profile_pictures/default.jpg', $default_image);
                            }
                            ?>
                            <img src="<?php echo !empty($product['image']) ? $image_path : $default_image; ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                 class="product-image"
                                 loading="lazy"
                                 onerror="this.onerror=null; this.src='<?php echo $default_image; ?>'">
                        </div>
                        
                        <div class="product-info" onclick="showProductDetails(<?php 
                            echo htmlspecialchars(json_encode([
                                'id' => $product['id'],
                                'name' => $product['name'],
                                'category' => $product['category'],
                                'price' => $product['price'],
                                'description' => $product['description'],
                                'image' => !empty($product['image']) ? $image_path : $default_image,
                                'stock' => $product['stock']
                            ])); 
                        ?>)">
                            <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                            <div class="product-category">
                                <i class="fas fa-tag"></i> 
                                <?php echo htmlspecialchars($product['category']); ?>
                            </div>
                            <div class="product-price">₱<?php echo number_format($product['price'], 2); ?></div>
                            <div class="product-description"><?php echo htmlspecialchars($product['description']); ?></div>
                            
                            <div class="stock-status <?php 
                                if($product['stock'] > 10) echo 'in-stock';
                                elseif($product['stock'] > 0) echo 'low-stock';
                                else echo 'out-of-stock';
                            ?>">
                                <i class="fas <?php 
                                    if($product['stock'] > 10) echo 'fa-check-circle';
                                    elseif($product['stock'] > 0) echo 'fa-exclamation-circle';
                                    else echo 'fa-times-circle';
                                ?>"></i>
                                <?php 
                                if($product['stock'] > 10) echo 'In Stock';
                                elseif($product['stock'] > 0) echo 'Low Stock (' . $product['stock'] . ' left)';
                                else echo 'Out of Stock';
                                ?>
                            </div>
                            
                            <button class="add-to-cart" 
                                    onclick="addToCart(<?php echo $product['id']; ?>)"
                                    <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>>
                                <i class="fas fa-shopping-cart"></i>
                                <?php echo $product['stock'] > 0 ? 'Add to Cart' : 'Out of Stock'; ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>            <?php endif; ?>
        </div>
    </div>
    
    <!-- Product Details Modal -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModal()">&times;</span>
            <img id="modalImage" src="" alt="" class="modal-image">
            <h2 id="modalName" class="product-name"></h2>
            <div id="modalCategory" class="product-category"></div>
            <div id="modalPrice" class="product-price"></div>
            <p id="modalDescription" style="margin: 15px 0;"></p>
            <div id="modalStock" class="stock-status"></div>
            <button id="modalAddToCart" class="add-to-cart">
                <i class="fas fa-shopping-cart"></i> Add to Cart
            </button>
        </div>
    </div>

    <script>
    function updateFilters() {
        const search = document.querySelector('.search-box').value;
        const category = document.getElementById('category').value;
        const sort = document.getElementById('sort').value;
        
        let url = 'products.php?';
        if(search) url += `search=${encodeURIComponent(search)}&`;
        if(category) url += `category=${encodeURIComponent(category)}&`;
        if(sort) url += `sort=${encodeURIComponent(sort)}`;
        
        window.location.href = url;
    }
    
    function updateCartBadge(count) {
        const cartBadge = document.querySelector('.cart-badge');
        if (count > 0) {
            if (!cartBadge) {
                const cartLink = document.querySelector('a[href="cart.php"]');
                const badge = document.createElement('span');
                badge.className = 'cart-badge';
                badge.textContent = count;
                cartLink.appendChild(badge);
            } else {
                cartBadge.textContent = count;
            }
        } else if (cartBadge) {
            cartBadge.remove();
        }
    }

    function showProductDetails(product) {
        document.getElementById('modalImage').src = product.image;
        document.getElementById('modalImage').alt = product.name;
        document.getElementById('modalName').textContent = product.name;
        document.getElementById('modalCategory').innerHTML = `<i class="fas fa-tag"></i> ${product.category}`;
        document.getElementById('modalPrice').textContent = `₱${product.price.toLocaleString('en-US', { minimumFractionDigits: 2 })}`;
        document.getElementById('modalDescription').textContent = product.description;

        const stockStatus = document.getElementById('modalStock');
        let stockClass, stockIcon, stockText;
        if (product.stock > 10) {
            stockClass = 'in-stock';
            stockIcon = 'fa-check-circle';
            stockText = 'In Stock';
        } else if (product.stock > 0) {
            stockClass = 'low-stock';
            stockIcon = 'fa-exclamation-circle';
            stockText = `Low Stock (${product.stock} left)`;
        } else {
            stockClass = 'out-of-stock';
            stockIcon = 'fa-times-circle';
            stockText = 'Out of Stock';
        }
        stockStatus.className = `stock-status ${stockClass}`;
        stockStatus.innerHTML = `<i class="fas ${stockIcon}"></i> ${stockText}`;

        const addToCartBtn = document.getElementById('modalAddToCart');
        if (product.stock > 0) {
            addToCartBtn.removeAttribute('disabled');
            addToCartBtn.onclick = () => addToCart(product.id);
        } else {
            addToCartBtn.setAttribute('disabled', 'disabled');
            addToCartBtn.textContent = 'Out of Stock';
        }

        document.getElementById('productModal').style.display = 'block';
    }

    function closeModal() {
        document.getElementById('productModal').style.display = 'none';
    }

    // Close modal when clicking outside of it
    window.onclick = function(event) {
        const modal = document.getElementById('productModal');
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    }

    function addToCart(productId) {
        fetch('add_to_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `product_id=${productId}`
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                // Update cart badge with new count
                if (data.cartCount !== undefined) {
                    updateCartBadge(data.cartCount);
                }
                // Close the modal after adding to cart
                closeModal();
            } else {
                alert(data.message || 'Error adding product to cart');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error adding product to cart');
        });
    }
    </script>
</body>
</html>
