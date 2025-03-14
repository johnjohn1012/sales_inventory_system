<style>
    body {
        font-family: 'Poppins', sans-serif;
        background-color: #f8f9fc;
    }

    .navbar {
        background: linear-gradient(135deg, #4e73df, #224abe);
        padding: 0.5rem 1rem;
    }

    .navbar-nav {
        gap: 0.2rem;
    }

    .nav-item {
        position: relative;
    }

    .nav-link {
        color: white !important;
        opacity: 0.8;
        transition: all 0.3s ease;
        padding: 0.5rem 1rem !important;
        font-size: 0.9rem;
        white-space: nowrap;
    }

    .nav-link:hover {
        opacity: 1;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 4px;
    }

    .nav-link.active {
        opacity: 1;
        font-weight: 500;
        background: rgba(255, 255, 255, 0.15);
        border-radius: 4px;
    }

    .dropdown-menu {
        background: #fff;
        border: none;
        box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        padding: 0.5rem;
        min-width: 200px;
        margin-top: 0.5rem;
        border-radius: 8px;
    }

    .dropdown-item {
        padding: 0.7rem 1rem;
        border-radius: 4px;
        transition: all 0.3s ease;
        color: #2c3e50;
    }

    .dropdown-item:hover {
        background: #f8f9fc;
        transform: translateX(5px);
        color: #4e73df;
    }

    .dropdown-item i {
        width: 20px;
        text-align: center;
        margin-right: 10px;
        opacity: 0.7;
    }

    .navbar-brand {
        font-weight: 600;
        font-size: 1.2rem;
        padding: 0.5rem 1rem;
        margin-right: 2rem;
        color: white !important;
    }

    .nav-group-title {
        color: #858796;
        font-size: 0.8rem;
        padding: 0.5rem 1rem;
        margin-bottom: 0.2rem;
        border-bottom: 1px solid #eee;
        display: block;
    }

    .stat-card {
        background: white;
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-5px);
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        margin-bottom: 15px;
    }

    .stat-users { background: #e3fcef; color: #1cc88a; }
    .stat-products { background: #e8f4ff; color: #4e73df; }
    .stat-orders { background: #fff4e5; color: #f6c23e; }
    .stat-sales { background: #ffe9e9; color: #e74a3b; }

    .stat-title {
        color: #858796;
        font-size: 0.9rem;
        margin-bottom: 5px;
    }

    .stat-value {
        font-size: 1.8rem;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 0;
    }

    .quick-actions {
        background: white;
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .action-card {
        background: #f8f9fc;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 15px;
        transition: all 0.3s ease;
        text-decoration: none;
        color: #2c3e50;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .action-card:hover {
        background: #4e73df;
        color: white;
        transform: translateX(5px);
    }

    .action-icon {
        font-size: 24px;
        width: 40px;
    }

    .recent-orders {
        background: white;
        border-radius: 15px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .order-item {
        padding: 15px;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .order-info {
        flex-grow: 1;
        padding-right: 15px;
    }

    .order-table {
        font-weight: 500;
        margin-bottom: 5px;
    }

    .order-time {
        font-size: 0.85rem;
        color: #858796;
    }

    .order-status {
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 500;
    }

    .status-pending { background: #fff4e5; color: #f6c23e; }
    .status-paid { background: #e3fcef; color: #1cc88a; }
    .status-preparing { background: #e8f4ff; color: #4e73df; }
    .status-completed { background: #edf2f9; color: #858796; }

    /* Footer Styles */
    .footer {
        background: #fff;
        border-top: 1px solid #eee;
    }

    .footer p {
        color: #6c757d;
        font-size: 0.9rem;
    }

    @media (max-width: 991.98px) {
        .navbar-collapse {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            padding: 1rem;
            margin-top: 1rem;
            max-height: calc(100vh - 100px);
            overflow-y: auto;
        }

        .nav-link {
            color: #2c3e50 !important;
        }

        .nav-link:hover,
        .nav-link.active {
            background: #f8f9fc;
            color: #4e73df !important;
        }

        .dropdown-menu {
            border: none;
            box-shadow: none;
            padding-left: 2rem;
            margin-top: 0;
        }

        .navbar-toggler {
            border: none;
            padding: 0.5rem;
        }

        .navbar-toggler:focus {
            box-shadow: none;
        }

        .nav-item {
            margin: 0.2rem 0;
        }
    }

    @media (max-width: 768px) {
        .footer {
            text-align: center;
        }
        
        .footer .social-links {
            margin-bottom: 2rem;
        }
        
        .footer ul li {
            margin-bottom: 0.5rem;
        }
    }
</style>
