<?php

require_once 'auth_check.php';
if ($_SESSION['user_type'] != 'educator') {
    header('Location: login.html?error=access_denied');
    exit();
}

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    die("Invalid request method");
}


$quiz_id        = isset($_POST['quiz_id']) ? intval($_POST['quiz_id']) : 0;
$question_text  = isset($_POST['question_text']) ? trim($_POST['question_text']) : '';
$choice_a       = isset($_POST['choice_a']) ? trim($_POST['choice_a']) : '';
$choice_b       = isset($_POST['choice_b']) ? trim($_POST['choice_b']) : '';
$choice_c       = isset($_POST['choice_c']) ? trim($_POST['choice_c']) : '';
$choice_d       = isset($_POST['choice_d']) ? trim($_POST['choice_d']) : '';
$correct_answer = isset($_POST['correct_answer']) ? trim($_POST['correct_answer']) : '';


if (
    $quiz_id == 0 ||
    empty($question_text) ||
    empty($choice_a) ||
    empty($choice_b) ||
    empty($choice_c) ||
    empty($choice_d) ||
    empty($correct_answer)
) {
    die("All fields are required");
}

$question_figure_name = null;

try {

    $stmt = $pdo->prepare("
        INSERT INTO quizquestion 
        (quizID, question, questionFigureFileName, answerA, answerB, answerC, answerD, correctAnswer) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $quiz_id,
        $question_text,
        null,          
        $choice_a,
        $choice_b,
        $choice_c,
        $choice_d,
        $correct_answer
    ]);

    $question_id = $pdo->lastInsertId();



    if (isset($_FILES['question_figure']) && $_FILES['question_figure']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['question_figure'];

        
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
        if (!in_array($file['type'], $allowed_types)) {
            die("Only image files are allowed");
        }

        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));


        $question_figure_name = "quiz{$quiz_id}_q{$question_id}." . $file_extension;

        $upload_dir  = 'uploads/';
        $upload_path = $upload_dir . $question_figure_name;

        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            die("Error uploading image");
        }

        $updateStmt = $pdo->prepare("
            UPDATE quizquestion
            SET questionFigureFileName = ?
            WHERE id = ?  
        ");
        $updateStmt->execute([
            $question_figure_name,
            $question_id
        ]);
    }


    header("Location: QuizPage.php?quiz_id=" . $quiz_id);
    exit();

} catch (PDOException $e) {

    if ($question_figure_name && file_exists('uploads/' . $question_figure_name)) {
        unlink('uploads/' . $question_figure_name);
    }

    die("Error adding question: " . $e->getMessage());
}
?>
