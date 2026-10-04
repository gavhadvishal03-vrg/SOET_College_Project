<?php
/**
 * Administrator Logout Controller
 *
 * Terminates the authenticated user session, logs the logout audit event,
 * destroys session cookies, and redirects the client to the login page.
 */
require_once __DIR__ . '/../core/bootstrap.php';

$auth = new Auth();
$auth->logout();
redirect(APP_URL . '/admin/login.php');
