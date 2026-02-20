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
<div class="container app-shell">
    <div class="topbar">
        <h1>Contas da casa</h1>
        <a class="button-link" href="dashboard.php">Voltar ao dashboard</a>
    </div>

    <?php if ($flash): ?>
        <div class="alert <?= e($flash['type']); ?>"><?= e($flash['message']); ?></div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="alert error"><?= e($error); ?></div>
    <?php endif; ?>

    <section>
        <h2>Nova conta</h2>
        <form method="post" action="contas.php" class="grid-form">
            <div>
                <label for="title">Descrição</label>
                <input id="title" name="title" type="text" placeholder="Ex.: Energia elétrica" required>
            </div>

            <div>
                <label for="category">Categoria</label>
                <input id="category" name="category" type="text" placeholder="Ex.: Moradia" required>
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

    <section>
        <h2>Lista de contas</h2>
        <?php if (!$accounts): ?>
            <p class="meta">Você ainda não cadastrou contas.</p>
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
                    <?php foreach ($accounts as $account): ?>
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
