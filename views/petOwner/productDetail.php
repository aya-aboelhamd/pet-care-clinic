<?php
// views/petOwner/productDetail.php

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header('Location: /pet-care-clinic/views/Auth/login.php');
    exit();
}

$productId = $_GET['id'] ?? null;
if (!$productId) {
    header('Location: marketplace.php');
    exit();
}

require_once __DIR__ . '/../../models/ProductModel.php';
require_once __DIR__ . '/../../models/PetModel.php';

$productModel = new ProductModel();
$petModel = new PetModel();

$product = $productModel->getProductById($productId);
$pets = $petModel->getPetsByOwner($_SESSION['user_id']);

if (!$product) {
    echo "<h1>Product not found</h1>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> - Product Details</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-green: #589A64;
            --bg-light: #F8FAF8;
            --text-dark: #1A1A1A;
            --text-gray: #666;
            --border-color: #E0E0E0;
        }

        * { font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-light); }
        
        .product-image {
            background: white;
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            border: 1px solid var(--border-color);
        }
        
        .product-image img {
            max-width: 100%;
            height: auto;
        }
        
        .product-info {
            background: white;
            border-radius: 16px;
            padding: 24px;
            border: 1px solid var(--border-color);
        }
        
        .price {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary-green);
        }
        
        .btn-add {
            background: var(--primary-green);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            width: 100%;
        }
        
        .btn-add:hover {
            opacity: 0.9;
            color: white;
        }
        
        .warning-box {
            background: #FEF3C7;
            border: 1px solid #FDE68A;
            border-radius: 8px;
            padding: 12px;
            margin-top: 15px;
        }
        
        .error-box {
            background: #FEE2E2;
            border: 1px solid #FECACA;
            border-radius: 8px;
            padding: 12px;
            margin-top: 15px;
        }
        
        .success-box {
            background: #ECFDF5;
            border: 1px solid #D1FAE5;
            border-radius: 8px;
            padding: 12px;
            margin-top: 15px;
        }
        
        .prescription-badge {
            background: #E0F2FE;
            color: #0284C7;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
    </style>
</head>
<body>

<div class="container mt-5 mb-5">
    <div class="row">
        <div class="col-md-6">
            <div class="product-image">
                <img src="https://via.placeholder.com/400x300?text=Product+Image" alt="<?= htmlspecialchars($product['name']) ?>">
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="product-info">
                <?php if (isset($product['requires_prescription']) && $product['requires_prescription'] == 1): ?>
                    <span class="prescription-badge"><i class="fa-solid fa-prescription"></i> Prescription Required</span>
                <?php endif; ?>
                
                <h1 class="mt-2"><?= htmlspecialchars($product['name']) ?></h1>
                <div class="price"><?= number_format($product['price'], 2) ?> EGP</div>
                
                <hr>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Select your pet:</label>
                    <select id="pet_id" class="form-control">
                        <option value="">-- Select Pet --</option>
                        <?php foreach ($pets as $pet): ?>
                            <option value="<?= $pet['id'] ?>" data-pet-name="<?= htmlspecialchars($pet['name']) ?>">
                                <?= htmlspecialchars($pet['name']) ?> (<?= htmlspecialchars($pet['species'] ?? 'Pet') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Quantity:</label>
                    <input type="number" id="quantity" class="form-control" value="1" min="1" style="width: 100px;">
                </div>
                
                <div id="message"></div>
                
                <button class="btn-add mt-3" onclick="addToCart()">
                    <i class="fa-solid fa-cart-plus"></i> Add to Cart
                </button>
                
                <a href="marketplace.php" class="btn btn-outline-secondary mt-3 w-100">
                    <i class="fa-solid fa-arrow-left"></i> Continue Shopping
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    function addToCart() {
        let petId = document.getElementById('pet_id').value;
        let quantity = document.getElementById('quantity').value;
        let messageDiv = document.getElementById('message');
        
        if (!petId) {
            messageDiv.innerHTML = '<div class="warning-box"><i class="fa-solid fa-triangle-exclamation"></i> Please select a pet first</div>';
            return;
        }
        
        messageDiv.innerHTML = '<div class="success-box"><i class="fa-solid fa-spinner fa-spin"></i> Checking...</div>';
        
        fetch('/pet-care-clinic/controllers/MarketplaceController.php?action=add_to_cart', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'product_id=<?= $productId ?>&pet_id=' + petId + '&quantity=' + quantity
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                messageDiv.innerHTML = '<div class="success-box"><i class="fa-solid fa-check-circle"></i> ' + data.message + '</div>';
                // Update cart count if needed
                if (data.cart_count) {
                    let cartBadge = document.getElementById('cart-count');
                    if (cartBadge) cartBadge.innerText = data.cart_count;
                }
            } else if (data.blocked) {
                messageDiv.innerHTML = '<div class="error-box"><i class="fa-solid fa-ban"></i> ' + data.error + '</div>';
            } else {
                messageDiv.innerHTML = '<div class="error-box"><i class="fa-solid fa-exclamation-circle"></i> ' + data.error + '</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            messageDiv.innerHTML = '<div class="error-box"><i class="fa-solid fa-wifi"></i> Connection error. Please try again.</div>';
        });
    }
</script>

</body>
</html>