<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_auth();

$pdo = db();
$q = trim((string)($_GET['q'] ?? ''));
$category = trim((string)($_GET['category'] ?? ''));

$categories = $pdo->query(
    'SELECT DISTINCT category FROM books WHERE category <> "" ORDER BY category'
)->fetchAll(PDO::FETCH_COLUMN);

$sql = 'SELECT book_id, title, author, category, isbn, publication_year, shelf_location, availability_status
        FROM books
        WHERE 1 = 1';
$params = [];

/*
 * Partial / hint search:
 * "data" can match "Database System Concepts".
 * "computer net" can match different parts of a title/author/category.
 * Unique placeholders avoid the PDO native-prepared-statement error that can
 * happen when the same named placeholder is reused multiple times.
 */
if ($q !== '') {
    $tokens = preg_split('/\s+/', $q, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $tokens = array_slice($tokens, 0, 6);

    foreach ($tokens as $index => $token) {
        $safeToken = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $token);
        $like = '%' . $safeToken . '%';

        $sql .= " AND (
            title LIKE :title{$index} ESCAPE '\\\\'
            OR author LIKE :author{$index} ESCAPE '\\\\'
            OR category LIKE :category_search{$index} ESCAPE '\\\\'
            OR isbn LIKE :isbn{$index} ESCAPE '\\\\'
        )";

        $params["title{$index}"] = $like;
        $params["author{$index}"] = $like;
        $params["category_search{$index}"] = $like;
        $params["isbn{$index}"] = $like;
    }
}

if ($category !== '') {
    $sql .= ' AND category = :filter_category';
    $params['filter_category'] = $category;
}

$sql .= " ORDER BY
    CASE availability_status WHEN 'Available' THEN 0 ELSE 1 END,
    title ASC
    LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

$pageTitle = 'Search Books';
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading">
    <div>
        <span class="eyebrow">Catalogue</span>
        <h1>Search books</h1>
        <p>You can type only part of a title, author, category, or ISBN.</p>
    </div>
</section>

<section class="card search-panel">
    <form method="get" class="filter-grid">
        <div class="form-group">
            <label for="q">Search keyword or hint</label>
            <input
                id="q"
                name="q"
                type="search"
                value="<?= e($q) ?>"
                placeholder="Try: data, network, clean, AI..."
                autocomplete="off"
            >
            <small class="field-help">Even a partial word such as “data” will be searched.</small>
        </div>

        <div class="form-group">
            <label for="category">Category</label>
            <select id="category" name="category">
                <option value="">All categories</option>
                <?php foreach ($categories as $item): ?>
                    <option value="<?= e($item) ?>" <?= $category === $item ? 'selected' : '' ?>>
                        <?= e($item) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-actions">
            <button class="btn btn-primary" type="submit">Search</button>
            <a class="btn btn-outline" href="<?= e(url('search.php')) ?>">Clear</a>
        </div>
    </form>
</section>

<div class="results-heading">
    <div>
        <h2><?= count($books) ?> book<?= count($books) === 1 ? '' : 's' ?> found</h2>
        <?php if ($q !== '' || $category !== ''): ?>
            <p>
                <?= $q !== '' ? 'Matching “' . e($q) . '”' : 'Filtered catalogue' ?>
                <?= $category !== '' ? ' in ' . e($category) : '' ?>
            </p>
        <?php else: ?>
            <p>Showing all catalogue records in this prototype.</p>
        <?php endif; ?>
    </div>
</div>

<?php if (!$books): ?>
    <div class="card empty-state">
        <strong>No matching book found.</strong>
        <p>Try a shorter keyword or a different part of the title, author, category, or ISBN.</p>
    </div>
<?php else: ?>
    <section class="book-grid">
        <?php foreach ($books as $book): ?>
            <article class="book-card card">
                <div class="book-topline">
                    <span class="book-category"><?= e($book['category']) ?></span>
                    <span class="badge badge-<?= e(status_class($book['availability_status'])) ?>">
                        <?= e($book['availability_status']) ?>
                    </span>
                </div>

                <div class="book-cover" aria-hidden="true">
                    <?= e(strtoupper(substr($book['title'], 0, 1))) ?>
                </div>

                <div class="book-content">
                    <h3><?= e($book['title']) ?></h3>
                    <p class="book-author">by <?= e($book['author']) ?></p>

                    <dl class="book-mini-details">
                        <div><dt>ISBN</dt><dd><?= e($book['isbn']) ?></dd></div>
                        <div><dt>Year</dt><dd><?= e((string)$book['publication_year']) ?></dd></div>
                        <div><dt>Shelf</dt><dd><?= e($book['shelf_location']) ?></dd></div>
                    </dl>
                </div>

                <a class="btn <?= $book['availability_status'] === 'Available' ? 'btn-primary' : 'btn-outline' ?> btn-block" href="<?= e(url('book.php?id=' . (int)$book['book_id'])) ?>">
                    <?= $book['availability_status'] === 'Available' ? 'View & borrow' : 'View book details' ?>
                </a>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
