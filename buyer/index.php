<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('buyer');

$vegetables = $pdo->query(
    'SELECT
        v.id,
        v.name,
        v.variety,
        v.description,
        v.unit,
        v.price,
        COALESCE(i.quantity, 0) AS stock
     FROM vegetables AS v
     LEFT JOIN inventory AS i ON i.vegetable_id = v.id
     WHERE v.active = 1
     ORDER BY v.name'
)->fetchAll();

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

<?php if (!$vegetables): ?>
    <section class="panel empty">There is no produce in the catalog yet.</section>
<?php else: ?>
    <div class="card-grid">
    <?php foreach ($vegetables as $vegetable): ?>
        <article class="card">
            <div class="product-image" aria-hidden="true">✿</div>
            <h2><?= h($vegetable['name']) ?></h2>

            <?php if ($vegetable['variety']): ?>
                <p class="muted"><?= h($vegetable['variety']) ?></p>
            <?php endif; ?>

            <p><?= h($vegetable['description'] ?? 'Fresh seasonal produce.') ?></p>
            <p>
                <strong><?= money($vegetable['price']) ?></strong>
                / <?= h($vegetable['unit']) ?>
            </p>
            <p class="muted">
                <?= number_format((float) $vegetable['stock'], 2) ?>
                <?= h($vegetable['unit']) ?> available
            </p>
            <p>
                <a href="<?= h(app_url('buyer/product.php?id=' . (int) $vegetable['id'])) ?>">
                    Product details
                </a>
            </p>

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
