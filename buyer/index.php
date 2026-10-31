<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('buyer');

$search = $_GET['search'] ?? '';
if (!is_string($search)) {
    $search = '';
}
$search = trim($search);

$sort = $_GET['sort'] ?? 'name_asc';
if (!is_string($sort)) {
    $sort = 'name_asc';
}
$availability = $_GET['availability'] ?? 'all';
if (!is_string($availability)) {
    $availability = 'all';
}

// Only these fixed SQL fragments can be used for sorting.
$sortOptions = [
    'name_asc' => 'v.name ASC',
    'price_asc' => 'v.price ASC, v.name ASC',
    'price_desc' => 'v.price DESC, v.name ASC',
];
if (!isset($sortOptions[$sort])) {
    $sort = 'name_asc';
}
if (!in_array($availability, ['all', 'in_stock'], true)) {
    $availability = 'all';
}

$conditions = ['v.active = 1'];
$parameters = [];

if ($search !== '') {
    $conditions[] = "(v.name LIKE ? OR COALESCE(v.variety, '') LIKE ?
        OR COALESCE(v.description, '') LIKE ?)";
    $searchTerm = '%' . $search . '%';
    $parameters[] = $searchTerm;
    $parameters[] = $searchTerm;
    $parameters[] = $searchTerm;
}

if ($availability === 'in_stock') {
    $conditions[] = 'COALESCE(i.quantity, 0) > 0';
}

$query = 'SELECT
        v.id,
        v.name,
        v.variety,
        v.description,
        v.unit,
        v.price,
        COALESCE(i.quantity, 0) AS stock
     FROM vegetables AS v
     LEFT JOIN inventory AS i ON i.vegetable_id = v.id
     WHERE ' . implode(' AND ', $conditions)
    . ' ORDER BY ' . $sortOptions[$sort];
$statement = $pdo->prepare($query);
$statement->execute($parameters);
$vegetables = $statement->fetchAll();

$pageTitle = 'Farm shop';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-heading">
    <div>
        <h1>Fresh from the farm</h1>
        <p class="muted">Browse available vegetables and add them to your cart.</p>
    </div>
    <a class="button secondary" href="<?= h(app_url('buyer/cart.php')) ?>">
        View cart (<?= cart_count() ?>)
    </a>
</div>

<form class="catalog-filters panel" method="get">
    <div class="field catalog-search">
        <label for="search">Search vegetables</label>
        <input
            id="search"
            type="search"
            name="search"
            value="<?= h($search) ?>"
            placeholder="Search by name or description"
        >
    </div>
    <div class="field">
        <label for="availability">Availability</label>
        <select id="availability" name="availability">
            <option value="all" <?= $availability === 'all' ? 'selected' : '' ?>>
                All products
            </option>
            <option value="in_stock" <?= $availability === 'in_stock' ? 'selected' : '' ?>>
                In stock only
            </option>
        </select>
    </div>
    <div class="field">
        <label for="sort">Sort by</label>
        <select id="sort" name="sort">
            <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>
                Name: A to Z
            </option>
            <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>
                Price: low to high
            </option>
            <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>
                Price: high to low
            </option>
        </select>
    </div>
    <div class="catalog-filter-actions">
        <button type="submit">Find vegetables</button>
        <a class="button secondary" href="<?= h(app_url('buyer/index.php')) ?>">Clear</a>
    </div>
</form>

<p class="muted catalog-result-count">
    <?= count($vegetables) ?> product(s) found
    <?php if ($search !== ''): ?>
        for “<?= h($search) ?>”
    <?php endif; ?>
</p>

<?php if (!$vegetables): ?>
    <section class="panel empty">
        No vegetables match your search. Try another name or clear the filters.
    </section>
<?php else: ?>
    <div class="card-grid catalog-grid">
    <?php foreach ($vegetables as $vegetable): ?>
        <article class="card catalog-card">
            <div class="product-image catalog-product-image" aria-hidden="true">✿</div>
            <h2 class="catalog-card-title"><?= h($vegetable['name']) ?></h2>

            <?php if ($vegetable['variety']): ?>
                <p class="muted"><?= h($vegetable['variety']) ?></p>
            <?php endif; ?>

            <p class="catalog-description">
                <?= h($vegetable['description'] ?? 'Fresh seasonal produce.') ?>
            </p>
            <p class="catalog-price">
                <strong><?= money($vegetable['price']) ?></strong>
                <span>/ <?= h($vegetable['unit']) ?></span>
            </p>
            <p class="muted catalog-stock">
                <?= number_format((float) $vegetable['stock'], 2) ?>
                <?= h($vegetable['unit']) ?> available
            </p>
            <a class="catalog-details" href="<?= h(app_url(
                'buyer/product.php?id=' . (int) $vegetable['id']
            )) ?>">View product details</a>

            <?php if ((float) $vegetable['stock'] > 0): ?>
                <form method="post" action="<?= h(app_url('buyer/cart.php')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add">
                    <input
                        type="hidden"
                        name="product_id"
                        value="<?= (int) $vegetable['id'] ?>"
                    >

                    <div class="field">
                        <label for="quantity-<?= (int) $vegetable['id'] ?>">
                            Quantity (<?= h($vegetable['unit']) ?>)
                        </label>
                        <input
                            id="quantity-<?= (int) $vegetable['id'] ?>"
                            class="quantity-input"
                            type="number"
                            min="0.01"
                            max="<?= h($vegetable['stock']) ?>"
                            step="0.01"
                            name="quantity"
                            value="1"
                            required
                        >
                    </div>

                    <button type="submit">Add to cart</button>
                </form>
            <?php else: ?>
                <span class="muted">Out of stock</span>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>

    </div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
