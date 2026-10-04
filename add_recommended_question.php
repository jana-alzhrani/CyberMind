<?php
require_once 'config.php';
require_once 'auth_check.php';


if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'learner') {
    header("Location: login.php?error=not_learner");
    exit;
}
$learnerId = (int)$_SESSION['user_id'];


$topicId      = isset($_POST['topic_id']) ? (int)$_POST['topic_id'] : 0;
$educatorId   = isset($_POST['educator_id']) ? (int)$_POST['educator_id'] : 0;
$questionText = isset($_POST['question_text']) ? trim($_POST['question_text']) : '';

$answerA = isset($_POST['answerA']) ? trim($_POST['answerA']) : '';
$answerB = isset($_POST['answerB']) ? trim($_POST['answerB']) : '';
$answerC = isset($_POST['answerC']) ? trim($_POST['answerC']) : '';
$answerD = isset($_POST['answerD']) ? trim($_POST['answerD']) : '';
$correct = isset($_POST['correct'])  ? $_POST['correct'] : '';

if ($topicId <= 0 || $educatorId <= 0 || $questionText === '' ||
    $answerA === '' || $answerB === '' || $answerC === '' || $answerD === '' ||
    !in_array($correct, ['A','B','C','D'], true)) {
    header("Location: recommend_question.php?msg=" . urlencode("Please fill all required fields."));
    exit;
}


$findQuiz = $pdo->prepare("SELECT id FROM quiz WHERE topicID = :t AND educatorID = :e LIMIT 1");
$findQuiz->execute([':t'=>$topicId, ':e'=>$educatorId]);
$quizId = (int)($findQuiz->fetchColumn() ?: 0);

if ($quizId === 0) {
    header("Location: recommend_question.php?msg=" . urlencode("No quiz found for this Topic & Educator."));
    exit;
}


$storedFileName = null;
if (!empty($_FILES['figure_file']['name']) && $_FILES['figure_file']['error'] === UPLOAD_ERR_OK) {
    $tmp  = $_FILES['figure_file']['tmp_name'];
    $orig = $_FILES['figure_file']['name'];
    $size = (int)$_FILES['figure_file']['size'];

    if ($size > 3 * 1024 * 1024) {
        header("Location: recommend_question.php?msg=" . urlencode("Figure too large (max 3MB)."));
        exit;
    }

    $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
    if (!in_array($ext, ['png','jpg','jpeg','svg'], true)) {
        header("Location: recommend_question.php?msg=" . urlencode("Invalid file type. Use PNG/JPG/SVG."));
        exit;
    }

    $storedFileName = 'rq_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = __DIR__ . '/uploads/' . $storedFileName;
    if (!@move_uploaded_file($tmp, $dest)) {
        header("Location: recommend_question.php?msg=" . urlencode("Error saving uploaded file."));
        exit;
    }
}


$sql = "INSERT INTO recommendedquestion
        (learnerID, quizID, question, questionFigureFileName,
         answerA, answerB, answerC, answerD, correctAnswer, status)
        VALUES (:learnerID, :quizID, :question, :figure,
                :A, :B, :C, :D, :correct, 'pending')";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':learnerID', $learnerId, PDO::PARAM_INT);
$stmt->bindValue(':quizID',    $quizId,   PDO::PARAM_INT);
$stmt->bindValue(':question',  $questionText, PDO::PARAM_STR);
$stmt->bindValue(':figure',    $storedFileName, $storedFileName ? PDO::PARAM_STR : PDO::PARAM_NULL);
$stmt->bindValue(':A', $answerA);
$stmt->bindValue(':B', $answerB);
$stmt->bindValue(':C', $answerC);
$stmt->bindValue(':D', $answerD);
$stmt->bindValue(':correct', $correct);

try {
    $stmt->execute();
    header("Location: learner_home.php?msg=" . urlencode("Your question was submitted and is now pending."));
    exit;
} catch (Throwable $e) {
    header("Location: recommend_question.php?msg=" . urlencode("Database error: ".$e->getMessage()));
    exit;
}
