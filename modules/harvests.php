<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin', 'staff');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $plantingId = (int) ($_POST['planting_id'] ?? 0);
    $quantity = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_FLOAT);
    $harvestedOn = $_POST['harvested_on'] ?? '';

    if ($plantingId < 1 || $quantity === false || $quantity <= 0 || !$harvestedOn) {
        $error = 'Choose a planting and enter a positive quantity and harvest date.';
    } else {
        try {
            $pdo->beginTransaction();

            // Lock the planting so two staff members cannot harvest it at once.
            $statement = $pdo->prepare(
                "SELECT id, vegetable_id
                 FROM plantings
                 WHERE id = ? AND status = 'growing'
                 FOR UPDATE"
            );
            $statement->execute([$plantingId]);
            $planting = $statement->fetch();

            if (!$planting) {
                throw new RuntimeException('That planting is no longer available for harvest.');
            }

            $insert = $pdo->prepare(
                'INSERT INTO harvests (planting_id, quantity, harvested_on, quality_notes)
                 VALUES (?, ?, ?, ?)'
            );
            $notes = trim($_POST['quality_notes'] ?? '');
            $insert->execute([$plantingId, $quantity, $harvestedOn, $notes ?: null]);

            // Record the harvest and add the same amount to available stock.
            $stock = $pdo->prepare(
                'INSERT INTO inventory (vegetable_id, quantity)
                 VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)'
            );
            $stock->execute([(int) $planting['vegetable_id'], $quantity]);

            // Both database changes succeed together or are rolled back together.
            $pdo->commit();
            flash('success', 'Harvest recorded and inventory updated.');
            redirect(app_url('modules/harvests.php'));
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($exception instanceof RuntimeException) {
                $error = $exception->getMessage();
            } else {
                throw $exception;
            }
        }
    }
}

$plantings = $pdo->query(
    "SELECT p.id, p.planted_on, v.name
     FROM plantings AS p
     JOIN vegetables AS v ON v.id = p.vegetable_id
     WHERE p.status = 'growing'
     ORDER BY p.planted_on"
)->fetchAll();
$harvests = $pdo->query(
    'SELECT h.*, v.name AS vegetable, p.id AS planting_number
     FROM harvests AS h
     JOIN plantings AS p ON p.id = h.planting_id
     JOIN vegetables AS v ON v.id = p.vegetable_id
     ORDER BY h.harvested_on DESC, h.id DESC'
)->fetchAll();

$pageTitle = 'Harvests';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-heading"><h1>Harvests</h1></div>

<section class="panel section">
    <h2>Record harvest</h2>

    <?php if ($error): ?>
        <div class="notice error"><?= h($error) ?></div>
    <?php endif; ?>

    <?php if (!$plantings): ?>
        <p class="muted">There are no growing plantings available to harvest.</p>
    <?php else: ?>
        <form method="post">
            <?= csrf_field() ?>
            <div class="form-grid">
                <div class="field">
                    <label for="planting_id">Planting</label>
                    <select id="planting_id" name="planting_id" required>
                        <option value="">Choose planting</option>
                        <?php foreach ($plantings as $planting): ?>
                            <option value="<?= (int) $planting['id'] ?>">
                                #<?= (int) $planting['id'] ?> ·
                                <?= h($planting['name']) ?> (<?= h($planting['planted_on']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="quantity">Quantity harvested</label>
                    <input
                        id="quantity"
                        type="number"
                        min="0.01"
                        step="0.01"
                        name="quantity"
                        required
                    >
                </div>
                <div class="field">
                    <label for="harvested_on">Harvest date</label>
                    <input
                        id="harvested_on"
                        type="date"
                        name="harvested_on"
                        value="<?= date('Y-m-d') ?>"
                        required
                    >
                </div>
            </div>

            <div class="field">
                <label for="quality_notes">Notes</label>
                <textarea id="quality_notes" name="quality_notes"></textarea>
            </div>

            <button type="submit">Save harvest</button>
        </form>
    <?php endif; ?>
</section>

<section class="panel section">
    <h2>Harvest history</h2>

    <?php if (!$harvests): ?>
        <p class="empty">No harvests recorded.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Vegetable</th>
                        <th>Planting</th>
                        <th>Quantity</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($harvests as $harvest): ?>
                        <tr>
                            <td><?= h($harvest['harvested_on']) ?></td>
                            <td><?= h($harvest['vegetable']) ?></td>
                            <td>#<?= (int) $harvest['planting_number'] ?></td>
                            <td><?= number_format((float) $harvest['quantity'], 2) ?></td>
                            <td><?= h($harvest['quality_notes'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
