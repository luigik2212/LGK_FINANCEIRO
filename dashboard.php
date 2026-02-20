<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

requireAuth();
$user = currentUser();
$flash = getFlash();

$statsStmt = db()->prepare(
    'SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status = "pendente" THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN status = "paga" THEN 1 ELSE 0 END) AS paid,
        COALESCE(SUM(CASE WHEN status = "pendente" THEN amount ELSE 0 END), 0) AS pending_amount,
        COALESCE(SUM(CASE WHEN status = "paga" THEN amount ELSE 0 END), 0) AS paid_amount
    FROM accounts
    WHERE user_id = :user_id'
);
$statsStmt->execute([':user_id' => $user['id']]);
$stats = $statsStmt->fetch() ?: [];

$recentStmt = db()->prepare(
    'SELECT title, category, amount, due_date, status
     FROM accounts
     WHERE user_id = :user_id
     ORDER BY due_date ASC, id DESC
     LIMIT 5'
);
$recentStmt->execute([':user_id' => $user['id']]);
$recentAccounts = $recentStmt->fetchAll();
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
<div class="container app-shell">
    <div class="topbar">
        <h1>Dashboard financeiro</h1>
        <div class="actions">
            <a class="button-link" href="contas.php">Gerenciar contas</a>
            <form method="post" action="logout.php">
                <button type="submit" class="button-secondary">Sair</button>
            </form>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="alert <?= e($flash['type']); ?>"><?= e($flash['message']); ?></div>
    <?php endif; ?>

    <p class="meta">Olá, <?= e($user['name'] ?? 'Usuário'); ?>. Confira o resumo das contas da sua casa.</p>

    <section class="stats-grid">
        <article class="stat-card">
            <h2>Total de contas</h2>
            <p><?= e((string)($stats['total'] ?? '0')); ?></p>
        </article>
        <article class="stat-card warning">
            <h2>Contas pendentes</h2>
            <p><?= e((string)($stats['pending'] ?? '0')); ?></p>
            <small><?= e(brl((float)($stats['pending_amount'] ?? 0))); ?></small>
        </article>
        <article class="stat-card success">
            <h2>Contas pagas</h2>
            <p><?= e((string)($stats['paid'] ?? '0')); ?></p>
            <small><?= e(brl((float)($stats['paid_amount'] ?? 0))); ?></small>
        </article>
    </section>

    <section>
        <h2>Próximas contas</h2>

        <?php if (!$recentAccounts): ?>
            <p class="meta">Nenhuma conta cadastrada ainda. Clique em "Gerenciar contas" para começar.</p>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                    <tr>
                        <th>Descrição</th>
                        <th>Categoria</th>
                        <th>Vencimento</th>
                        <th>Valor</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recentAccounts as $account): ?>
                        <tr>
                            <td><?= e($account['title']); ?></td>
                            <td><?= e($account['category']); ?></td>
                            <td><?= e(date('d/m/Y', strtotime($account['due_date']))); ?></td>
                            <td><?= e(brl((float)$account['amount'])); ?></td>
                            <td><span class="badge <?= e($account['status']); ?>"><?= e(ucfirst($account['status'])); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
</body>
</html>
