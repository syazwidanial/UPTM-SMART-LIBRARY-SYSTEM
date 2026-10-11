<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_auth();

$user = current_user();
$pdo = db();

$totalBooks = (int)$pdo->query('SELECT COUNT(*) FROM books')->fetchColumn();
$availableBooks = (int)$pdo->query(
    "SELECT COUNT(*) FROM books WHERE availability_status = 'Available'"
)->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT COUNT(*)
     FROM borrowings
     WHERE user_id = :user_id
       AND status = 'Borrowed'"
);
$stmt->execute(['user_id' => $user['user_id']]);
$activeBorrowings = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT COUNT(*)
     FROM borrowings
     WHERE user_id = :user_id
       AND status = 'Borrowed'
       AND due_date < CURDATE()"
);
$stmt->execute(['user_id' => $user['user_id']]);
$overdueBorrowings = (int)$stmt->fetchColumn();

$newBooks = $pdo->query(
    "SELECT book_id, title, author, category, availability_status
     FROM books
     ORDER BY book_id DESC
     LIMIT 5"
)->fetchAll();

$stmt = $pdo->prepare(
    "SELECT
        br.borrowing_id,
        br.borrow_date,
        br.due_date,
        br.return_date,
        br.status,
        b.book_id,
        b.title,
        b.author
     FROM borrowings br
     INNER JOIN books b ON b.book_id = br.book_id
     WHERE br.user_id = :user_id
     ORDER BY
        CASE WHEN br.status = 'Borrowed' THEN 0 ELSE 1 END,
        br.due_date ASC,
        br.borrow_date DESC
     LIMIT 5"
);
$stmt->execute(['user_id' => $user['user_id']]);
$recentBorrowings = $stmt->fetchAll();

$pageTitle = 'Dashboard';
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading">
    <div>
        <span class="eyebrow">Dashboard</span>
        <h1>Hello, <?= e($user['full_name']) ?></h1>
        <p>Quick access to books, availability, and your borrowing information.</p>
    </div>

    <a class="btn btn-primary" href="<?= e(url('search.php')) ?>">Search library books</a>
</section>

<?php if ($overdueBorrowings > 0): ?>
    <aside class="penalty-notice" role="alert">
        <div class="penalty-icon" aria-hidden="true">!</div>
        <div>
            <strong>Penalty notice: <?= $overdueBorrowings ?> overdue book<?= $overdueBorrowings === 1 ? '' : 's' ?></strong>
            <p>Please return overdue books as soon as possible. Late returns may be subject to a library penalty according to library policy.</p>
        </div>
        <a class="btn btn-danger btn-small" href="<?= e(url('borrowings.php')) ?>">View overdue</a>
    </aside>
<?php endif; ?>

<section class="stats-grid" aria-label="Library summary">
    <article class="stat-card">
        <span class="stat-label">Books in catalogue</span>
        <strong class="stat-value"><?= number_format($totalBooks) ?></strong>
        <span class="stat-note">Sample records in this prototype</span>
    </article>

    <article class="stat-card">
        <span class="stat-label">Currently available</span>
        <strong class="stat-value"><?= number_format($availableBooks) ?></strong>
        <span class="stat-note">Ready to be viewed in the catalogue</span>
    </article>

    <article class="stat-card">
        <span class="stat-label">Your active borrowings</span>
        <strong class="stat-value"><?= number_format($activeBorrowings) ?></strong>
        <span class="stat-note">Books not yet returned</span>
    </article>
</section>

<section class="dashboard-grid">
    <article class="card">
        <div class="card-header row-between">
            <div>
                <span class="eyebrow">Quick search</span>
                <h2>Find a book</h2>
            </div>
        </div>

        <form action="<?= e(url('search.php')) ?>" method="get" class="search-bar">
            <label class="sr-only" for="dashboard-search">Search books</label>
            <input
                id="dashboard-search"
                name="q"
                type="search"
                placeholder="Search by title, author, category or ISBN"
            >
            <button class="btn btn-primary" type="submit">Search</button>
        </form>

        <div class="quick-links">
            <a href="<?= e(url('search.php')) ?>">
                <span>Browse catalogue</span>
                <small>View all books</small>
            </a>
            <a href="<?= e(url('borrowings.php')) ?>">
                <span>My borrowings</span>
                <small>View dates and status</small>
            </a>
        </div>
    </article>

    <article class="card">
        <div class="card-header row-between">
            <div>
                <span class="eyebrow">Recent activity</span>
                <h2>My borrowing records</h2>
            </div>
            <a class="text-link" href="<?= e(url('borrowings.php')) ?>">View all</a>
        </div>

        <?php if (!$recentBorrowings): ?>
            <div class="empty-state">
                <strong>No borrowing records yet.</strong>
                <p>Your borrowing history will appear here.</p>
            </div>
        <?php else: ?>
            <div class="record-list">
                <?php foreach ($recentBorrowings as $record): ?>
                    <?php $displayStatus = borrowing_display_status($record); ?>
                    <a class="record-item" href="<?= e(url('book.php?id=' . (int)$record['book_id'])) ?>">
                        <div>
                            <strong><?= e($record['title']) ?></strong>
                            <span><?= e($record['author']) ?></span>
                        </div>
                        <div class="record-meta">
                            <span class="badge badge-<?= e(status_class($displayStatus)) ?>">
                                <?= e($displayStatus) ?>
                            </span>
                            <small>Due <?= e(friendly_date($record['due_date'])) ?></small>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>
</section>

<?php if ($newBooks): ?>
<section class="catalogue-showcase">
    <div class="section-heading-row">
        <div>
            <span class="eyebrow">Catalogue highlights</span>
            <h2>Five books to explore</h2>
            <p>A quick look at some of the newest books in the catalogue.</p>
        </div>
        <a class="text-link" href="<?= e(url('search.php')) ?>">Browse all books</a>
    </div>

    <div class="mini-book-grid">
        <?php foreach ($newBooks as $index => $book): ?>
            <a class="mini-book-card" href="<?= e(url('book.php?id=' . (int)$book['book_id'])) ?>">
                <span class="mini-book-cover cover-tone-<?= ($index % 5) + 1 ?>" aria-hidden="true">
                    <?= e(strtoupper(substr($book['title'], 0, 1))) ?>
                </span>
                <span class="mini-book-info">
                    <strong><?= e($book['title']) ?></strong>
                    <small><?= e($book['author']) ?></small>
                    <span class="badge badge-<?= e(status_class($book['availability_status'])) ?>">
                        <?= e($book['availability_status']) ?>
                    </span>
                </span>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
