<?php
/**
 * Create Superuser Account Script
 *
 * Generates a secure password hash for the superuser account.
 * Run this script to generate the hash to use in the SQL migration.
 *
 * Usage: php create-superuser.php [password]
 *
 * @package WritgoAI-API-Server
 */

// Generate a secure random password if none provided.
$password = isset( $argv[1] ) ? $argv[1] : bin2hex( random_bytes( 16 ) );

// Generate password hash using ARGON2ID (most secure).
if ( defined( 'PASSWORD_ARGON2ID' ) ) {
	$hash = password_hash( $password, PASSWORD_ARGON2ID );
	$algorithm = 'ARGON2ID';
} else {
	// Fallback to BCRYPT if ARGON2ID not available.
	$hash = password_hash( $password, PASSWORD_BCRYPT, array( 'cost' => 12 ) );
	$algorithm = 'BCRYPT';
}

// Display results.
echo str_repeat( '=', 70 ) . "\n";
echo "WRITGOAI SUPERUSER PASSWORD HASH GENERATOR\n";
echo str_repeat( '=', 70 ) . "\n\n";

echo "Algorithm:     {$algorithm}\n";
echo "Password:      {$password}\n";
echo "Password Hash: {$hash}\n\n";

echo str_repeat( '-', 70 ) . "\n";
echo "IMPORTANT: SAVE THIS PASSWORD SECURELY!\n";
echo str_repeat( '-', 70 ) . "\n\n";

echo "Next steps:\n";
echo "1. Copy the password hash above\n";
echo "2. Update migrations/create-superuser-account.sql\n";
echo "3. Replace '\$2y\$10\$PLACEHOLDER_HASH' with the hash above\n";
echo "4. Run the SQL migration on your database\n";
echo "5. Store the password in a secure password manager\n\n";

echo "Login credentials:\n";
echo "  Email:    info@writgo.nl\n";
echo "  Password: {$password}\n\n";

echo str_repeat( '=', 70 ) . "\n";
echo "WARNING: Never commit this password or hash to version control!\n";
echo str_repeat( '=', 70 ) . "\n";
