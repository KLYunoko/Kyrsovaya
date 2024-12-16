<?php
$user_id = require_login();

// Получение информации о текущем пользователе
try {
    $stmt = $pdo->prepare("SELECT id, username, email, created_at, role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $current_user = $stmt->fetch();
} catch (PDOException $e) {
    die("Ошибка базы данных: " . $e->getMessage());
}


?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Блог</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f0f0f0;
            color: #333;
        }

        header {
            background-color: #333;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
            background-color: #333;
            color: #fff;
        }

        /* Стили для списка и его элементов */
        nav ul {
            list-style: none;
            display: flex;
            margin: 0;
            padding: 0;
        }

        nav ul li {
            margin: 0 15px;
        }

        nav ul li a {
            color: #fff;
            text-decoration: none;
        }

        nav ul li a:hover {
            text-decoration: underline;
        }

        /* Стили для центрации профиля */
        .nav-center {
            flex-grow: 1;
            text-align: center;
        }

        article {
            border-bottom: 1px solid #ccc;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }

        h1,
        h2 {
            color: #222;
        }

        .meta {
            color: #666;
            font-size: 0.9em;
        }

        footer {
            background-color: #f0f0f0;
            color: #333;
            padding: 10px;
            margin-top: 20px;
            text-align: center;
            border-radius: 5px;
        }

        form {
            margin-bottom: 20px;
        }

        .filters {
            margin-bottom: 20px;
        }

        .filter-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        select,
        input[type="text"],
        input[type="submit"] {

            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        select {
            margin-top: 10px;
            background-color: white;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        textarea {
            font-size: 28px;
            margin-top: 10px;
            margin-bottom: 5px;
            width: 100%;
            padding: 8px;
            background-color: #fff;
            border: 1px solid #ccc;
            color: #333;
            border-radius: 4px;
        }

        input[type="submit"] {
            margin-top: 10px;
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

        .search-form {
            margin-bottom: 20px;
        }

        .search-form input[type="text"] {
            padding: 8px;
            width: 350px;
            border-radius: 5px;
            border: 1px solid #ddd;
            margin-right: 10px;
        }

        .search-form input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
        }

        .search-form input[type="submit"]:hover {
            background-color: #45a049;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        th,
        td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #333;
            /* Цвет заголовка */
            color: white;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 14px;
        }

        td {
            background-color: #fff;
            color: #333;
            font-size: 14px;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
            /* Цвет для чётных строк */
        }

        tr:hover {
            background-color: #ddd;
            /* Эффект при наведении на строку */
            cursor: pointer;
        }

        button {
            background-color: #f44336;
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 15px;
        }

        button:hover {
            background-color: #d32f2f;
        }

        .container {
            display: flex;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .user-info {
            flex: 0 0 300px;
            padding: 20px;
            border-radius: 5px;
            margin-right: 20px;
        }

        .posts {
            flex: 1;
        }

        .subscription-buttons {
            display: flex;
            flex-direction: column;
            margin-top: 20px;
        }

        .btn {
            display: inline-block;
            padding: 10px 15px;
            margin: 5px 0;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            text-align: center;
        }

        .btn:hover {
            background-color: #0056b3;
        }

        .btn-danger {
            background-color: #dc3545;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .post {
            margin-top: 10px;
            background-color: #fff;
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .post-meta {
            color: #777;
            font-size: 0.9em;
        }

        .stars {
            display: flex;
        }

        .stars input {
            display: none;
            /* Hide radio buttons */
        }

        .stars label {
            font-size: 30px;
            color: lightgray;
            /* Default star color */
            cursor: pointer;
            margin: 0 2px;
            /* Space between stars */
        }

        .stars input:checked~label {
            color: gold;
            /* Color of the selected stars */
        }

        .stars label:hover,
        .stars label:hover~label {
            color: gold;
            /* Color of the stars on hover */
        }

        input[type="number"] {
            width: 130px;
            padding: 10px;
            /* Отступ внутри поля */
            margin: 10px 0;
            /* Отступ сверху и снизу */
            border: 2px solid #ccc;
            /* Цвет рамки */
            border-radius: 5px;
            /* Закругленные углы */
            font-size: 16px;
            /* Размер шрифта */
            transition: border-color 0.3s;
            /* Плавный переход цвета рамки */
        }

        input[type="number"]:focus {
            border-color: #007bff;
            /* Цвет рамки при фокусе */
            outline: none;
            /* Убирает стандартный контур */
        }


        /* Для улучшения читаемости */
        input[type="number"]::placeholder {
            color: #aaa;
            /* Цвет подсказки */
            font-style: italic;
            /* Курсив для подсказки */
        }

        .pagination ul {
            display: flex;
            list-style: none;
            padding: 0;
        }

        .pagination li {
            margin: 0 5px;
        }

        .pagination a {
            text-decoration: none;
            padding: 5px 10px;
            border: 1px solid #ccc;
            color: #333;
            border-radius: 5px;
        }

        .pagination a.active {
            background-color: #007bff;
            color: #fff;
            border-color: #007bff;
        }

        .pagination a:hover {
            background-color: #0056b3;
            color: #fff;
        }

        .pagination a.active {
    font-weight: bold;
}

    </style>
</head>

<body>
    <header>
        <nav>
            <ul>
                <?php if ($current_user['role'] !== 'publisher'): ?>
                    <li><a href="index.php">Новости</a></li>
                <?php endif; ?>

                <li class="nav-center">
                    <?php if (get_user_id()): ?>
                        <a href="my_posts.php">Профиль</a>
                    <?php endif; ?>
                </li>
                <li>
                    <?php if (get_user_id()): ?>
                        <a href="logout.php" onclick="return confirm('Вы уверены, что хотите выйти?');">Выход</a>
                    <?php else: ?>
                        <a href="login.php">Вход</a>
                    <?php endif; ?>
                </li>
            </ul>
        </nav>


        <script>
            document.addEventListener('DOMContentLoaded', function() {
                function Base64Encrypt(text) {
                    const encrypted = btoa(text);
                    console.log(`Base64 Encrypted: ${encrypted}`);
                    return encrypted;
                }

                function Base64Decrypt(text) {
                    const decrypted = atob(text);
                    console.log(`Base64 Decrypted: ${decrypted}`);
                    return decrypted;
                }

                // Функция для установки куки
                function setCookie(name, value, days) {
                    const encryptedValue = Base64Encrypt(value);
                    let expires = '';
                    if (days) {
                        const date = new Date();
                        date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
                        expires = '; expires=' + date.toUTCString();
                    }
                    document.cookie = `${name}=${encryptedValue || ''}${expires}; path=/`;
                    console.log(`Cookie set: ${name}=${encryptedValue || ''}`);
                }

                function getCookie(name) {
                    const nameEQ = `${name}=`;
                    const ca = document.cookie.split(';');
                    for (let i = 0; i < ca.length; i++) {
                        let c = ca[i];
                        while (c.charAt(0) === ' ') c = c.substring(1);
                        if (c.indexOf(nameEQ) === 0) {
                            const encryptedValue = c.substring(nameEQ.length);
                            console.log(`Retrieved encrypted cookie: ${encryptedValue}`);
                            return Base64Decrypt(encryptedValue);
                        }
                    }
                    return null;
                }

                // Функция для удаления куки
                function deleteCookie(name) {
                    document.cookie = name + '=; Max-Age=0; path=/';
                    console.log(`Deleted cookie: ${name}`);
                }

                // Восстанавливаем положение страницы из куки при загрузке
                const scrollY = getCookie('scrollPosition');
                if (scrollY && !isNaN(scrollY)) {
                    const scrollPosition = Math.round(parseFloat(scrollY));
                    console.log(`Restoring scroll position to: ${scrollPosition}`);
                    window.scrollTo(0, scrollPosition);
                    deleteCookie('scrollPosition');
                } else {
                    console.warn('Scroll position not found or invalid:', scrollY);
                }

                // Находим все кнопки лайка и дизлайка
                const likeButtons = document.querySelectorAll('button[name="like"], button[name="dislike"]');

                likeButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const scrollValue = window.scrollY.toString();
                        setCookie('scrollPosition', scrollValue, 1);
                    });
                });
            });
        </script>

    </header>