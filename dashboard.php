<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

requireAuth();
$user = currentUser();
$flash = getFlash();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= e(APP_NAME); ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>Olá, <?= e($user['name'] ?? 'Usuário'); ?> 👋</h1>

    <?php if ($flash): ?>
        <div class="alert <?= e($flash['type']); ?>"><?= e($flash['message']); ?></div>
    <?php endif; ?>

    <p>Este é seu espaço privado no SaaS.</p>
    <p class="meta">ID: <?= e((string)($user['id'] ?? '')); ?></p>
    <p class="meta">E-mail: <?= e($user['email'] ?? ''); ?></p>
    <p class="meta">Cadastro: <?= e($user['created_at'] ?? ''); ?></p>

    <form method="post" action="logout.php">
        <button type="submit">Sair</button>
    </form>
</div>
</body>
</html>
