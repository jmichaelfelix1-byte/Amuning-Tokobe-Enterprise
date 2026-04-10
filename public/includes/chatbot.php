<?php
// Chatbot API Handler

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';

    // Predefined Q&A responses
    $responses = [
        'services' => "We offer professional photo booth and printing services perfect for events, celebrations, and special occasions!",
        'booking' => "You can book our services through the 'Photo' or 'Print' sections on our website. Click on the desired service and select your preferred date and time.",
        'price' => "Our prices vary depending on the service and duration. Visit the service page to see detailed pricing for each package.",
        'hours' => "Our operating hours are Monday to Friday 9 AM - 6 PM, and Saturday 10 AM - 8 PM. We're closed on Sundays.",
        'contact' => "You can reach us at:\n📞 Phone: (123) 456-7890\n📧 Email: info@amuning.com\n📍 Address: [Your Address Here]",
        'payment' => "We accept online payment through our secure payment gateway. You can pay directly during the booking process.",
        'cancel' => "You can cancel or modify your booking up to 24 hours before the scheduled date without any charges.",
        'photos' => "After your photo booth session, we'll process and print your photos within 2-3 business days.",
        'delivery' => "We offer both pickup and delivery options. Delivery charges may apply based on location.",
        'group' => "Yes! We offer special group discounts. Contact us for more details on bulk orders.",
    ];

    // Predefined questions for quick access
    $predefined = [
        ['id' => 'services', 'text' => 'What services do you offer?'],
        ['id' => 'booking', 'text' => 'How do I book a service?'],
        ['id' => 'price', 'text' => 'What are your prices?'],
        ['id' => 'hours', 'text' => 'What are your operating hours?'],
        ['id' => 'contact', 'text' => 'How can I contact you?'],
        ['id' => 'payment', 'text' => 'What payment methods do you accept?'],
        ['id' => 'cancel', 'text' => 'Can I cancel my booking?'],
        ['id' => 'photos', 'text' => 'When will I get my photos?'],
        ['id' => 'delivery', 'text' => 'Do you offer delivery?'],
        ['id' => 'group', 'text' => 'Do you offer group discounts?'],
    ];

    if ($action === 'get_predefined') {
        echo json_encode(['success' => true, 'questions' => $predefined]);
    } elseif ($action === 'send_message') {
        // Check if message matches any predefined question
        $response = null;
        $found = false;

        // Check by ID
        if (isset($responses[$message])) {
            $response = $responses[$message];
            $found = true;
        } else {
            // Check by keyword matching
            $message_lower = strtolower($message);
            
            foreach ($responses as $key => $value) {
                if (strpos($message_lower, $key) !== false) {
                    $response = $value;
                    $found = true;
                    break;
                }
            }

            // If no match found, provide default response
            if (!$found) {
                $response = "Thanks for your question! For specific inquiries not covered above, please contact us directly:\n📞 Phone: (123) 456-7890\n📧 Email: info@amuning.com";
            }
        }

        echo json_encode([
            'success' => true,
            'response' => $response,
            'user_message' => htmlspecialchars($message)
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>
