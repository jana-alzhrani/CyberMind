
<?php

require_once 'auth_check.php';
if($_SESSION['user_type'] != 'educator') {
    header("Location: unauthorized.php");
    exit();
}


$quiz_id = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : 0;
if($quiz_id == 0) {
    die("Quiz ID is required");
}
?>

<!DOCTYPE html>
<html >
<head>
  <meta charset="UTF-8" />
  <title>Educator-Home</title>
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


 <main class="add-page">
  <h1 class="add-title">Add Question</h1>
<form class="add-card form-grid" action="ProcessAddQuestion.php" method="post" enctype="multipart/form-data">
<input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>">
  
    
    <div class="form-row">
      <label>Question:</label>
      <input type="text" name="question_text" class="input" placeholder="Enter question..." required>
    </div>

    
    <div class="form-row">
      <label>Upload question figure:</label>
      <input type="file" name="question_figure" accept="image/*" class="file">
    </div>

    
    <fieldset class="choice-set">
      <legend>Choices   </legend>
      <div class="choice-grid">
        <label class="choice-line">A
          <input type="text" name="choice_a" class="input" placeholder="Choice A" required>
        </label>
  <label class="choice-line">B
          <input type="text" name="choice_b" class="input" placeholder="Choice B" required>
        </label>
        <label class="choice-line">C
          <input type="text" name="choice_c" class="input" placeholder="Choice C" required>
        </label>
        <label class="choice-line">D
          <input type="text" name="choice_d" class="input" placeholder="Choice D" required>
        </label>
      </div>
    </fieldset>

    
    <div class="form-row">
      <label>Correct Answer:</label>
     <select name="correct_answer" class="select" required>
    <option value="">Select…</option>
    <option value="A">A</option>
    <option value="B">B</option>
    <option value="C">C</option>
    <option value="D">D</option>
    </select>
    </div>


  
    <div class="actions">
      <button type="submit" class="btn-save">Add</button>
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

