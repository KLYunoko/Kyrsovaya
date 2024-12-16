<?php
require_once 'config.php';
require_once 'functions.php';

$user_id = require_login();

if (!isset($_GET['id'])) {
    die("Идентификатор темы не задан.");
}

$theme_id = $_GET['id'];

// Получение информации о теме
try {
    $stmt = $pdo->prepare("SELECT * FROM themes WHERE id = ?");
    $stmt->execute([$theme_id]);
    $theme = $stmt->fetch();

    if (!$theme) {
        die("Тема не найдена.");
    }
} catch (PDOException $e) {
    die("Ошибка базы данных: " . $e->getMessage());
}

// Обновление темы
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_theme'])) {
    $new_theme_name = $_POST['theme_name'];
    $new_rating = $_POST['rating'];

    if (!empty($new_theme_name) && is_numeric($new_rating) && $new_rating >= 1 && $new_rating <= 10) {
        try {
            $stmt = $pdo->prepare("UPDATE themes SET theme_name = ?, rating = ? WHERE id = ?");
            $stmt->execute([$new_theme_name, $new_rating, $theme_id]);
            header("Location: themes.php");
            exit;
        } catch (PDOException $e) {
            die("Ошибка базы данных при обновлении темы: " . $e->getMessage());
        }
    } else {
        $error_message = "Название темы не может быть пустым и рейтинг должен быть от 1 до 10";
    }
}

include 'header.php';
?>

<h1>Редактировать тему</h1>

<form method="post">
    <input type="text" name="theme_name" value="<?= h($theme['theme_name']) ?>" required>
    <input type="number" name="rating" value="<?= h($theme['rating']) ?>" min="1" max="10" required>
    <button type="submit" name="update_theme">Обновить тему</button>
</form>

<?php if (isset($error_message)): ?>
    <p style="color: red;"><?= h($error_message) ?></p>
<?php endif; ?>

<?php include 'footer.php'; ?>