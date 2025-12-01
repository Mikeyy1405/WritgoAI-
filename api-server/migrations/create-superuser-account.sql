-- ============================================================================
-- WritgoAI Superuser Account Migration
-- ============================================================================
-- This migration creates the superuser account for info@writgo.nl with
-- unlimited credits and never-expiring license.
--
-- BEFORE RUNNING:
-- 1. Run scripts/create-superuser.php to generate password hash
-- 2. Replace $PLACEHOLDER_HASH below with the generated hash
-- 3. Store the password securely
--
-- USAGE:
-- mysql -u username -p database_name < create-superuser-account.sql
-- ============================================================================

-- Start transaction for atomicity
START TRANSACTION;

-- Set variables for superuser account
SET @superuser_email = 'info@writgo.nl';
SET @superuser_name = 'Writgo Admin';
SET @superuser_company = 'Writgo';
SET @license_key = 'WRITGO-SUPER-ADMIN-001';
SET @license_type = 'superuser';
SET @superuser_credits = 999999999;

-- ============================================================================
-- STEP 1: Create the superuser account
-- ============================================================================
-- CRITICAL: Replace $PLACEHOLDER_HASH with actual password hash from create-superuser.php
-- DO NOT run this migration without updating the password hash!
-- The migration will fail if you forget to update it.

-- Check if placeholder is still present (basic validation)
-- If you see this error, you forgot to update the password hash!
-- Run: php scripts/create-superuser.php
SET @check_placeholder = '$2y$10$PLACEHOLDER_HASH';
SELECT 
    CASE 
        WHEN @check_placeholder LIKE '%PLACEHOLDER%' 
        THEN SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'ERROR: Password hash placeholder not replaced! Run scripts/create-superuser.php first.'
    END AS validation;

INSERT INTO wp_writgo_users (
    email,
    password_hash,
    name,
    company,
    email_verified_at,
    created_at,
    updated_at
) VALUES (
    @superuser_email,
    '$2y$10$PLACEHOLDER_HASH', -- ⚠️ UPDATE THIS WITH ACTUAL HASH FROM create-superuser.php ⚠️
    @superuser_name,
    @superuser_company,
    NOW(), -- Email already verified
    NOW(),
    NOW()
) ON DUPLICATE KEY UPDATE
    updated_at = NOW();

-- Get the user ID
SET @user_id = LAST_INSERT_ID();

-- ============================================================================
-- STEP 2: Create superuser license
-- ============================================================================
INSERT INTO wp_writgo_licenses (
    license_key,
    license_type,
    status,
    expires_at,
    created_at,
    updated_at
) VALUES (
    @license_key,
    @license_type,
    'active',
    NULL, -- Never expires
    NOW(),
    NOW()
) ON DUPLICATE KEY UPDATE
    status = 'active',
    updated_at = NOW();

-- Get the license ID
SET @license_id = LAST_INSERT_ID();

-- ============================================================================
-- STEP 3: Link user to license
-- ============================================================================
INSERT INTO wp_writgo_user_licenses (
    user_id,
    license_id,
    is_owner,
    created_at
) VALUES (
    @user_id,
    @license_id,
    1, -- User owns this license
    NOW()
) ON DUPLICATE KEY UPDATE
    is_owner = 1;

-- ============================================================================
-- STEP 4: Initialize credits for superuser
-- ============================================================================
INSERT INTO wp_writgo_credits (
    license_id,
    balance,
    monthly_allowance,
    last_reset,
    created_at,
    updated_at
) VALUES (
    @license_id,
    @superuser_credits,
    @superuser_credits, -- Unlimited monthly allowance
    NOW(),
    NOW(),
    NOW()
) ON DUPLICATE KEY UPDATE
    balance = @superuser_credits,
    monthly_allowance = @superuser_credits,
    updated_at = NOW();

-- ============================================================================
-- STEP 5: Create audit log entry
-- ============================================================================
INSERT INTO wp_writgo_audit_log (
    user_id,
    action,
    entity_type,
    entity_id,
    description,
    created_at
) VALUES (
    @user_id,
    'superuser_created',
    'user',
    @user_id,
    'Superuser account created via migration',
    NOW()
);

-- Commit transaction
COMMIT;

-- ============================================================================
-- Verification Queries (Optional - run these to verify)
-- ============================================================================
-- SELECT * FROM wp_writgo_users WHERE email = 'info@writgo.nl';
-- SELECT * FROM wp_writgo_licenses WHERE license_key = 'WRITGO-SUPER-ADMIN-001';
-- SELECT * FROM wp_writgo_credits WHERE license_id = @license_id;
-- SELECT * FROM wp_writgo_user_licenses WHERE user_id = @user_id;

-- ============================================================================
-- Success Message
-- ============================================================================
SELECT 
    '✅ Superuser account created successfully!' AS Status,
    @superuser_email AS Email,
    @license_key AS License_Key,
    @superuser_credits AS Credits,
    'NEVER' AS Expires;

-- ============================================================================
-- IMPORTANT REMINDERS
-- ============================================================================
-- 1. Store the password securely in a password manager
-- 2. Update API server README.md with authentication flow
-- 3. Test login with email and password
-- 4. Verify Bearer token authentication works
-- 5. Confirm credit deductions work correctly
-- ============================================================================
