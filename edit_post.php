<?php
require_once 'config.php';
require_once 'functions.php';

$user_id = require_login();

$post_id = $_GET['id'] ?? null;

if (!$post_id) {
    header('Location: my_posts.php');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ? AND user_id = ?");
    $stmt->execute([$post_id, $user_id]);
    $post = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Ошибка при получении поста: " . $e->getMessage());
    $error_message = "Произошла ошибка при получении поста";
}

if (!$post) {
    header('Location: my_posts.php');
    exit;
}

try {
    $stmt = $pdo->query("SELECT id, theme_name FROM themes");
    $themes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Ошибка при получении списка тем: " . $e->getMessage());
    $error_message = "Не удалось загрузить список тем.";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $theme_id = $_POST['theme_id'] ?? '';
    $img_blob = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['image']['tmp_name'];
        $file_type = mime_content_type($file_tmp);
        $allowed_types = ['image/gif', 'image/jpeg', 'image/png'];
        $file_size = $_FILES['image']['size'];

        if ($file_size > 3 * 1024 * 1024) {
            $error_message = "Размер изображения не должен превышать 3 МБ.";
        } elseif (!in_array($file_type, $allowed_types)) {
            $error_message = "Недопустимый формат изображения. Разрешены: gif, jpg, jpeg, png.";
        } else {
            $img_blob = file_get_contents($file_tmp);
        }
    }

    if (!isset($error_message)) {
        $img_blob = $img_blob ?? $post['img'];

        if (isset($_POST['delete_image'])) {
            $img_blob = null;
        }

        if ($title && $content && $theme_id) {
            try {
                $stmt = $pdo->prepare("UPDATE posts SET title = ?, content = ?, theme_id = ?, img = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$title, $content, $theme_id, $img_blob, $post_id, $user_id]);
                header('Location: my_posts.php');
                exit;
            } catch (PDOException $e) {
                error_log("Ошибка при обновлении поста: " . $e->getMessage());
                $error_message = "Произошла ошибка при обновлении поста.";
            }
        } else {
            $error_message = "Пожалуйста, заполните все поля.";
        }
    }
}

include 'header.php';
?>

<h1>Редактировать пост</h1>

<?php if (isset($error_message)): ?>
    <p style="color: red;"><?= h($error_message) ?></p>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
    <div>
        <label for="theme">Тема:</label>
        <select id="theme" name="theme_id" required>
            <?php foreach ($themes as $theme): ?>
                <option value="<?= h($theme['id']) ?>" <?= $post['theme_id'] == $theme['id'] ? 'selected' : '' ?>>
                    <?= h($theme['theme_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <label for="title">Заголовок:</label>
    <input type="text" id="title" name="title" value="<?= h($post['title']) ?>" required>

    <label for="content">Содержание:</label>
    <textarea id="content" name="content" required><?= h($post['content']) ?></textarea>

    <label for="image">Изображение:</label>
    <?php if ($post['img']): ?>
        <div>
            <img src="data:image/jpeg;base64,<?= base64_encode($post['img']) ?>" alt="Изображение поста" style="max-width: 500px; height: auto;">
            <p><input type="checkbox" name="delete_image" id="delete_image" value="1">
            <label for="delete_image">Удалить изображение</label></p>
        </div>
    <?php endif; ?>
    <input type="file" id="image" name="image" accept="image/gif, image/jpeg, image/png">

    <input type="submit" value="Сохранить изменения">
</form>

<a href="my_posts.php">Назад к моим постам</a>

<?php include 'footer.php'; ?>
