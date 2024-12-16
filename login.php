<?php
require_once 'config.php';
require_once 'functions.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Валидация данных
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $rememberMe = isset($_POST['remember_me']); // Check if "Remember Me" is checked

    if (empty($username) || empty($password)) {
        $error = 'Заполните все поля.';
    } else {
        try {
            // Подготовленный запрос для безопасности
            $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                if ($rememberMe) {
                    // Генерация случайных данных для "Запомнить меня"
                    $selector = bin2hex(random_bytes(16)); // Селектор
                    $token = bin2hex(random_bytes(32)); // Токен
                    $hashedToken = password_hash($token, PASSWORD_DEFAULT); 
                    $expiry = time() + 60 * 60 * 24 * 30; // 30 дней

                    // Сохранение токенов в базе данных
                    $stmt = $pdo->prepare("INSERT INTO auth_tokens (selector, hashed_token, user_id, expiry) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$selector, $hashedToken, $user['id'], $expiry]);

                    // Шифрование токена для безопасного хранения в куки
                    $encryptedToken = encryptToken($token);

                    // Устанавливаем куки с необходимыми данными
                    setcookie('remember_me_selector', $selector, $expiry, '/', '', true, true); 
                    setcookie('remember_me_token', $encryptedToken, $expiry, '/', '', true, true); 
                }

                // Перенаправление в зависимости от роли пользователя
                if ($user['role'] === 'publisher') {
                    header('Location: my_posts.php');
                } else {
                    header('Location: index.php');
                }
                exit;
            } else {
                $error = 'Неверный пароль или пользователь не найден.';
            }
        } catch (PDOException $e) {
            $error = 'Ошибка при выполнении запроса: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход</title>
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
    <h2>Вход</h2>
    <?php if ($error): ?>
        <p class="error"><?php echo $error; ?></p>
    <?php endif; ?>
    <form method="post">
        <input type="text" name="username" placeholder="Имя пользователя" required>
        <input type="password" name="password" placeholder="Пароль" required>
        <div class="remember-me">
            <input type="checkbox" id="remember_me" name="remember_me">
            <label for="remember_me">Запомнить меня</label>
        </div>
        <input type="submit" value="Войти">
    </form>
    <p>Нет аккаунта? <a href="register.php">Зарегистрируйтесь здесь</a></p>
</body>

</html>
