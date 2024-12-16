<?php
require_once 'config.php';
require_once 'functions.php';

$user_id = require_login();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_id'])) {
    $request_id = $_POST['request_id'];

    if (isset($_POST['approve_request'])) {
        // Одобрение заявки
        try {
            // Получаем информацию о заявке
            $stmt = $pdo->prepare("SELECT reader_id, publisher_id FROM vip_request WHERE id = ?");
            $stmt->execute([$request_id]);
            $request = $stmt->fetch();

            if ($request) {
                // Обновляем статус заявки
                $stmt = $pdo->prepare("UPDATE vip_request SET status = 'одобрено' WHERE id = ?");
                $stmt->execute([$request_id]);

                // Обновляем подписку
                $stmt = $pdo->prepare("INSERT INTO subscriptions (subscriber_id, publisher_id, vip_subscription) VALUES (?, ?, 1)
                                        ON DUPLICATE KEY UPDATE vip_subscription = 1");
                $stmt->execute([$request['reader_id'], $request['publisher_id']]);

                header("Location: vip_requests.php?message=Заявка одобрена.");
                exit;
            } else {
                throw new Exception("Заявка не найдена.");
            }
        } catch (PDOException $e) {
            die("Ошибка базы данных при одобрении заявки: " . $e->getMessage());
        }
    }

    if (isset($_POST['reject_request'])) {
        // Отклонение заявки
        try {
            $stmt = $pdo->prepare("UPDATE vip_request SET status = 'отклонено' WHERE id = ?");
            $stmt->execute([$request_id]);
            header("Location: vip_requests.php?message=Заявка отклонена.");
            exit;
        } catch (PDOException $e) {
            die("Ошибка базы данных при отклонении заявки: " . $e->getMessage());
        }
    }
}