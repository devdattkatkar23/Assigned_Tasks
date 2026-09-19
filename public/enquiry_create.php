<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

$errors = [];

$data = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'company' => '',
    'source' => '',
    'requirement' => '',
    'estimated_budget' => '',
    'status' => 'New',
    'next_followup_date' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $data = [
        'name' => trim((string) ($_POST['name'] ?? '')),
        'email' => trim((string) ($_POST['email'] ?? '')),
        'phone' => trim((string) ($_POST['phone'] ?? '')),
        'company' => trim((string) ($_POST['company'] ?? '')),
        'source' => trim((string) ($_POST['source'] ?? '')),
        'requirement' => trim((string) ($_POST['requirement'] ?? '')),
        'estimated_budget' => trim(
            (string) ($_POST['estimated_budget'] ?? '')
        ),
        'status' => trim((string) ($_POST['status'] ?? '')),
        'next_followup_date' => trim(
            (string) ($_POST['next_followup_date'] ?? '')
        )
    ];

    $errors = validate_enquiry($data);

    if (!$errors) {

        try {

            $stmt = $pdo->prepare("
                INSERT INTO enquiries
                (
                    user_id,
                    name,
                    email,
                    phone,
                    company,
                    source,
                    requirement,
                    estimated_budget,
                    status,
                    next_followup_date
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )
            ");

            $stmt->execute([
                (int) $_SESSION['user_id'],
                $data['name'],
                $data['email'],
                $data['phone'],
                $data['company'],
                $data['source'],
                $data['requirement'],
                $data['estimated_budget'],
                $data['status'],
                $data['next_followup_date'] === ''
                    ? null
                    : $data['next_followup_date']
            ]);

            redirect('dashboard.php');

        } catch (PDOException $e) {

            /*
             * Duplicate company + email.
             */

            if (
                $e->getCode() === '23000'
                && str_contains(
                    strtolower($e->getMessage()),
                    'unique_company_email'
                )
            ) {

                $errors[] =
                    'This email already exists for this company.';

            } else {

                $errors[] =
                    'Unable to create enquiry.';
            }
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

    <title>New Enquiry</title>

    <link
        rel="stylesheet"
        href="style.css"
    >

</head>

<body>

<header class="topbar">

    <strong>Client Enquiry System</strong>

    <a
        href="dashboard.php"
        class="btn secondary"
    >
        Dashboard
    </a>

</header>

<main class="container">

    <div class="card">

        <h1>Create Enquiry</h1>

        <?php if ($errors): ?>

            <div class="alert error">

                <ul>

                    <?php foreach ($errors as $error): ?>

                        <li>
                            <?= e($error) ?>
                        </li>

                    <?php endforeach; ?>

                </ul>

            </div>

        <?php endif; ?>


        <form method="POST">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrf_token()) ?>"
            >

            <div class="form-grid">

                <div>

                    <label>Name *</label>

                    <input
                        type="text"
                        name="name"
                        value="<?= e($data['name']) ?>"
                        required
                    >

                </div>


                <div>

                    <label>Email *</label>

                    <input
                        type="email"
                        name="email"
                        value="<?= e($data['email']) ?>"
                        required
                    >

                </div>


                <div>

                    <label>Phone *</label>

                    <input
                        type="text"
                        name="phone"
                        value="<?= e($data['phone']) ?>"
                        required
                    >

                </div>


                <div>

                    <label>Company *</label>

                    <input
                        type="text"
                        name="company"
                        value="<?= e($data['company']) ?>"
                        required
                    >

                </div>


                <div>

                    <label>Source *</label>

                    <input
                        type="text"
                        name="source"
                        placeholder="Website / Referral / LinkedIn"
                        value="<?= e($data['source']) ?>"
                        required
                    >

                </div>


                <div>

                    <label>Estimated Budget *</label>

                    <input
                        type="number"
                        name="estimated_budget"
                        step="0.01"
                        min="0"
                        value="<?= e($data['estimated_budget']) ?>"
                        required
                    >

                </div>


                <div>

                    <label>Status *</label>

                    <select
                        name="status"
                        required
                    >

                        <?php foreach (allowed_statuses() as $item): ?>

                            <option
                                value="<?= e($item) ?>"
                                <?= $data['status'] === $item
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= e($item) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div>

                    <label>Next Follow-up Date</label>

                    <input
                        type="date"
                        name="next_followup_date"
                        value="<?= e(
                            $data['next_followup_date']
                        ) ?>"
                    >

                </div>


                <div class="full-width">

                    <label>Requirement *</label>

                    <textarea
                        name="requirement"
                        rows="5"
                        required
                    ><?= e($data['requirement']) ?></textarea>

                </div>

            </div>


            <div class="form-actions">

                <button
                    type="submit"
                    class="btn primary"
                >
                    Save Enquiry
                </button>

                <a
                    href="dashboard.php"
                    class="btn secondary"
                >
                    Cancel
                </a>

            </div>

        </form>

    </div>

</main>

</body>
</html>