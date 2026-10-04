<?php
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_GET['topic_id']) || empty($_GET['topic_id'])) {
    echo json_encode([]);
    exit;
}

$topicId = (int)$_GET['topic_id'];

$stmt = $pdo->prepare("
    SELECT DISTINCT u.id, u.firstName, u.lastName 
    FROM user u 
    INNER JOIN quiz q ON u.id = q.educatorID 
    WHERE q.topicID = ? AND u.userType = 'educator'
    ORDER BY u.firstName, u.lastName
");
$stmt->execute([$topicId]);
$educators = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($educators);
?>

