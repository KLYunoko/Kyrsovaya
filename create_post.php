<?php
require_once 'config.php';
require_once 'functions.php';

$user_id = require_login();
$error_message = '';

try {
    $stmt = $pdo->query("SELECT id, theme_name FROM themes ORDER BY theme_name");
    $themes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Ошибка при получении списка тем: " . $e->getMessage());
    $error_message = "Произошла ошибка при получении списка тем.";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $theme_id = $_POST['theme_id'] ?? '';
    $vip_post = isset($_POST['vip_post']) ? 1 : 0;

    // Проверка заполненности полей
    if (!$title || !$content || !$theme_id) {
        $error_message = "Пожалуйста, заполните все поля.";
    } else {
        try {
            // Проверяем, загружен ли файл изображения
            $img_blob = null;
            if (!empty($_FILES['image']['tmp_name'])) {
                $img_blob = handleImageUpload($_FILES['image']);
            }

            // Вставка данных в базу
            $stmt = $pdo->prepare("INSERT INTO posts (user_id, title, content, theme_id, vip_post, img) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $title, $content, $theme_id, $vip_post, $img_blob]);

            header('Location: my_posts.php');
            exit;
        } catch (Exception $e) {
            $error_message = $e->getMessage();
            error_log("Ошибка при добавлении поста: " . $e->getMessage());
        }
    }
}

include 'header.php';
?>
<h1>Создать новый пост</h1>

<?php if (isset($error_message)): ?>
    <p style="color: red;"><?= h($error_message) ?></p>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" oninput="saveFormData()">
    <label for="title">Заголовок:</label>
    <input type="text" id="title" name="title" required>

    <label for="content">Содержание:</label>
    <textarea id="content" name="content" required></textarea>

    <label for="theme">Тема:</label>
    <select id="theme" name="theme_id" required>
        <option value="">Выберите тему</option>
        <?php foreach ($themes as $theme): ?>
            <option value="<?= h($theme['id']) ?>"><?= h($theme['theme_name']) ?></option>
        <?php endforeach; ?>
    </select>

    <label for="image">Изображение:</label>
    <input type="file" id="image" name="image" accept="image/gif, image/jpeg, image/png">

    <label for="vip_post">
        <input type="checkbox" id="vip_post" name="vip_post" value="1">
        VIP пост
    </label>

    <div>
        <input type="submit" value="Создать пост">
    </div>
</form>

<?php include 'footer.php'; ?>
