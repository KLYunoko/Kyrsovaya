<?php
require_once 'config.php';
require_once 'functions.php';

$user_id = get_user_id();

try {
    $stmt = $pdo->prepare("SELECT id, username, email, created_at, role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $current_user = $stmt->fetch();
} catch (PDOException $e) {
    die("Ошибка базы данных: " . $e->getMessage());
}

if ($current_user['role'] === 'publisher') {
    header('Location: my_posts.php');
    exit;
}

$stmt = $pdo->query("SELECT like_weight, dislike_weight, theme_weight FROM weights WHERE id = 1");
$weights = $stmt->fetch();
$like_weight = $weights['like_weight'];
$dislike_weight = $weights['dislike_weight'];
$theme_weight = $weights['theme_weight'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user_id) {
    handlePostRequest($pdo, $user_id, $current_user['role']);
    header("Location: index.php?filter=$filter&search=$search");
    exit;
}

$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';
$is_admin = ($current_user['role'] === 'admin') ? 1 : 0;

$query = "
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

try {
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':like_weight', $like_weight);
    $stmt->bindValue(':dislike_weight', $dislike_weight);
    $stmt->bindValue(':theme_weight', $theme_weight);
    $stmt->bindValue(':is_admin', $is_admin, PDO::PARAM_INT);
    $stmt->bindValue(':filter', $filter, PDO::PARAM_STR);
    $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);

    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Ошибка при получении постов: " . $e->getMessage());
    $error_message = "Произошла ошибка при загрузке постов."  . $e->getMessage();
}


include 'header.php'; ?>
<h1>Последние посты</h1>

<div class="filters">
    <form method="get" class="filter-form">
        <input type="text" name="search" placeholder="Поиск по постам" value="<?= h($search) ?>">
        <input type="submit" value="Поиск">
    </form>
</div>


<?php if (isset($error_message)): ?>
    <p style="color: red;"><?= h($error_message) ?></p>
<?php elseif (empty($posts)): ?>
    <p>Постов не найдено</p>
<?php else: ?>
    <?php foreach ($posts as $post): ?>
        <div class="post" style="position: relative; padding: 15px; border: 1px solid #ccc; margin-bottom: 20px;">
            <h2>
                <?= h($post['title']) ?>
                <?php if ($post['vip_post'] && $post['can_see_vip']): ?>
                    <span title="VIP Пост" style="color: gold; font-weight: bold;">★</span>
                <?php endif; ?>
            </h2>
            <p><?= nl2br(h($post['content'])) ?></p>

            <?php if (!empty($post['img'])): ?>
                <img src="data:image/jpeg;base64,<?= base64_encode($post['img']) ?>" alt="Изображение поста" style="max-width: 500px; height: auto; margin-top: 10px;">
            <?php endif; ?>

            <p class="post-meta">
                Автор: <?= h($post['username']) ?> |
                Тема: <?= h($post['theme_name'] ?? 'Не указана') ?> |
                Опубликовано: <?= h($post['created_at']) ?>
            </p>

            <p class="post-rating" style="position: absolute; top: 10px; right: 20px; color: #777;">
                Рейтинг: <?= number_format(h($post['rating']), 2, '.', '') ?>
            </p>

            <?php if ($post['user_id'] != $user_id): ?>
                <?php if ($current_user['role'] === 'reader'): ?>
                    <div class="post-ratings">
                        <form method="post" class="rating-form" style="display: inline;">
                            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                            <button type="submit" name="like" style="background-color: #fff;" class="btn btn-like">👍</button>
                            <span><?= h($post['likes']) ?></span>
                            <button type="submit" name="dislike" style="background-color: #fff;" class="btn btn-dislike">👎</button>
                            <span><?= h($post['dislikes']) ?></span>
                        </form>
                    </div>
                    <form method="post" class="subscription-form" style="position: absolute; bottom: 10px; right: 20px;">
                        <input type="hidden" name="publisher_id" value="<?= $post['user_id'] ?>">
                        <?php if ($post['is_subscribed']): ?>
                            <button type="submit" name="unsubscribe" class="btn btn-unsubscribe">Отписаться</button>
                        <?php else: ?>
                            <button type="submit" name="subscribe" class="btn btn-subscribe">Подписаться</button>
                        <?php endif; ?>
                    </form>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($current_user['role'] === 'admin'): ?>
                <?php if ($post['isDeleted']): ?>
                    <span style="color: red; font-weight: bold;">[Удалён <?= $post['deleted_at'] ?>]</span>
                <?php endif; ?>
                <form method="post" class="delete-form" onsubmit="return confirm('Вы уверены, что хотите удалить этот пост?');">
                    <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                    <button type="submit" name="delete_post" class="btn btn-delete">Удалить пост</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php include 'footer.php'; ?>