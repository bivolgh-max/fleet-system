<?php
declare(strict_types=1);
// Copy to config.local.php only if the defaults do not match your local XAMPP setup.
// Use a dedicated low-privilege database account for any non-demo installation.
return [
    'dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=fleet_system;charset=utf8mb4',
    'user' => 'root',
    'password' => '',
];
