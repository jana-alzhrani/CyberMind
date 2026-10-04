<?php
require_once 'config.php';
require_once 'auth_check.php';


if ($_SESSION['user_type'] !== 'learner') {
    header("Location: login.php?error=not_learner");
    exit;
}


$quiz_id = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : 0;
if ($quiz_id == 0) {
    header("Location: learner_home.php?error=invalid_quiz");
    exit;
}


try {

    $quizQuery = "
        SELECT q.id, t.topicName as topic, u.firstName, u.lastName 
        FROM quiz q 
        INNER JOIN topic t ON t.id = q.topicID 
        INNER JOIN user u ON u.id = q.educatorID 
        WHERE q.id = ?
    ";
    $stmt = $pdo->prepare($quizQuery);
    $stmt->execute([$quiz_id]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$quiz) {
        header("Location: learner_home.php?error=quiz_not_found");
        exit;
    }
    

    $questionsQuery = "SELECT * FROM quizquestion WHERE quizID = ?";
    $stmt = $pdo->prepare($questionsQuery);
    $stmt->execute([$quiz_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($questions)) {
        header("Location: learner_home.php?error=no_questions");
        exit;
    }
    

    if (count($questions) > 5) {
        shuffle($questions);
        $questions = array_slice($questions, 0, 5);
    }
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}


function e($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Take Quiz - <?= e($quiz['topic']) ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        .quiz-header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
        }
        
        .question-card {
            background: #FFFFFF;
            border: 0.0625em solid #eee;
            border-radius: 1em;
            padding: 1.375em;
            box-shadow: 0 0.625em 1.25em rgba(0,0,0,.08);
            margin: 1em 0;
        }
        
        .question-number {
            font-size: 1.2rem;
            font-weight: bold;
            color: #4F46E5;
            margin-bottom: 10px;
        }
        
        .option-label {
            display: block;
            padding: 12px 15px;
            margin: 8px 0;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .option-label:hover {
            border-color: #4F46E5;
            background: #f8faff;
        }
        
        .option-input {
            margin-right: 10px;
        }
        
        .submit-container {
            text-align: center;
            margin: 30px 0;
        }
        
        .btn-submit {
            background: linear-gradient(45deg, #10B981, #059669);
            color: white;
            border: none;
            padding: 15px 40px;
            font-size: 1.1rem;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
        }
    </style>
</head>
<body>
    <header class="header">
    <div class="container nav">
      <div class="brand">
        <img src="CyberMind.png" alt="CyberMind Logo" width="100" height="100">
        CyberMind
      </div>
      <nav>
            <a href="learner_home.php">Home</a>
        <a id="signOut" class="btn" href="logout.php">Sign-out</a>
      </nav>
    </div>
  </header>

    <main class="wrap">
        <div class="quiz-header">
            <h1><?= e($quiz['topic']) ?> Quiz</h1>
            <p>Educator: <?= e($quiz['firstName'] . ' ' . $quiz['lastName']) ?></p>
            <p>Total Questions: <?= count($questions) ?></p>
        </div>

        <form action="quiz-score.php" method="POST" id="quizForm">
            <input type="hidden" name="quizID" value="<?= $quiz_id ?>">
            
            <?php foreach ($questions as $index => $question): ?>
                <div class="question-card">
                    <div class="question-number">
                        Question <?= $index + 1 ?> of <?= count($questions) ?>
                    </div>
                    
                    <input type="hidden" name="questionIDs[]" value="<?= $question['id'] ?>">
                    
                    <h3><?= e($question['question']) ?></h3>
                    
                    <?php if (!empty($question['questionFigureFileName'])): ?>
                        <img src="uploads/<?= e($question['questionFigureFileName']) ?>" 
                             alt="Question figure" style="max-width: 300px; margin: 15px 0; border-radius: 8px;">
                    <?php endif; ?>
                    
                    <div class="options-container">
                        <label class="option-label">
                            <input class="option-input" type="radio" name="answers[<?= $index ?>]" value="A" required>
                            <strong>A)</strong> <?= e($question['answerA']) ?>
                        </label>
                        
                        <label class="option-label">
                            <input class="option-input" type="radio" name="answers[<?= $index ?>]" value="B">
                            <strong>B)</strong> <?= e($question['answerB']) ?>
                        </label>
                        
                        <label class="option-label">
                            <input class="option-input" type="radio" name="answers[<?= $index ?>]" value="C">
                            <strong>C)</strong> <?= e($question['answerC']) ?>
                        </label>
                        
                        <label class="option-label">
                            <input class="option-input" type="radio" name="answers[<?= $index ?>]" value="D">
                            <strong>D)</strong> <?= e($question['answerD']) ?>
                        </label>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div class="submit-container">
                <button type="submit" class="btn-submit">Submit Quiz</button>
                <p style="margin-top: 10px; color: #6b7280;">
                    Make sure you've answered all questions before submitting
                </p>
            </div>
        </form>
    </main>

    <script>
        document.getElementById('quizForm').addEventListener('submit', function(e) {
            const totalQuestions = <?= count($questions) ?>;
            let answered = 0;
            
            for (let i = 0; i < totalQuestions; i++) {
                const radios = document.querySelectorAll('input[name="answers[' + i + ']"]:checked');
                if (radios.length > 0) {
                    answered++;
                }
            }
            
            if (answered < totalQuestions) {
                e.preventDefault();
                alert('Please answer all ' + totalQuestions + ' questions before submitting.');
            }
        });
    </script>
    
      <footer>
  <div class="footer-container">
    
    <div>
      <div class="brand" style="color: white; margin-bottom: 15px;">
      <img src="CyberMind.png" alt="CyberMind Logo" width="100" height="100" class="footer-logo">
        ٍCyberMind
      </div>
      <p class="textfooter">CyberMind Where learners connect with cybersecurity experts.</p>
    </div>

    
    <div class="social-media">
      <h3>Follow Us</h3>
      <p class="linebrak">____________________</p>
      <p><i class="fa-brands fa-facebook"></i> Facebook</p>
      <p><i class="fa-brands fa-instagram"></i> Instagram</p>
      <p><i class="fa-brands fa-x-twitter"></i> Twitter</p>
    </div>

    
    
    <div class="contact-us">
      <h3>Contact Us</h3>
      <p class="linebrak">____________________</p>
      <p><i class="fa-solid fa-phone"></i> 123-4567</a></p>
      <p><i class="fa-solid fa-envelope"></i> Email</a></p>
      <p><i class="fa-solid fa-location-dot"></i> Riyadh, Saudi Arabia</p>
    </div>


    <p class="copyright">&copy; 2025 BrightPath. All Rights Reserved.</p>
  </div>
</footer>
</body>
</html>