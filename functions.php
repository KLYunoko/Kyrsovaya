<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Установите путь к вашему файлу журнала
//$log_file = "D:\\Apps\\xampp\\htdocs\\blogging-platform-4\\custom_log.log";

// Пример использования error_log для записи сообщений
//error_log("Это сообщение для журнала.", 3, $log_file);
function get_user_id()
{
    return $_SESSION['user_id'] ?? null;
}


function require_login()
{
    $user_id = get_user_id();
    if (!$user_id) {
        header('Location: login.php');
        exit;
    }
    return $user_id;
}


function is_subscribed($subscriber_id, $publisher_id)
{
    try {
        global $pdo;
        $stmt = $pdo->prepare("SELECT 1 FROM subscriptions WHERE subscriber_id = ? AND publisher_id = ?");
        $stmt->execute([$subscriber_id, $publisher_id]);
        return $stmt->fetchColumn() !== false;
    } catch (PDOException $e) {
        // Логируем ошибку
       // error_log("Ошибка : " . $e->getMessage());
    }
}

function h($string)
{
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}


function get_user_role($user_id) {
    global $pdo; // Используем глобальную переменную PDO для доступа к базе данных

    try {
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user) {
            return $user['role']; // Возвращаем роль пользователя
        } else {
            throw new Exception("Пользователь не найден.");
        }
    } catch (PDOException $e) {
        die("Ошибка базы данных: " . $e->getMessage());
    } catch (Exception $e) {
        die($e->getMessage());
    }
}

function updateAuthorRating($pdo, $post_id) {
    global $log_file; // Делаем переменную доступной в функции
    try {
       //error_log("Запуск updateAuthorRating для post_id: $post_id", 3, $log_file);

        $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
        $stmt->execute([$post_id]);
        $author_id = $stmt->fetchColumn();
        
        if (!$author_id) {
            //error_log("Автор не найден для post_id: $post_id", 3, $log_file);
            return;
        }

        //error_log("Автор ID: $author_id", 3, $log_file);

        // Получаем веса
        $stmt = $pdo->query("SELECT like_weight, dislike_weight, theme_weight FROM weights WHERE id = 1");
        $weights = $stmt->fetch();

        if (!$weights) {
           // error_log("Весы не найдены в базе данных.", 3, $log_file);
            return;
        }

        $like_weight = $weights['like_weight'];
        $dislike_weight = $weights['dislike_weight'];
        $theme_weight = $weights['theme_weight'];

       // error_log("Весы: like_weight=$like_weight, dislike_weight=$dislike_weight, theme_weight=$theme_weight", 3, $log_file);

        // Пересчитываем средний рейтинг
        $stmt = $pdo->prepare("
            UPDATE users 
            SET rating = (
                SELECT AVG(
                    (p.likes * :like_weight + p.dislikes * :dislike_weight + c.rating * :theme_weight) / 
                    (:like_weight + ABS(:dislike_weight) + :theme_weight)
                ) 
                FROM posts p 
                JOIN themes c ON p.theme_id = c.id 
                WHERE p.user_id = :author_id
            ) 
            WHERE id = :author_id");
        
        $stmt->bindParam(':like_weight', $like_weight);
        $stmt->bindParam(':dislike_weight', $dislike_weight);
        $stmt->bindParam(':theme_weight', $theme_weight);
        $stmt->bindParam(':author_id', $author_id);
        $stmt->execute();

       // error_log("Рейтинг обновлен для автора ID: $author_id", 3, $log_file);

    } catch (PDOException $e) {
      //  error_log("Ошибка при обновлении рейтинга автора: " . $e->getMessage(), 3, $log_file);
    }
}

function encryptToken($token) {
    $iv = openssl_random_pseudo_bytes(16); // Генерация случайного вектора инициализации
    $encryptedToken = openssl_encrypt($token, 'AES-256-CBC', AES_KEY, 0, $iv);
    
    // Возвращаем зашифрованный токен и вектор инициализации в виде строки
    return base64_encode($encryptedToken . '::' . $iv);
}


function decryptToken($encryptedToken) {
    list($encryptedData, $iv) = explode('::', base64_decode($encryptedToken), 2);
    return openssl_decrypt($encryptedData, 'AES-256-CBC', AES_KEY, 0, $iv);
}


function handlePostRequest($pdo, $user_id, $role) {
    try {
        if (isset($_POST['like']) || isset($_POST['dislike'])) {
            $post_id = $_POST['post_id'];
            $column = isset($_POST['like']) ? 'likes' : 'dislikes';
            $stmt = $pdo->prepare("UPDATE posts SET $column = $column + 1 WHERE id = ?");
            $stmt->execute([$post_id]);
            updateAuthorRating($pdo, $post_id);
        } elseif (isset($_POST['subscribe'])) {
            $publisher_id = $_POST['publisher_id'];
            $stmt = $pdo->prepare("INSERT IGNORE INTO subscriptions (subscriber_id, publisher_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $publisher_id]);
        } elseif (isset($_POST['unsubscribe'])) {
            $publisher_id = $_POST['publisher_id'];
            $stmt = $pdo->prepare("DELETE FROM subscriptions WHERE subscriber_id = ? AND publisher_id = ?");
            $stmt->execute([$user_id, $publisher_id]);
        } elseif (isset($_POST['delete_post']) && $role === 'admin') {
            $post_id = $_POST['post_id'];
            $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
            $stmt->execute([$post_id]);
        }
    } catch (PDOException $e) {
       // error_log("Ошибка при изменении данных: " . $e->getMessage());
        $error_message = "Произошла ошибка. Попробуйте еще раз.";
    }
}

function buildPostsQuery() {
    return "
   SELECT p.*, u.username, c.theme_name, c.rating AS theme_rating, 
       (SELECT COUNT(*) FROM subscriptions WHERE subscriber_id = :user_id AND publisher_id = p.user_id) as is_subscribed, 
       (SELECT COUNT(*) FROM subscriptions WHERE subscriber_id = :user_id AND publisher_id = p.user_id AND vip_subscription = 1) as can_see_vip,
       p.likes, p.dislikes, p.img, 
       (CASE WHEN (p.likes + p.dislikes) > 0 THEN 
           (p.likes * :like_weight + p.dislikes * :dislike_weight + c.rating * :theme_weight) / 
           (:like_weight + ABS(:dislike_weight) + :theme_weight) 
       ELSE 0 END) as rating,
       (SELECT AVG(CASE WHEN (p_inner.likes + p_inner.dislikes) > 0 THEN 
           (p_inner.likes * :like_weight + p_inner.dislikes * :dislike_weight + c_inner.rating * :theme_weight) / 
           (:like_weight + ABS(:dislike_weight) + :theme_weight) 
       ELSE 0 END) 
       FROM posts p_inner 
       JOIN themes c_inner ON p_inner.theme_id = c_inner.id 
       WHERE p_inner.user_id = p.user_id) as author_average_rating
FROM posts p 
JOIN users u ON p.user_id = u.id
LEFT JOIN themes c ON p.theme_id = c.id
WHERE p.isDeleted = false
ORDER BY DATE(p.created_at) DESC, p.vip_post DESC, is_subscribed DESC, author_average_rating DESC, rating DESC;
";
}



function handleImageUpload($file) {
    if ($file['error'] == UPLOAD_ERR_NO_FILE) {
        return null; // Нет файла - возвращаем null
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Ошибка загрузки изображения: " . $file['error']);
    }

    $file_tmp = $file['tmp_name'];
    $file_type = mime_content_type($file_tmp);
    $allowed_types = ['image/gif', 'image/jpeg', 'image/png'];
    $file_size = $file['size'];

    if ($file_size > 3 * 1024 * 1024) {
        throw new Exception("Размер изображения не должен превышать 3 МБ.");
    }

    if (!in_array($file_type, $allowed_types)) {
        throw new Exception("Недопустимый формат изображения. Разрешены: gif, jpg, jpeg, png.");
    }

    return file_get_contents($file_tmp); // Возвращаем содержимое файла
}