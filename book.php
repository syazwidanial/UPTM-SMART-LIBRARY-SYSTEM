<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_auth();

$user = current_user();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(404);
    $pageTitle = 'Book Not Found';
    require __DIR__ . '/includes/header.php';
    ?>
    <div class="card empty-state">
        <strong>Book not found.</strong>
        <p>The requested book record is not available.</p>
        <a class="btn btn-primary" href="<?= e(url('search.php')) ?>">Back to search</a>
    </div>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

$pdo = db();
$stmt = $pdo->prepare(
    'SELECT
        book_id,
        title,
        author,
        category,
        isbn,
        publication_year,
        shelf_location,
        availability_status,
        description
     FROM books
     WHERE book_id = :book_id
     LIMIT 1'
);
$stmt->execute(['book_id' => $id]);
$book = $stmt->fetch();

if (!$book) {
    http_response_code(404);
    $pageTitle = 'Book Not Found';
    require __DIR__ . '/includes/header.php';
    ?>
    <div class="card empty-state">
        <strong>Book not found.</strong>
        <p>The requested book record is not available.</p>
        <a class="btn btn-primary" href="<?= e(url('search.php')) ?>">Back to search</a>
    </div>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

$activeBorrowing = null;
if ($book['availability_status'] === 'Borrowed') {
    $stmt = $pdo->prepare(
        "SELECT borrowing_id, user_id, due_date
         FROM borrowings
         WHERE book_id = :book_id AND status = 'Borrowed'
         ORDER BY borrowing_id DESC
         LIMIT 1"
    );
    $stmt->execute(['book_id' => $id]);
    $activeBorrowing = $stmt->fetch() ?: null;
}

$pageTitle = $book['title'];
require __DIR__ . '/includes/header.php';
?>
<a class="back-link" href="<?= e(url('search.php')) ?>">← Back to book search</a>

<section class="book-detail-layout">
    <div class="book-detail-cover">
        <span><?= e(strtoupper(substr($book['title'], 0, 1))) ?></span>
    </div>

    <article class="book-detail-content">
        <div class="book-topline">
            <span class="book-category"><?= e($book['category']) ?></span>
            <span class="badge badge-<?= e(status_class($book['availability_status'])) ?>">
                <?= e($book['availability_status']) ?>
            </span>
        </div>

        <h1><?= e($book['title']) ?></h1>
        <p class="book-detail-author">by <?= e($book['author']) ?></p>

        <p class="book-description">
            <?= e($book['description'] ?: 'No description is available for this prototype record.') ?>
        </p>

        <dl class="detail-list">
            <div><dt>ISBN</dt><dd><?= e($book['isbn']) ?></dd></div>
            <div><dt>Category</dt><dd><?= e($book['category']) ?></dd></div>
            <div><dt>Publication year</dt><dd><?= e((string)$book['publication_year']) ?></dd></div>
            <div><dt>Shelf location</dt><dd><?= e($book['shelf_location']) ?></dd></div>
            <div>
                <dt>Availability</dt>
                <dd><span class="badge badge-<?= e(status_class($book['availability_status'])) ?>"><?= e($book['availability_status']) ?></span></dd>
            </div>
            <?php if ($activeBorrowing && !empty($activeBorrowing['due_date'])): ?>
                <div><dt>Expected return</dt><dd><?= e(friendly_date($activeBorrowing['due_date'])) ?></dd></div>
            <?php endif; ?>
        </dl>

        <?php if ($book['availability_status'] === 'Available'): ?>
            <div class="info-box">
                <strong>This book is currently available.</strong>
                <span>Choose a borrowing period of 7, 14, or 21 days before confirming.</span>
            </div>
            <a class="btn btn-primary book-action" href="<?= e(url('borrow.php?id=' . (int)$book['book_id'])) ?>">Borrow this book</a>
        <?php elseif ($activeBorrowing && (int)$activeBorrowing['user_id'] === (int)$user['user_id']): ?>
            <div class="info-box info-box-red">
                <strong>You currently have this book.</strong>
                <span>Due on <?= e(friendly_date($activeBorrowing['due_date'])) ?>.</span>
            </div>
            <a class="btn btn-danger book-action" href="<?= e(url('return.php?id=' . (int)$activeBorrowing['borrowing_id'])) ?>">Return this book</a>
        <?php else: ?>
            <div class="info-box info-box-red">
                <strong>This book is currently borrowed.</strong>
                <span>
                    <?= $activeBorrowing ? 'Expected return date: ' . e(friendly_date($activeBorrowing['due_date'])) . '.' : 'Please check again later.' ?>
                </span>
            </div>
        <?php endif; ?>
    </article>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
