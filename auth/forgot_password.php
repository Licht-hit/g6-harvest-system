<?php
require_once __DIR__ . '/../includes/auth.php';

$submitted = false;
$developmentResetUrl = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $emailInput = $_POST['email'] ?? '';
    $email = is_string($emailInput)
        ? filter_var(trim($emailInput), FILTER_VALIDATE_EMAIL)
        : false;

    if ($email) {
        $statement = $pdo->prepare(
            'SELECT id, name, email
             FROM users
             WHERE email = ?'
        );
        $statement->execute([$email]);
        $user = $statement->fetch();

        if ($user) {
            $cooldown = $pdo->prepare(
                'SELECT COUNT(*) FROM password_reset_tokens
                 WHERE user_id = ? AND created_at > DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 60 SECOND)'
            );
            $cooldown->execute([(int) $user['id']]);

            if ((int) $cooldown->fetchColumn() === 0) {
                try {
                    send_password_reset_email($pdo, $user);
                } catch (RuntimeException $exception) {
                    error_log('Unable to prepare password reset email: ' . $exception->getMessage());
                }
            }
        }
    }

    $developmentResetUrl = $_SESSION['development_password_reset_url'] ?? null;
    unset($_SESSION['development_password_reset_url']);
    $submitted = true;
}

$pageTitle = 'Reset password';
require __DIR__ . '/../includes/header.php';
?>
<section class="form-card">
    <?php if ($submitted): ?>
        <h1>Check your inbox</h1>
        <p class="muted">
            If an account exists for that email address, password reset instructions will be sent.
            If no email arrives, check your email configuration and the PHP error log.
        </p>

        <?php if ($developmentResetUrl): ?>
            <div class="notice">
                <strong>Local development link</strong>
                <p>Open this one-time link to reset the password:</p>
                <p>
                    <a href="<?= h($developmentResetUrl) ?>">
                        <?= h($developmentResetUrl) ?>
                    </a>
                </p>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <h1>Forgot your password?</h1>
        <p class="muted">
            Enter your account email. If it is registered, we will send a one-time reset link.
        </p>
        <form method="post">
            <?= csrf_field() ?>
            <div class="field">
                <label for="email">Email</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    autocomplete="email"
                    required
                >
            </div>
            <button type="submit">Send reset link</button>
        </form>
    <?php endif; ?>

    <p class="auth-links">
        <a href="<?= h(app_url('auth/login.php')) ?>">Back to log in</a>
    </p>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
