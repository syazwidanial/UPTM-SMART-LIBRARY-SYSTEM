<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_auth();

$user = current_user();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    flash('error', 'Please choose a valid book.');
    redirect('search.php');
}

$pdo = db();
$stmt = $pdo->prepare('SELECT book_id, title, author, category, shelf_location, availability_status FROM books WHERE book_id = :book_id LIMIT 1');
$stmt->execute(['book_id' => $id]);
$book = $stmt->fetch();

if (!$book) {
    flash('error', 'That book could not be found.');
    redirect('search.php');
}

$allowedDays = [7, 14, 21];
$selectedDays = (int)($_POST['borrow_days'] ?? 14);
if (!in_array($selectedDays, $allowedDays, true)) {
    $selectedDays = 14;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Your session expired. Please refresh and try again.';
    } else {
        try {
            $pdo->beginTransaction();

            $lock = $pdo->prepare('SELECT book_id, title, availability_status FROM books WHERE book_id = :book_id FOR UPDATE');
            $lock->execute(['book_id' => $id]);
            $lockedBook = $lock->fetch();

            if (!$lockedBook || $lockedBook['availability_status'] !== 'Available') {
                $pdo->rollBack();
                $error = 'Sorry, this book is no longer available to borrow.';
            } else {
                $borrowDate = new DateTimeImmutable('today');
                $dueDate = $borrowDate->modify('+' . $selectedDays . ' days');

                $insert = $pdo->prepare(
                    "INSERT INTO borrowings (user_id, book_id, borrow_date, due_date, status)
                     VALUES (:user_id, :book_id, :borrow_date, :due_date, 'Borrowed')"
                );
                $insert->execute([
                    'user_id' => $user['user_id'],
                    'book_id' => $id,
                    'borrow_date' => $borrowDate->format('Y-m-d'),
                    'due_date' => $dueDate->format('Y-m-d'),
                ]);

                $update = $pdo->prepare("UPDATE books SET availability_status = 'Borrowed' WHERE book_id = :book_id");
                $update->execute(['book_id' => $id]);

                $pdo->commit();
                flash('success', 'Book borrowed successfully. Your due date is ' . $dueDate->format('d M Y') . '.');
                redirect('borrowings.php');
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'The borrowing request could not be completed. Please try again.';
        }
    }
}

$pageTitle = 'Borrow Book';
require __DIR__ . '/includes/header.php';
?>
<a class="back-link" href="<?= e(url('book.php?id=' . (int)$book['book_id'])) ?>">← Back to book details</a>

<section class="action-layout">
    <article class="card action-summary action-summary-blue">
        <span class="eyebrow">Borrowing request</span>
        <h1>Borrow this book</h1>
        <h2><?= e($book['title']) ?></h2>
        <p>by <?= e($book['author']) ?></p>

        <div class="summary-accent-line" aria-hidden="true"></div>

        <dl class="detail-list compact-details">
            <div><dt>Category</dt><dd><?= e($book['category']) ?></dd></div>
            <div><dt>Shelf</dt><dd><?= e($book['shelf_location']) ?></dd></div>
            <div><dt>Status</dt><dd><span class="badge badge-<?= e(status_class($book['availability_status'])) ?>"><?= e($book['availability_status']) ?></span></dd></div>
        </dl>
    </article>

    <article class="card action-form-card borrow-form-card">
        <div class="card-header">
            <span class="eyebrow">Choose a period</span>
            <h2>How long would you like to borrow it?</h2>
            <p>Select one period below. Your return date updates automatically.</p>
        </div>

        <?php if ($error !== ''): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if ($book['availability_status'] !== 'Available'): ?>
            <div class="alert alert-error">This book is currently borrowed and cannot be borrowed again yet.</div>
            <a class="btn btn-outline btn-block" href="<?= e(url('search.php')) ?>">Find another book</a>
        <?php else: ?>
            <form method="post" class="form-stack" data-borrow-form>
                <?= csrf_field() ?>

                <div class="borrow-options" role="radiogroup" aria-label="Borrowing period">
                    <?php foreach ($allowedDays as $days): ?>
                        <?php $due = (new DateTimeImmutable('today'))->modify('+' . $days . ' days'); ?>
                        <label class="period-card">
                            <input
                                class="period-radio"
                                type="radio"
                                name="borrow_days"
                                value="<?= $days ?>"
                                <?= $selectedDays === $days ? 'checked' : '' ?>
                                data-due-date="<?= e($due->format('d M Y')) ?>"
                            >
                            <span class="period-card-ui">
                                <span class="period-number"><strong><?= $days ?></strong><small>days</small></span>
                                <span class="period-date"><small>Return by</small><strong><?= e($due->format('d M Y')) ?></strong></span>
                                <span class="period-check" aria-hidden="true">✓</span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="borrow-date-summary">
                    <div class="date-summary-item">
                        <span>Borrow date</span>
                        <strong><?= e((new DateTimeImmutable('today'))->format('d M Y')) ?></strong>
                    </div>
                    <div class="date-arrow" aria-hidden="true">→</div>
                    <div class="date-summary-item date-summary-return">
                        <span>Selected return date</span>
                        <strong data-due-preview><?= e((new DateTimeImmutable('today'))->modify('+' . $selectedDays . ' days')->format('d M Y')) ?></strong>
                    </div>
                </div>

                <div class="notice-panel notice-panel-red">
                    <span class="notice-icon">!</span>
                    <div>
                        <strong>Please return the book by the selected date.</strong>
                        <p>You can monitor the remaining time from <b>My Borrowings</b>.</p>
                    </div>
                </div>

                <button class="btn btn-primary btn-block btn-large" type="submit">Confirm borrowing</button>
            </form>
        <?php endif; ?>
    </article>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
