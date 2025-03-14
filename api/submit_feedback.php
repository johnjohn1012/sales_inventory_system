<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate required fields
    if (!isset($data['rating']) || !isset($data['comment']) || !isset($data['order_id'])) {
        throw new Exception('Missing required fields');
    }

    // Validate rating range
    $rating = intval($data['rating']);
    if ($rating < 1 || $rating > 5) {
        throw new Exception('Invalid rating value');
    }

    // First, get the customer_id from the order
    $stmt = $conn->prepare("SELECT customer_id FROM orders WHERE order_id = ?");
    $stmt->execute([$data['order_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception('Order not found');
    }

    // Insert feedback
    $stmt = $conn->prepare("
        INSERT INTO customer_feedback (order_id, customer_id, rating, comment)
        VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([
        $data['order_id'],
        $order['customer_id'],
        $rating,
        $data['comment']
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Feedback submitted successfully'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 