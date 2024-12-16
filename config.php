<?php
require_once 'functions.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = 'localhost';
$dbname = 'kyrsovaya';
$username = 'root';
$password = '';
setlocale(LC_NUMERIC, 'en_US.UTF-8');

define('AES_KEY', 'your-secret-key'); 
define('AES_IV', '1234567890abcdef'); 


try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}

function is_logged_in()
{
    return isset($_SESSION['user_id']);
}


if (isset($_COOKIE['remember_me_selector']) && isset($_COOKIE['remember_me_token'])) {
    $selector = $_COOKIE['remember_me_selector'];
    $encryptedToken = $_COOKIE['remember_me_token'];

    // Дешифруем токен
    $token = decryptToken($encryptedToken);

    try {
        // Проверка наличия селектора в базе данных
        $stmt = $pdo->prepare("SELECT * FROM auth_tokens WHERE selector = ?");
        $stmt->execute([$selector]);
        $auth_token = $stmt->fetch();

        // Если токен существует и соответствует хешу, а также если срок действия не истек
        if ($auth_token && password_verify($token, $auth_token['hashed_token']) && $auth_token['expiry'] > time()) {
            // Получаем данные пользователя
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$auth_token['user_id']]);
            $user = $stmt->fetch();

            if ($user) {
                // Авторизуем пользователя
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
            }
        }

    } catch (PDOException $e) {
        // Обработка ошибки
        error_log("Database error during cookie login: " . $e->getMessage());
    }
}


?>


<!--
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subscriber_id INT NOT NULL,
    publisher_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subscriber_id) REFERENCES users(id),
    FOREIGN KEY (publisher_id) REFERENCES users(id),
    UNIQUE KEY unique_subscription (subscriber_id, publisher_id)
); -->