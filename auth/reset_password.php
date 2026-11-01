<?php
require_once __DIR__ . '/../includes/auth.php';

$token = $_GET['token'] ?? ($_POST['token'] ?? '');
$tokenValidFormat = is_string($token) && preg_match('/^[a-f0-9]{64}$/', $token);
$error = '';
$resetComplete = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $password = $_POST['password'] ?? '';
    $confirmation = $_POST['password_confirmation'] ?? '';

    if (!$tokenValidFormat) {
        $error = 'This reset link is invalid or has expired. Request a new one.';
    } elseif (!is_string($password) || strlen($password) < 10) {
        $error = 'Choose a password with at least 10 characters.';
    } elseif (!is_string($confirmation) || !hash_equals($password, $confirmation)) {
        $error = 'The passwords do not match.';
    } else {
        try {
            $pdo->beginTransaction();

            $statement = $pdo->prepare(
                'SELECT user_id
                 FROM password_reset_tokens
                 WHERE token_hash = ? AND expires_at > CURRENT_TIMESTAMP
                 FOR UPDATE'
            );
            $statement->execute([hash('sha256', $token)]);
            $userId = $statement->fetchColumn();

            if ($userId === false) {
                $pdo->rollBack();
                $error = 'This reset link is invalid or has expired. Request a new one.';
            } else {
                $update = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
                $update->execute([password_hash($password, PASSWORD_DEFAULT), (int) $userId]);

                $delete = $pdo->prepare('DELETE FROM password_reset_tokens WHERE user_id = ?');
                $delete->execute([(int) $userId]);

                $pdo->commit();
                $resetComplete = true;
            }
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }
}

$tokenAvailable = false;
if (!$resetComplete && $tokenValidFormat) {
    $statement = $pdo->prepare(
        'SELECT 1
         FROM password_reset_tokens
         WHERE token_hash = ? AND expires_at > CURRENT_TIMESTAMP'
    );
    $statement->execute([hash('sha256', $token)]);
    $tokenAvailable = (bool) $statement->fetchColumn();
}

$pageTitle = 'Set a new password';
require __DIR__ . '/../includes/header.php';
?>
<section class="form-card">
    <?php if ($resetComplete): ?>
        <h1>Password updated</h1>
        <p>Your password has been changed. You can now log in with the new password.</p>
        <a class="button" href="<?= h(app_url('auth/login.php')) ?>">Continue to log in</a>
    <?php elseif (!$tokenAvailable): ?>
        <h1>Reset link unavailable</h1>
        <p class="muted">This link is invalid, expired, or has already been used.</p>
        <a class="button" href="<?= h(app_url('auth/forgot_password.php')) ?>">Request a new link</a>
    <?php else: ?>
        <h1>Set a new password</h1>
        <p class="muted">Choose a password with at least 10 characters.</p>

        <?php if ($error): ?>
            <div class="notice error"><?= h($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="token" value="<?= h($token) ?>">

            <div class="field">
                <label for="password">New password</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    minlength="10"
                    autocomplete="new-password"
                    required
                >
            </div>

            <div class="field">
                <label for="password_confirmation">Confirm new password</label>
                <input
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    minlength="10"
                    autocomplete="new-password"
                    required
                >
            </div>

            <button type="submit">Update password</button>
        </form>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
