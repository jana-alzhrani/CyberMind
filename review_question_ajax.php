<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'config.php';
require_once 'auth_check.php';

header('Content-Type: application/json');


if ($_SESSION['user_type'] !== 'educator') {
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question_id = $_POST['question_id'];
    $decision = $_POST['decision'];
    $comment = $_POST['comment'] ?? '';
    $educator_id = $_SESSION['user_id'];
    
    try {
        
        $stmt = $pdo->prepare("UPDATE recommendedquestion SET status = ?, comments = ? WHERE id = ?");
        $stmt->execute([$decision, $comment, $question_id]);
        
        if ($decision === 'approved') {
            
            $stmt = $pdo->prepare("SELECT * FROM recommendedquestion WHERE id = ?");
            $stmt->execute([$question_id]);
            $question = $stmt->fetch(PDO::FETCH_ASSOC);
            
            
            $stmt = $pdo->prepare("INSERT INTO quizquestion (quizID, question, questionFigureFileName, answerA, answerB, answerC, answerD, correctAnswer) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $question['quizID'],
                $question['question'],
                $question['questionFigureFileName'],
                $question['answerA'],
                $question['answerB'],
                $question['answerC'],
                $question['answerD'],
                $question['correctAnswer']
            ]);
        }
        
        
        $stmt = $pdo->prepare("
            SELECT 
                q.id as quiz_id,
                (SELECT COUNT(*) FROM quizquestion qq WHERE qq.quizID = q.id) as question_count
            FROM quiz q 
            WHERE q.educatorID = ?
        ");
        $stmt->execute([$educator_id]);
        $updated_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true, 
            'updated_counts' => $updated_counts
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>