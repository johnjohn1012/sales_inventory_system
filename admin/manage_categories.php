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
                    $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
                    $stmt->execute([$_POST['name'], $_POST['description']]);
                    $_SESSION['success'] = "Category added successfully!";
                    break;
                case 'edit':
                    $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ? WHERE category_id = ?");
                    $stmt->execute([$_POST['name'], $_POST['description'], $_POST['category_id']]);
                    $_SESSION['success'] = "Category updated successfully!";
                    break;
                case 'delete':
                    $stmt = $conn->prepare("DELETE FROM categories WHERE category_id = ?");
                    $stmt->execute([$_POST['category_id']]);
                    $_SESSION['success'] = "Category deleted successfully!";
                    break;
            }
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    header('Location: manage_categories.php');
    exit();
}

// Fetch all categories
try {
    $stmt = $conn->query("SELECT * FROM categories ORDER BY name");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $_SESSION['error'] = "Error: " . $e->getMessage();
    $categories = [];
}
?>

<?php include 'components/header.php'; ?>
<body>
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

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">
                <i class="fas fa-tags me-2"></i>
                Manage Categories
            </h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                <i class="fas fa-plus me-2"></i>Add New Category
            </button>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($category['name']); ?></td>
                                <td><?php echo htmlspecialchars($category['description']); ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary me-2" 
                                            onclick='editCategory(<?php echo json_encode($category); ?>)'
                                            data-bs-toggle="modal" data-bs-target="#editCategoryModal">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                            onclick='deleteCategory(<?php echo json_encode($category); ?>)'
                                            data-bs-toggle="modal" data-bs-target="#deleteCategoryModal">
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

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="manage_categories.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label for="name" class="form-label">Category Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="manage_categories.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="category_id" id="edit_category_id">
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Category Name</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Category Modal -->
    <div class="modal fade" id="deleteCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="manage_categories.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="category_id" id="delete_category_id">
                        <p>Are you sure you want to delete <strong id="delete_category_name"></strong>?</p>
                        <p class="text-danger"><small>This action cannot be undone.</small></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'components/footer.php'; ?>
    <?php include 'components/notification_modal.php'; ?>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editCategory(category) {
            document.getElementById('edit_category_id').value = category.category_id;
            document.getElementById('edit_name').value = category.name;
            document.getElementById('edit_description').value = category.description;
        }

        function deleteCategory(category) {
            document.getElementById('delete_category_id').value = category.category_id;
            document.getElementById('delete_category_name').textContent = category.name;
        }

        // Initialize notifications
        $(document).ready(function() {
            if (typeof fetchNotifications === 'function') {
                setInterval(fetchNotifications, 30000);
                fetchNotifications();
            }
        });
    </script>
</body>
</html>