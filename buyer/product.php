<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('buyer');

$productId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$productId || $productId < 1) {
    http_response_code(404);
    exit('Product not found.');
}

$statement = $pdo->prepare(
    'SELECT v.*, COALESCE(i.quantity, 0) AS stock
     FROM vegetables AS v
     LEFT JOIN inventory AS i ON i.vegetable_id = v.id
     WHERE v.id = ? AND v.active = 1'
);
$statement->execute([$productId]);
$product = $statement->fetch();

if (!$product) {
    http_response_code(404);
    exit('Product not found.');
}

$pageTitle = $product['name'];
require __DIR__ . '/../includes/header.php';
?>
<nav class="product-breadcrumbs" aria-label="Breadcrumb">
    <a href="<?= h(app_url('buyer/index.php')) ?>">Home</a>
    <span aria-hidden="true">/</span>
    <a href="<?= h(app_url('buyer/index.php')) ?>">Products</a>
    <span aria-hidden="true">/</span>
    <span aria-current="page"><?= h($product['name']) ?></span>
</nav>

<section class="product-detail">
    <div class="product-detail-image">
        <img
            src="<?= h(app_url('assets/images/bell-pepper.jpg')) ?>"
            alt="Fresh red, yellow, orange, and green bell peppers"
            fetchpriority="high"
        >
        <span class="product-image-caption">
            <span aria-hidden="true">✦</span> Fresh from the farm
        </span>
    </div>

    <div class="product-detail-content">
        <p class="product-eyebrow">Farm-fresh produce</p>
        <h1><?= h($product['name']) ?></h1>

        <?php if ($product['variety']): ?>
            <p class="product-variety"><?= h($product['variety']) ?></p>
        <?php endif; ?>

        <p class="product-description">
            <?= h($product['description'] ?? 'Fresh seasonal produce, carefully selected from local farms.') ?>
        </p>

        <div class="product-price">
            <strong><?= money($product['price']) ?></strong>
            <span>/ <?= h($product['unit']) ?></span>
        </div>

        <?php if ((float) $product['stock'] > 0): ?>
            <p class="stock-badge">
                <span class="stock-indicator" aria-hidden="true"></span>
                <?= number_format((float) $product['stock'], 2) ?>
                <?= h($product['unit']) ?> available
            </p>
        <?php else: ?>
            <p class="stock-badge is-out-of-stock">
                <span class="stock-indicator" aria-hidden="true"></span>
                Currently out of stock
            </p>
        <?php endif; ?>

        <?php if ((float) $product['stock'] >= 1): ?>
            <form class="product-cart-form" method="post" action="<?= h(app_url('buyer/cart.php')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">

                <div class="product-purchase-row">
                    <div class="field product-quantity-field">
                        <label for="quantity">Quantity</label>
                        <div class="quantity-control">
                            <button
                                class="quantity-stepper"
                                type="button"
                                data-quantity-action="decrease"
                                aria-label="Decrease quantity"
                                disabled
                            >−</button>
                            <input
                                id="quantity"
                                class="quantity-input"
                                type="number"
                                min="1"
                                max="<?= h($product['stock']) ?>"
                                step="0.01"
                                name="quantity"
                                value="1"
                                inputmode="decimal"
                                required
                            >
                            <span class="quantity-unit"><?= h($product['unit']) ?></span>
                            <button
                                class="quantity-stepper"
                                type="button"
                                data-quantity-action="increase"
                                aria-label="Increase quantity"
                                <?= (float) $product['stock'] <= 1 ? 'disabled' : '' ?>
                            >+</button>
                        </div>
                    </div>

                    <button class="add-to-cart-button" type="submit">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none">
                            <path d="M3 4h2l2.2 10.1a2 2 0 0 0 2 1.6h8.5a2 2 0 0 0 1.9-1.4L21 8H6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="10" cy="19" r="1.4" fill="currentColor"/>
                            <circle cx="18" cy="19" r="1.4" fill="currentColor"/>
                        </svg>
                        Add to cart
                    </button>
                </div>
            </form>
        <?php elseif ((float) $product['stock'] > 0): ?>
            <p class="minimum-order-note">
                Less than the 1 <?= h($product['unit']) ?> minimum order is currently available.
            </p>
        <?php endif; ?>

        <p class="product-note">
            <span aria-hidden="true">✓</span> Carefully packed for freshness
        </p>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
