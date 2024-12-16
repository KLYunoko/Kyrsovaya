<?php
require_once 'config.php';
require_once 'functions.php';

$user_id = require_login();



if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $like_weight = $_POST['like_weight'];
    $dislike_weight = $_POST['dislike_weight'];
    $theme_weight = $_POST['theme_weight'];

    // Обновление весов в базе данных
    try {
        $stmt = $pdo->prepare("UPDATE weights SET like_weight = ?, dislike_weight = ?, theme_weight = ? WHERE id = 1");
        $stmt->execute([$like_weight, $dislike_weight, $theme_weight]);
        $success_message = "Вес успешно обновлен.";
    } catch (PDOException $e) {
        $error_message = "Ошибка при обновлении весов: " . $e->getMessage();
    }
}

// Получаем текущие веса
$stmt = $pdo->query("SELECT * FROM weights WHERE id = 1");
$weights = $stmt->fetch();

include 'header.php';
?>

<h1>Настройки весов рейтинга</h1>

<?php if (isset($success_message)): ?>
    <p style="color: green;"><?= h($success_message) ?></p>
<?php elseif (isset($error_message)): ?>
    <p style="color: red;"><?= h($error_message) ?></p>
<?php endif; ?>

<form method="post">
    <label for="like_weight">Вес лайка:</label>
    <input type="number" step="0.01" name="like_weight" value="<?= h($weights['like_weight']) ?>" min="0.1" max="1" required>

    <label for="dislike_weight">Вес дизлайка:</label>
    <input type="number" step="0.01" name="dislike_weight" value="<?= h($weights['dislike_weight']) ?>" min="-1" max="-0.1" required>

    <label for="theme_weight">Вес темы:</label>
    <input type="number" step="0.01" name="theme_weight" value="<?= h($weights['theme_weight']) ?>" min="0.1" max="1" required>

    <button type="submit">Сохранить изменения</button>
</form>

<?php include 'footer.php'; ?>