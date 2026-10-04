
<?php

require_once 'auth_check.php';
if($_SESSION['user_type'] != 'educator') {
    header('Location: login.html?error=access_denied');
    exit();
}


if($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: educator_home.php");
    exit();
}


$question_id = isset($_POST['question_id']) ? intval($_POST['question_id']) : 0;
$quiz_id = isset($_POST['quiz_id']) ? intval($_POST['quiz_id']) : 0;
$question_text = $_POST['question_text'] ?? '';
$choice_a = $_POST['choice_a'] ?? '';
$choice_b = $_POST['choice_b'] ?? '';
$choice_c = $_POST['choice_c'] ?? '';
$choice_d = $_POST['choice_d'] ?? '';
$correct_answer = $_POST['correct_answer'] ?? '';


if($question_id == 0 || $quiz_id == 0 || empty($question_text) || 
   empty($choice_a) || empty($choice_b) || empty($choice_c) || empty($choice_d) || empty($correct_answer)) {
    die("All fields are required");
}


require_once 'config.php';

try {

    $checkStmt = $pdo->prepare("SELECT * FROM quizquestion WHERE id = ? AND quizID = ?");
    $checkStmt->execute([$question_id, $quiz_id]);
    $existingQuestion = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$existingQuestion) {
        die("Question not found or you don't have permission to edit it");
    }
    

    $question_figure_name = $existingQuestion['questionFigureFileName'];
    
    if(isset($_FILES['question_figure']) && $_FILES['question_figure']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = $_FILES['question_figure']['type'];
        $file_size = $_FILES['question_figure']['size'];
        
        
        if(in_array($file_type, $allowed_types)) {
            
            if($file_size <= 5 * 1024 * 1024) {
                $file_extension = pathinfo($_FILES['question_figure']['name'], PATHINFO_EXTENSION);
$question_figure_name = 'quiz' . $quiz_id . '_q' . $question_id . '.' . $file_extension;                $upload_path = 'uploads/' . $question_figure_name;
                
                
                if(!empty($existingQuestion['questionFigureFileName']) && file_exists('uploads/' . $existingQuestion['questionFigureFileName'])) {
                    unlink('uploads/' . $existingQuestion['questionFigureFileName']);
                }
                
                
                if(!move_uploaded_file($_FILES['question_figure']['tmp_name'], $upload_path)) {
                    $question_figure_name = $existingQuestion['questionFigureFileName']; 
                }
            }
        }
    }
    

    $updateStmt = $pdo->prepare("UPDATE quizquestion SET 
                                question = ?, 
                                answerA = ?, 
                                answerB = ?, 
                                answerC = ?, 
                                answerD = ?, 
                                correctAnswer = ?, 
                                questionFigureFileName = ? 
                                WHERE id = ?");
    
    $success = $updateStmt->execute([
        $question_text,
        $choice_a,
        $choice_b,
        $choice_c,
        $choice_d,
        $correct_answer,
        $question_figure_name,
        $question_id
    ]);
    
    if($success) {

        header("Location: QuizPage.php?quiz_id=" . $quiz_id);        exit();
    } else {
        die("Error updating question in database");
    }
    
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
  