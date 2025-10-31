<?php
require_once '../config/config.php';
require_once '../includes/auth_functions.php';

$auth = new Auth();
$auth->logout();