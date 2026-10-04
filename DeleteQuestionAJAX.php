<?php

require_once 'auth_check.php';


function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

if ($_SESSION['user_type'] !== 'educator') {
    if (isAjaxRequest()) {
        echo "false";
    } else {
    header('Location: login.html?error=access_denied');
    }
    exit();
}


if (isAjaxRequest()) {
    $question_id = isset($_POST['question_id']) ? intval($_POST['question_id']) : 0;
    $quiz_id = isset($_POST['quiz_id']) ? intval($_POST['quiz_id']) : 0;
} 

else {
    $question_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $quiz_id = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : 0;
}

if ($question_id === 0 || $quiz_id === 0) {
    if (isAjaxRequest()) {
        echo "false";
    } else {
        die("Question ID and Quiz ID are required");
    }
    exit();
}


require_once 'config.php';

try {
    
    $stmt = $pdo->prepare("SELECT questionFigureFileName FROM quizquestion WHERE id = ?");
    $stmt->execute([$question_id]);
    $question = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$question) {
        if (isAjaxRequest()) {
            echo "false";
        } else {
            die("Question not found");
        }
        exit();
    }

    
    if (!empty($question['questionFigureFileName'])) {
        $filePath = __DIR__ . '/uploads/' . $question['questionFigureFileName'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    
    $stmtDel = $pdo->prepare("DELETE FROM quizquestion WHERE id = ?");
    $stmtDel->execute([$question_id]);

    if (isAjaxRequest()) {
        echo "true";  
    } else {
        
        header("Location: QuizPage.php?quiz_id=" . $quiz_id);
    }
    exit();

} catch (PDOException $e) {
    if (isAjaxRequest()) {
        echo "false";
    } else {
        die("Error deleting question: " . $e->getMessage());
    }
    exit();
}
?>