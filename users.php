<?php
require_once 'config.php';
require_once 'functions.php';

$user_id = require_login();


try {
    $stmt = $pdo->prepare("SELECT id, username, email, created_at, role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $current_user = $stmt->fetch();
} catch (PDOException $e) {
    die("Ошибка базы данных: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_subscriber'])) {
    $_id = $_POST['_id'];  // Изменено на правильное имя

    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$_id]);
        header("Location: users.php");
        exit;
    } catch (PDOException $e) {
        die("Ошибка базы данных при удалении пользователя: " . $e->getMessage());
    }
}
try {
    // Поиск подписчиков
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $query = "SELECT u.id, u.username, u.email, u.created_at, u.img, u.role, u.rating
              FROM users u
              WHERE u.id != ?";  // Добавляем условие для исключения текущего пользователя

    // Если есть поисковый запрос, добавляем условие поиска
    if ($search) {
        $query .= " AND (u.username LIKE ? OR u.email LIKE ?)";
        $search_param = "%$search%";
    }

    $query .= " ORDER BY u.created_at DESC";  // Сортируем по дате создания

    $stmt = $pdo->prepare($query);

    if ($search) {
        $stmt->execute([$user_id, $search_param, $search_param]);  // Передаем текущего пользователя и параметры поиска
    } else {
        $stmt->execute([$user_id]);  // Передаем только текущего пользователя
    }

    $subscribers = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Ошибка базы данных при получении пользователей: " . $e->getMessage());
}



include 'header.php';
?>

<h1>Пользователи</h1>

<form class="search-form" method="get">
    <input type="text" name="search" placeholder="Поиск по пользователям" value="<?= h($search) ?>">
    <input type="submit" value="Поиск">
</form>

<?php if (empty($subscribers)): ?>
    <?php if ($search): ?>
        <p>По вашему запросу "<?= h($search) ?>" пользователи не найдены.</p>
    <?php else: ?>
        <p>Нет пользователей.</p>
    <?php endif; ?>
<?php else: ?>
    <table>
        <tr>
            <th>Фото</th>
            <th>Имя пользователя</th>
            <th>Email</th>
            <th>Дата регистрации</th>
            <th>Роль</th>
            <th>Рейтинг</th>
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
                <td><?= h($subscriber['username']) ?></td>
                <td><?= h($subscriber['email']) ?></td>
                <td><?= h($subscriber['created_at']) ?></td>
                <td><?= h($subscriber['role']) ?></td>
                <td>
                    <?php if ($subscriber['role'] == 'publisher'): ?>
                        <?= $subscriber['rating'] !== null ? str_replace('.', ',', number_format($subscriber['rating'], 2)) : 'Нет данных' ?>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>

                <td>
                    <form method="post" onsubmit="return confirm('Вы уверены, что хотите удалить этого подписчика?');">
                        <input type="hidden" name="_id" value="<?= $subscriber['id'] ?>">
                        <button type="submit" name="delete_subscriber">Удалить</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>


<?php include 'footer.php'; ?>