<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">
            <i class="fas fa-qrcode me-2"></i>
            HarahQR Sales
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="dashboard.php">
                        <i class="fas fa-home me-2"></i>Dashboard
                    </a>
                </li>

                <!-- Menu Management -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-utensils me-2"></i>Menu
                    </a>
                    <ul class="dropdown-menu">
                        <li><span class="nav-group-title">Menu Management</span></li>
                        <li><a class="dropdown-item" href="manage_products.php">
                            <i class="fas fa-box"></i>Products
                        </a></li>
                        <li><a class="dropdown-item" href="manage_categories.php">
                            <i class="fas fa-tags"></i>Categories
                        </a></li>
                    </ul>
                </li>

                <!-- Operations -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-cogs me-2"></i>Operations
                    </a>
                    <ul class="dropdown-menu">
                        <li><span class="nav-group-title">Restaurant Operations</span></li>
                        <li><a class="dropdown-item" href="manage_tables.php">
                            <i class="fas fa-chair"></i>Tables
                        </a></li>
                        <li><a class="dropdown-item" href="manage_reservations.php">
                            <i class="fas fa-calendar-check"></i>Reservations
                        </a></li>
                        <li><a class="dropdown-item" href="manage_payment_transactions.php">
                            <i class="fas fa-money-bill-wave"></i>Payments
                        </a></li>
                    </ul>
                </li>

                <!-- Staff Management -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-users me-2"></i>Staff
                    </a>
                    <ul class="dropdown-menu">
                        <li><span class="nav-group-title">Staff Management</span></li>
                        <li><a class="dropdown-item" href="manage_users.php">
                            <i class="fas fa-users"></i>Users
                        </a></li>
                        <li><a class="dropdown-item" href="manage_employees.php">
                            <i class="fas fa-user-tie"></i>Employees
                        </a></li>
                        <li><a class="dropdown-item" href="manage_shifts.php">
                            <i class="fas fa-clock"></i>Shifts
                        </a></li>
                        <li><a class="dropdown-item" href="manage_staff_shifts.php">
                            <i class="fas fa-calendar-alt"></i>Staff Shifts
                        </a></li>
                    </ul>
                </li>

                <!-- Reports & Analytics -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-chart-bar me-2"></i>Analytics
                    </a>
                    <ul class="dropdown-menu">
                        <li><span class="nav-group-title">Reports & Analytics</span></li>
                        <li><a class="dropdown-item" href="manage_sales.php">
                            <i class="fas fa-chart-line"></i>Sales
                        </a></li>
                        <li><a class="dropdown-item" href="reports.php">
                            <i class="fas fa-chart-bar"></i>Reports
                        </a></li>
                        <li><a class="dropdown-item" href="manage_feedback.php">
                            <i class="fas fa-comments"></i>Feedback
                        </a></li>
                    </ul>
                </li>

                <!-- System -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-cog me-2"></i>System
                    </a>
                    <ul class="dropdown-menu">
                        <li><span class="nav-group-title">System Management</span></li>
                        <li><a class="dropdown-item" href="manage_system_logs.php">
                            <i class="fas fa-history"></i>System Logs
                        </a></li>
                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#notificationModal">
                            <i class="fas fa-bell"></i>Notifications
                        </a></li>
                    </ul>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="../logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
