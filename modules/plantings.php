<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin', 'staff');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $vegetableId = (int) ($_POST['vegetable_id'] ?? 0);
    $quantity = filter_var($_POST['quantity_planted'] ?? null, FILTER_VALIDATE_FLOAT);
    $plantedOn = $_POST['planted_on'] ?? '';

    if ($vegetableId < 1 || $quantity === false || $quantity <= 0 || !$plantedOn) {
        $error = 'Choose a vegetable and enter a positive quantity and planting date.';
    } else {
        $statement = $pdo->prepare(
            'INSERT INTO plantings (
                vegetable_id,
                quantity_planted,
                planted_on,
                expected_harvest_on
            ) VALUES (?, ?, ?, ?)'
        );
        $expectedHarvestDate = $_POST['expected_harvest_on'] ?? '';
        $statement->execute([
            $vegetableId,
            $quantity,
            $plantedOn,
            $expectedHarvestDate ?: null,
        ]);
        flash('success', 'Planting recorded.');
        redirect(app_url('modules/plantings.php'));
    }
}

$vegetables = $pdo->query(
    'SELECT id, name
     FROM vegetables
     WHERE active = 1
     ORDER BY name'
)->fetchAll();
$plantings = $pdo->query(
    'SELECT p.*, v.name AS vegetable
     FROM plantings AS p
     JOIN vegetables AS v ON v.id = p.vegetable_id
     ORDER BY p.planted_on DESC, p.id DESC'
)->fetchAll();

$pageTitle = 'Plantings';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-heading"><h1>Plantings</h1></div>

<section class="panel section">
    <h2>Record planting</h2>

    <?php if ($error): ?>
        <div class="notice error"><?= h($error) ?></div>
    <?php endif; ?>

    <?php if (!$vegetables): ?>
        <p class="muted">Add an active vegetable before recording plantings.</p>
    <?php else: ?>
        <form method="post">
            <?= csrf_field() ?>

            <div class="form-grid">
                <div class="field">
                    <label for="vegetable_id">Vegetable</label>
                    <select id="vegetable_id" name="vegetable_id" required>
                        <option value="">Choose one</option>
                        <?php foreach ($vegetables as $vegetable): ?>
                            <option value="<?= (int) $vegetable['id'] ?>">
                                <?= h($vegetable['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="quantity_planted">Quantity planted</label>
                    <input
                        id="quantity_planted"
                        type="number"
                        min="0.01"
                        step="0.01"
                        name="quantity_planted"
                        required
                    >
                </div>
                <div class="field">
                    <label for="planted_on">Planted on</label>
                    <input
                        id="planted_on"
                        type="date"
                        name="planted_on"
                        required
                        value="<?= date('Y-m-d') ?>"
                    >
                </div>
                <div class="field">
                    <label for="expected_harvest_on">Expected harvest</label>
                    <input
                        id="expected_harvest_on"
                        type="date"
                        name="expected_harvest_on"
                    >
                </div>
            </div>

            <button type="submit">Save planting</button>
        </form>
    <?php endif; ?>
</section>

<section class="panel section">
    <h2>Planting records</h2>

    <?php if (!$plantings): ?>
        <p class="empty">No plantings recorded.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Vegetable</th>
                        <th>Quantity</th>
                        <th>Planted</th>
                        <th>Expected harvest</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($plantings as $planting): ?>
                        <tr>
                            <td><?= h($planting['vegetable']) ?></td>
                            <td>
                                <?= number_format((float) $planting['quantity_planted'], 2) ?>
                            </td>
                            <td><?= h($planting['planted_on']) ?></td>
                            <td><?= h($planting['expected_harvest_on'] ?? '—') ?></td>
                            <td><?= h(ucfirst($planting['status'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
