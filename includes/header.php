<?php
declare(strict_types=1);

$pageTitle = $pageTitle ?? APP_NAME;
$user = current_user();
$flashes = get_flashes();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="UPTM Smart Library System">
    <title><?= e($pageTitle) ?> | <?= e(APP_SHORT_NAME) ?></title>
    <?php $cssFile = __DIR__ . '/../assets/css/style.css'; $cssVersion = is_file($cssFile) ? (string) filemtime($cssFile) : '3'; ?>
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>?v=<?= e($cssVersion) ?>">
</head>
<body>
<a class="skip-link" href="#main-content">Skip to content</a>

<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="<?= e(url(is_logged_in() ? 'dashboard.php' : 'login.php')) ?>">
            <span class="brand-mark">SLS</span>
            <span>
                <strong>UPTM Smart Library</strong>
                <small>Simple. Clear. Organised.</small>
            </span>
        </a>

        <?php if ($user): ?>
            <nav class="main-nav" aria-label="Main navigation">
                <a href="<?= e(url('dashboard.php')) ?>">Dashboard</a>
                <a href="<?= e(url('search.php')) ?>">Search Books</a>
                <a href="<?= e(url('borrowings.php')) ?>">My Borrowings</a>
            </nav>

            <div class="user-menu">
                <span class="user-name"><?= e($user['full_name']) ?></span>
                <form method="post" action="<?= e(url('logout.php')) ?>" class="inline-form">
                    <?= csrf_field() ?>
                    <button class="btn btn-outline btn-small" type="submit">Logout</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</header>

<main id="main-content" class="main-content">
    <div class="container">
        <?php foreach ($flashes as $type => $messages): ?>
            <?php foreach ($messages as $message): ?>
                <div class="alert alert-<?= e($type) ?>" role="alert">
                    <?= e($message) ?>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
