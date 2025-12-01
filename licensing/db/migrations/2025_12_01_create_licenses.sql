-- WritgoAI Licensing Database Schema
-- Migration: 2025_12_01_create_licenses
-- 
-- This migration creates the following tables:
-- 1. licenses - Stores license keys and subscription information
-- 2. user_credits - Tracks credit balances for each license
-- 3. license_activity - Logs all license-related activities
--
-- Run this migration against your database before using the licensing API.

-- ============================================================================
-- Table: licenses
-- Stores license keys linked to Stripe subscriptions
-- ============================================================================
CREATE TABLE IF NOT EXISTS licenses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    license_key VARCHAR(32) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL,
    stripe_customer_id VARCHAR(64) DEFAULT NULL,
    stripe_subscription_id VARCHAR(64) DEFAULT NULL,
    stripe_price_id VARCHAR(64) DEFAULT NULL,
    site_url VARCHAR(512) DEFAULT NULL,
    status ENUM('active', 'expired', 'suspended', 'cancelled', 'trial') NOT NULL DEFAULT 'trial',
    plan_name VARCHAR(64) DEFAULT NULL,
    activated_at DATETIME DEFAULT NULL,
    expires_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_license_key (license_key),
    INDEX idx_email (email),
    INDEX idx_stripe_customer_id (stripe_customer_id),
    INDEX idx_stripe_subscription_id (stripe_subscription_id),
    INDEX idx_status (status),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Table: user_credits
-- Tracks credit balances for each license
-- Credits are refreshed monthly based on subscription plan
-- ============================================================================
CREATE TABLE IF NOT EXISTS user_credits (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    license_id INT UNSIGNED NOT NULL,
    credits_total INT NOT NULL DEFAULT 0,
    credits_used INT NOT NULL DEFAULT 0,
    credits_remaining INT GENERATED ALWAYS AS (credits_total - credits_used) STORED,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE CASCADE,
    INDEX idx_license_id (license_id),
    INDEX idx_period (period_start, period_end),
    UNIQUE KEY unique_license_period (license_id, period_start, period_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Table: license_activity
-- Logs all activities related to licenses for auditing and debugging
-- ============================================================================
CREATE TABLE IF NOT EXISTS license_activity (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    license_id INT UNSIGNED NOT NULL,
    activity_type ENUM(
        'created',
        'activated',
        'deactivated',
        'renewed',
        'expired',
        'suspended',
        'cancelled',
        'credits_consumed',
        'credits_refunded',
        'credits_refreshed',
        'validation',
        'site_changed'
    ) NOT NULL,
    credits_amount INT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    metadata JSON DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE CASCADE,
    INDEX idx_license_id (license_id),
    INDEX idx_activity_type (activity_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
