<?php
/**
 * Application Core Bootstrapper
 *
 * Initializes the global application runtime environment:
 * 1. Starts output buffering to prevent premature header dispatch.
 * 2. Loads system configuration (constants, paths, database settings).
 * 3. Requires foundational core service classes (DB, Session, Security, Auth, etc.).
 * 4. Initializes secure PHP sessions.
 * 5. Applies baseline HTTP security headers (XSS protection, Clickjacking defense).
 */

ob_start();
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Session.php';
require_once __DIR__ . '/Security.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Visitor.php';
require_once __DIR__ . '/ContentManager.php';
require_once __DIR__ . '/Mailer.php';
require_once __DIR__ . '/../includes/functions.php';

Session::start();
Security::applySecurityHeaders();
