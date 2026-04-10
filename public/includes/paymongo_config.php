<?php
/**
 * PayMongo Configuration File
 * Test API credentials for GCash payments
 */

// PayMongo Test API Credentials
define('PAYMONGO_PUBLIC_KEY', 'pk_test_KfSqPb3JGsUNm9dE1NSQnCLQ');
define('PAYMONGO_SECRET_KEY', 'sk_test_MhznHYgB2zid6g3WRPjXZbtx');

// PayMongo API Base URL
define('PAYMONGO_API_URL', 'https://api.paymongo.com/v1');

// PayMongo Webhook Secret (generate this in PayMongo dashboard)
define('PAYMONGO_WEBHOOK_SECRET', 'whsk_test_your_webhook_secret_here');

// Payment Configuration
define('PAYMONGO_PAYMENT_METHOD', 'gcash'); // Only gcash for now
define('PAYMONGO_CURRENCY', 'PHP');

/**
 * Get PayMongo API Authorization Header
 * @return array Authorization header
 */
function getPayMongoAuthHeader() {
    $auth = base64_encode(PAYMONGO_SECRET_KEY . ':');
    return [
        'Authorization' => 'Basic ' . $auth,
        'Content-Type' => 'application/json'
    ];
}

/**
 * Make PayMongo API Request
 * @param string $endpoint API endpoint path
 * @param string $method HTTP method (GET, POST, etc.)
 * @param array $data Request data for POST/PUT requests
 * @return array|bool API response or false on error
 */
function makePayMongoRequest($endpoint, $method = 'GET', $data = []) {
    try {
        $url = PAYMONGO_API_URL . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $auth = getPayMongoAuthHeader();
        $headers = [
            'Authorization: Basic ' . base64_encode(PAYMONGO_SECRET_KEY . ':'),
            'Content-Type: application/json'
        ];
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        if (!empty($data) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new Exception('CURL Error: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        $responseData = json_decode($response, true);
        
        if ($httpCode < 200 || $httpCode >= 300) {
            error_log("PayMongo API Error ({$httpCode}): " . json_encode($responseData));
            return false;
        }
        
        return $responseData;
        
    } catch (Exception $e) {
        error_log("PayMongo Request Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Create PayMongo Checkout Session for GCash
 * @param int $itemId Reference ID (booking_id or order_id)
 * @param string $itemType Type of item ('photo_booking' or 'printing_order')
 * @param float $amount Amount in PHP
 * @param string $userEmail User email
 * @param string $userName User name
 * @return array|bool Checkout session data or false on error
 */
function createPayMongoCheckout($itemId, $itemType, $amount, $userEmail, $userName = '') {
    try {
        // Determine description based on item type
        $description = ($itemType === 'photo_booking') ? 
            'Photo Booking Payment - ID: ' . str_pad($itemId, 4, '0', STR_PAD_LEFT) :
            'Printing Order Payment - ID: ' . str_pad($itemId, 4, '0', STR_PAD_LEFT);
        
        // Generate success and cancel URLs with proper protocol detection
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $successUrl = $protocol . $_SERVER['HTTP_HOST'] . '/Amuning/public/paymongo_success.php?reference_id=' . $itemId . '&item_type=' . $itemType;
        $cancelUrl = $protocol . $_SERVER['HTTP_HOST'] . '/Amuning/public/payment.php?booking_id=' . $itemId;
        
        // Prepare checkout data
        $checkoutData = [
            'data' => [
                'attributes' => [
                    'line_items' => [
                        [
                            'amount' => (int)($amount * 100), // Convert to cents
                            'currency' => PAYMONGO_CURRENCY,
                            'description' => $description,
                            'quantity' => 1,
                            'name' => $description
                        ]
                    ],
                    'payment_method_types' => [PAYMONGO_PAYMENT_METHOD],
                    'reference_number' => $itemType . '_' . $itemId,
                    'description' => $description,
                    'success_url' => $successUrl,
                    'cancel_url' => $cancelUrl,
                    'client_key' => PAYMONGO_PUBLIC_KEY,
                    'customer' => [
                        'email' => $userEmail,
                        'name' => $userName
                    ]
                ]
            ]
        ];
        
        // Create checkout session
        $response = makePayMongoRequest('/checkout_sessions', 'POST', $checkoutData);
        
        if (!$response || !isset($response['data'])) {
            throw new Exception('Failed to create checkout session');
        }
        
        return $response['data'];
        
    } catch (Exception $e) {
        error_log("PayMongo Checkout Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Retrieve PayMongo Checkout Session
 * @param string $checkoutId Checkout session ID
 * @return array|bool Checkout session data or false on error
 */
function getPayMongoCheckout($checkoutId) {
    try {
        $response = makePayMongoRequest('/checkout_sessions/' . $checkoutId, 'GET');
        
        if (!$response || !isset($response['data'])) {
            throw new Exception('Failed to retrieve checkout session');
        }
        
        return $response['data'];
        
    } catch (Exception $e) {
        error_log("PayMongo Retrieve Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Verify PayMongo Webhook Signature
 * @param string $payload Raw webhook payload
 * @param string $signature Webhook signature from headers
 * @return bool True if signature is valid
 */
function verifyPayMongoWebhookSignature($payload, $signature) {
    $hash = hash_hmac('sha256', $payload, PAYMONGO_WEBHOOK_SECRET);
    return hash_equals($hash, $signature);
}
?>
