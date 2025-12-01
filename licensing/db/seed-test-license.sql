-- WritgoAI Test License Generator
-- Run dit script om een test license aan te maken voor development/testing
-- 
-- BELANGRIJK: Vervang YOUR_EMAIL@EXAMPLE.COM met je eigen email!
-- 
-- Gebruik: mysql -u user -p database < seed-test-license.sql

-- Genereer een test license key (format: TEST-XXXX-XXXX-XXXX)
SET @license_key = CONCAT('TEST-', 
    UPPER(SUBSTRING(MD5(RAND()), 1, 4)), '-',
    UPPER(SUBSTRING(MD5(RAND()), 1, 4)), '-',
    UPPER(SUBSTRING(MD5(RAND()), 1, 4))
);

SET @email = 'YOUR_EMAIL@EXAMPLE.COM';
SET @plan = 'pro'; -- starter, pro, of enterprise
SET @credits = 3000; -- 1000 voor starter, 3000 voor pro, 10000 voor enterprise

-- Insert de test license
INSERT INTO licenses (
    license_key,
    email,
    stripe_customer_id,
    stripe_subscription_id,
    stripe_price_id,
    site_url,
    status,
    plan_name,
    activated_at,
    expires_at,
    created_at,
    updated_at
) VALUES (
    @license_key,
    @email,
    'cus_test_development',
    'sub_test_development',
    'price_test_pro',
    NULL,
    'active',
    @plan,
    NOW(),
    DATE_ADD(NOW(), INTERVAL 1 YEAR), -- 1 jaar geldig voor testen
    NOW(),
    NOW()
);

-- Haal de license ID op
SET @license_id = LAST_INSERT_ID();

-- Voeg credits toe voor de huidige periode
INSERT INTO user_credits (
    license_id,
    credits_total,
    credits_used,
    period_start,
    period_end,
    created_at,
    updated_at
) VALUES (
    @license_id,
    @credits,
    0,
    CURDATE(),
    DATE_ADD(CURDATE(), INTERVAL 1 MONTH),
    NOW(),
    NOW()
);

-- Log de activiteit
INSERT INTO license_activity (
    license_id,
    activity_type,
    credits_amount,
    metadata,
    created_at
) VALUES (
    @license_id,
    'created',
    @credits,
    JSON_OBJECT('type', 'test_license', 'plan', @plan),
    NOW()
);

-- Toon de gegenereerde license
SELECT 
    '✅ Test License Aangemaakt!' as status,
    @license_key as license_key,
    @email as email,
    @plan as plan,
    @credits as credits,
    'active' as status,
    DATE_ADD(NOW(), INTERVAL 1 YEAR) as expires_at;
