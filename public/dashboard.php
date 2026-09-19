<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

$userId = (int) $_SESSION['user_id'];

$search = trim((string) ($_GET['search'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));
$fromDate = trim((string) ($_GET['from_date'] ?? ''));
$toDate = trim((string) ($_GET['to_date'] ?? ''));

$page = max(
    1,
    (int) ($_GET['page'] ?? 1)
);

$perPage = 5;

$where = [
    'e.user_id = :user_id'
];

$params = [
    ':user_id' => $userId
];

/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $where[] = "
        (
            e.name LIKE :search
            OR e.company LIKE :search
            OR e.email LIKE :search
        )
    ";

    $params[':search'] = '%' . $search . '%';
}

/*
|--------------------------------------------------------------------------
| Status filter
|--------------------------------------------------------------------------
*/

if (
    $status !== ''
    && in_array(
        $status,
        allowed_statuses(),
        true
    )
) {

    $where[] = 'e.status = :status';

    $params[':status'] = $status;
}

/*
|--------------------------------------------------------------------------
| Follow-up date range
|--------------------------------------------------------------------------
*/

if ($fromDate !== '' && valid_date($fromDate)) {

    $where[] = 'e.next_followup_date >= :from_date';

    $params[':from_date'] = $fromDate;
}

if ($toDate !== '' && valid_date($toDate)) {

    $where[] = 'e.next_followup_date <= :to_date';

    $params[':to_date'] = $toDate;
}

$whereSql = implode(' AND ', $where);

/*
|--------------------------------------------------------------------------
| Total records
|--------------------------------------------------------------------------
*/

$countStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM enquiries e
    WHERE {$whereSql}
");

$countStmt->execute($params);

$totalRecords = (int) $countStmt->fetchColumn();

$totalPages = max(
    1,
    (int) ceil($totalRecords / $perPage)
);

if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $perPage;

/*
|--------------------------------------------------------------------------
| Fetch enquiries
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        e.id,
        e.name,
        e.email,
        e.phone,
        e.company,
        e.source,
        e.requirement,
        e.estimated_budget,
        e.status,
        e.next_followup_date,
        e.created_at,
        e.updated_at
    FROM enquiries e
    WHERE {$whereSql}
    ORDER BY e.created_at DESC
    LIMIT {$perPage}
    OFFSET {$offset}
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$enquiries = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Dashboard statistics
|--------------------------------------------------------------------------
*/

$statsStmt = $pdo->prepare("
    SELECT

        COUNT(*) AS total,

        SUM(
            CASE
                WHEN status = 'New'
                THEN 1 ELSE 0
            END
        ) AS new_count,

        SUM(
            CASE
                WHEN status = 'Qualified'
                THEN 1 ELSE 0
            END
        ) AS qualified_count,

        SUM(
            CASE
                WHEN status = 'Converted'
                THEN 1 ELSE 0
            END
        ) AS converted_count,

        SUM(
            CASE
                WHEN next_followup_date < CURDATE()
                AND next_followup_date IS NOT NULL
                AND status NOT IN ('Converted', 'Not Interested')
                THEN 1 ELSE 0
            END
        ) AS overdue_count

    FROM enquiries
    WHERE user_id = ?
");

$statsStmt->execute([$userId]);

$stats = $statsStmt->fetch();

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Dashboard</title>

    <link
        rel="stylesheet"
        href="style.css"
    >

</head>

<body>

<header class="topbar">

    <div>

        <strong>Client Enquiry System</strong>

    </div>

    <div>

        Welcome,
        <strong>
            <?= e($_SESSION['user_name'] ?? 'Staff') ?>
        </strong>

        &nbsp;

        <a
            href="logout.php"
            class="btn danger"
        >
            Logout
        </a>

    </div>

</header>

<main class="container">

    <div class="page-header">

        <div>

            <h1>Dashboard</h1>

            <p>
                Manage your client enquiries.
            </p>

        </div>

        <a
            href="enquiry_create.php"
            class="btn primary"
        >
            + New Enquiry
        </a>

    </div>


    <!-- Statistics -->

    <div class="stats-grid">

        <div class="stat-card">

            <span>Total</span>

            <strong>
                <?= (int) ($stats['total'] ?? 0) ?>
            </strong>

        </div>

        <div class="stat-card">

            <span>New</span>

            <strong>
                <?= (int) ($stats['new_count'] ?? 0) ?>
            </strong>

        </div>

        <div class="stat-card">

            <span>Qualified</span>

            <strong>
                <?= (int) ($stats['qualified_count'] ?? 0) ?>
            </strong>

        </div>

        <div class="stat-card">

            <span>Converted</span>

            <strong>
                <?= (int) ($stats['converted_count'] ?? 0) ?>
            </strong>

        </div>

        <div class="stat-card overdue-card">

            <span>Overdue</span>

            <strong>
                <?= (int) ($stats['overdue_count'] ?? 0) ?>
            </strong>

        </div>

    </div>


    <!-- Search -->

    <div class="card">

        <h2>Search & Filter</h2>

        <form
            method="GET"
            class="filter-form"
        >

            <div>

                <label>Search</label>

                <input
                    type="text"
                    name="search"
                    placeholder="Name, company or email"
                    value="<?= e($search) ?>"
                >

            </div>

            <div>

                <label>Status</label>

                <select name="status">

                    <option value="">
                        All Statuses
                    </option>

                    <?php foreach (allowed_statuses() as $item): ?>

                        <option
                            value="<?= e($item) ?>"
                            <?= $status === $item ? 'selected' : '' ?>
                        >
                            <?= e($item) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div>

                <label>Follow-up From</label>

                <input
                    type="date"
                    name="from_date"
                    value="<?= e($fromDate) ?>"
                >

            </div>

            <div>

                <label>Follow-up To</label>

                <input
                    type="date"
                    name="to_date"
                    value="<?= e($toDate) ?>"
                >

            </div>

            <div class="filter-buttons">

                <button
                    type="submit"
                    class="btn primary"
                >
                    Search
                </button>

                <a
                    href="dashboard.php"
                    class="btn secondary"
                >
                    Reset
                </a>

            </div>

        </form>

    </div>


    <!-- Enquiries -->

    <div class="card">

        <div class="table-header">

            <h2>My Enquiries</h2>

            <span>
                <?= $totalRecords ?> record(s)
            </span>

        </div>

        <?php if (!$enquiries): ?>

            <div class="empty">
                No enquiries found.
            </div>

        <?php else: ?>

            <div class="table-wrapper">

                <table>

                    <thead>

                    <tr>

                        <th>ID</th>

                        <th>Client</th>

                        <th>Company</th>

                        <th>Email</th>

                        <th>Budget</th>

                        <th>Status</th>

                        <th>Follow-up</th>

                        <th>Actions</th>

                    </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($enquiries as $enquiry): ?>

                        <tr>

                            <td>
                                <?= (int) $enquiry['id'] ?>
                            </td>

                            <td>

                                <strong>
                                    <?= e($enquiry['name']) ?>
                                </strong>

                                <small>
                                    <?= e($enquiry['phone']) ?>
                                </small>

                            </td>

                            <td>
                                <?= e($enquiry['company']) ?>
                            </td>

                            <td>
                                <?= e($enquiry['email']) ?>
                            </td>

                            <td>
                                ₹<?= number_format(
                                    (float) $enquiry['estimated_budget'],
                                    2
                                ) ?>
                            </td>

                            <td>

                                <span class="status">
                                    <?= e($enquiry['status']) ?>
                                </span>

                            </td>

                            <td>

                                <?php

                                $followupStatus = followUpStatus(
                                    $enquiry['next_followup_date'],
                                    'Asia/Kolkata'
                                );

                                ?>

                                <?php if ($enquiry['next_followup_date']): ?>

                                    <strong>
                                        <?= e(
                                            $enquiry['next_followup_date']
                                        ) ?>
                                    </strong>

                                    <small>
                                        <?= e($followupStatus) ?>
                                    </small>

                                <?php else: ?>

                                    <span>
                                        No Follow-up
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td class="actions">

                                <a
                                    href="enquiry_edit.php?id=<?= (int) $enquiry['id'] ?>"
                                    class="btn small"
                                >
                                    Edit
                                </a>

                                <form
                                    method="POST"
                                    action="enquiry_delete.php"
                                    onsubmit="return confirm('Delete this enquiry?');"
                                >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= e(csrf_token()) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= (int) $enquiry['id'] ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="btn small danger"
                                    >
                                        Delete
                                    </button>

                                </form>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>


        <!-- Pagination -->

        <?php if ($totalPages > 1): ?>

            <div class="pagination">

                <?php for (
                    $i = 1;
                    $i <= $totalPages;
                    $i++
                ): ?>

                    <?php

                    $query = http_build_query([
                        'search' => $search,
                        'status' => $status,
                        'from_date' => $fromDate,
                        'to_date' => $toDate,
                        'page' => $i
                    ]);

                    ?>

                    <a
                        href="?<?= e($query) ?>"
                        class="<?= $i === $page ? 'active' : '' ?>"
                    >
                        <?= $i ?>
                    </a>

                <?php endfor; ?>

            </div>

        <?php endif; ?>

    </div>

</main>

</body>
</html>