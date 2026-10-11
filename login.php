<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_guest();

$error = '';
$identifier = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Your session expired. Please refresh the page and try again.';
    } else {
        $identifier = trim((string)($_POST['identifier'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if ($identifier === '' || $password === '') {
            $error = 'Please enter your student ID/email and password.';
        } else {
            $stmt = db()->prepare(
                'SELECT user_id, student_id, full_name, email, password
                 FROM users
                 WHERE student_id = :student_id OR email = :email
                 LIMIT 1'
            );

            $stmt->execute([
                'student_id' => $identifier,
                'email' => $identifier,
            ]);

            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);

                $_SESSION['user'] = [
                    'user_id' => (int)$user['user_id'],
                    'student_id' => $user['student_id'],
                    'full_name' => $user['full_name'],
                    'email' => $user['email'],
                ];

                flash('success', 'Welcome back, ' . $user['full_name'] . '.');
                redirect('dashboard.php');
            }

            $error = 'Invalid student ID/email or password.';
        }
    }
}

$pageTitle = 'Login';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-layout">
    <div class="auth-copy">
        <span class="eyebrow">UPTM-SLS</span>
        <h1>Find library information without unnecessary steps.</h1>
        <p>
            Search for books, check availability, and view your borrowing information
            from one simple web interface.
        </p>

        <div class="feature-list">
            <div class="feature-item">
                <span class="feature-icon">01</span>
                <div>
                    <strong>Search books</strong>
                    <p>Search by title, author, category, or ISBN.</p>
                </div>
            </div>

            <div class="feature-item">
                <span class="feature-icon">02</span>
                <div>
                    <strong>Check availability</strong>
                    <p>See whether a book is currently available or borrowed.</p>
                </div>
            </div>

            <div class="feature-item">
                <span class="feature-icon">03</span>
                <div>
                    <strong>Track borrowings</strong>
                    <p>View borrowed books together with due and return dates.</p>
                </div>
            </div>
        </div>

    </div>

    <div class="auth-card card">
        <div class="card-header">
            <span class="eyebrow">Student access</span>
            <h2>Sign in</h2>
            <p>Use your registered student ID or email.</p>
        </div>

        <?php if ($error !== ''): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" class="form-stack" novalidate>
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="identifier">Student ID or email</label>
                <input
                    id="identifier"
                    name="identifier"
                    type="text"
                    autocomplete="username"
                    value="<?= e($identifier) ?>"
                    placeholder="e.g. STU001"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-wrap">
                    <input
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="current-password"
                        placeholder="Enter your password"
                        required
                    >
                    <button
                        type="button"
                        class="password-toggle"
                        data-password-toggle
                        aria-label="Show password"
                    >Show</button>
                </div>
            </div>

            <button class="btn btn-primary btn-block" type="submit">Sign in to UPTM-SLS</button>
        </form>

        <p class="auth-switch">New to UPTM-SLS? <a href="<?= e(url('register.php')) ?>">Create an account</a>.</p>

    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
