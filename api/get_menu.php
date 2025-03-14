<?php
require_once '../config/database.php';

try {
    $stmt = $conn->query("
        SELECT p.*, c.name as category_name 
        FROM products p 
        JOIN categories c ON p.category_id = c.category_id 
        WHERE p.is_available = 1 
        ORDER BY c.name, p.name
    ");
    
    $currentCategory = '';
    $output = '';
    
    while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($currentCategory !== $product['category_name']) {
            if ($currentCategory !== '') {
                $output .= '</div>'; // Close previous category row
            }
            $currentCategory = $product['category_name'];
            $output .= '<h3 class="col-12 mt-4 mb-3">' . htmlspecialchars($currentCategory) . '</h3><div class="row">';
        }
        
        $output .= '
        <div class="col-md-4 col-lg-3 mb-4">
            <div class="card h-100">
                ' . ($product['image_url'] ? '<img src="' . htmlspecialchars($product['image_url']) . '" class="card-img-top" alt="' . htmlspecialchars($product['name']) . '">' : '') . '
                <div class="card-body">
                    <h5 class="card-title">' . htmlspecialchars($product['name']) . '</h5>
                    <p class="card-text">' . htmlspecialchars($product['description']) . '</p>
                    <p class="card-text"><strong>₱' . number_format($product['price'], 2) . '</strong></p>
                    <button class="btn btn-primary w-100" onclick="addToCart(' . $product['product_id'] . ', \'' . addslashes($product['name']) . '\', ' . $product['price'] . ')">
                        Add to Cart
                    </button>
                </div>
            </div>
        </div>';
    }
    
    if ($currentCategory !== '') {
        $output .= '</div>'; // Close last category row
    }
    
    echo $output;
} catch (PDOException $e) {
    http_response_code(500);
    echo "Error: " . $e->getMessage();
}
?> 