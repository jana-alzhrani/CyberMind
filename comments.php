<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}


$quiz_id = $_GET['quiz_id'] ?? '';

if (empty($quiz_id)) {
    die("Quiz ID is required.");
}


$stmt = $pdo->prepare("SELECT q.*, t.topicName 
                      FROM quiz q 
                      JOIN topic t ON q.topicID = t.id 
                      WHERE q.id = ?");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$quiz) {
    die("Quiz not found.");
}

$stmt = $pdo->prepare("SELECT * FROM quizfeedback 
                      WHERE quizID = ? AND comments IS NOT NULL AND comments != '' 
                      ORDER BY date DESC");
$stmt->execute([$quiz_id]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quiz Comments - CyberMind</title>
  <link rel="stylesheet" href="style.css">
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
    <a id="signOut" class="btn" href="logout.php" style="margin-right: 10px;">Sign-out</a> 
</nav>
    </div>
  </header>


  <main class="wrap">
    <div class="card">
      <h2>Quiz Comments</h2>
      <p class="highlight" style="font-style: italic;">See what learners are saying about "<?php echo htmlspecialchars($quiz['topicName']); ?>" quiz</p>
    </div>

    <div class="comments-container" style="max-height:600px; overflow-y:auto; margin-top: 20px;">
      <?php if (count($comments) > 0): ?>
        <?php foreach ($comments as $comment): ?>
          <section class="card educator-info" style="margin: 15px 0; padding: 20px;">
            <div class="info-flex">
              <div class="avatar">
                <img src="uploads\default_avatar.svg" alt="Anonymous User" style="width: 60px; height: 60px;">
              </div>
              <div style="flex: 1;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                  <div>
                    
                    <div style="margin-bottom: 8px;">
                      <?php for ($i = 1; $i <= 5; $i++): ?>
                        <?php if ($i <= $comment['rating']): ?>
                          <i class="fas fa-star" style="color: #FFD700; font-size: 1.1em;"></i>
                        <?php else: ?>
                          <i class="far fa-star" style="color: #FFD700; font-size: 1.1em;"></i>
                        <?php endif; ?>
                      <?php endfor; ?>
                      <span style="margin-left: 8px; color: #6B7280; font-weight: 600;">
                        (<?php echo $comment['rating']; ?>/5)
                      </span>
                    </div>
                    
                    
                    <p style="margin: 8px 0; font-size: 1em; line-height: 1.5; color: #111827;">
                      <?php echo htmlspecialchars($comment['comments']); ?>
                    </p>
                  </div>
                  
                  
                  <div style="text-align: right;">
                    <p style="margin: 0; color: #6B7280; font-size: 0.9em; font-weight: 600;">
                      <?php echo date('M j, Y', strtotime($comment['date'])); ?>
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </section>
        <?php endforeach; ?>
      <?php else: ?>
        <section class="card educator-info" style="text-align: center; padding: 40px 20px;">
          <div class="info-flex" style="justify-content: center;">
            <div class="avatar">
              <img src="uploads\default_avatar.svg" alt="No Comments" style="width: 80px; height: 80px;">
            </div>
            <div style="margin-left: 20px;">
              <h3 style="color: #6B7280; margin-bottom: 10px;">No Comments Yet</h3>
              <p style="color: #6B7280; margin: 0;">Be the first to leave feedback on this quiz!</p>
            </div>
          </div>
        </section>
      <?php endif; ?>
    </div>

   
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
        <p class="linebrak"></p>
        <p><i class="fa-brands fa-facebook"></i> Facebook</p>
        <p><i class="fa-brands fa-instagram"></i> Instagram</p>
        <p><i class="fa-brands fa-x-twitter"></i> Twitter</p>
      </div>

      
      <div class="contact-us">
        <h3>Contact Us</h3>
        <p class="linebrak"></p>
        <p><i class="fa-solid fa-phone"></i> 123-4567</p>
        <p><i class="fa-solid fa-envelope"></i> Email</p>
        <p><i class="fa-solid fa-location-dot"></i> Riyadh, Saudi Arabia</p>
      </div>

      
      <p class="copyright">&copy; 2025 CyberMind. All Rights Reserved.</p>
    </div>
  </footer>

</body>
</html>