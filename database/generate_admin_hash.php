<?php
/**
 * database/generate_admin_hash.php
 *
 * Run this once from the command line to generate a bcrypt hash for your
 * chosen admin password, then paste it into schema.sql (or run the UPDATE
 * statement it prints for you).
 *
 * Usage:
 *   php generate_admin_hash.php YourStrongPassword123
 */

if ($argc < 2) {
    echo "Usage: php generate_admin_hash.php <password>\n";
    exit(1);
}

$password = $argv[1];
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Password hash:\n$hash\n\n";
echo "Run this SQL to set/update your admin password:\n";
echo "UPDATE users SET password = '$hash' WHERE email = 'admin@startupportal.test';\n";
