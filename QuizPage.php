<?php
require_once 'auth_check.php';
if($_SESSION['user_type'] != 'educator') {
    header('Location: login.html?error=access_denied');
    exit();
}

$quiz_id = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : 0;
if($quiz_id == 0) {
    die("Quiz ID is required");
}
require_once 'config.php';

try {
    $stmt = $pdo->prepare("
        SELECT t.topicName AS topic_name
        FROM quiz q
        JOIN topic t ON q.topicID = t.id
        WHERE q.id = ?
        LIMIT 1
    ");
    $stmt->execute([$quiz_id]);
    $quizInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($quizInfo) {
        $topic_name = $quizInfo['topic_name'];
    } else {
        die("Quiz not found");
    }
} catch(PDOException $e) {
    die("Error fetching quiz info: " . $e->getMessage());
}

try {
    $stmt = $pdo->prepare("SELECT * FROM quizquestion WHERE quizID = ?");
    $stmt->execute([$quiz_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error fetching questions: " . $e->getMessage());
}
?> 

<!DOCTYPE html>
<html >
<head>
  <meta charset="UTF-8" />
  <title>Quizpage-Home</title>
  <link rel="stylesheet" href="Style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


</head>
<body>
 <header class="header">
    <div class="container nav">
      <div class="brand">
        <img src="CyberMind.png" alt="CyberMind Logo" width="100" height="100">
        CyberMind
      </div>

<nav>
  <a href="educator.php">Home</a>
  <a id="signOut" class="btn" href="logout.php">Sign-out</a> 
</nav>
    </div>
  </header>

  <main>
    <header class="page-head">
  <h2>Quiz for <?php echo htmlspecialchars($topic_name); ?></h2>
  <a class="btn" href="AddQuestion.php?quiz_id=<?php echo $quiz_id; ?>">+ Add New Question</a>
</header>

    <div class="q-list-header">
      <span class="q-title"><br>Question Details<br><br></span>
    </div>

   
    <section class="quiz-list" id="quizList" aria-live="polite">
      <?php if(empty($questions)): ?>
<p>This quiz does not have any questions yet.</p>
      <?php else: ?>
        <?php foreach($questions as $question): ?>
          <article class="q-item" aria-label="Question">
            <?php if(!empty($question['questionFigureFileName'])): ?>
              <img class="q-media" src="uploads/<?php echo $question['questionFigureFileName']; ?>" alt="Question figure">
            <?php endif; ?>
            
            <h3><?php echo htmlspecialchars($question['question']); ?></h3>
            <ol class="choices-auto">
              <li><?php echo htmlspecialchars($question['answerA']); ?></li>
              <li><?php echo htmlspecialchars($question['answerB']); ?></li>
              <li><?php echo htmlspecialchars($question['answerC']); ?></li>
              <li><?php echo htmlspecialchars($question['answerD']); ?></li>
            </ol>
            
            <div class="q-card-actions">
        
              <a class="btn-edit" href="EditQuestion.php?id=<?php echo $question['id']; ?>">Edit</a>
            
              <a class="btn-delete" href="#" data-question-id="<?php echo $question['id']; ?>" data-quiz-id="<?php echo $quiz_id; ?>">Delete</a>      
              
            </div>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </section>

  </main>



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
<!-- ==================== END FOOTER ====================== -->

<!-- ====================  START SCRIPT AJAX ====================== -->

<script>
$(document).ready(function() {
    $('.btn-delete').on('click', function(e) {
        e.preventDefault();
        
        var deleteButton = $(this);
        var questionId = deleteButton.attr('data-question-id');
        var quizId = deleteButton.attr('data-quiz-id');
        var questionElement = deleteButton.closest('.q-item');
        
        if(confirm('Are you sure you want to delete this question?')) {
            // إرسال طلب AJAX
            $.ajax({
            url: 'DeleteQuestionAJAX.php',
            type: 'POST',
                data: {
                    question_id: questionId,
                    quiz_id: quizId
                },
                success: function(response) {
    console.log('Response:', response); 
    console.log('Response type:', typeof response); 
    
    if(response === 'true') {
         // ==================== COMMENT AREA ====================
        // Success: Question deleted successfully from database
        // ======================================================
        console.log('Deletion successful'); 
        questionElement.remove();
        alert('Question deleted successfully!');
 
         if($('.q-item').length === 0) {
            $('#quizList').html('<p>This quiz does not have any questions yet.</p>');
        }
    } 
    else {
        console.log('Deletion failed'); 
        alert('Error deleting question. Please try again.');
    }
},

error: function(xhr, status, error) {
    console.log('AJAX Error:', error); 
    alert('Error deleting question. Please try again.');
}
            });
        }
    });
});
</script>

<!-- ==================== END SCRIPT AJAX ====================== -->

</body>
</html>