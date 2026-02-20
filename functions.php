<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function requireGuest(): void
{
    if (isLoggedIn()) {
        redirect('dashboard.php');
    }
}

function requireAuth(): void
{
    if (!isLoggedIn()) {
        redirect('index.php');
    }
}

function currentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }

    $stmt = db()->prepare('SELECT id, name, email, created_at FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function sendWelcomeEmail(string $toName, string $toEmail): bool
{
    $subject = 'Bem-vindo(a) ao ' . APP_NAME;
    $message = "Olá {$toName},\n\nSeu cadastro foi concluído com sucesso no " . APP_NAME . ".\nAgora você já pode fazer login e acessar apenas seus próprios dados.\n\nAbraço!";
    $headers = [
        'From: ' . APP_NAME . ' <' . APP_FROM_EMAIL . '>',
        'Content-Type: text/plain; charset=UTF-8'
    ];

    $sent = mail($toEmail, $subject, $message, implode("\r\n", $headers));

    if (!$sent) {
        $logDir = __DIR__ . '/storage/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0775, true);
        }

        $line = sprintf("[%s] Falha ao enviar e-mail para %s <%s>\n", date('c'), $toName, $toEmail);
        file_put_contents($logDir . '/emails.log', $line, FILE_APPEND);
    }

    return $sent;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
