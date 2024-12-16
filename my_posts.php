<?php
require_once 'config.php';
require_once 'functions.php';

$user_id = require_login();


try {
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.email, u.role, u.rating, u.img, u.created_at,
               (SELECT COUNT(*) FROM subscriptions WHERE publisher_id = u.id) AS subscribers_count,
               (SELECT COUNT(*) FROM subscriptions WHERE subscriber_id = u.id) AS subscriptions_count
        FROM users u
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $current_user = $stmt->fetch();

    if (!$current_user) {
        die("Пользователь не найден.");
    }
    $rating = $current_user['rating'] ?? 0.00;
    $subscribers_count = $subscriptions_count = 0;
    $subscribers_count = $current_user['subscribers_count'] ?? 0;
    $subscriptions_count = $current_user['subscriptions_count'] ?? 0;

} catch (PDOException $e) {
    error_log("Ошибка базы данных: " . $e->getMessage());
    die("Ошибка при загрузке данных пользователя.");
}

// Удаление поста
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post'])) {
    $post_id = $_POST['post_id'] ?? null;

    if ($post_id && $current_user['role'] === 'publisher') {
        try {
            $stmt = $pdo->prepare("
                UPDATE posts 
                SET isDeleted = true, deleted_at = NOW() 
                WHERE id = ? AND user_id = ? AND isDeleted = false
            ");
            $stmt->execute([$post_id, $user_id]);

            if ($stmt->rowCount() > 0) {
                header("Location: my_posts.php?message=deleted");
                exit;
            } else {
                $error_message = "У вас нет прав на удаление этого поста.";
            }
        } catch (PDOException $e) {
            error_log("Ошибка базы данных при удалении поста: " . $e->getMessage());
            $error_message = "Ошибка при удалении поста. Попробуйте еще раз.";
        }
    } else {
        $error_message = "Невозможно удалить пост. Недостаточно прав.";
    }
}


// Обновление профиля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $new_username = $_POST['username'] ?? '';
    $new_email = $_POST['email'] ?? '';
    $error_message = '';
    $img_path = $current_user['img']; // Используем текущее изображение по умолчанию

    // Обработка загрузки изображения
    if (!empty($_FILES['profile_image']['name'])) {
        $allowed_types = ['image/gif', 'image/jpeg', 'image/png'];
        $max_size = 3 * 1024 * 1024; // 3 MB
        $file = $_FILES['profile_image'];

        if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
            $upload_dir = __DIR__ . '/uploads/';
            $filename = uniqid() . '_' . basename($file['name']);
            $file_path = $upload_dir . $filename;

            if (!is_dir($upload_dir) || !is_writable($upload_dir)) {
                $error_message = 'Ошибка: нет доступа к папке загрузки.';
            } else {
                if (move_uploaded_file($file['tmp_name'], $file_path)) {
                    $img_path = 'uploads/' . $filename;
                } else {
                    $error_message = 'Ошибка при загрузке файла.';
                }
            }
        } else {
            $error_message = $file['size'] > $max_size
                ? 'Размер файла превышает 3 МБ.'
                : 'Файл должен быть изображением (gif, jpg, jpeg, png).';
        }
    }

    if (empty($error_message)) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, img = ? WHERE id = ?");
            $stmt->execute([$new_username, $new_email, $img_path, $user_id]);
            header("Location: my_posts.php");
            exit;
        } catch (PDOException $e) {
            $error_message = "Ошибка базы данных при обновлении профиля: " . $e->getMessage();
        }
    }
}

// Получаем посты пользователя
try {
    $stmt = $pdo->prepare("
    SELECT posts.id, posts.title, posts.content, posts.created_at, posts.likes, posts.dislikes,  
           themes.theme_name, posts.vip_post, posts.img, posts.user_id
    FROM posts
    LEFT JOIN themes ON posts.theme_id = themes.id
    WHERE posts.user_id = ? AND posts.isDeleted = false
    ORDER BY posts.created_at DESC
");

    $stmt->execute([$user_id]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Ошибка при получении постов: " . $e->getMessage());
    $posts = [];
}

// Подсчет подписок и подписчиков


// Установка пути к изображению профиля
$profile_img_path = !empty($current_user['img']) && file_exists(__DIR__ . '/' . $current_user['img'])
    ? $current_user['img']
    : 'uploads/default_avatar.png';

include 'header.php';
?>
<div class="container">
    <div class="user-info">
        <h1 class="user-role">
            <?= $current_user['role'] === 'admin' ? 'Администратор' : ($current_user['role'] === 'publisher' ? 'Хореограф' : 'Танцор') ?>
        </h1>

        <?php if (isset($error_message) && !empty($error_message)): ?>
            <p class="error-message"><?= htmlspecialchars($error_message) ?></p>
        <?php endif; ?>

        <div class="profile-avatar">
            <img src="<?= htmlspecialchars($profile_img_path) ?>" alt="Аватар" style="width: 250px; height: auto;">
        </div>

        <form class="profile-form" method="post" enctype="multipart/form-data">
            <label for="username">Имя пользователя:</label>
            <input type="text" name="username" id="username" value="<?= htmlspecialchars($current_user['username']) ?>" required>

            <label for="email">Email:</label>
            <input type="email" name="email" id="email" value="<?= htmlspecialchars($current_user['email']) ?>" required>

            <label for="profile_image">Изображение профиля:</label>
            <input type="file" name="profile_image" id="profile_image" accept="image/gif, image/jpeg, image/png">

            <button type="submit" name="update_profile" class="btn btn-primary">Обновить профиль</button>
        </form>

        <p><strong>Дата регистрации:</strong> <?= htmlspecialchars($current_user['created_at']) ?></p>
        <?php if ($current_user['role'] === 'publisher'): ?>
            <p><strong>Рейтинг:</strong> <?= str_replace('.', ',', number_format($rating, 2)) ?></p>
        <?php endif; ?>
    </div>

    <div class="additional-info">
        <?php if ($current_user['role'] === 'reader'): ?>
            <a href="subscriptions.php" class="btn" style="margin-top: 28px; margin-left: 100px;">Подписки - <?= $subscriptions_count ?></a>
        <?php elseif ($current_user['role'] === 'publisher'): ?>
            <div style="display: flex; gap: 5px; margin-top: 24px;">
                <a href="subscribers.php" class="btn">Подписчики - <?= $subscribers_count ?></a>
                <?php if ($current_user['role'] === 'publisher'): ?>
                    <a href="create_post.php" class="btn btn-add">Добавить пост</a>
                <?php endif; ?>
            </div>
        <?php elseif ($current_user['role'] === 'admin'): ?>
            <a href="users.php" class="btn">Просмотр всех пользователей</a>
            <a href="themes.php" class="btn">Справочник тем</a>
            <a href="weights.php" class="btn">Веса</a>
        <?php endif; ?>
        <a href="vip_requests.php" class="btn">VIP-заявки</a>
    </div>
</div>

<?php if ($current_user['role'] === 'publisher'): ?>
    <h1>Мои посты</h1>
    <?php if (empty($posts)): ?>
        <p>Постов не найдено</p>
    <?php else: ?>
        <?php foreach ($posts as $post): ?>
            <div class="post">
                <h2>
                    <?= h($post['title']) ?>
                    <?php if ($post['vip_post']): ?>
                        <span title="VIP Пост" style="color: gold; font-weight: bold;">★</span>
                    <?php endif; ?>
                </h2>
                <p><?= nl2br(h($post['content'])) ?></p>
                <p><strong>Тема:</strong> <?= h($post['theme_name'] ?? 'Не указан') ?></p>

                <?php if ($post['img']): ?>
                    <img src="data:image/jpeg;base64,<?= base64_encode($post['img']) ?>" alt="Изображение поста" style="max-width: 500px; height: auto;">
                <?php endif; ?>

                <p class="post-meta">Опубликовано: <?= h($post['created_at']) ?></p>

                <div>
                    <button name="like" style="background-color: #fff;" class="btn btn-like">👍</button>
                    <span><?= h($post['likes']) ?></span>
                    <button name="dislike" style="background-color: #fff;" class="btn btn-dislike">👎</button>
                    <span><?= h($post['dislikes']) ?></span>
                </div>


                <?php if ($post['user_id'] == $user_id): ?>
                    <a href="edit_post.php?id=<?= $post['id'] ?>" class="btn btn-edit">Редактировать</a>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Вы уверены, что хотите удалить этот пост?');">
                        <input type="hidden" name="post_id" value="<?= h($post['id']) ?>">
                        <input type="submit" value="Удалить" class="btn btn-delete" name="delete_post">
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
<?php endif; ?>

<?php include 'footer.php'; ?>