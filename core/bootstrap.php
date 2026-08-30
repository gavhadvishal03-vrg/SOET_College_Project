<?php
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
