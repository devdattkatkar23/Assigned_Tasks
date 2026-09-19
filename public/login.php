<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

if (isset($_SESSION['user_id'])) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $email = trim(
        (string) ($_POST['email'] ?? '')
    );

    $password = (string) (
        $_POST['password'] ?? ''
    );

    if (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error = 'Invalid email or password.';

    } else {

        $stmt = $pdo->prepare("
            SELECT
                id,
                name,
                email,
                password
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        $stmt->execute([$email]);

        $user = $stmt->fetch();

        if (
            $user
            && password_verify(
                $password,
                $user['password']
            )
        ) {

            session_regenerate_id(true);

            $_SESSION['user_id'] =
                (int) $user['id'];

            $_SESSION['user_name'] =
                $user['name'];

            redirect('dashboard.php');

        } else {

            $error = 'Invalid email or password.';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Staff Login</title>

    <link
        rel="stylesheet"
        href="style.css"
    >
<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: "Segoe UI", Arial, sans-serif;
    min-height: 100vh;
    background: linear-gradient(135deg, #eef4ff, #f8fbff);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #1f2937;
}

.login-container {
    width: 100%;
    max-width: 450px;
    padding: 20px;
}

.card {
    background: #ffffff;
    border-radius: 18px;
    padding: 40px;
    box-shadow: 0 15px 45px rgba(31, 41, 55, 0.12);
    border: 1px solid #e5e7eb;
}

.card h1 {
    text-align: center;
    font-size: 30px;
    color: #2563eb;
    margin-bottom: 6px;
    font-weight: 700;
}

.card h2 {
    text-align: center;
    font-size: 18px;
    color: #6b7280;
    margin-bottom: 30px;
    font-weight: 500;
}

form {
    display: flex;
    flex-direction: column;
}

label {
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 8px;
    color: #374151;
}

input[type="email"],
input[type="password"] {
    width: 100%;
    height: 48px;
    border: 1px solid #d1d5db;
    border-radius: 9px;
    padding: 0 14px;
    font-size: 15px;
    margin-bottom: 20px;
    outline: none;
    transition: 0.2s;
    background: #f9fafb;
}

input[type="email"]:focus,
input[type="password"]:focus {
    border-color: #2563eb;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

.btn {
    border: none;
    height: 48px;
    border-radius: 9px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.2s;
}

.btn.primary {
    background: #2563eb;
    color: #ffffff;
}

.btn.primary:hover {
    background: #1d4ed8;
    transform: translateY(-1px);
}

.btn.full {
    width: 100%;
}

.alert {
    padding: 12px 14px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
}

.alert.error {
    background: #fef2f2;
    color: #b91c1c;
    border: 1px solid #fecaca;
}

.login-info {
    margin-top: 28px;
    padding: 18px;
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    text-align: center;
}

.login-info strong {
    display: block;
    color: #374151;
    margin-bottom: 10px;
}

.login-info p {
    font-size: 13px;
    color: #6b7280;
    margin: 5px 0;
}

@media (max-width: 500px) {
    .login-container {
        padding: 15px;
    }

    .card {
        padding: 28px 22px;
    }

    .card h1 {
        font-size: 26px;
    }
}

</style>
</head>

<body>

<div class="login-container">

    <div class="card">

        <h1>Client Enquiry</h1>

        <h2>Staff Login</h2>

        <?php if ($error): ?>

            <div class="alert error">
                <?= e($error) ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrf_token()) ?>"
            >

            <label>Email</label>

            <input
                type="email"
                name="email"
                required
                autocomplete="email"
            >

            <label>Password</label>

            <input
                type="password"
                name="password"
                required
                autocomplete="current-password"
            >

            <button
                type="submit"
                class="btn primary full"
            >
                Login
            </button>

        </form>

        

    </div>

</div>

</body>
</html>