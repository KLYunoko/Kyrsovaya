<?php
require_once 'config.php';
require_once 'functions.php';


$errors = [];
$role = $_POST['role'] ?? 'reader'; // Устанавливаем значение по умолчанию

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Проверка полей
    if (empty($username)) $errors[] = "Требуется имя пользователя";
    if (empty($email)) $errors[] = "Требуется email";
    if (empty($password)) $errors[] = "Требуется пароль";
    if ($password !== $confirm_password) $errors[] = "Пароли не совпадают";

    // Если нет ошибок, попробуем выполнить SQL-запрос
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT), $role]);

            $user_id = $pdo->lastInsertId();
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $role;


            if ($rememberMe) {
                $selector = bin2hex(random_bytes(16)); // Генерация случайного селектора
                $token = bin2hex(random_bytes(32)); // Генерация случайного токена
            
                // Хешируем токен для хранения в базе данных
                $hashedToken = password_hash($token, PASSWORD_DEFAULT); 
                $expiry = time() + 60 * 60 * 24 * 30; // Срок действия куки: 30 дней
            
                // Сохраняем селектор и хешированный токен в базе данных
                $stmt = $pdo->prepare("INSERT INTO auth_tokens (selector, hashed_token, user_id, expiry) VALUES (?, ?, ?, ?)");
                $stmt->execute([$selector, $hashedToken, $user['id'], $expiry]);
            
                // Шифруем токен перед установкой в куки
                $encryptedToken = encryptToken($token);
            
                // Устанавливаем куки с зашифрованными значениями
                setcookie('remember_me_selector', $selector, $expiry, '/', '', true, true); // Устанавливаем куку с селектором
                setcookie('remember_me_token', $encryptedToken, $expiry, '/', '', true, true); // Устанавливаем куку с зашифрованным токеном
            }   

            // Перенаправление в зависимости от роли
            if ($role === 'publisher') {
                header('Location: my_posts.php');
            } else {
                header('Location: index.php');
            }
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $errors[] = "Имя пользователя или email уже существует.";
            } else {
                $errors[] = "Ошибка базы данных: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 300px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f0f0f0;
            color: #333;
        }

        h2 {
            color: #222;
        }

        input {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            background-color: #fff;
            border: 1px solid #ccc;
            color: #333;
            border-radius: 4px;
        }

        select {
            margin-top: 8px;
            width: 317px;
            padding: 8px;
            margin-bottom: 10px;
            background-color: #fff;
            border: 1px solid #ccc;
            color: #333;
            border-radius: 4px;
        }

        input[type="submit"] {
            width: 317px;
            background-color: #333;
            color: #fff;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 4px;
        }

        input[type="submit"]:hover {
            background-color: #555;
        }

        .error {
            color: #ff0000;
        }

        a {
            color: #333;
        }

        a:hover {
            color: #555;
        }

        .remember-me {
            display: flex;
            white-space: nowrap;
            justify-content: flex-start;
        }


        input[type="checkbox"] {
            --bs-form-check-bg: var(--bs-body-bg);
            flex-shrink: 0;
            width: 1em;
            height: 1em;
            margin-top: .25em;
            vertical-align: top;
            background-position: center;
            background-size: contain;
            border: var(--bs-border-width) solid var(--bs-border-color);
        }

        label {
            cursor: pointer;
            user-select: none;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <h2>Регистрация</h2>
    <?php
    // Вывод ошибок
    if (!empty($errors)) {
        foreach ($errors as $error) {
            echo "<p class='error'>$error</p>";
        }
    }
    ?>
    <form method="post">
        <input type="text" name="username" placeholder="Имя пользователя" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Пароль" required>
        <input type="password" name="confirm_password" placeholder="Подтвердите пароль" required>

        <label for="role">Роль:</label>
        <select name="role" id="role" required>
            <option value="reader">Танцор</option>
            <option value="publisher">Хореограф</option>
        </select>


        <div class="remember-me">
            <input type="checkbox" id="remember_me" name="remember_me">
            <label for="remember_me">Запомнить меня</label>
        </div>


        <input type="submit" value="Зарегистрироваться">
    </form>
    <p>Уже есть аккаунт? <a href="login.php">Войдите здесь</a></p>
</body>

</html>