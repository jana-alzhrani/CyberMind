<?php
require_once 'config.php';
require_once 'auth_check.php';

header('Content-Type: application/json');


if ($_SESSION['user_type'] !== 'learner') {
    echo json_encode(['error' => 'Access denied']);
    exit;
}

try {
    $topicId = isset($_POST['topic_id']) && $_POST['topic_id'] !== '' ? (int)$_POST['topic_id'] : null;
    
    if ($topicId) {

        $quizQuery = "
            SELECT
                q.id,
                t.topicName,
                CONCAT(u.firstName,' ',u.lastName) AS educatorName,
                u.photoFileName AS educatorPhoto,
                COUNT(qq.id) AS questionCount
            FROM quiz q
            INNER JOIN topic t ON t.id = q.topicID
            INNER JOIN user u ON u.id = q.educatorID
            LEFT JOIN quizquestion qq ON qq.quizID = q.id
            WHERE q.topicID = ?
            GROUP BY q.id, t.topicName, educatorName, educatorPhoto
            ORDER BY t.topicName, q.id";
        $stmt = $pdo->prepare($quizQuery);
        $stmt->execute([$topicId]);
    } else {
        
        $quizQuery = "
            SELECT
                q.id,
                t.topicName,
                CONCAT(u.firstName,' ',u.lastName) AS educatorName,
                u.photoFileName AS educatorPhoto,
                COUNT(qq.id) AS questionCount
            FROM quiz q
            INNER JOIN topic t ON t.id = q.topicID
            INNER JOIN user u ON u.id = q.educatorID
            LEFT JOIN quizquestion qq ON qq.quizID = q.id
            GROUP BY q.id, t.topicName, educatorName, educatorPhoto
            ORDER BY t.topicName, q.id";
        $stmt = $pdo->query($quizQuery);
    }
    
    $quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    
    foreach ($quizzes as &$quiz) {
        $quiz['questionCount'] = (int)$quiz['questionCount'];
    }
    
    echo json_encode($quizzes);
    
} catch (PDOException $e) {
    error_log("Database error in ajax_quizzes_by_topic.php: " . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("Error in ajax_quizzes_by_topic.php: " . $e->getMessage());
    echo json_encode(['error' => 'An error occurred']);
}
?>