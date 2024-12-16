<?php
require_once 'config.php';
require_once 'functions.php';

$user_id = require_login();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Удаление подписки
    if (isset($_POST['delete_subscription'])) {
        $publisher_id = $_POST['publisher_id'];

        try {
            $stmt = $pdo->prepare("DELETE FROM subscriptions WHERE subscriber_id = ? AND publisher_id = ?");
            $stmt->execute([$user_id, $publisher_id]);
            header("Location: subscriptions.php");
            exit;
        } catch (PDOException $e) {
            die("Ошибка базы данных при удалении подписки: " . $e->getMessage());
        }
    }

    // Отправка VIP-заявки
    if (isset($_POST['vip_request'])) {
        $publisher_id = $_POST['publisher_id'];

        try {
            // Получаем имя пользователя
            $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->execute([$publisher_id]);
            $publisher = $stmt->fetch();

            $stmt = $pdo->prepare("INSERT INTO vip_request (reader_id, publisher_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $publisher_id]);
            $success_message = "Заявка на VIP-подписку отправлена пользователю {$publisher['username']}.";
        } catch (PDOException $e) {
            die("Ошибка базы данных при отправке VIP-заявки: " . $e->getMessage());
        }
    }
}

try {
    // Поиск подписок
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $query = "SELECT u.id, u.username, u.email, u.img, s.created_at as subscribed_at, 
              (SELECT vip_subscription FROM subscriptions WHERE subscriber_id = ? AND publisher_id = u.id) as vip_subscription
              FROM users u 
              INNER JOIN subscriptions s ON u.id = s.publisher_id 
              WHERE s.subscriber_id = ?";

    if ($search) {
        $query .= " AND (u.username LIKE ? OR u.email LIKE ?)";
        $search_param = "%$search%";
    }
    $query .= " ORDER BY vip_subscription DESC, s.created_at DESC";  // Сортировка по VIP-подписке и дате подписки

    $stmt = $pdo->prepare($query);
    if ($search) {
        $stmt->execute([$user_id, $user_id, $search_param, $search_param]);
    } else {
        $stmt->execute([$user_id, $user_id]);
    }
    $subscriptions = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Ошибка базы данных при получении подписок: " . $e->getMessage());
}
include 'header.php';
?>

<h1>Мои подписки</h1>

<form class="search-form" method="get">
    <input type="text" name="search" placeholder="Поиск по подпискам" value="<?= h($search) ?>">
    <input type="submit" value="Поиск">
</form>

<?php if (isset($success_message)): ?>
    <p><?= h($success_message) ?></p>
<?php endif; ?>

<?php if (empty($subscriptions)): ?>
    <?php if ($search): ?>
        <p>По вашему запросу "<?= h($search) ?>" подписки не найдены</p>
    <?php else: ?>
        <p>У вас пока нет подписок</p>
    <?php endif; ?>
<?php else: ?>
    <table>
        <tr>
            <th>Фото</th>
            <th>Имя пользователя</th>
            <th>Email</th>
            <th>Дата подписки</th>
            <th>Действия</th>
        </tr>
        <?php foreach ($subscriptions as $subscription): ?>
            <tr>
                <td>
                    <?php if (!empty($subscription['img'])): ?>
                        <img src="<?= h($subscription['img']) ?>" alt="<?= h($subscription['username']) ?>" style="width: 100px; height: 100px; border-radius: 50%;">
                    <?php else: ?>
                        <img src="uploads\default_avatar.png" alt="Дефолтное изображение" style="width: 100px; height: 100px; border-radius: 50%;">
                    <?php endif; ?>
                </td>
                <td>
                    <?= h($subscription['username']) ?>
                    <?php if ($subscription['vip_subscription']): ?>
                        <span title="VIP Автор" style="color: gold; font-weight: bold;">★</span>
                    <?php endif; ?>
                </td>
                <td><?= h($subscription['email']) ?></td>
                <td><?= h($subscription['subscribed_at']) ?></td>
                <td>
                    <form method="post" onsubmit="return confirm('Вы уверены, что хотите отписаться?');">
                        <input type="hidden" name="publisher_id" value="<?= $subscription['id'] ?>">
                        <button type="submit" name="delete_subscription">Отписаться</button>
                    </form>
                    <?php if (!$subscription['vip_subscription']): ?>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="publisher_id" value="<?= $subscription['id'] ?>">
                            <button type="submit" name="vip_request" style="background-color: #4CAF50; color: white;">VIP заявка</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php include 'footer.php'; ?>