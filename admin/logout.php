<?php
require_once __DIR__ . '/../core/bootstrap.php';
$auth = new Auth();
$auth->logout();
redirect(APP_URL . '/admin/login.php');
