<?php
require_once 'config.php';
require_once 'functions.php';

$user_id = require_login();

// Получение роли пользователя
$user_role = get_user_role($user_id);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_id'])) {
    $request_id = $_POST['request_id'];

    try {
        // Удаление заявки
        $stmt = $pdo->prepare("DELETE FROM vip_request WHERE id = ?");
        $stmt->execute([$request_id]);
        $success_message = "Заявка успешно удалена.";
    } catch (PDOException $e) {
        die("Ошибка базы данных при удалении заявки: " . $e->getMessage());
    }
}

try {
    // Получение заявок в зависимости от роли
    if ($user_role === 'admin') {
        // Администратор видит все заявки с сортировкой по статусу
        $query = "SELECT vr.id, u.username AS reader_name, u.email AS reader_email, 
                         pu.username AS publisher_name, vr.status
                  FROM vip_request vr
                  JOIN users u ON vr.reader_id = u.id
                  JOIN users pu ON vr.publisher_id = pu.id
                  ORDER BY FIELD(vr.status, 'ожидание', 'одобрено', 'отклонено'), vr.request_date DESC";

        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $vip_requests = $stmt->fetchAll();
    } elseif ($user_role === 'publisher') {
        // Автор видит только свои заявки, исключая одобренные
        $query = "SELECT vr.id, u.username AS reader_name, u.email AS reader_email, 
                         vr.status
                  FROM vip_request vr
                  JOIN users u ON vr.reader_id = u.id
                  WHERE vr.publisher_id = ? AND vr.status != 'одобрено'
                  ORDER BY FIELD(vr.status, 'ожидание'), vr.request_date DESC";

        $stmt = $pdo->prepare($query);
        $stmt->execute([$user_id]);
        $vip_requests = $stmt->fetchAll();
    } elseif ($user_role === 'reader') {
        // Читатель видит только свои отправленные заявки
        $query = "SELECT vr.id, pu.username AS publisher_name, vr.status
                  FROM vip_request vr
                  JOIN users pu ON vr.publisher_id = pu.id
                  WHERE vr.reader_id = ?
                  ORDER BY FIELD(vr.status, 'ожидание', 'одобрено', 'отклонено'), vr.request_date DESC";

        $stmt = $pdo->prepare($query);
        $stmt->execute([$user_id]);
        $vip_requests = $stmt->fetchAll();
    } else {
        die("Неизвестная роль пользователя.");
    }
} catch (PDOException $e) {
    die("Ошибка базы данных при получении заявок: " . $e->getMessage());
}

include 'header.php';
?>

<h1>VIP Заявки</h1>

<?php if (isset($success_message)): ?>
    <p><?= h($success_message) ?></p>
<?php endif; ?>

<?php if (empty($vip_requests)): ?>
    <p>Нет новых VIP-заявок.</p>
<?php else: ?>
    <table>
        <tr>
            <?php if ($user_role === 'admin'): ?>
                <th>Читатель</th>
                <th>Email читателя</th>
                <th>Автор</th>
            <?php elseif ($user_role === 'publisher'): ?>
                <th>Читатель</th>
                <th>Email читателя</th>
            <?php else: // reader ?>
                <th>Автор</th>
            <?php endif; ?>
            <th>Статус</th>
            <th>Действия</th>
        </tr>
        <?php foreach ($vip_requests as $request): ?>
            <tr>
                <?php if ($user_role === 'admin'): ?>
                    <td><?= h($request['reader_name']) ?></td>
                    <td><?= h($request['reader_email']) ?></td>
                    <td><?= h($request['publisher_name']) ?></td>
                <?php elseif ($user_role === 'publisher'): ?>
                    <td><?= h($request['reader_name']) ?></td>
                    <td><?= h($request['reader_email']) ?></td>
                <?php else: // reader ?>
                    <td><?= h($request['publisher_name']) ?></td>
                <?php endif; ?>
                <td><?= h($request['status']) ?></td>
                <td>
                    <form method="post" action="" style="display:inline;">
                        <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                        <button type="submit" onclick="return confirm('Вы уверены, что хотите удалить эту заявку?');">Удалить</button>
                    </form>

                    <?php if ($user_role === 'publisher'): ?>
                        <form method="post" action="process_vip_request.php" style="display:inline;">
                            <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                            <button type="submit" name="approve_request" style="background-color: #4CAF50; color: white;" onclick="return confirm('Вы уверены, что хотите одобрить эту заявку?');">Одобрить</button>
                            <button type="submit" name="reject_request" onclick="return confirm('Вы уверены, что хотите отклонить эту заявку?');">Отклонить</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php include 'footer.php'; ?>