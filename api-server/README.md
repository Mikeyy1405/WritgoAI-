# WritgoAI API Server

This directory contains API server components for the WritgoAI plugin authentication system.

## 🔐 Authentication System

The WritgoAI plugin now uses **user account authentication** instead of license keys. Users log in with their email and password, and the API server issues Bearer tokens for authentication.

### Authentication Flow

```
┌─────────────┐                    ┌─────────────┐                    ┌─────────────┐
│             │  1. Login Request  │             │  2. Verify User    │             │
│  WordPress  │ ──────────────────>│  API Server │ ──────────────────>│  Database   │
│   Plugin    │  (email/password)  │             │                    │             │
│             │                    │             │<───────────────────│             │
│             │<───────────────────│             │  3. Return Token   │             │
│             │  (Bearer token)    │             │                    │             │
└─────────────┘                    └─────────────┘                    └─────────────┘
       │                                  │
       │  4. API Requests                 │
       │     (Bearer token in header)     │
       │ ────────────────────────────────>│
       │                                  │
       │<─────────────────────────────────│
       │  5. API Response                 │
```

### Authentication Endpoints

#### POST `/v1/auth/login`

Authenticate user with email and password.

**Request:**
```json
{
  "email": "user@example.com",
  "password": "secure-password"
}
```

**Response (Success):**
```json
{
  "success": true,
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "expires_at": "2024-12-02T21:58:34Z",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "name": "John Doe",
    "company": "Example Inc"
  }
}
```

**Response (Error):**
```json
{
  "success": false,
  "message": "Invalid credentials"
}
```

#### POST `/v1/auth/logout`

Logout and invalidate current token.

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

#### POST `/v1/auth/refresh`

Refresh authentication token before expiry.

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "expires_at": "2024-12-03T21:58:34Z"
}
```

### API Request Authentication

All API requests must include the Bearer token in the Authorization header:

```
Authorization: Bearer {token}
```

**Example API Request:**
```bash
curl -X GET \
  https://api.writgoai.com/v1/credits/balance \
  -H 'Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...' \
  -H 'Content-Type: application/json'
```

The API server will:
1. Validate the Bearer token
2. Look up the user associated with the token
3. Find the user's active license
4. Process the request using that license
5. Deduct credits from the user's license balance

## 🗄️ Database Schema

### Users Table: `wp_writgo_users`

```sql
CREATE TABLE wp_writgo_users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(255),
    company VARCHAR(255),
    email_verified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email)
);
```

### Licenses Table: `wp_writgo_licenses`

```sql
CREATE TABLE wp_writgo_licenses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    license_key VARCHAR(100) NOT NULL UNIQUE,
    license_type ENUM('starter', 'pro', 'enterprise', 'superuser') NOT NULL,
    status ENUM('active', 'expired', 'suspended', 'cancelled') DEFAULT 'active',
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_license_key (license_key),
    INDEX idx_status (status)
);
```

### User-License Mapping: `wp_writgo_user_licenses`

```sql
CREATE TABLE wp_writgo_user_licenses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    license_id BIGINT UNSIGNED NOT NULL,
    is_owner BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES wp_writgo_users(id) ON DELETE CASCADE,
    FOREIGN KEY (license_id) REFERENCES wp_writgo_licenses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_license (user_id, license_id),
    INDEX idx_user_id (user_id),
    INDEX idx_license_id (license_id)
);
```

### Credits Table: `wp_writgo_credits`

```sql
CREATE TABLE wp_writgo_credits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    license_id BIGINT UNSIGNED NOT NULL,
    balance INT NOT NULL DEFAULT 0,
    monthly_allowance INT NOT NULL DEFAULT 0,
    last_reset TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (license_id) REFERENCES wp_writgo_licenses(id) ON DELETE CASCADE,
    INDEX idx_license_id (license_id)
);
```

## 🚀 Setup Instructions

### 1. Create Superuser Account

Run the password hash generator:

```bash
cd api-server/scripts
php create-superuser.php
```

This will output:
- A secure random password
- The password hash
- Instructions for the next steps

**Save the password securely!**

### 2. Run the Migration

Update the SQL migration file with the generated password hash:

```bash
# Edit the migration file
vim migrations/create-superuser-account.sql

# Find this line:
# '$2y$10$PLACEHOLDER_HASH',

# Replace with your generated hash:
# '$2y$10$your_actual_hash_here',
```

Run the migration:

```bash
mysql -u username -p database_name < migrations/create-superuser-account.sql
```

### 3. Test Authentication

Test the login endpoint:

```bash
curl -X POST \
  https://api.writgoai.com/v1/auth/login \
  -H 'Content-Type: application/json' \
  -d '{
    "email": "info@writgo.nl",
    "password": "your_generated_password"
  }'
```

You should receive a Bearer token in the response.

### 4. Test API Calls

Test an authenticated API call:

```bash
# Use the token from previous step
curl -X GET \
  https://api.writgoai.com/v1/credits/balance \
  -H 'Authorization: Bearer YOUR_TOKEN_HERE' \
  -H 'Content-Type: application/json'
```

## 📝 WordPress Plugin Integration

The WordPress plugin (`inc/class-auth-manager.php`) handles:
- User login with email/password
- Secure token storage (encrypted in WordPress options)
- Automatic token refresh before expiry
- Logout functionality
- Bearer token authentication for all API calls

### Plugin Usage

1. Users open the plugin and see a login form
2. Enter email and password
3. Plugin stores the Bearer token securely
4. All API calls include `Authorization: Bearer {token}` header
5. Credits are deducted from the user's license

## 🔒 Security Features

- **Password Hashing**: ARGON2ID or BCRYPT with high cost
- **Token Storage**: Encrypted in WordPress options using WordPress salts
- **Token Expiry**: Tokens expire after 24 hours by default
- **Auto-refresh**: Plugin refreshes tokens within 1 hour of expiry
- **HTTPS Required**: All API calls must use HTTPS in production
- **Rate Limiting**: Implement rate limiting on authentication endpoints

## 🧪 Testing

### Test Accounts

Superuser account:
- **Email**: info@writgo.nl
- **Password**: (generated and stored securely)
- **License Type**: superuser
- **Credits**: 999,999,999 (unlimited)
- **Expires**: Never

### Test Scenarios

1. **Login**: Test email/password authentication
2. **Token Validation**: Verify Bearer token works for API calls
3. **Token Refresh**: Test automatic token refresh
4. **Logout**: Verify token is invalidated on logout
5. **Credit Deduction**: Confirm credits are deducted correctly
6. **Multiple Licenses**: Test users with multiple licenses
7. **Expired Tokens**: Verify expired tokens are rejected

## 📊 Monitoring

Monitor these metrics:
- Authentication success/failure rate
- Token refresh rate
- API request rate per user
- Credit consumption per user
- License expiration warnings

## 🛠️ Troubleshooting

### "Invalid credentials" error
- Verify email and password are correct
- Check user exists in database
- Verify password hash is correct

### "Token expired" error
- Token has expired (24 hours default)
- User needs to log in again
- Plugin should auto-refresh before expiry

### "Unauthorized" error
- Bearer token not included in request
- Token is invalid or expired
- User/license is suspended

## 📚 Additional Resources

- [WordPress Plugin Documentation](../README.md)
- [API Endpoints Reference](./API_REFERENCE.md) (coming soon)
- [Security Best Practices](./SECURITY.md) (coming soon)

## 🤝 Support

For issues or questions:
- GitHub Issues: [github.com/Mikeyy1405/WritgoAI-plugin/issues](https://github.com/Mikeyy1405/WritgoAI-plugin/issues)
- Email: info@writgo.nl
