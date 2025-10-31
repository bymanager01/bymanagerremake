<?php
require_once '../../config/config.php';
require_once '../../includes/auth_functions.php';
require_once '../../includes/calls_functions.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(BASE_URL . '/auth/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/communication/index.php');
}

$callsManager = new CallsManager();

$action = $_POST['action'] ?? '';
$contact_id = $_POST['contact_id'] ?? 0;
$type = $_POST['type'] ?? 'video';

if (empty($action) || empty($contact_id)) {
    $_SESSION['error'] = 'Parâmetros inválidos.';
    redirect(BASE_URL . '/communication/index.php');
}

if ($action === 'start_call') {
    $result = $callsManager->startCall($_SESSION['usuario']['id'], $contact_id, $type);
    if ($result['success']) {
        redirect(BASE_URL . '/communication/call.php?id=' . $result['call_id']);
    } else {
        $_SESSION['error'] = $result['message'];
        redirect(BASE_URL . '/communication/index.php');
    }
} else {
    $_SESSION['error'] = 'Ação inválida.';
    redirect(BASE_URL . '/communication/index.php');
}