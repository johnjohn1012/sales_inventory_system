<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    http_response_code(403);
    exit('Unauthorized');
}

// Get filter parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$payment_method = isset($_GET['payment_method']) ? $_GET['payment_method'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Build query
$query = "
    SELECT 
        pt.transaction_id,
        pt.created_at,
        pt.payment_method,
        pt.status,
        pt.amount,
        pt.reference_number,
        o.order_number,
        o.order_date,
        c.first_name as customer_first_name,
        c.last_name as customer_last_name,
        c.phone_number as customer_phone,
        e.first_name as employee_first_name,
        e.last_name as employee_last_name
    FROM payment_transactions pt
    LEFT JOIN orders o ON pt.order_id = o.order_id
    LEFT JOIN customers c ON o.customer_id = c.customer_id
    LEFT JOIN employees e ON pt.employee_id = e.employee_id
    WHERE pt.created_at BETWEEN ? AND ?
";

$params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];

if ($payment_method) {
    $query .= " AND pt.payment_method = ?";
    $params[] = $payment_method;
}

if ($status) {
    $query .= " AND pt.status = ?";
    $params[] = $status;
}

$query .= " ORDER BY pt.created_at DESC";

// Get transactions
$stmt = $conn->prepare($query);
$stmt->execute($params);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="payment_transactions_' . date('Y-m-d') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add CSV headers
fputcsv($output, [
    'Transaction ID',
    'Date & Time',
    'Payment Method',
    'Status',
    'Amount',
    'Reference Number',
    'Order Number',
    'Order Date',
    'Customer Name',
    'Customer Phone',
    'Processed By'
]);

// Add data rows
foreach ($transactions as $transaction) {
    fputcsv($output, [
        $transaction['transaction_id'],
        date('Y-m-d h:i A', strtotime($transaction['created_at'])),
        ucfirst($transaction['payment_method']),
        ucfirst($transaction['status']),
        number_format($transaction['amount'], 2),
        $transaction['reference_number'] ?? '',
        $transaction['order_number'] ?? '',
        $transaction['order_date'] ? date('Y-m-d', strtotime($transaction['order_date'])) : '',
        $transaction['customer_first_name'] && $transaction['customer_last_name'] 
            ? $transaction['customer_first_name'] . ' ' . $transaction['customer_last_name']
            : '',
        $transaction['customer_phone'] ?? '',
        $transaction['employee_first_name'] && $transaction['employee_last_name']
            ? $transaction['employee_first_name'] . ' ' . $transaction['employee_last_name']
            : ''
    ]);
}

// Close output stream
fclose($output); 