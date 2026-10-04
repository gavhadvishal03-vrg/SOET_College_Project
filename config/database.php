<?php
/**
 * SOET College Website - Database Configuration
 *
 * Defines connection parameters for the MySQL/MariaDB database.
 * Supports environment variable overrides for containerized or custom deployments,
 * with fallbacks configured for the standard local XAMPP environment (port 3307/3306).
 */

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3307');
define('DB_NAME', getenv('DB_NAME') ?: 'soet_college');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');
