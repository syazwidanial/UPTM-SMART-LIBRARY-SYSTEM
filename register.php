<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_guest();

$errors = [];
$form = [
    'student_id' => '',
    'full_name' => '',
    'email' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Your session expired. Please refresh the page and try again.';
    } else {
        $form['student_id'] = strtoupper(trim((string)($_POST['student_id'] ?? '')));
        $form['full_name'] = trim((string)($_POST['full_name'] ?? ''));
        $form['email'] = strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');

        if ($form['student_id'] === '' || !preg_match('/^[A-Z0-9-]{4,30}$/', $form['student_id'])) {
            $errors[] = 'Student ID must contain 4–30 letters, numbers, or hyphens.';
        }

        if (mb_strlen($form['full_name']) < 3 || mb_strlen($form['full_name']) > 120) {
            $errors[] = 'Please enter your full name.';
        }

        if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }

        if (strlen($password) < 8) {
            $errors[] = 'Password must contain at least 8 characters.';
        }

        if ($password !== $confirmPassword) {
            $errors[] = 'Password confirmation does not match.';
        }

        if (!$errors) {
            $pdo = db();
            $stmt = $pdo->prepare(
                'SELECT user_id
                 FROM users
                 WHERE student_id = :student_id OR email = :email
                 LIMIT 1'
            );
            $stmt->execute([
                'student_id' => $form['student_id'],
                'email' => $form['email'],
            ]);

            if ($stmt->fetch()) {
                $errors[] = 'That student ID or email is already registered.';
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO users (student_id, full_name, email, password)
                     VALUES (:student_id, :full_name, :email, :password)'
                );
                $stmt->execute([
                    'student_id' => $form['student_id'],
                    'full_name' => $form['full_name'],
                    'email' => $form['email'],
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                ]);

                session_regenerate_id(true);
                $_SESSION['user'] = [
                    'user_id' => (int)$pdo->lastInsertId(),
                    'student_id' => $form['student_id'],
                    'full_name' => $form['full_name'],
                    'email' => $form['email'],
                ];

                flash('success', 'Your UPTM-SLS account has been created successfully.');
                redirect('dashboard.php');
            }
        }
    }
}

$pageTitle = 'Register';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-layout auth-layout-register">
    <div class="auth-copy">
        <span class="eyebrow">Create account</span>
        <h1>Start using UPTM-SLS.</h1>
        <p>
            Register once, then you can search the catalogue, borrow available books,
            keep track of due dates, and return books with feedback.
        </p>

        <div class="feature-list">
            <div class="feature-item">
                <span class="feature-icon feature-icon-red">01</span>
                <div>
                    <strong>Personal borrowing record</strong>
                    <p>Your active and returned books are linked to your own account.</p>
                </div>
            </div>
            <div class="feature-item">
                <span class="feature-icon">02</span>
                <div>
                    <strong>Clear due dates</strong>
                    <p>Choose a borrowing period and see the return date before confirming.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="auth-card card">
        <div class="card-header">
            <span class="eyebrow">New student account</span>
            <h2>Register</h2>
            <p>Fill in the details below to create an account.</p>
        </div>

        <?php if ($errors): ?>
            <div class="alert alert-error">
                <strong>Please check the following:</strong>
                <ul class="alert-list">
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" class="form-stack" novalidate>
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="student_id">Student ID</label>
                <input id="student_id" name="student_id" type="text" value="<?= e($form['student_id']) ?>" placeholder="e.g. AM2412018217" maxlength="30" required>
            </div>

            <div class="form-group">
                <label for="full_name">Full name</label>
                <input id="full_name" name="full_name" type="text" value="<?= e($form['full_name']) ?>" placeholder="Enter your full name" maxlength="120" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="<?= e($form['email']) ?>" placeholder="name@example.com" maxlength="150" autocomplete="email" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" placeholder="At least 8 characters" minlength="8" autocomplete="new-password" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm password</label>
                <input id="confirm_password" name="confirm_password" type="password" placeholder="Re-enter your password" minlength="8" autocomplete="new-password" required>
            </div>

            <button class="btn btn-primary btn-block" type="submit">Create my account</button>
        </form>

        <p class="auth-switch">Already registered? <a href="<?= e(url('login.php')) ?>">Sign in here</a>.</p>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
