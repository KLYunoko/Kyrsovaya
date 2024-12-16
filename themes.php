<?php
require_once 'config.php';
require_once 'functions.php';

$user_id = require_login();

// Добавление темы
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_theme'])) {
    $theme_name = trim($_POST['theme_name']);
    $rating = $_POST['rating'];

    if (!empty($theme_name) && is_numeric($rating) && $rating >= 1 && $rating <= 10) {
        try {
            // Проверка на уникальность темы
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM themes WHERE theme_name = ?");
            $checkStmt->execute([$theme_name]);
            $themeExists = $checkStmt->fetchColumn();

            if ($themeExists > 0) {
                $error_message = "Тема с таким названием уже существует.";
            } else {
                // Если тема уникальна, добавляем ее
                $stmt = $pdo->prepare("INSERT INTO themes (theme_name, rating) VALUES (?, ?)");
                $stmt->execute([$theme_name, $rating]);
                header("Location: themes.php");
                exit;
            }
        } catch (PDOException $e) {
            die("Ошибка базы данных при добавлении темы: " . $e->getMessage());
        }
    } else {
        $error_message = "Название темы не может быть пустым и (или) рейтинг должен быть от 1 до 10.";
    }
}

// Удаление темы
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_theme'])) {
    $theme_id = $_POST['theme_id'];

    try {
        $stmt = $pdo->prepare("DELETE FROM themes WHERE id = ?");
        $stmt->execute([$theme_id]);
        header("Location: themes.php");
        exit;
    } catch (PDOException $e) {
        die("Ошибка базы данных при удалении темы: " . $e->getMessage());
    }
}

// Получение списка тем
try {
    $stmt = $pdo->prepare("SELECT * FROM themes ORDER BY theme_name ASC");
    $stmt->execute();
    $themes = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Ошибка базы данных при получении тем: " . $e->getMessage());
}

include 'header.php';
?>

<h1>Управление темами</h1>

<form method="post">
    <input type="text" name="theme_name" placeholder="Название темы" required>
    <input type="number" name="rating" placeholder="Рейтинг (1-10)" min="1" max="10" required>
    <button type="submit" name="add_theme">Добавить тему</button>
</form>

<?php if (isset($error_message)): ?>
    <p style="color: red;"><?= h($error_message) ?></p>
<?php endif; ?>

<?php if (empty($themes)): ?>
    <p>Темы не найдены.</p>
<?php else: ?>
    <table>
        <tr>
            <th>Название темы</th>
            <th>Рейтинг</th>
            <th>Действия</th>
        </tr>
        <?php foreach ($themes as $theme): ?>
            <tr>
                <td><?= h($theme['theme_name']) ?></td>
                <td><?= h($theme['rating']) ?></td>
                <td>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Вы уверены, что хотите удалить эту тему?');">
                        <input type="hidden" name="theme_id" value="<?= $theme['id'] ?>">
                        <button type="submit" name="delete_theme">Удалить</button>
                    </form>
                    <a href="edit_themes.php?id=<?= $theme['id'] ?>" class="btn">Редактировать</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php include 'footer.php'; ?>