<?php
/**
 * WritgoAI Test License Generator
 * 
 * Gebruik via command line:
 * php generate-test-license.php --email=test@example.com --plan=pro
 * php generate-test-license.php --demo  (voor vaste TEST-DEMO-1234-5678 license)
 * 
 * Opties:
 *   --email    Email adres voor de license (verplicht, tenzij --demo)
 *   --plan     Plan type: starter, pro, enterprise (default: pro)
 *   --credits  Aantal credits (optioneel, default op basis van plan)
 *   --demo     Genereer vaste demo license TEST-DEMO-1234-5678
 */

// Laad config
define('LICENSING_API', true);
require_once __DIR__ . '/../config.php';

// Parse command line arguments
$options = getopt('', ['email::', 'plan::', 'credits::', 'demo']);

// Check if demo mode
$is_demo = isset($options['demo']);

if (!$is_demo && empty($options['email'])) {
    echo "❌ Error: --email is verplicht (of gebruik --demo voor een vaste demo license)\n";
    echo "Gebruik: php generate-test-license.php --email=test@example.com --plan=pro\n";
    echo "Of:      php generate-test-license.php --demo\n";
    exit(1);
}

if ($is_demo) {
    // Demo license met vaste gegevens
    $license_key = 'TEST-DEMO-1234-5678';
    $email = 'demo@writgoai.com';
    $plan = 'pro';
    $credits = 3000;
} else {
    $email = $options['email'];
    $plan = $options['plan'] ?? 'pro';

    // Credits op basis van plan
    $plan_credits = [
        'starter' => 1000,
        'pro' => 3000,
        'enterprise' => 10000,
    ];

    if (!isset($plan_credits[$plan])) {
        echo "❌ Error: Ongeldig plan. Kies uit: starter, pro, enterprise\n";
        exit(1);
    }

    $credits = $options['credits'] ?? $plan_credits[$plan];
}

try {
    $pdo = get_pdo_connection();
    
    // Genereer license key (tenzij demo)
    if (!$is_demo) {
        $license_key = generate_license_key();
        // Prefix met TEST- voor duidelijkheid
        $license_key = 'TEST-' . substr($license_key, 5);
    }
    
    // Check of license key al bestaat
    $stmt = $pdo->prepare('SELECT license_key FROM licenses WHERE license_key = ?');
    $stmt->execute([$license_key]);
    if ($stmt->fetch()) {
        echo "⚠️  License key bestaat al: $license_key\n";
        echo "Verwijder deze eerst of gebruik een andere email.\n";
        exit(1);
    }
    
    // Insert license
    $stmt = $pdo->prepare('
        INSERT INTO licenses (
            license_key, email, stripe_customer_id, stripe_subscription_id,
            stripe_price_id, status, plan_name, activated_at, expires_at,
            created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 1 YEAR), NOW(), NOW())
    ');
    
    $stmt->execute([
        $license_key,
        $email,
        'cus_test_' . time(),
        'sub_test_' . time(),
        'price_test_' . $plan,
        'active',
        ucfirst($plan),
    ]);
    
    $license_id = $pdo->lastInsertId();
    
    // Voeg credits toe
    $stmt = $pdo->prepare('
        INSERT INTO user_credits (
            license_id, credits_total, credits_used, period_start, period_end,
            created_at, updated_at
        ) VALUES (?, ?, 0, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 MONTH), NOW(), NOW())
    ');
    $stmt->execute([$license_id, $credits]);
    
    // Log activiteit
    $stmt = $pdo->prepare('
        INSERT INTO license_activity (license_id, activity_type, credits_amount, metadata, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ');
    $stmt->execute([
        $license_id,
        'created',
        $credits,
        json_encode(['type' => 'test_license', 'plan' => $plan, 'is_demo' => $is_demo])
    ]);
    
    echo "\n";
    echo "╔════════════════════════════════════════════════════════════╗\n";
    if ($is_demo) {
        echo "║           ✅ DEMO LICENSE SUCCESVOL AANGEMAAKT             ║\n";
    } else {
        echo "║           ✅ TEST LICENSE SUCCESVOL AANGEMAAKT             ║\n";
    }
    echo "╠════════════════════════════════════════════════════════════╣\n";
    echo "║  License Key: " . str_pad($license_key, 44) . "║\n";
    echo "║  Email:       " . str_pad($email, 44) . "║\n";
    echo "║  Plan:        " . str_pad(ucfirst($plan), 44) . "║\n";
    echo "║  Credits:     " . str_pad($credits, 44) . "║\n";
    echo "║  Status:      " . str_pad('Active', 44) . "║\n";
    echo "║  Geldig tot:  " . str_pad(date('Y-m-d', strtotime('+1 year')), 44) . "║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n";
    echo "\n";
    if ($is_demo) {
        echo "🎯 Dit is een vaste demo license voor documentatie en testing.\n";
    }
    echo "📋 Kopieer deze license key naar je WordPress plugin instellingen.\n";
    echo "\n";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    echo "\n";
    echo "Zorg ervoor dat:\n";
    echo "1. De database credentials correct zijn in config.php\n";
    echo "2. De database tabellen zijn aangemaakt (run migrations)\n";
    exit(1);
}
