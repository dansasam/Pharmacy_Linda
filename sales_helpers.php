<?php
/**
 * Sales System Helper Functions
 * Process 15-18: Customer Sales, Cart, Checkout, Dispensing
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/PayMongoClient.php';

// =====================================================
// CART FUNCTIONS
// =====================================================

/**
 * Add item to cart (session-based)
 */
function cart_add($product_id, $quantity = 1) {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    $product_id = (int)$product_id;
    $quantity = (int)$quantity;
    
    if ($product_id <= 0 || $quantity <= 0) {
        return false;
    }
    
    if (!isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id] = 0;
    }
    
    $_SESSION['cart'][$product_id] += $quantity;
    return true;
}

/**
 * Update cart item quantity
 */
function cart_update($product_id, $quantity) {
    $product_id = (int)$product_id;
    $quantity = (int)$quantity;
    
    if (!isset($_SESSION['cart'])) {
        return false;
    }
    
    if ($quantity <= 0) {
        unset($_SESSION['cart'][$product_id]);
    } else {
        $_SESSION['cart'][$product_id] = $quantity;
    }
    
    return true;
}

/**
 * Remove item from cart
 */
function cart_remove($product_id) {
    $product_id = (int)$product_id;
    
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        return true;
    }
    
    return false;
}

/**
 * Clear entire cart
 */
function cart_clear() {
    unset($_SESSION['cart']);
}

/**
 * Get all cart items
 * @return array [product_id => quantity]
 */
function cart_get_all() {
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        return [];
    }
    
    $cart = [];
    foreach ($_SESSION['cart'] as $pid => $qty) {
        $pid = (int)$pid;
        $qty = (int)$qty;
        if ($pid > 0 && $qty > 0) {
            $cart[$pid] = $qty;
        }
    }
    
    return $cart;
}

/**
 * Get cart item count
 */
function cart_count() {
    $cart = cart_get_all();
    return array_sum($cart);
}

/**
 * Get cart with product details
 * @return array Cart items with product info
 */
function cart_get_details($conn) {
    $cart = cart_get_all();
    if (empty($cart)) {
        return [];
    }
    
    $items = [];
    $total = 0;
    
    foreach ($cart as $product_id => $quantity) {
        $stmt = $conn->prepare("SELECT * FROM product_inventory WHERE product_id = ?");
        $stmt->bind_param('i', $product_id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        
        if (!$product) {
            continue;
        }
        
        $unit_price = (float)$product['unit_price'];
        $line_total = $unit_price * $quantity;
        $total += $line_total;
        
        $items[] = [
            'product_id' => $product_id,
            'product' => $product,
            'quantity' => $quantity,
            'unit_price' => $unit_price,
            'line_total' => $line_total
        ];
    }
    
    return [
        'items' => $items,
        'total' => $total,
        'count' => count($items)
    ];
}

// =====================================================
// SALES/ORDER FUNCTIONS
// =====================================================

/**
 * Create a new sale/order
 * @return int sale_id
 */
function sales_create($conn, $customer_id, $payment_method, $prescription_id = null) {
    $customer_id = (int)$customer_id;
    $payment_method = esc($conn, $payment_method);
    $prescription_id = $prescription_id ? (int)$prescription_id : null;
    
    $stmt = $conn->prepare("INSERT INTO sales (customer_id, prescription_id, payment_method, payment_status) VALUES (?, ?, ?, 'pending')");
    $stmt->bind_param('iis', $customer_id, $prescription_id, $payment_method);
    $stmt->execute();
    
    return $conn->insert_id;
}

/**
 * Add items to sale from cart
 */
function sales_add_items($conn, $sale_id, $cart) {
    $sale_id = (int)$sale_id;
    
    $stmt = $conn->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, line_total) VALUES (?, ?, ?, ?, ?)");
    
    foreach ($cart as $product_id => $quantity) {
        $product_id = (int)$product_id;
        $quantity = (int)$quantity;
        
        // Get product price
        $prod_stmt = $conn->prepare("SELECT unit_price FROM product_inventory WHERE product_id = ?");
        $prod_stmt->bind_param('i', $product_id);
        $prod_stmt->execute();
        $product = $prod_stmt->get_result()->fetch_assoc();
        
        if (!$product) {
            continue;
        }
        
        $unit_price = (float)$product['unit_price'];
        $line_total = $unit_price * $quantity;
        
        $stmt->bind_param('iiidd', $sale_id, $product_id, $quantity, $unit_price, $line_total);
        $stmt->execute();
    }
    
    return true;
}

/**
 * Update sale payment status
 */
function sales_update_status($conn, $sale_id, $status, $reference = null) {
    $sale_id = (int)$sale_id;
    $status = esc($conn, $status);
    $reference = $reference ? esc($conn, $reference) : null;
    
    if ($reference) {
        $stmt = $conn->prepare("UPDATE sales SET payment_status = ?, payment_reference = ? WHERE sale_id = ?");
        $stmt->bind_param('ssi', $status, $reference, $sale_id);
    } else {
        $stmt = $conn->prepare("UPDATE sales SET payment_status = ? WHERE sale_id = ?");
        $stmt->bind_param('si', $status, $sale_id);
    }
    
    $stmt->execute();
    return true;
}

/**
 * Attach PayMongo session to sale
 */
function sales_attach_paymongo($conn, $sale_id, $session_id) {
    $sale_id = (int)$sale_id;
    $session_id = esc($conn, $session_id);
    
    $stmt = $conn->prepare("UPDATE sales SET paymongo_checkout_session_id = ? WHERE sale_id = ?");
    $stmt->bind_param('si', $session_id, $sale_id);
    $stmt->execute();
    
    return true;
}

/**
 * Get sale by ID
 */
function sales_get_by_id($conn, $sale_id) {
    $sale_id = (int)$sale_id;
    
    $stmt = $conn->prepare("SELECT * FROM sales WHERE sale_id = ?");
    $stmt->bind_param('i', $sale_id);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Get sale items
 */
function sales_get_items($conn, $sale_id) {
    $sale_id = (int)$sale_id;
    
    $stmt = $conn->prepare("
        SELECT si.*, p.drug_name, p.manufacturer 
        FROM sale_items si 
        JOIN product_inventory p ON si.product_id = p.product_id 
        WHERE si.sale_id = ? 
        ORDER BY si.item_id
    ");
    $stmt->bind_param('i', $sale_id);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Dispense products (deduct from inventory)
 */
function sales_dispense($conn, $sale_id, $processed_by) {
    $sale_id = (int)$sale_id;
    $processed_by = (int)$processed_by;
    
    $conn->begin_transaction();
    
    try {
        // Get sale items
        $items = sales_get_items($conn, $sale_id);
        
        if (empty($items)) {
            throw new Exception('No items in sale');
        }
        
        // Deduct from inventory
        $stmt = $conn->prepare("UPDATE product_inventory SET current_inventory = current_inventory - ? WHERE product_id = ?");
        $log_stmt = $conn->prepare("INSERT INTO product_logs (product_id, sale_id, action_type, quantity, performed_by) VALUES (?, ?, 'dispensed', ?, ?)");
        
        foreach ($items as $item) {
            $product_id = (int)$item['product_id'];
            $quantity = (int)$item['quantity'];
            
            // Check stock
            $check_stmt = $conn->prepare("SELECT current_inventory FROM product_inventory WHERE product_id = ?");
            $check_stmt->bind_param('i', $product_id);
            $check_stmt->execute();
            $stock = $check_stmt->get_result()->fetch_assoc();
            
            if (!$stock || $stock['current_inventory'] < $quantity) {
                throw new Exception('Insufficient stock for product ID ' . $product_id);
            }
            
            // Deduct stock
            $stmt->bind_param('ii', $quantity, $product_id);
            $stmt->execute();
            
            // Log dispensing
            $log_stmt->bind_param('iiii', $product_id, $sale_id, $quantity, $processed_by);
            $log_stmt->execute();
        }
        
        // Update sale
        $update_stmt = $conn->prepare("UPDATE sales SET processed_by = ?, payment_status = 'paid' WHERE sale_id = ?");
        $update_stmt->bind_param('ii', $processed_by, $sale_id);
        $update_stmt->execute();
        
        $conn->commit();
        return true;
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

// =====================================================
// PAYMONGO FUNCTIONS
// =====================================================

/**
 * Create PayMongo checkout session
 * @return array ['checkout_url' => string, 'session_id' => string]
 */
function paymongo_create_session($sale_id, $items, $total, $success_url, $cancel_url) {
    // Get PayMongo key
    $secret_key = defined('PAYMONGO_SECRET_KEY') ? PAYMONGO_SECRET_KEY : '';
    
    if (empty($secret_key) || strpos($secret_key, 'xxxxx') !== false) {
        throw new Exception('PayMongo API key not configured');
    }
    
    // Build line items
    $line_items = [];
    foreach ($items as $item) {
        $amount = (int)round($item['unit_price'] * 100); // Convert to centavos
        $line_items[] = [
            'name' => $item['product']['drug_name'] . ' (' . $item['product']['manufacturer'] . ')',
            'amount' => $amount,
            'currency' => 'PHP',
            'quantity' => (int)$item['quantity']
        ];
    }
    
    // Create payload
    $payload = [
        'data' => [
            'attributes' => [
                'line_items' => $line_items,
                'payment_method_types' => ['gcash', 'card'],
                'success_url' => $success_url,
                'cancel_url' => $cancel_url,
                'description' => 'Pharmacy Order #' . $sale_id
            ]
        ]
    ];
    
    // Call PayMongo API
    $client = new PayMongoClient($secret_key);
    $response = $client->createCheckoutSession($payload);
    
    return [
        'checkout_url' => $response['data']['attributes']['checkout_url'],
        'session_id' => $response['data']['id']
    ];
}

/**
 * Verify PayMongo payment
 * @return bool True if paid
 */
function paymongo_verify_payment($session_id) {
    $secret_key = defined('PAYMONGO_SECRET_KEY') ? PAYMONGO_SECRET_KEY : '';
    
    if (empty($secret_key)) {
        return false;
    }
    
    try {
        $client = new PayMongoClient($secret_key);
        $session = $client->retrieveCheckoutSession($session_id);
        
        $attrs = $session['data']['attributes'] ?? [];
        
        // Check payment_intent status
        $pi_status = $attrs['payment_intent']['status'] ?? null;
        if ($pi_status === 'succeeded') {
            return true;
        }
        
        // Check payments array
        $payments = $attrs['payments'] ?? [];
        if (!empty($payments)) {
            return true;
        }
        
        return false;
        
    } catch (Exception $e) {
        return false;
    }
}

// =====================================================
// PRODUCT LOG FUNCTIONS
// =====================================================

/**
 * Log product action
 */
function product_log($conn, $product_id, $action_type, $performed_by, $sale_id = null, $quantity = null, $notes = null) {
    $product_id = (int)$product_id;
    $action_type = esc($conn, $action_type);
    $performed_by = (int)$performed_by;
    $sale_id = $sale_id ? (int)$sale_id : null;
    $quantity = $quantity ? (int)$quantity : null;
    $notes = $notes ? esc($conn, $notes) : null;
    
    $stmt = $conn->prepare("INSERT INTO product_logs (product_id, sale_id, action_type, quantity, notes, performed_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('iisisi', $product_id, $sale_id, $action_type, $quantity, $notes, $performed_by);
    $stmt->execute();
    
    return true;
}

// =====================================================
// NOTIFICATION HELPERS
// =====================================================

/**
 * Notify pharmacy assistants of new order
 */
function notify_new_order($conn, $pdo, $sale_id, $customer_name, $total) {
    // Get all pharmacy assistants
    $stmt = $pdo->prepare('SELECT id FROM users WHERE role = "Pharmacy Assistant"');
    $stmt->execute();
    $assistants = $stmt->fetchAll();
    
    if ($assistants) {
        $notif_stmt = $pdo->prepare('INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)');
        $title = 'New Order Received';
        $message = "Order #$sale_id from $customer_name. Total: ₱" . number_format($total, 2) . ". Please check availability.";
        
        foreach ($assistants as $assistant) {
            $notif_stmt->execute([$assistant['id'], $title, $message]);
        }
    }
}

/**
 * Notify customer of order status
 */
function notify_customer($pdo, $customer_id, $title, $message) {
    $stmt = $pdo->prepare('INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)');
    $stmt->execute([$customer_id, $title, $message]);
}
