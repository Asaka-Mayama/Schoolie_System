<?php

require_once __DIR__ . '/config.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error    = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {

        $stmt = mysqli_prepare($conn, 'SELECT id, username, password_hash FROM users WHERE username = ?');
        mysqli_stmt_bind_param($stmt, 's', $username);
        mysqli_stmt_execute($stmt);
        $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_user_id']   = (int) $user['id'];
            $_SESSION['admin_username']  = (string) $user['username'];
            header('Location: index.php');
            exit;
        }
        $error = 'Invalid username or password. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign In — Schoolie</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-page">

    <main class="login-card">
        <div class="login-brand">
            <img class="login-logo" src="uploads/logo/logo.png" alt="Schoolie logo">
            <h1>Schoolie</h1>
            <p>School &amp; Office Supplies Inventory System</p>
        </div>

        <?php if ($error !== ''): ?>
            <div class="alert alert-error" role="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" action="login.php" class="login-form" novalidate>
            <label for="username">Username</label>
            <input type="text" id="username" name="username"
                   value="<?= htmlspecialchars($username, ENT_QUOTES) ?>"
                   placeholder="Enter username" autocomplete="username"
                   required autofocus>

            <label for="password">Password</label>
            <input type="password" id="password" name="password"
                   placeholder="Enter password" autocomplete="current-password"
                   required>

            <button type="submit" class="btn btn-primary btn-block">Sign In</button>
        </form>

        <p class="login-hint">
            Demo credentials &mdash; user: <code>admin</code>
            &nbsp;&middot;&nbsp; pass: <code>admin123</code>
        </p>
    </main>

</body>
</html>
