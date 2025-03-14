<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    header('Location: ../login.php');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add':
                    // Handle file upload
                    $image_url = '';
                    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                        $upload_dir = '../uploads/products/';
                        if (!file_exists($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
                        
                        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                        $new_filename = uniqid() . '.' . $file_extension;
                        $target_path = $upload_dir . $new_filename;
                        
                        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                            $image_url = 'uploads/products/' . $new_filename;
                        }
                    }

                    $stmt = $conn->prepare("INSERT INTO products (name, description, price, category_id, image_url) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['name'],
                        $_POST['description'],
                        $_POST['price'],
                        $_POST['category_id'],
                        $image_url
                    ]);
                    $_SESSION['success'] = "Product added successfully!";
                    break;

                case 'edit':
                    $image_url = $_POST['current_image'];
                    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                        $upload_dir = '../uploads/products/';
                        if (!file_exists($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
                        
                        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                        $new_filename = uniqid() . '.' . $file_extension;
                        $target_path = $upload_dir . $new_filename;
                        
                        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                            // Delete old image if exists
                            if (!empty($_POST['current_image'])) {
                                @unlink('../' . $_POST['current_image']);
                            }
                            $image_url = 'uploads/products/' . $new_filename;
                        }
                    }

                    $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, category_id = ?, image_url = ? WHERE product_id = ?");
                    $stmt->execute([
                        $_POST['name'],
                        $_POST['description'],
                        $_POST['price'],
                        $_POST['category_id'],
                        $image_url,
                        $_POST['product_id']
                    ]);
                    $_SESSION['success'] = "Product updated successfully!";
                    break;

                case 'delete':
                    // Delete product image if exists
                    $stmt = $conn->prepare("SELECT image_url FROM products WHERE product_id = ?");
                    $stmt->execute([$_POST['product_id']]);
                    $product = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($product && !empty($product['image_url'])) {
                        @unlink('../' . $product['image_url']);
                    }

                    $stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
                    $stmt->execute([$_POST['product_id']]);
                    $_SESSION['success'] = "Product deleted successfully!";
                    break;

                case 'toggle_status':
                    // Check if the category is disabled
                    $stmt = $conn->prepare("SELECT is_disabled FROM categories WHERE category_id = (SELECT category_id FROM products WHERE product_id = ?)");
                    $stmt->execute([$_POST['product_id']]);
                    $category = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($category['is_disabled'] && $_POST['is_disabled'] == '1') {
                        $_SESSION['error'] = "Cannot enable product while its category is disabled.";
                    } else {
                        $stmt = $conn->prepare("UPDATE products SET is_disabled = ? WHERE product_id = ?");
                        $stmt->execute([
                            $_POST['is_disabled'] == '1' ? 0 : 1,
                            $_POST['product_id']
                        ]);
                        $_SESSION['success'] = "Product status updated successfully!";
                    }
                    break;
            }
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    
    // Redirect to prevent form resubmission
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Get all products with their categories
try {
    $stmt = $conn->query("
        SELECT p.*, c.name as category_name, c.is_disabled as category_disabled 
        FROM products p 
        JOIN categories c ON p.category_id = c.category_id 
        ORDER BY c.name, p.name
    ");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all active categories for the dropdown
    $stmt = $conn->query("SELECT * FROM categories ORDER BY name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>


    <style>

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .card-header {
            background-color: white;
            border-bottom: 1px solid #eee;
            padding: 1.5rem;
            border-radius: 15px 15px 0 0 !important;
        }

        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 10px;
        }

        .product-card {
            transition: transform 0.3s ease;
        }

        .product-card:hover {
            transform: translateY(-5px);
        }

        .btn-icon {
            width: 35px;
            height: 35px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-icon:hover {
            transform: translateY(-2px);
        }

        .modal-content {
            border-radius: 15px;
            border: none;
        }

        .modal-header {
            border-bottom: 1px solid #eee;
            padding: 1.5rem;
        }

        .modal-footer {
            border-top: 1px solid #eee;
            padding: 1.5rem;
        }

        .form-control, .form-select {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            border: 1px solid #e0e0e0;
        }

        .form-control:focus, .form-select:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 0.2rem rgba(78,115,223,0.25);
        }

        .alert {
            border-radius: 10px;
            border: none;
        }

        .badge {
            padding: 0.5em 1em;
            border-radius: 6px;
            font-weight: 500;
        }
    </style>

    <?php include 'components/header.php'; ?>
    <?php include 'components/navbar.php'; ?>

    <div class="container mt-4">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Manage Products</h5>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                    <i class="fas fa-plus me-2"></i>Add New Product
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                            <tr class="product-card <?php echo ($product['is_disabled'] || $product['category_disabled']) ? 'table-danger' : ''; ?>">
                                <td class="align-middle">
                                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                                         class="product-image" style="width: 50px; height: 50px; object-fit: cover;">
                                </td>
                                <td class="align-middle"><?php echo htmlspecialchars($product['name']); ?></td>
                                <td class="align-middle">
                                    <?php echo htmlspecialchars($product['category_name']); ?>
                                    <?php if ($product['category_disabled']): ?>
                                        <span class="badge bg-danger">Category Disabled</span>
                                    <?php endif; ?>
                                </td>
                                <td class="align-middle">₱<?php echo number_format($product['price'], 2); ?></td>
                                <td class="align-middle">
                                    <span class="badge <?php echo ($product['is_disabled'] || $product['category_disabled']) ? 'bg-danger' : 'bg-success'; ?>">
                                        <?php 
                                        if ($product['category_disabled']) {
                                            echo 'Category Disabled';
                                        } else {
                                            echo $product['is_disabled'] ? 'Disabled' : 'Active';
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td class="align-middle">
                                    <button class="btn btn-icon btn-warning me-2" 
                                            onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if (!$product['category_disabled']): ?>
                                    <button class="btn btn-icon <?php echo $product['is_disabled'] ? 'btn-success' : 'btn-secondary'; ?> me-2"
                                            onclick="toggleProductStatus(<?php echo $product['product_id']; ?>, <?php echo $product['is_disabled']; ?>)">
                                        <i class="fas <?php echo $product['is_disabled'] ? 'fa-check' : 'fa-ban'; ?>"></i>
                                    </button>
                                    <?php endif; ?>
                                    <button class="btn btn-icon btn-danger" 
                                            onclick="deleteProduct(<?php echo $product['product_id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['category_id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price</label>
                            <input type="number" class="form-control" name="price" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Image</label>
                            <input type="file" class="form-control" name="image" accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div class="modal fade" id="editProductModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="product_id" id="edit_product_id">
                    <input type="hidden" name="current_image" id="edit_current_image">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="name" id="edit_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category_id" id="edit_category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['category_id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price</label>
                            <input type="number" class="form-control" name="price" id="edit_price" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Current Image</label>
                            <div id="edit_image_preview" class="mb-2"></div>
                            <input type="file" class="form-control" name="image" accept="image/*">
                            <small class="text-muted">Leave empty to keep current image</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Product Modal -->
    <div class="modal fade" id="deleteProductModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="product_id" id="delete_product_id">
                    <div class="modal-body">
                        <p>Are you sure you want to delete <strong id="delete_product_name"></strong>?</p>
                        <p class="text-danger mb-0">This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php include 'components/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editProduct(product) {
            document.getElementById('edit_product_id').value = product.product_id;
            document.getElementById('edit_name').value = product.name;
            document.getElementById('edit_category_id').value = product.category_id;
            document.getElementById('edit_description').value = product.description;
            document.getElementById('edit_price').value = product.price;
            document.getElementById('edit_current_image').value = product.image_url;

            const imagePreview = document.getElementById('edit_image_preview');
            imagePreview.innerHTML = '';
            if (product.image_url) {
                const img = document.createElement('img');
                img.src = '../' + product.image_url;
                img.className = 'product-image';
                img.alt = product.name;
                imagePreview.appendChild(img);
            }

            const editModal = new bootstrap.Modal(document.getElementById('editProductModal'));
            editModal.show();
        }

        function deleteProduct(productId) {
            document.getElementById('delete_product_id').value = productId;

            const deleteModal = new bootstrap.Modal(document.getElementById('deleteProductModal'));
            deleteModal.show();
        }

        function toggleProductStatus(productId, currentStatus) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="toggle_status">
                <input type="hidden" name="product_id" value="${productId}">
                <input type="hidden" name="is_disabled" value="${currentStatus}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    </script>
