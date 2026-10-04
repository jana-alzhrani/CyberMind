<?php
require_once 'config.php';
require_once 'auth_check.php';


if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'learner') {
    header("Location: login.php?error=not_learner");
    exit;
}
$learnerId = (int)$_SESSION['user_id'];


$topicsStmt = $pdo->query("SELECT id, topicName FROM topic ORDER BY topicName ASC");
$topics = $topicsStmt->fetchAll(PDO::FETCH_ASSOC);


$educatorsStmt = $pdo->prepare("SELECT id, firstName, lastName FROM user WHERE userType = 'educator' ORDER BY firstName, lastName");
$educatorsStmt->execute();
$educators = $educatorsStmt->fetchAll(PDO::FETCH_ASSOC);


$msg = isset($_GET['msg']) ? $_GET['msg'] : '';

function e($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Recommend a Question • CyberMind</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="header">
  <div class="container nav">
    <div class="brand">
      <img src="CyberMind.png" alt="CyberMind Logo" width="45" height="45">
      CyberMind
    </div>
    <nav>
      <a class="btn" href="learner_home.php">Back</a>
      <a class="btn" href="logout.php">Sign-out</a>
    </nav>
  </div>
</header>

<main class="container add-page">
  <h1 class="add-title">Recommend a Question</h1>
  <div class="add-card">
    <p class="helper">Select a topic to see educators who create quizzes in that area.</p>
    
    <?php if ($msg): ?>
      <div class="notice"><?= e($msg) ?></div>
    <?php endif; ?>

    <form action="add_recommended_question.php" method="POST" enctype="multipart/form-data">

      
      <div class="first_row">
        <div class="form-row">
          <label for="topic_id">Topic *</label>
          <select id="topic_id" name="topic_id" class="select" required>
            <option value="">— Select Topic —</option>
            <?php foreach ($topics as $t): ?>
              <option value="<?= (int)$t['id'] ?>"><?= e($t['topicName']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-row">
    <label for="educator_id">Educator *</label>
    <select id="educator_id" name="educator_id" class="select" required disabled>
        <option value="">— First select a topic —</option>
    </select>
</div>
      </div>

      
      <div class="form-row">
        <label for="question_text">Question Text *</label>
        <input id="question_text" name="question_text" class="input" type="text"
               placeholder="Write the question here..." required>
      </div>

      
      <div class="first_row">
        <div class="form-row">
          <label for="answerA">Answer A *</label>
          <input id="answerA" name="answerA" class="input" type="text" required>
        </div>
        <div class="form-row">
          <label for="answerB">Answer B *</label>
          <input id="answerB" name="answerB" class="input" type="text" required>
        </div>
      </div>

      <div class="first_row">
        <div class="form-row">
          <label for="answerC">Answer C *</label>
          <input id="answerC" name="answerC" class="input" type="text" required>
        </div>
        <div class="form-row">
          <label for="answerD">Answer D *</label>
          <input id="answerD" name="answerD" class="input" type="text" required>
        </div>
      </div>

      
      <div class="form-row">
        <label for="correct">Correct Answer *</label>
        <select id="correct" name="correct" class="select" required>
          <option value="">— Select —</option>
          <option value="A">A</option>
          <option value="B">B</option>
          <option value="C">C</option>
          <option value="D">D</option>
        </select>
      </div>

      
      <div class="form-row">
        <label for="figure_file">Figure (optional, PNG/JPG/SVG ≤ 3MB)</label>
        <input id="figure_file" name="figure_file" class="file" type="file"
               accept=".png,.jpg,.jpeg,.svg">
      </div>

      <p class="helper">On submit, your recommendation is saved with status <b>pending</b> and routed to the selected educator.</p>

      <div class="actions">
        <button type="submit" class="btn">Submit Recommendation</button>
        <a href="learner_home.php" class="btn">Back to Learner Home</a>
      </div>
    </form>
  </div>
</main>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    
    $('#topic_id').change(function() {
        var topicId = $(this).val();
        var educatorSelect = $('#educator_id');
        
        
        educatorSelect.find('option:not(:first)').remove();
        
        if (topicId === '') {
            return; 
        }
        
        
        educatorSelect.prop('disabled', true);
        
        
        $.ajax({
            url: 'get_educators_by_topic.php',
            type: 'GET',
            data: { topic_id: topicId },
            dataType: 'json',
            success: function(educators) {
                educatorSelect.prop('disabled', false);
                
                if (educators.length > 0) {
                    
                    $.each(educators, function(index, educator) {
                        var fullName = educator.firstName + ' ' + educator.lastName;
                        educatorSelect.append(
                            $('<option>', {
                                value: educator.id,
                                text: fullName
                            })
                        );
                    });
                } else {
                    educatorSelect.append(
                        $('<option>', {
                            value: '',
                            text: '— No educators found for this topic —',
                            disabled: true
                        })
                    );
                }
            },
            error: function() {
                educatorSelect.prop('disabled', false);
                educatorSelect.append(
                    $('<option>', {
                        value: '',
                        text: '— Error loading educators —',
                        disabled: true
                    })
                );
            }
        });
    });
});
</script>
</body>
</html>
