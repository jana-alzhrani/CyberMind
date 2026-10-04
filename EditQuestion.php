
<?php

require_once 'auth_check.php';
if($_SESSION['user_type'] != 'educator') {
    header('Location: login.html?error=access_denied');
    exit();
}


$question_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if($question_id == 0) {
    die("Question ID is required");
}


require_once 'config.php';
try {
    $stmt = $pdo->prepare("SELECT * FROM quizquestion WHERE id = ?");
    $stmt->execute([$question_id]);
    $question = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$question) {
        die("Question not found");
    }
    
    $quiz_id = $question['quizID'];
    
} catch(PDOException $e) {
    die("Error fetching question: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8" />
  <title>Edit Question</title>
  <link rel="stylesheet" href="Style.css" />
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
        <a href="educator.php">Home</a>
        <a id="signOut" class="btn" href="logout.php">Sign-out</a> 
      </nav>
    </div>
  </header>
 

  <main class="edit-page">
    <h1 class="edit-title">Edit Question</h1>
    
   
    <form class="edit-card form-grid" action="ProcessEditQuestion.php" method="post" enctype="multipart/form-data">
      
     
      <input type="hidden" name="question_id" value="<?php echo $question_id; ?>">
      <input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>">
      
      
      <div>
        <label style="display:block; font-weight:700; margin-bottom:6px;">Question:</label>
        <input type="text" name="question_text"
               value="<?php echo htmlspecialchars($question['question']); ?>"
               required
               style="padding:12px; border:1px solid #e5e7eb; border-radius:10px; width:100%;">
      </div>

      
      <div>
        <label style="display:block; font-weight:700; margin-bottom:6px;">Current Figure:</label>
        <?php if(!empty($question['questionFigureFileName'])): ?>
          <img src="uploads/<?php echo $question['questionFigureFileName']; ?>" alt="Current question figure" style="display:block; width:320px; max-width:100%; border-radius:10px; border:6px solid #003366; margin-bottom:10px;">
        <?php else: ?>
          <p>No image uploaded</p>
        <?php endif; ?>
        <label style="display:block; font-weight:700; margin-bottom:6px;">Upload question figure:</label>
        <input type="file" name="question_figure" accept="image/*" style="padding:10px; border:1px solid #e5e7eb; border-radius:10px;">
      </div>

      
      <fieldset style="border:1px solid #e5e7eb; border-radius:12px; padding:14px;">
        <legend style="font-weight:800; padding:0 6px;">Choices</legend>
        <div style="display:grid; gap:10px;">
          <label style="display:grid; grid-template-columns:24px 1fr; gap:10px; align-items:center;">
            <span style="font-weight:800;">A</span>
            <input type="text" name="choice_a"
                   value="<?php echo htmlspecialchars($question['answerA']); ?>"
                   required
                   style="padding:10px; border:1px solid #e5e7eb; border-radius:10px;">
          </label>
          <label style="display:grid; grid-template-columns:24px 1fr; gap:10px; align-items:center;">
            <span style="font-weight:800;">B</span>
            <input type="text" name="choice_b"
                   value="<?php echo htmlspecialchars($question['answerB']); ?>"
                   required
                   style="padding:10px; border:1px solid #e5e7eb; border-radius:10px;">
          </label>
          <label style="display:grid; grid-template-columns:24px 1fr; gap:10px; align-items:center;">
            <span style="font-weight:800;">C</span>
            <input type="text" name="choice_c"
                   value="<?php echo htmlspecialchars($question['answerC']); ?>"
                   required
                   style="padding:10px; border:1px solid #e5e7eb; border-radius:10px;">
          </label>
          <label style="display:grid; grid-template-columns:24px 1fr; gap:10px; align-items:center;">
            <span style="font-weight:800;">D</span>
            <input type="text" name="choice_d"
                   value="<?php echo htmlspecialchars($question['answerD']); ?>"
                   required
                   style="padding:10px; border:1px solid #e5e7eb; border-radius:10px;">
          </label>
        </div>
      </fieldset>

      
      <div>
        <label style="display:block; font-weight:800; margin-bottom:6px;">Correct Answer:</label>
        <select name="correct_answer" required style="padding:10px; border:1px solid #e5e7eb; border-radius:10px; width:140px;">
          <option value="A" <?php echo ($question['correctAnswer'] == 'A') ? 'selected' : ''; ?>>A</option>
          <option value="B" <?php echo ($question['correctAnswer'] == 'B') ? 'selected' : ''; ?>>B</option>
          <option value="C" <?php echo ($question['correctAnswer'] == 'C') ? 'selected' : ''; ?>>C</option>
          <option value="D" <?php echo ($question['correctAnswer'] == 'D') ? 'selected' : ''; ?>>D</option>
        </select>
      </div>

      <div class="actions">
        <button type="submit" class="btn-save">Save</button>
      </div>
    </form>
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
 

  <script src="script.js"></script>
</body>
</html>
   