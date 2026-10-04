<?php
require_once 'config.php';
require_once 'auth_check.php';


if ($_SESSION['user_type'] !== 'learner') {
    header("Location: login.php?error=not_learner");
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (!isset($_POST['quizID']) || empty($_POST['quizID']) || !isset($_POST['rating']) || empty($_POST['rating'])) {
        header("Location: learner_home.php?error=missing_feedback_data");
        exit;
    }
    
    $quizID = (int)$_POST['quizID'];
    $rating = (int)$_POST['rating'];
    $comments = isset($_POST['comments']) ? trim($_POST['comments']) : '';
    $learnerID = (int)$_SESSION['user_id'];
    
    
    if ($rating < 1 || $rating > 5) {
        header("Location: learner_home.php?error=invalid_rating");
        exit;
    }
    
    
    try {
        $feedbackQuery = "INSERT INTO quizfeedback (quizID, rating, comments, date) VALUES (?, ?, ?, NOW())";
        $stmt = $pdo->prepare($feedbackQuery);
        $stmt->execute([$quizID, $rating, $comments]);
        
        
        header("Location: learner_home.php?success=feedback_submitted");
        exit;
        
    } catch (PDOException $e) {
        header("Location: learner_home.php?error=feedback_failed");
        exit;
    }
    
} else {
    
    header("Location: learner_home.php?error=invalid_request");
    exit;
}
?>