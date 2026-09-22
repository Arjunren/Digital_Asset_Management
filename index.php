<?php
require_once __DIR__ . '/includes/auth.php';
redirect(current_user() ? 'dashboard.php' : 'login.php');

