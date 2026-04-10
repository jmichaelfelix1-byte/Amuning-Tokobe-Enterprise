# PayMongo GCash Integration Guide

## Overview
This integration adds PayMongo as a payment processor for GCash payments in the Amuning system. Users can now securely pay for photo bookings and printing orders using GCash through PayMongo's hosted checkout.

## Features
- **Secure GCash Payments**: Integrate with PayMongo for safe, encrypted GCash transactions
- **Instant Confirmation**: Automatic payment confirmation via PayMongo checksumming
- **Webhook Support**: Real-time payment status updates via webhooks
- **Test Mode**: PayMongo test API credentials for development and testing
- **User-Friendly**: Seamless checkout experience with redirect to PayMongo payment page

## Setup Instructions

### 1. Database Setup
Execute the SQL migration to create the PayMongo checkouts table:

```bash
mysql -u root amuning_db_new < database/add_paymongo_support.sql
```

Or manually run the SQL query in your MySQL client:
```sql
CREATE TABLE `paymongo_checkouts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `item_type` enum('photo_booking','printing_order') NOT NULL,
  `checkout_id` varchar(255) NOT NULL UNIQUE,
  `payment_id` int(11) NULL,
  `amount` decimal(10, 2) NOT NULL,
  `status` enum('pending','paid','failed','expired') NOT NULL DEFAULT 'pending',
  `payment_intent_id` varchar(255) NULL,
  `notes` text NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `checkout_id` (`checkout_id`),
  KEY `user_id` (`user_id`),
  KEY `item_id_type` (`item_id`, `item_type`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2. Configuration Setup
The PayMongo test credentials are already configured in `public/includes/paymongo_config.php`:

- **Public Key**: `pk_test_4KKeQkEAk2i87bDYuDYMJddX`
- **Secret Key**: `sk_test_4KKeKKL68Ub6EukrqVqVgYcP`

These are **test credentials** only and should not be used in production.

### 3. Production Setup (When Ready)
To switch to production:

1. Log in to [PayMongo Dashboard](https://dashboard.paymongo.com)
2. Get your production API keys
3. Update `public/includes/paymongo_config.php`:
   ```php
   define('PAYMONGO_PUBLIC_KEY', 'pk_live_YOUR_LIVE_PUBLIC_KEY');
   define('PAYMONGO_SECRET_KEY', 'sk_live_YOUR_LIVE_SECRET_KEY');
   ```
4. Update the API URL if needed (remove `_test` from endpoint if required)

### 4. Webhook Configuration
To enable webhook support:

1. Go to [PayMongo Dashboard](https://dashboard.paymongo.com) → Developers → Webhooks
2. Add a new webhook endpoint: `https://yourdomain.com/Amuning/public/api/paymongo_webhook.php`
3. Subscribe to these events:
   - `payment.paid` - Payment successful
   - `payment.failed` - Payment failed
   - `checkout.session.expired` - Checkout session expired
4. Copy the webhook secret and update in `paymongo_config.php`:
   ```php
   define('PAYMONGO_WEBHOOK_SECRET', 'whsk_your_webhook_secret_here');
   ```

## Files Added/Modified

### New Files
- **`public/includes/paymongo_config.php`** - PayMongo configuration and API functions
- **`public/api/paymongo_init_checkout.php`** - AJAX endpoint for checkout initialization
- **`public/paymongo_success.php`** - Success redirect handler after payment
- **`public/api/paymongo_webhook.php`** - Webhook endpoint for payment confirmations
- **`database/add_paymongo_support.sql`** - Database migration script

### Modified Files
- **`public/payment.php`** - Updated payment form to include PayMongo GCash option
- **`public/includes/paymongo_config.php`** - Contains all PayMongo configuration

## Payment Flow

### GCash (PayMongo) Flow
1. User selects "GCash (PayMongo - Instant)" from payment method dropdown
2. User clicks "Proceed to GCash Payment" button
3. System creates a PayMongo checkout session via `api/paymongo_init_checkout.php`
4. User is redirected to PayMongo's secure checkout page
5. User completes payment via GCash
6. PayMongo redirects user to success page
7. System verifies payment and marks booking as "validated"
8. User receives confirmation email

### Traditional Methods (PayMaya, Bank Transfer, Cash)
- User selects payment method
- User enters reference number
- User uploads proof of payment
- Payment status remains "pending" until verified by admin

## API Reference

### PayMongo Configuration Functions

#### `createPayMongoCheckout($itemId, $itemType, $amount, $userEmail, $userName)`
Creates a PayMongo checkout session.

**Parameters:**
- `$itemId` (int): Photo booking or printing order ID
- `$itemType` (string): 'photo_booking' or 'printing_order'
- `$amount` (float): Amount in PHP
- `$userEmail` (string): User's email address
- `$userName` (string): User's full name

**Returns:** Array with checkout details or false on error

**Example:**
```php
$checkout = createPayMongoCheckout(1, 'photo_booking', 5000, 'user@email.com', 'John Doe');
if ($checkout) {
    $checkoutUrl = $checkout['attributes']['checkout_url'];
    header('Location: ' . $checkoutUrl);
}
```

#### `getPayMongoCheckout($checkoutId)`
Retrieves checkout session details.

**Parameters:**
- `$checkoutId` (string): PayMongo checkout session ID

**Returns:** Array with checkout details or false on error

#### `verifyPayMongoWebhookSignature($payload, $signature)`
Verifies webhook signature from PayMongo.

**Parameters:**
- `$payload` (string): Raw webhook request body
- `$signature` (string): Signature from X-PayMongo-Signature header

**Returns:** Boolean (true if valid)

## Testing

### Test with PayMongo Test Account
1. Use test credentials provided in `paymongo_config.php`
2. Create a test booking/order
3. Proceed to payment and select GCash
4. You'll be redirected to PayMongo test environment
5. PayMongo provides test GCash QR codes for testing

### Test Scenarios
- **Successful Payment**: Complete the full test checkout flow
- **Failed Payment**: Close browser before completing payment (simulates failure)
- **Expired Session**: Wait for session expiration (typically 1 hour)

## Security Considerations

1. **Never expose secret keys**: Keep your SK_ keys private
2. **Use webhooks**: Don't rely solely on redirects for payment confirmation
3. **HTTPS only**: Always use HTTPS in production
4. **Validate amounts**: Always verify that payment amount matches order total
5. **Rate limiting**: Add rate limiting to prevent abuse

## Troubleshooting

### Issue: Checkout creation fails
**Solution:** 
- Check network connectivity
- Verify API keys are correct
- Check error logs in `error_log`

### Issue: Redirect loop
**Solution:**
- Ensure `paymongo_success.php` is accessible
- Check database connectivity
- Verify user session is valid

### Issue: Webhook not received
**Solution:**
- Verify webhook URL is publicly accessible
- Check firewall/server logs for blocked requests
- Confirm webhook secret is correct
- Check PayMongo webhook logs in dashboard

### Issue: Amount mismatch
**Solution:**
- Ensure travel_fee is included in calculation
- Verify decimal precision (amounts in cents)
- Check for currency conversion issues

## Support & Documentation

- **PayMongo Documentation**: https://developers.paymongo.com
- **PayMongo API Reference**: https://developers.paymongo.com/docs/api/v1
- **PayMongo Support**: https://support.paymongo.com

## Migration from Old Payment System

If you had manual GCash payments before:

1. Old payments will continue to work
2. New payments will use PayMongo automatically when GCash is selected
3. No data loss or migration needed
4. Users can still see payment history

## Maintenance

### Regular Tasks
- Monitor webhook delivery logs
- Check failed payments regularly
- Update PayMongo library if needed
- Review payment reconciliation reports

### Configuration Updates
When updating credentials:
1. Update in `paymongo_config.php`
2. Test with small amount
3. Verify webhook delivery
4. Monitor for failed payments

## Support Contact
For issues with this integration, contact Amuning development team.
