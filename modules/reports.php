<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin', 'staff');

$sales = $pdo->query(
    'SELECT
        DATE(created_at) AS day,
        COUNT(DISTINCT order_code) AS order_count,
        SUM(quantity) AS quantity,
        SUM(total) AS revenue
     FROM sales
     WHERE status = "paid"
     GROUP BY DATE(created_at)
     ORDER BY day DESC
     LIMIT 30'
)->fetchAll();
$harvests = $pdo->query(
    'SELECT v.name, SUM(h.quantity) AS quantity, v.unit
     FROM harvests AS h
     JOIN plantings AS p ON p.id = h.planting_id
     JOIN vegetables AS v ON v.id = p.vegetable_id
     GROUP BY v.id, v.name, v.unit
     ORDER BY v.name'
)->fetchAll();

$pageTitle = 'Reports';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-heading">
    <div>
        <h1>Reports</h1>
        <p class="muted">
            Sales for the last 30 recorded days and total harvest by vegetable.
        </p>
    </div>
</div>

<section class="panel section">
    <h2>Daily sales</h2>

    <?php if (!$sales): ?>
        <p class="empty">No sales to report.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Orders</th>
                        <th>Quantity</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sales as $row): ?>
                        <tr>
                            <td><?= h($row['day']) ?></td>
                            <td><?= (int) $row['order_count'] ?></td>
                            <td><?= number_format((float) $row['quantity'], 2) ?></td>
                            <td><?= money($row['revenue']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="panel section">
    <h2>Harvest totals by vegetable</h2>

    <?php if (!$harvests): ?>
        <p class="empty">No harvests to report.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Vegetable</th>
                        <th>Total harvested</th>
                        <th>Unit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($harvests as $row): ?>
                        <tr>
                            <td><?= h($row['name']) ?></td>
                            <td><?= number_format((float) $row['quantity'], 2) ?></td>
                            <td><?= h($row['unit']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
