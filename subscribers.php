<?php
require_once 'config.php';
require_once 'functions.php';

$user_id = require_login();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['delete_subscriber'])) {
        $subscriber_id = $_POST['subscriber_id'];

        try {
            $stmt = $pdo->prepare("DELETE FROM subscriptions WHERE publisher_id = ? AND subscriber_id = ?");
            $stmt->execute([$user_id, $subscriber_id]);
            header("Location: subscribers.php");
            exit;
        } catch (PDOException $e) {
            die("Ошибка базы данных при удалении подписчика: " . $e->getMessage());
        }
    }

    if (isset($_POST['remove_vip'])) {
        $subscriber_id = $_POST['subscriber_id'];

        try {
            $stmt = $pdo->prepare("UPDATE subscriptions SET vip_subscription = 0 WHERE publisher_id = ? AND subscriber_id = ?");
            $stmt->execute([$user_id, $subscriber_id]);
            header("Location: subscribers.php");
            exit;
        } catch (PDOException $e) {
            die("Ошибка базы данных при снятии VIP подписки: " . $e->getMessage());
        }
    }
}

try {
    // Поиск подписчиков
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $query = "SELECT u.id, u.username, u.email, u.created_at, s.created_at as subscribed_at, s.vip_subscription, u.img
          FROM users u 
          INNER JOIN subscriptions s ON u.id = s.subscriber_id 
          WHERE s.publisher_id = ?";

    if ($search) {
        $query .= " AND (u.username LIKE ? OR u.email LIKE ?)";
        $search_param = "%$search%";
    }
    // Сортировка: сначала VIP-подписчики, затем по дате подписки
    $query .= " ORDER BY s.vip_subscription DESC, s.created_at DESC";

    $stmt = $pdo->prepare($query);
    if ($search) {
        $stmt->execute([$user_id, $search_param, $search_param]);
    } else {
        $stmt->execute([$user_id]);
    }
    $subscribers = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Ошибка базы данных при получении подписчиков: " . $e->getMessage());
}
include 'header.php';
?>

<h1>Мои подписчики</h1>

<form class="search-form" method="get">
    <input type="text" name="search" placeholder="Поиск по подпискам" value="<?= h($search) ?>">
    <input type="submit" value="Поиск">
</form>

<?php if (empty($subscribers)): ?>
    <?php if ($search): ?>
        <p>По вашему запросу "<?= h($search) ?>" подписчики не найдены.</p>
    <?php else: ?>
        <p>У вас пока нет подписчиков.</p>
    <?php endif; ?>
<?php else: ?>
    <table>
        <tr>
            <th>Фото</th> <!-- Добавьте новый заголовок для фотографии -->
            <th>Имя пользователя</th>
            <th>Email</th>
            <th>Дата подписки</th>
            <th>Действия</th>
        </tr>
        <?php foreach ($subscribers as $subscriber): ?>
            <tr>
                <td>
                    <?php if (!empty($subscriber['img'])): ?>
                        <img src="<?= h($subscriber['img']) ?>" alt="Фото <?= h($subscriber['username']) ?>" style="width: 100px; height: 100px; border-radius: 50%;">
                    <?php else: ?>
                        <img src="uploads\default_avatar.png" alt="Дефолтное фото" style="width: 100px; height: 100px; border-radius: 50%;">
                    <?php endif; ?>
                </td>
                <td>
                    <?= h($subscriber['username']) ?>
                    <?php if ($subscriber['vip_subscription']): ?>
                        <span title="VIP Подписка" style="color: gold; font-weight: bold;">★</span>
                    <?php endif; ?>
                </td>
                <td><?= h($subscriber['email']) ?></td>
                <td><?= h($subscriber['subscribed_at']) ?></td>
                <td>
                    <form method="post" onsubmit="return confirm('Вы уверены, что хотите удалить этого подписчика?');">
                        <input type="hidden" name="subscriber_id" value="<?= $subscriber['id'] ?>">
                        <button type="submit" name="delete_subscriber">Удалить</button>
                    </form>
                    <?php if ($subscriber['vip_subscription']): ?>
                        <form method="post" onsubmit="return confirm('Вы уверены, что хотите снять VIP подписку?');" style="display:inline;">
                            <input type="hidden" name="subscriber_id" value="<?= $subscriber['id'] ?>">
                            <button type="submit" name="remove_vip">Снять VIP</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php include 'footer.php'; ?>