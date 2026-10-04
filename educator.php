<?php
require_once 'config.php';

require_once 'auth_check.php';

if ($_SESSION['user_type'] !== 'educator') {
    header('Location: login.html?error=access_denied');
    exit();
}


$educator_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM user WHERE id = ?");
$stmt->execute([$educator_id]);
$educator = $stmt->fetch(PDO::FETCH_ASSOC);


$stmt = $pdo->prepare("SELECT DISTINCT t.topicName 
                      FROM quiz q 
                      JOIN topic t ON q.topicID = t.id 
                      WHERE q.educatorID = ?");
$stmt->execute([$educator_id]);
$expertise_topics = $stmt->fetchAll(PDO::FETCH_ASSOC);


$stmt = $pdo->prepare("SELECT 
    q.id as quiz_id,
    t.topicName,
    (SELECT COUNT(*) FROM quizquestion qq WHERE qq.quizID = q.id) as question_count,
    (SELECT COUNT(*) FROM takenquiz tq WHERE tq.quizID = q.id) as taker_count,
    (SELECT AVG(score) FROM takenquiz tq WHERE tq.quizID = q.id) as avg_score,
    (SELECT AVG(rating) FROM quizfeedback qf WHERE qf.quizID = q.id) as avg_rating,
    (SELECT COUNT(*) FROM quizfeedback qf WHERE qf.quizID = q.id AND comments IS NOT NULL AND comments != '') as comment_count
FROM quiz q 
JOIN topic t ON q.topicID = t.id 
WHERE q.educatorID = ?");
$stmt->execute([$educator_id]);
$quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);


$stmt = $pdo->prepare("SELECT rq.*, t.topicName, u.firstName, u.lastName, u.photoFileName
                      FROM recommendedquestion rq
                      JOIN quiz q ON rq.quizID = q.id
                      JOIN topic t ON q.topicID = t.id
                      JOIN user u ON rq.learnerID = u.id
                      WHERE q.educatorID = ? AND rq.status = 'pending'");
$stmt->execute([$educator_id]);
$pending_questions = $stmt->fetchAll(PDO::FETCH_ASSOC);


?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <title>Educator Dashboard - CyberMind</title>
    <link rel="stylesheet" href="style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header class="header">
        <div class="container nav">
            <div class="brand">
                <img src="CyberMind.png" alt="CyberMind Logo" width="100" height="100">
                CyberMind
            </div>
            <nav>
                <a id="signOut" class="btn" href="logout.php">Sign-out</a>
            </nav>
        </div>
    </header>

    <main class="wrap">
        <h2>Welcome, <span id="eduName" class="highlight">
            <?php 
            
            if (isset($_SESSION['first_name'])) {
                echo htmlspecialchars($_SESSION['first_name']);
            } else {
                echo htmlspecialchars($educator['firstName'] . ' ' . $educator['lastName']);
            }
            ?>
        </span></h2>

        <section class="card educator-info">
            <h3>Educator Information</h3>
            <div class="info-flex">
      <div class="avatar">
            <img src="uploads/<?php echo htmlspecialchars($educator['photofileName'] ?? 'default_avatar.svg'); ?>" alt="Educator Photo">
        </div>
                <div id="eduInfo">
                    <p><strong>Name:</strong> 
                        <?php 
                        if (isset($_SESSION['first_name'])) {
                            echo htmlspecialchars($_SESSION['first_name']);
                        } else {
                            echo htmlspecialchars($educator['firstName'] . ' ' . $educator['lastName']);
                        }
                        ?>
                    </p>
                    <p><strong>Email:</strong> 
                        <?php 
                        if (isset($_SESSION['email'])) {
                            echo htmlspecialchars($_SESSION['email']);
                        } else {
                            echo htmlspecialchars($educator['emailAddress']);
                        }
                        ?>
                    </p>
                    <p><strong>Expertise:</strong> <i>Selected Topics:</i></p>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                        <?php if (count($expertise_topics) > 0): ?>
                            <?php foreach ($expertise_topics as $topic): ?>
                                <p style="background: #f8fafc; border: 0.0625em solid #e5e7eb; border-radius: 999em; padding: 0.5em 0.75em;">
                                    <?php echo htmlspecialchars($topic['topicName']); ?>
                                </p>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color: gray; font-style: italic;">No expertise selected yet</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <section class="card">
            <h3>Your Quizzes</h3>
            <?php if (count($quizzes) > 0): ?>
                <table class="table-alt">
                    <thead>
                        <tr>
                            <th>Topic</th>
                            <th>Number of Questions</th>
                            <th>Quiz Statistics</th>
                            <th>Quiz Feedback</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($quizzes as $quiz): ?>
                            <tr>
                                <td>
                                    <a href="QuizPage.php?quiz_id=<?php echo $quiz['quiz_id']; ?>" class="topic-link">
                                        <?php echo htmlspecialchars($quiz['topicName']); ?>
                                    </a>
                                </td>
                                <td><?php echo $quiz['question_count']; ?></td>
                                <td>
                                    <?php if ($quiz['taker_count'] > 0): ?>
                                        <strong>Quiz Takers:</strong> <?php echo $quiz['taker_count']; ?><br>
                                        <strong>Average Score:</strong> <?php echo $quiz['avg_score'] ? round($quiz['avg_score'], 1) . '%' : '0%'; ?>
                                    <?php else: ?>
                                        <span style="color: gray; font-style: italic;">Quiz not taken yet</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($quiz['avg_rating'] !== null): ?>
                                        <strong>Average Rating:</strong> <?php echo round($quiz['avg_rating'], 1); ?>/5<br>
                                        <?php if ($quiz['comment_count'] > 0): ?>
                                            <a href="comments.php?quiz_id=<?php echo $quiz['quiz_id']; ?>" class="topic-link">View Comments</a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: gray; font-style: italic;">No feedback yet</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No quizzes created yet.</p>
            <?php endif; ?>
        </section>

        <section class="card">
            <h3>Question Recommendations</h3>
            <?php if (count($pending_questions) > 0): ?>
                <table class="table-alt">
                    <thead>
                        <tr>
                            <th>Topic</th>
                            <th>Learner</th>
                            <th>Question</th>
                            <th>Review</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_questions as $question): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($question['topicName']); ?></td>
                                <td>
                                    <div class="learner-info">
                                        <img src="uploads/<?php echo htmlspecialchars($question['photoFileName'] ?? 'default_avatar.svg'); ?>" alt="Learner" style="width:30px;height:30px;border-radius:50%;">
                                        <?php echo htmlspecialchars($question['firstName'] . ' ' . $question['lastName']); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($question['questionFigureFileName']): ?>
                                        <div class="figure">
<img src="uploads/<?php echo htmlspecialchars($question['questionFigureFileName'] ?? ''); ?>" alt="question figure" style="max-width: 200px;">                                        </div>
                                    <?php endif; ?>
                                    <p><strong><?php echo htmlspecialchars($question['question']); ?></strong></p>
                                    <ul class="answers">
                                        <li>A) <?php echo htmlspecialchars($question['answerA']); ?></li>
                                        <li>B) <?php echo htmlspecialchars($question['answerB']); ?></li>
                                        <li>C) <?php echo htmlspecialchars($question['answerC']); ?></li>
                                        <li>D) <?php echo htmlspecialchars($question['answerD']); ?></li>
                                    </ul>
                                    <p style="color: green; font-weight: bold;">Correct Answer: <?php echo $question['correctAnswer']; ?></p>
                                </td>
                                <td>
                                    <form action="review_question_ajax.php" method="POST">
                                        <input type="hidden" name="question_id" value="<?php echo $question['id']; ?>">
                                        <label for="comment">Comment:</label>
                                        <textarea name="comment" rows="2" style="width: 100%;"></textarea>
                                        <div class="approval">
                                            <label><input type="radio" name="decision" value="approved" required> Approve</label>
                                            <label><input type="radio" name="decision" value="disapproved" required> Reject</label>
                                        </div>
                                        <button type="submit" class="btn">Submit Review</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No pending question recommendations.</p>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        <div class="footer-container">
            <div>
                <div class="brand" style="color: white; margin-bottom: 15px;">
                    <img src="CyberMind.png" alt="CyberMind Logo" width="100" height="100" class="footer-logo">
                    CyberMind
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
                <p><i class="fa-solid fa-phone"></i> 123-4567</p>
                <p><i class="fa-solid fa-envelope"></i> Email</p>
                <p><i class="fa-solid fa-location-dot"></i> Riyadh, Saudi Arabia</p>
            </div>
            <p class="copyright">&copy; 2025 CyberMind. All Rights Reserved.</p>
        </div>
    </footer>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> 
<script>
$(document).ready(function() {
    
    $('.table-alt form').on('submit', function(e) { 
        e.preventDefault();
        
        var form = $(this);
        var row = form.closest('tr');
        var button = form.find('button');
        
        button.prop('disabled', true).text('Processing...');
        
        var formData = new FormData(form[0]);
        
        
        $.ajax({
            url: 'review_question_ajax.php', 
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    if (response.updated_counts) { 
    response.updated_counts.forEach(function(quiz) {
        var quizRow = $('a[href*="quiz_id=' + quiz.quiz_id + '"]').closest('tr');
        var questionCountCell = quizRow.find('td:nth-child(2)');
        
        if (questionCountCell.length) {
            questionCountCell.text(quiz.question_count);
        }
    });
}
                    
                    row.remove();
                    
                    
                    if ($('.table-alt tbody tr').length === 0) {
                        $('.card:contains("Question Recommendations")').html(
                            '<h3>Question Recommendations</h3><p>No pending question recommendations.</p>'
                        );
                    }
                } else {
                    alert('Error: ' + response.error);
                    button.prop('disabled', false).text('Submit Review');
                }
            },
            error: function() {
                alert('Error processing review');
                button.prop('disabled', false).text('Submit Review');
            }
        });
    });
});
</script>
</body>
</html>