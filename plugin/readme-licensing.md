# WritgoAI Licensing System

This document describes the server-side licensing API and the WordPress plugin client for managing subscriptions, credits, and license validation.

## Overview

The licensing system implements a central licensing API that the WritgoAI plugin communicates with to:

- Validate license keys
- Track and consume credits
- Handle Stripe subscription webhooks
- Log all license-related activities

## Architecture

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│                 │     │                 │     │                 │
│  WordPress      │────▶│  Licensing API  │────▶│   Database      │
│  Plugin         │     │  (PHP Server)   │     │   (MySQL)       │
│                 │     │                 │     │                 │
└─────────────────┘     └────────┬────────┘     └─────────────────┘
                                 │
                                 ▲
                                 │
                        ┌────────┴────────┐
                        │                 │
                        │     Stripe      │
                        │    Webhooks     │
                        │                 │
                        └─────────────────┘
```

## Server Setup

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or MariaDB 10.3 or higher
- Composer (for Stripe PHP SDK)
- SSL certificate (HTTPS required)

### Installation Steps

#### 1. Configure Database

Edit `licensing/config.php` and update the database credentials:

```php
define( 'DB_HOST', 'localhost' );
define( 'DB_NAME', 'your_database_name' );
define( 'DB_USER', 'your_database_user' );
define( 'DB_PASS', 'your_database_password' );
```

#### 2. Run Database Migration

Execute the SQL migration to create the required tables:

```bash
mysql -u your_user -p your_database < licensing/db/migrations/2025_12_01_create_licenses.sql
```

This creates three tables:
- `licenses` - Stores license keys and subscription information
- `user_credits` - Tracks credit balances per billing period
- `license_activity` - Logs all license-related activities

#### 3. Configure Stripe

1. Get your Stripe API keys from [Stripe Dashboard](https://dashboard.stripe.com/apikeys)

2. Update `licensing/config.php`:

```php
define( 'STRIPE_SECRET_KEY', 'sk_live_YOUR_STRIPE_SECRET_KEY' );
define( 'STRIPE_WEBHOOK_SECRET', 'whsec_YOUR_STRIPE_WEBHOOK_SECRET' );
```

3. Configure the plan-to-credits mapping with your Stripe price IDs:

```php
$plan_credits_map = array(
    'price_STARTER_PLAN_ID'     => 100,
    'price_PROFESSIONAL_PLAN_ID' => 500,
    'price_BUSINESS_PLAN_ID'     => 2000,
    'price_ENTERPRISE_PLAN_ID'   => 10000,
);
```

#### 4. Install Stripe PHP SDK

```bash
cd licensing
composer require stripe/stripe-php
```

#### 5. Configure Stripe Webhook

1. Go to [Stripe Webhooks](https://dashboard.stripe.com/webhooks)
2. Add a new webhook endpoint pointing to: `https://your-domain.com/licensing/webhooks/stripe_webhook.php`
3. Select the following events:
   - `customer.subscription.created`
   - `customer.subscription.updated`
   - `customer.subscription.deleted`
   - `invoice.payment_succeeded`
   - `invoice.payment_failed`
4. Copy the webhook signing secret and add it to `config.php`

#### 6. Set Up Web Server

Ensure your web server routes requests to the API endpoints correctly. Example Nginx configuration:

```nginx
location /licensing/ {
    try_files $uri $uri/ =404;
    
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
    }
}
```

## API Endpoints

### Validate License

**Endpoint:** `POST /licensing/api/license/validate.php`

**Request:**
```json
{
    "license_key": "XXXX-XXXX-XXXX-XXXX",
    "site_url": "https://example.com"
}
```

**Response:**
```json
{
    "valid": true,
    "status": "active",
    "credits_remaining": 450,
    "credits_total": 500,
    "site_url": "https://example.com",
    "plan_name": "Professional",
    "expires_at": "2025-12-31 23:59:59"
}
```

### Consume Credits

**Endpoint:** `POST /licensing/api/license/consume.php`

**Request:**
```json
{
    "license_key": "XXXX-XXXX-XXXX-XXXX",
    "amount": 1,
    "action": "text_generation"
}
```

**Response (Success):**
```json
{
    "success": true,
    "credits_remaining": 449,
    "credits_consumed": 1
}
```

**Response (Insufficient Credits):**
```json
{
    "success": false,
    "error": "Insufficient credits",
    "credits_remaining": 0,
    "credits_requested": 1
}
```

## WordPress Plugin Integration

### Including the License Client

The license client is located at `plugin/includes/license-client.php`. Include it in your plugin:

```php
require_once plugin_dir_path( __FILE__ ) . 'includes/license-client.php';
```

### Usage Examples

#### Validate a License

```php
// Using the helper function
$result = writgocms_validate_license();

if ( is_wp_error( $result ) ) {
    error_log( 'License validation failed: ' . $result->get_error_message() );
} else {
    if ( $result['valid'] ) {
        echo 'License is valid! Credits remaining: ' . $result['credits_remaining'];
    } else {
        echo 'License is not valid. Status: ' . $result['status'];
    }
}
```

#### Consume Credits

```php
// Check if user has enough credits before performing action
if ( writgocms_has_credits( 5 ) ) {
    // Perform the AI generation
    $ai_result = perform_ai_generation();
    
    // Consume the credits
    $consume_result = writgocms_consume_credits( 5, 'text_generation' );
    
    if ( is_wp_error( $consume_result ) ) {
        error_log( 'Failed to consume credits: ' . $consume_result->get_error_message() );
    }
}
```

#### Using the Client Class Directly

```php
$client = WritgoCMS_License_Client::get_instance();

// Validate license
$validation = $client->validate_license( 'XXXX-XXXX-XXXX-XXXX' );

// Get remaining credits
$credits = $client->get_remaining_credits();

// Consume credits with custom action
$result = $client->consume_credits( 3, 'image_generation' );
```

#### Customizing the API URL

For development or custom API servers:

```php
// Via filter
add_filter( 'writgocms_license_api_url', function( $url ) {
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        return 'https://staging-api.writgoai.com/v1';
    }
    return $url;
} );

// Or directly
$client = WritgoCMS_License_Client::get_instance();
$client->set_api_base_url( 'https://custom-api.example.com/v1' );
```

## Security Considerations

1. **HTTPS Required:** All API endpoints require HTTPS in production
2. **Signature Validation:** Stripe webhooks are validated using the webhook secret
3. **Prepared Statements:** All database queries use PDO prepared statements
4. **Race Condition Prevention:** Credit consumption uses `SELECT ... FOR UPDATE` for atomicity
5. **Input Sanitization:** All user inputs are sanitized before processing
6. **No Hardcoded Secrets:** All secrets are stored in configuration files (not committed to version control)

## Environment Variables (Alternative)

For enhanced security, you can use environment variables instead of hardcoded values in `config.php`:

```php
define( 'DB_HOST', getenv( 'DB_HOST' ) ?: 'localhost' );
define( 'DB_NAME', getenv( 'DB_NAME' ) );
define( 'DB_USER', getenv( 'DB_USER' ) );
define( 'DB_PASS', getenv( 'DB_PASS' ) );
define( 'STRIPE_SECRET_KEY', getenv( 'STRIPE_SECRET_KEY' ) );
define( 'STRIPE_WEBHOOK_SECRET', getenv( 'STRIPE_WEBHOOK_SECRET' ) );
```

## Database Schema

### licenses Table

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key |
| license_key | VARCHAR(32) | Unique license key |
| email | VARCHAR(255) | User email |
| stripe_customer_id | VARCHAR(64) | Stripe customer ID |
| stripe_subscription_id | VARCHAR(64) | Stripe subscription ID |
| stripe_price_id | VARCHAR(64) | Stripe price ID for plan |
| site_url | VARCHAR(512) | Registered site URL |
| status | ENUM | active, expired, suspended, cancelled, trial |
| plan_name | VARCHAR(64) | Human-readable plan name |
| activated_at | DATETIME | When license was activated |
| expires_at | DATETIME | When license expires |
| created_at | DATETIME | Record creation timestamp |
| updated_at | DATETIME | Record update timestamp |

### user_credits Table

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key |
| license_id | INT | Foreign key to licenses |
| credits_total | INT | Total credits for period |
| credits_used | INT | Credits used this period |
| credits_remaining | INT | Computed: total - used |
| period_start | DATE | Billing period start |
| period_end | DATE | Billing period end |
| created_at | DATETIME | Record creation timestamp |
| updated_at | DATETIME | Record update timestamp |

### license_activity Table

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key |
| license_id | INT | Foreign key to licenses |
| activity_type | ENUM | Type of activity |
| credits_amount | INT | Credits involved (if applicable) |
| ip_address | VARCHAR(45) | Client IP address |
| user_agent | TEXT | Client user agent |
| metadata | JSON | Additional metadata |
| created_at | DATETIME | Activity timestamp |

## Troubleshooting

### Common Issues

1. **"HTTPS required" error:**
   - Ensure your server is configured with SSL
   - Check if `APP_ENV` environment variable is set correctly

2. **Webhook signature validation fails:**
   - Verify the webhook secret matches Stripe dashboard
   - Ensure the raw payload is being passed to verification

3. **Database connection errors:**
   - Verify database credentials in `config.php`
   - Check that the database server is running

4. **"License not found" errors:**
   - Ensure the license was created via Stripe subscription
   - Check the license key format (XXXX-XXXX-XXXX-XXXX)

### Debug Mode

For development, you can enable debug logging:

```php
// Add to config.php
define( 'LICENSING_DEBUG', true );

// Then in your API files, add logging:
if ( defined( 'LICENSING_DEBUG' ) && LICENSING_DEBUG ) {
    error_log( 'Debug: ' . print_r( $data, true ) );
}
```

## Support

For issues or feature requests, please open an issue in the GitHub repository.
