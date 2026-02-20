<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

requireGuest();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name === '' || $email === '' || $password === '') {
        $error = 'Preencha todos os campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Informe um e-mail válido.';
    } elseif (strlen($password) < 6) {
        $error = 'A senha precisa ter no mínimo 6 caracteres.';
    } else {
        $stmt = db()->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $exists = $stmt->fetch();

        if ($exists) {
            $error = 'Este e-mail já está cadastrado.';
        } else {
            $insert = db()->prepare(
                'INSERT INTO users (name, email, password_hash, created_at) VALUES (:name, :email, :password_hash, :created_at)'
            );
            $insert->execute([
                ':name' => $name,
                ':email' => $email,
                ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
                ':created_at' => date('c'),
            ]);

            sendWelcomeEmail($name, $email);

            flash('success', 'Cadastro concluído! Verifique seu e-mail para a mensagem de boas-vindas.');
            redirect('index.php');
        }
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - <?= e(APP_NAME); ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>Criar conta</h1>

    <?php if ($error !== ''): ?>
        <div class="alert error"><?= e($error); ?></div>
    <?php endif; ?>

    <form method="post" action="register.php">
        <label for="name">Nome</label>
        <input id="name" name="name" type="text" required>

        <label for="email">E-mail</label>
        <input id="email" name="email" type="email" required>

        <label for="password">Senha</label>
        <input id="password" name="password" type="password" required>

        <button type="submit">Cadastrar</button>
    </form>

    <p class="meta">Já possui conta? <a href="index.php">Fazer login</a></p>
</div>
</body>
</html>
