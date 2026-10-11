<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function is_logged_in(): bool
{
    return isset($_SESSION['user']) && is_array($_SESSION['user']);
}

function current_user(): ?array
{
    return is_logged_in() ? $_SESSION['user'] : null;
}

function require_auth(): void
{
    if (!is_logged_in()) {
        flash('error', 'Please sign in to continue.');
        redirect('login.php');
    }
}

function require_guest(): void
{
    if (is_logged_in()) {
        redirect('dashboard.php');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][$type][] = $message;
}

function get_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return is_array($messages) ? $messages : [];
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): bool
{
    $submitted = $_POST['csrf_token'] ?? '';
    $stored = $_SESSION['csrf_token'] ?? '';

    return is_string($submitted)
        && is_string($stored)
        && $stored !== ''
        && hash_equals($stored, $submitted);
}

function friendly_date(?string $date): string
{
    if (!$date) {
        return '—';
    }

    try {
        return (new DateTime($date))->format('d M Y');
    } catch (Throwable) {
        return e($date);
    }
}

function borrowing_display_status(array $row): string
{
    if (($row['status'] ?? '') === 'Returned') {
        return 'Returned';
    }

    if (!empty($row['due_date']) && $row['due_date'] < date('Y-m-d')) {
        return 'Overdue';
    }

    return 'Borrowed';
}

function borrowing_time_text(array $row): string
{
    if (($row['status'] ?? '') === 'Returned' || empty($row['due_date'])) {
        return 'Completed';
    }

    try {
        $today = new DateTimeImmutable('today');
        $due = new DateTimeImmutable($row['due_date']);
        $days = (int)$today->diff($due)->format('%r%a');

        if ($days < 0) {
            $over = abs($days);
            return $over . ' day' . ($over === 1 ? '' : 's') . ' overdue';
        }

        if ($days === 0) {
            return 'Due today';
        }

        return $days . ' day' . ($days === 1 ? '' : 's') . ' left';
    } catch (Throwable) {
        return '';
    }
}

function status_class(string $status): string
{
    return match ($status) {
        'Available', 'Returned' => 'success',
        'Borrowed'              => 'warning',
        'Overdue'               => 'danger',
        default                 => 'neutral',
    };
}
