<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_auth();

$user = current_user();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    flash('error', 'Please choose a valid borrowing record.');
    redirect('borrowings.php');
}

$pdo = db();
$stmt = $pdo->prepare(
    "SELECT br.borrowing_id, br.book_id, br.borrow_date, br.due_date, br.return_date, br.status,
            b.title, b.author, b.category
     FROM borrowings br
     INNER JOIN books b ON b.book_id = br.book_id
     WHERE br.borrowing_id = :borrowing_id AND br.user_id = :user_id
     LIMIT 1"
);
$stmt->execute(['borrowing_id' => $id, 'user_id' => $user['user_id']]);
$record = $stmt->fetch();

if (!$record) {
    flash('error', 'That borrowing record could not be found.');
    redirect('borrowings.php');
}

if ($record['status'] === 'Returned') {
    flash('error', 'This book has already been returned.');
    redirect('borrowings.php');
}

$error = '';
$rating = (int)($_POST['rating'] ?? 0);
$feedback = trim((string)($_POST['feedback'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Your session expired. Please refresh and try again.';
    } elseif ($rating < 1 || $rating > 5) {
        $error = 'Please choose a star rating from 1 to 5.';
    } elseif (mb_strlen($feedback) > 1000) {
        $error = 'Feedback must be 1000 characters or fewer.';
    } else {
        try {
            $pdo->beginTransaction();

            $lock = $pdo->prepare(
                "SELECT borrowing_id, book_id, status
                 FROM borrowings
                 WHERE borrowing_id = :borrowing_id AND user_id = :user_id
                 FOR UPDATE"
            );
            $lock->execute(['borrowing_id' => $id, 'user_id' => $user['user_id']]);
            $locked = $lock->fetch();

            if (!$locked || $locked['status'] !== 'Borrowed') {
                $pdo->rollBack();
                $error = 'This borrowing record is no longer active.';
            } else {
                $updateBorrow = $pdo->prepare(
                    "UPDATE borrowings
                     SET return_date = CURDATE(), status = 'Returned', rating = :rating, feedback = :feedback
                     WHERE borrowing_id = :borrowing_id"
                );
                $updateBorrow->execute([
                    'rating' => $rating,
                    'feedback' => $feedback !== '' ? $feedback : null,
                    'borrowing_id' => $id,
                ]);

                $updateBook = $pdo->prepare("UPDATE books SET availability_status = 'Available' WHERE book_id = :book_id");
                $updateBook->execute(['book_id' => $locked['book_id']]);

                $pdo->commit();
                flash('success', 'Book returned successfully. Thank you for your feedback!');
                redirect('borrowings.php');
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'The return could not be completed. Please try again.';
        }
    }
}

$pageTitle = 'Return Book';
require __DIR__ . '/includes/header.php';
?>
<a class="back-link" href="<?= e(url('borrowings.php')) ?>">← Back to my borrowings</a>

<section class="action-layout">
    <article class="card action-summary action-summary-red">
        <span class="eyebrow eyebrow-red">Return book</span>
        <h1><?= e($record['title']) ?></h1>
        <p>by <?= e($record['author']) ?></p>

        <div class="summary-accent-line" aria-hidden="true"></div>

        <dl class="detail-list compact-details">
            <div><dt>Borrowed</dt><dd><?= e(friendly_date($record['borrow_date'])) ?></dd></div>
            <div><dt>Due date</dt><dd><?= e(friendly_date($record['due_date'])) ?></dd></div>
            <div><dt>Returning today</dt><dd><?= e(date('d M Y')) ?></dd></div>
        </dl>
    </article>

    <article class="card action-form-card return-card">
        <div class="card-header">
            <span class="eyebrow eyebrow-red">Quick feedback</span>
            <h2>How was your experience?</h2>
            <p>Choose a rating, add an optional comment, then confirm the return.</p>
        </div>

        <?php if ($error !== ''): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" class="form-stack" data-rating-form>
            <?= csrf_field() ?>

            <fieldset class="rating-fieldset">
                <legend>Star rating</legend>
                <div class="rating-picker" data-rating-picker>
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <label class="rating-choice" data-rating-value="<?= $i ?>">
                            <input type="radio" name="rating" value="<?= $i ?>" <?= $rating === $i ? 'checked' : '' ?> required>
                            <span class="rating-star" aria-hidden="true">★</span>
                            <small><?= $i ?></small>
                        </label>
                    <?php endfor; ?>
                </div>
                <div class="rating-caption">
                    <span data-rating-caption><?= $rating > 0 ? e(['', 'Poor', 'Fair', 'Good', 'Very good', 'Excellent'][$rating]) : 'Select 1 to 5 stars' ?></span>
                </div>
            </fieldset>

            <div class="form-group">
                <label for="feedback">Feedback <span class="optional-label">(optional)</span></label>
                <textarea id="feedback" name="feedback" rows="5" maxlength="1000" placeholder="Tell us what worked well or what could be improved..."><?= e($feedback) ?></textarea>
                <small class="field-help">Maximum 1000 characters.</small>
            </div>

            <div class="notice-panel notice-panel-red">
                <span class="notice-icon">↩</span>
                <div>
                    <strong>Ready to return this book?</strong>
                    <p>After confirmation, the book will immediately become available for another user.</p>
                </div>
            </div>

            <button class="btn btn-danger btn-block btn-large" type="submit">Confirm return</button>
        </form>
    </article>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
