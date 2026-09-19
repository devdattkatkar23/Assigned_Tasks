<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

$users = [
    [
        'name' => 'Staff One',
        'email' => 'staff1@example.com',
        'password' => 'Password@123'
    ],
    [
        'name' => 'Staff Two',
        'email' => 'staff2@example.com',
        'password' => 'Password@123'
    ]
];

$stmt = $pdo->prepare("
    INSERT INTO users
    (
        name,
        email,
        password
    )
    VALUES (?, ?, ?)
");

foreach ($users as $user) {

    /*
     * Check existing user.
     */

    $check = $pdo->prepare("
        SELECT id
        FROM users
        WHERE email = ?
        LIMIT 1
    ");

    $check->execute([
        $user['email']
    ]);

    if ($check->fetch()) {

        echo
            "User already exists: "
            . $user['email']
            . "<br>";

        continue;
    }

    $hashedPassword = password_hash(
        $user['password'],
        PASSWORD_DEFAULT
    );

    $stmt->execute([
        $user['name'],
        $user['email'],
        $hashedPassword
    ]);

    echo
        "Created: "
        . $user['email']
        . "<br>";
}

echo "<br>Setup completed.";