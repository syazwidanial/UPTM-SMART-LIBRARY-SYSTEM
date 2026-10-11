<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_auth();

$user = current_user();

$stmt = db()->prepare(
    "SELECT
        br.borrowing_id,
        br.borrow_date,
        br.due_date,
        br.return_date,
        br.status,
        br.rating,
        br.feedback,
        b.book_id,
        b.title,
        b.author,
        b.isbn,
        b.category
     FROM borrowings br
     INNER JOIN books b ON b.book_id = br.book_id
     WHERE br.user_id = :user_id
     ORDER BY
        CASE WHEN br.status = 'Borrowed' THEN 0 ELSE 1 END,
        br.due_date ASC,
        br.borrow_date DESC"
);
$stmt->execute(['user_id' => $user['user_id']]);
$records = $stmt->fetchAll();

$active = 0;
$returned = 0;
$overdue = 0;

foreach ($records as $record) {
    $displayStatus = borrowing_display_status($record);
    if ($displayStatus === 'Returned') {
        $returned++;
    } elseif ($displayStatus === 'Overdue') {
        $overdue++;
        $active++;
    } else {
        $active++;
    }
}

$pageTitle = 'My Borrowings';
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading">
    <div>
        <span class="eyebrow">Personal account</span>
        <h1>My borrowings</h1>
        <p>Track how long you have left, return books, and view your previous feedback.</p>
    </div>
    <a class="btn btn-primary" href="<?= e(url('search.php')) ?>">Borrow another book</a>
</section>

<section class="stats-grid">
    <article class="stat-card">
        <span class="stat-label">Active</span>
        <strong class="stat-value"><?= $active ?></strong>
        <span class="stat-note">Currently borrowed</span>
    </article>
    <article class="stat-card stat-card-red">
        <span class="stat-label">Overdue</span>
        <strong class="stat-value"><?= $overdue ?></strong>
        <span class="stat-note">Past the due date</span>
    </article>
    <article class="stat-card">
        <span class="stat-label">Returned</span>
        <strong class="stat-value"><?= $returned ?></strong>
        <span class="stat-note">Completed borrowing records</span>
    </article>
</section>

<?php if ($overdue > 0): ?>
    <aside class="penalty-notice penalty-notice-wide" role="alert">
        <div class="penalty-icon" aria-hidden="true">!</div>
        <div>
            <strong>Overdue penalty notice</strong>
            <p>You currently have <?= $overdue ?> overdue book<?= $overdue === 1 ? '' : 's' ?>. Please use the Return function as soon as possible. Books returned after the due date may be subject to a library penalty according to library policy.</p>
        </div>
    </aside>
<?php endif; ?>

<section class="card">
    <?php if (!$records): ?>
        <div class="empty-state">
            <strong>No borrowing records found.</strong>
            <p>Search the catalogue and borrow an available book to get started.</p>
            <a class="btn btn-primary" href="<?= e(url('search.php')) ?>">Search books</a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Book</th>
                        <th>Borrowed</th>
                        <th>Due / time left</th>
                        <th>Return date</th>
                        <th>Status</th>
                        <th>Feedback</th>
                        <th><span class="sr-only">Action</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $record): ?>
                        <?php
                        $displayStatus = borrowing_display_status($record);
                        $timeText = borrowing_time_text($record);
                        ?>
                        <tr>
                            <td>
                                <strong><?= e($record['title']) ?></strong>
                                <span class="table-subtext"><?= e($record['author']) ?></span>
                            </td>
                            <td><?= e(friendly_date($record['borrow_date'])) ?></td>
                            <td>
                                <?= e(friendly_date($record['due_date'])) ?>
                                <?php if ($displayStatus !== 'Returned'): ?>
                                    <span class="table-subtext <?= $displayStatus === 'Overdue' ? 'text-danger' : '' ?>"><?= e($timeText) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= e(friendly_date($record['return_date'])) ?></td>
                            <td>
                                <span class="badge badge-<?= e(status_class($displayStatus)) ?>"><?= e($displayStatus) ?></span>
                            </td>
                            <td>
                                <?php if ($record['rating']): ?>
                                    <span class="stars-static" aria-label="<?= (int)$record['rating'] ?> out of 5 stars"><?= str_repeat('★', (int)$record['rating']) ?><?= str_repeat('☆', 5 - (int)$record['rating']) ?></span>
                                <?php else: ?>
                                    <span class="table-subtext">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="table-actions">
                                <?php if ($record['status'] === 'Borrowed'): ?>
                                    <a class="btn btn-danger btn-small" href="<?= e(url('return.php?id=' . (int)$record['borrowing_id'])) ?>">Return</a>
                                <?php else: ?>
                                    <a class="text-link" href="<?= e(url('book.php?id=' . (int)$record['book_id'])) ?>">View book</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if ($record['status'] === 'Returned' && !empty($record['feedback'])): ?>
                            <tr class="feedback-row">
                                <td colspan="7"><strong>Your feedback:</strong> <?= e($record['feedback']) ?></td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
