<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

requireAuth();
$user = currentUser();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $amountRaw = str_replace(',', '.', trim($_POST['amount'] ?? '0'));
    $amount = (float)$amountRaw;
    $dueDate = $_POST['due_date'] ?? '';
    $status = $_POST['status'] ?? 'pendente';

    if ($title === '' || $category === '' || $dueDate === '') {
        $error = 'Preencha descrição, categoria e vencimento.';
    } elseif ($amount <= 0) {
        $error = 'Informe um valor maior que zero.';
    } elseif (!in_array($status, ['pendente', 'paga'], true)) {
        $error = 'Status inválido.';
    } else {
        $stmt = db()->prepare(
            'INSERT INTO accounts (user_id, title, category, amount, due_date, status, created_at)
             VALUES (:user_id, :title, :category, :amount, :due_date, :status, :created_at)'
        );

        $stmt->execute([
            ':user_id' => $user['id'],
            ':title' => $title,
            ':category' => $category,
            ':amount' => $amount,
            ':due_date' => $dueDate,
            ':status' => $status,
            ':created_at' => date('c'),
        ]);

        flash('success', 'Conta adicionada com sucesso.');
        redirect('contas.php');
    }
}

$flash = getFlash();
$listStmt = db()->prepare(
    'SELECT id, title, category, amount, due_date, status
     FROM accounts
     WHERE user_id = :user_id
     ORDER BY due_date ASC, id DESC'
);
$listStmt->execute([':user_id' => $user['id']]);
$accounts = $listStmt->fetchAll();

$today = new DateTimeImmutable('today');
$weekEnd = $today->modify('+6 days');
$counters = [
    'all' => count($accounts),
    'today' => 0,
    'week' => 0,
    'late' => 0,
];

$groupedRows = [
    'Hoje' => [],
    'Esta semana' => [],
    'Próximas' => [],
    'Atrasadas' => [],
];

foreach ($accounts as $account) {
    $due = DateTimeImmutable::createFromFormat('Y-m-d', $account['due_date']);
    if (!$due) {
        continue;
    }

    if ($due->format('Y-m-d') === $today->format('Y-m-d')) {
        $counters['today']++;
    }

    if ($due >= $today && $due <= $weekEnd) {
        $counters['week']++;
    }

    $isLate = $account['status'] === 'pendente' && $due < $today;
    if ($isLate) {
        $counters['late']++;
    }

    $groupLabel = 'Próximas';
    if ($isLate) {
        $groupLabel = 'Atrasadas';
    } elseif ($due->format('Y-m-d') === $today->format('Y-m-d')) {
        $groupLabel = 'Hoje';
    } elseif ($due > $today && $due <= $weekEnd) {
        $groupLabel = 'Esta semana';
    }

    $groupedRows[$groupLabel][] = $account;
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contas - <?= e(APP_NAME); ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container app-shell contas-layout with-sidebar">
    <aside class="sidebar">
        <div>
            <p class="sidebar-label">Área logada</p>
            <h2>Menu</h2>
        </div>
        <p class="meta">Olá, <?= e($user['name'] ?? 'Usuário'); ?></p>

        <nav class="sidebar-nav" aria-label="Navegação principal">
            <a class="sidebar-link" href="dashboard.php">Dashboard</a>
            <a class="sidebar-link active" href="contas.php">Contas a pagar</a>
            <a class="sidebar-link" href="#nova-conta">+ Adicionar conta</a>
        </nav>

        <form method="post" action="logout.php" class="sidebar-logout">
            <button type="submit" class="button-secondary">Sair</button>
        </form>
    </aside>

    <main class="content-panel">
        <header class="contas-header">
            <div>
                <h1>Contas a Pagar</h1>
                <p class="meta">Organize pagamentos por prioridade e vencimento.</p>
            </div>
        </header>

        <?php if ($flash): ?>
            <div class="alert <?= e($flash['type']); ?>"><?= e($flash['message']); ?></div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="alert error"><?= e($error); ?></div>
        <?php endif; ?>

        <section class="kpi-tabs" aria-label="Resumo de contas">
            <span class="tab-chip active">Todas (<?= e((string)$counters['all']); ?>)</span>
            <span class="tab-chip">Vencem hoje (<?= e((string)$counters['today']); ?>)</span>
            <span class="tab-chip">Na semana (<?= e((string)$counters['week']); ?>)</span>
            <span class="tab-chip danger">Atrasadas (<?= e((string)$counters['late']); ?>)</span>
        </section>

        <section class="toolbar">
            <div class="toolbar-filter">Categoria: <strong>Todas</strong></div>
            <div class="toolbar-search">
                <input type="search" placeholder="Buscar conta..." aria-label="Buscar conta">
            </div>
        </section>

        <section>
            <?php if (!$accounts): ?>
                <p class="meta">Você ainda não cadastrou contas.</p>
            <?php else: ?>
                <div class="table-wrapper contas-table-wrap">
                    <table class="contas-table">
                        <thead>
                        <tr>
                            <th>Fornecedor</th>
                            <th>Descrição</th>
                            <th>Vencimento</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th class="ta-right">Ações</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($groupedRows as $groupTitle => $rows): ?>
                            <?php if (!$rows) {
                                continue;
                            } ?>
                            <tr class="group-row">
                                <td colspan="6"><?= e($groupTitle); ?></td>
                            </tr>
                            <?php foreach ($rows as $account): ?>
                                <tr>
                                    <td class="supplier-cell"><?= e($account['title']); ?></td>
                                    <td><?= e($account['category']); ?></td>
                                    <td><?= e(date('d/m/Y', strtotime($account['due_date']))); ?></td>
                                    <td><?= e(brl((float)$account['amount'])); ?></td>
                                    <td>
                                        <span class="badge <?= e($account['status']); ?>">
                                            <?= e($account['status'] === 'paga' ? 'Paga' : 'Pendente'); ?>
                                        </span>
                                    </td>
                                    <td class="ta-right">
                                        <button class="mini-btn" type="button" disabled>Editar</button>
                                        <button class="mini-btn danger" type="button" disabled>Excluir</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section id="nova-conta" class="form-panel">
            <h2>Nova conta</h2>
            <form method="post" action="contas.php" class="grid-form">
                <div>
                    <label for="title">Fornecedor</label>
                    <input id="title" name="title" type="text" placeholder="Ex.: Luz & Energia" required>
                </div>

                <div>
                    <label for="category">Descrição</label>
                    <input id="category" name="category" type="text" placeholder="Ex.: Conta de energia" required>
                </div>

                <div>
                    <label for="amount">Valor (R$)</label>
                    <input id="amount" name="amount" type="number" step="0.01" min="0" required>
                </div>

                <div>
                    <label for="due_date">Vencimento</label>
                    <input id="due_date" name="due_date" type="date" required>
                </div>

                <div>
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="pendente">Pendente</option>
                        <option value="paga">Paga</option>
                    </select>
                </div>

                <div class="grid-full">
                    <button type="submit">Salvar conta</button>
                </div>
            </form>
        </section>
    </main>
</div>
</body>
</html>
