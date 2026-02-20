<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

requireGuest();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = db()->prepare('SELECT id, password_hash FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        $error = 'E-mail ou senha inválidos.';
    } else {
        $_SESSION['user_id'] = (int)$user['id'];
        flash('success', 'Login realizado com sucesso.');
        redirect('dashboard.php');
    }
}

$flash = getFlash();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= e(APP_NAME); ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-page">
<div class="container">
    <h1>Entrar</h1>

    <?php if ($flash): ?>
        <div class="alert <?= e($flash['type']); ?>"><?= e($flash['message']); ?></div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="alert error"><?= e($error); ?></div>
    <?php endif; ?>

    <form method="post" action="index.php">
        <label for="email">E-mail</label>
        <input id="email" name="email" type="email" required>

        <label for="password">Senha</label>
        <input id="password" name="password" type="password" required>

        <button type="submit">Entrar</button>
    </form>

    <p class="meta">Ainda não tem conta? <a href="register.php">Cadastre-se</a></p>
</div>
</body>
</html>
