<?php
require_once 'config.php';
require_once 'auth_check.php';


if ($_SESSION['user_type'] !== 'learner') {
    header("Location: login.php?error=not_learner");
    exit;
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: learner_home.php?error=complete_quiz_first");
    exit;
}


if (!isset($_POST['quizID']) || empty($_POST['quizID'])) {
    header("Location: learner_home.php?error=invalid_quiz_data");
    exit;
}

$quizID = (int)$_POST['quizID'];


if (!isset($_POST['questionIDs']) || !is_array($_POST['questionIDs']) || empty($_POST['questionIDs']) ||
    !isset($_POST['answers']) || !is_array($_POST['answers']) || empty($_POST['answers'])) {
    header("Location: learner_home.php?error=no_answers_submitted");
    exit;
}


try {
    
    $quizQuery = "
        SELECT q.id, t.topicName as topic, u.firstName, u.lastName, u.emailAddress as educator_email 
        FROM quiz q 
        INNER JOIN topic t ON t.id = q.topicID 
        INNER JOIN user u ON u.id = q.educatorID 
        WHERE q.id = ?
    ";
    $stmt = $pdo->prepare($quizQuery);
    $stmt->execute([$quizID]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$quiz) {
        throw new Exception("Quiz not found in database");
    }
    
 
    $questionIDs = $_POST['questionIDs'];
    $userAnswers = $_POST['answers'];
    
    
    $placeholders = str_repeat('?,', count($questionIDs) - 1) . '?';
    $answersQuery = "SELECT id, question, correctAnswer FROM quizquestion WHERE id IN ($placeholders)";
    $stmt = $pdo->prepare($answersQuery);
    $stmt->execute($questionIDs);
    $questionsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    
    $questionsByID = [];
    foreach ($questionsData as $q) {
        $questionsByID[$q['id']] = $q;
    }
    
    
    $score = 0;
    $totalQuestions = count($questionIDs);
    $questionResults = [];
    
    foreach ($questionIDs as $index => $questionID) {
        $isCorrect = false;
        $userAnswer = $userAnswers[$index] ?? null;
        $correctAnswer = $questionsByID[$questionID]['correctAnswer'] ?? null;
        
        if ($userAnswer && $correctAnswer && $userAnswer == $correctAnswer) {
            $score++;
            $isCorrect = true;
        }
        
        $questionResults[] = [
            'questionID' => $questionID,
            'questionText' => $questionsByID[$questionID]['question'] ?? 'Unknown question',
            'userAnswer' => $userAnswer ?? 'No answer',
            'correctAnswer' => $correctAnswer ?? 'Unknown',
            'isCorrect' => $isCorrect
        ];
    }
    
    $percentage = round(($score / $totalQuestions) * 100);
    
    
    $storeAttemptQuery = "INSERT INTO takenquiz (quizID, score) VALUES (?, ?)";
    $stmt = $pdo->prepare($storeAttemptQuery);
    $stmt->execute([$quizID, $percentage]);
    
} catch (Exception $e) {
    header("Location: learner_home.php?error=quiz_processing_error");
    exit;
}


function getVideoByScore($percentage) {
    if ($percentage >= 90) {
        return [
            'path' => 'videos/fullMark.mp4',
            'message' => '🎉 Outstanding! You aced this quiz!',
            'color' => '#10B981'
        ];
    } elseif ($percentage >= 80) {
        return [
            'path' => 'videos/GoodJob.mp4',
            'message' => '👍 Excellent work! You really know your stuff.',
            'color' => '#8BC34A'
        ];
    } elseif ($percentage >= 70) {
        return [
            'path' => 'videos/good.mp4',
            'message' => '😊 Good job! Solid understanding of the material.',
            'color' => '#FFC107'
        ];
    } elseif ($percentage >= 60) {
        return [
            'path' => 'videos/good.mp4',
            'message' => '📚 Not bad! Keep practicing to improve.',
            'color' => '#FF9800'
        ];
    } else {
        return [
            'path' => 'videos/why.mp4',
            'message' => '💪 Keep going! Review the material and try again.',
            'color' => '#EF4444'
        ];
    }
}

$videoFeedback = getVideoByScore($percentage);


function e($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Results - <?= e($quiz['topic']) ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        .score-display {
            text-align: center;
            padding: 30px;
            margin: 20px 0;
            border-radius: 10px;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        
        .score-value {
            font-size: 4rem;
            font-weight: bold;
            color: #4F46E5;
            margin: 10px 0;
        }
        
        .score-message {
            font-size: 1.5rem;
            margin: 10px 0;
        }
        
        .video-feedback {
            text-align: center;
            margin: 40px 0;
            padding: 30px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            border-left: 5px solid <?= $videoFeedback['color'] ?>;
        }
        
        .video-message {
            font-size: 1.4rem;
            font-weight: bold;
            margin-bottom: 20px;
            color: <?= $videoFeedback['color'] ?>;
        }
        
        .video-container {
            max-width: 800px;
            margin: 0 auto;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }
        
        .video-container video {
            width: 100%;
            display: block;
        }
        
        .video-placeholder {
            width: 100%;
            height: 300px;
            background: #222;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 10px;
            color: #fff;
            font-size: 1.2rem;
        }
        
        .feedback-form {
            margin-top: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .rating-options {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .rating-option {
            flex: 1;
            min-width: 100px;
        }
        
        .rating-option input {
            display: none;
        }
        
        .rating-option label {
            display: block;
            padding: 10px;
            text-align: center;
            background: #f8f9fa;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .rating-option input:checked + label {
            background: #4F46E5;
            color: white;
        }
        
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            resize: vertical;
            min-height: 100px;
        }
        
        .question-review {
            margin: 20px 0;
            padding: 15px;
            border-radius: 8px;
            background: #f8f9fa;
        }
        
        .question-review.correct {
            border-left: 4px solid #10B981;
            background: #f0fdf4;
        }
        
        .question-review.incorrect {
            border-left: 4px solid #EF4444;
            background: #fef2f2;
        }
        
        .answer-status {
            font-weight: bold;
            margin-top: 10px;
        }
        
        .answer-status.correct {
            color: #10B981;
        }
        
        .answer-status.incorrect {
            color: #EF4444;
        }
        
        .actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s;
        }
        
        .btn.primary {
            background: #4F46E5;
            color: white;
        }
        
        .btn.primary:hover {
            background: #4338CA;
        }
        
        .btn.secondary {
            background: #6B7280;
            color: white;
        }
        
        .btn.secondary:hover {
            background: #4B5563;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="nav">
            <div class="brand">
                <img src="CyberMind.png" alt="CyberMind Logo" width="100" height="100">
                CyberMind
            </div>
            <nav>
                <a href="learner_home.php">Home</a>
                <a href="logout.php">Sign-out</a>
            </nav>
        </div>
    </header>

    <main class="wrap">
        <div class="card">
            <h1>Quiz Results</h1>
            
            
            <div class="grid-2">
                <div class="card">
                    <h3>Quiz Topic</h3>
                    <p><?= e($quiz['topic']) ?></p>
                </div>
                
                <div class="card">
                    <h3>Educator</h3>
                    <p><?= e($quiz['firstName'] . ' ' . $quiz['lastName']) ?></p>
                    <p><?= e($quiz['educator_email']) ?></p>
                </div>
            </div>

            
            <div class="score-display">
                <h2>Your Score</h2>
                <div class="score-value"><?= $percentage ?>%</div>
                <div class="score-message"><?= $score ?> out of <?= $totalQuestions ?> correct</div>
            </div>

            
            <div class="video-feedback">
                <div class="video-message">
                    <?= $videoFeedback['message'] ?>
                </div>
                
                <div class="video-container">
                    <?php if (file_exists($videoFeedback['path'])): ?>
                        <video controls autoplay muted>
                            <source src="<?= e($videoFeedback['path']) ?>" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    <?php else: ?>
                        <div class="video-placeholder">
                            <p>Video feedback file not found: <?= e($videoFeedback['path']) ?></p>
                            <p>Please make sure the video files are in the 'videos/' folder</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            
            <div class="card">
                <h3>Question Review</h3>
                <p>Here's how you performed on each question:</p>
                
                <?php foreach ($questionResults as $index => $result): ?>
                    <div class="question-review <?= $result['isCorrect'] ? 'correct' : 'incorrect' ?>">
                        <p><strong>Question <?= $index + 1 ?>:</strong></p>
                        <p><?= e($result['questionText']) ?></p>
                        <p><strong>Your answer:</strong> <?= e($result['userAnswer']) ?></p>
                        <p><strong>Correct answer:</strong> <?= e($result['correctAnswer']) ?></p>
                        <div class="answer-status <?= $result['isCorrect'] ? 'correct' : 'incorrect' ?>">
                            <?= $result['isCorrect'] ? '✓ Correct' : '✗ Incorrect' ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        
        <div class="card">
            <div class="feedback-form">
                <h2>Provide Feedback</h2>
                <p>We value your opinion! Please let us know about your experience with this quiz.</p>
                
                <form action="process-feedback.php" method="POST">
                    <input type="hidden" name="quizID" value="<?= $quizID ?>">
                    
                    <div class="form-group">
                        <label for="rating">How would you rate this quiz?</label>
                        <div class="rating-options">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <div class="rating-option">
                                    <input type="radio" id="rating-<?= $i ?>" name="rating" value="<?= $i ?>" 
                                           <?= $i == 3 ? 'checked' : '' ?> required>
                                    <label for="rating-<?= $i ?>">
                                        <?= str_repeat('⭐', $i) ?><br>
                                        <?= $i == 1 ? 'Poor' : ($i == 2 ? 'Fair' : ($i == 3 ? 'Good' : ($i == 4 ? 'Very Good' : 'Excellent'))) ?>
                                    </label>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="comments">Additional Comments (Optional)</label>
                        <textarea id="comments" name="comments" placeholder="Tell us more about your experience..."></textarea>
                    </div>
                    
                    <div class="actions">
                        <button type="submit" class="btn primary">Submit Feedback</button>
                        <a href="learner_home.php" class="btn secondary">Skip Feedback</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
</body>
</html>