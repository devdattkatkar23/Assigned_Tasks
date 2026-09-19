<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);
    exit('Method Not Allowed.');
}

verify_csrf();

$id = filter_input(
    INPUT_POST,
    'id',
    FILTER_VALIDATE_INT
);

if (!$id || $id <= 0) {

    http_response_code(400);
    exit('Invalid enquiry ID.');
}

$userId = (int) $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| Delete only owner's enquiry
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    DELETE FROM enquiries
    WHERE id = ?
    AND user_id = ?
");

$stmt->execute([
    $id,
    $userId
]);

header('Location: dashboard.php');
exit;