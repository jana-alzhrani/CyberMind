<?php
require_once 'config.php';
require_once 'auth_check.php';
// 7.a — Ensure learner is logged in

if ($_SESSION['user_type'] !== 'learner') {
    header("Location: login.php?error=not_learner");
    exit;
}
$learnerId = (int)$_SESSION['user_id'];


$learnerQuery = "SELECT firstName, lastName, emailAddress, photoFileName 
                 FROM user 
                 WHERE id = ?";
$stmt = $pdo->prepare($learnerQuery);
$stmt->execute([$learnerId]);
$learner = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$learner) {
    $learner = [
        'firstName' => 'Learner',
        'lastName'  => '',
        'emailAddress' => '',
        'photoFileName' => 'default_avatar.svg'
    ];
}


$topicQuery = "SELECT id, topicName FROM topic ORDER BY topicName";
$stmt = $pdo->query($topicQuery);
$topics = $stmt->fetchAll(PDO::FETCH_ASSOC);

$selectedTopic = ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['topic_id']) && $_POST['topic_id'] !== '')
    ? (int)$_POST['topic_id']
    : '';

if ($selectedTopic !== '') {
    $quizQuery = "
        SELECT
            q.id,
            t.topicName,
            CONCAT(u.firstName,' ',u.lastName) AS educatorName,
            u.photoFileName AS educatorPhoto,
            COUNT(qq.id) AS questionCount
        FROM quiz q
        INNER JOIN topic t ON t.id = q.topicID
        INNER JOIN user u ON u.id = q.educatorID
        LEFT JOIN quizquestion qq ON qq.quizID = q.id
        WHERE q.topicID = ?
        GROUP BY q.id, t.topicName, educatorName, educatorPhoto
        ORDER BY t.topicName, q.id";
    $stmt = $pdo->prepare($quizQuery);
    $stmt->execute([$selectedTopic]);
} else {
    $quizQuery = "
        SELECT
            q.id,
            t.topicName,
            CONCAT(u.firstName,' ',u.lastName) AS educatorName,
            u.photoFileName AS educatorPhoto,
            COUNT(qq.id) AS questionCount
        FROM quiz q
        INNER JOIN topic t ON t.id = q.topicID
        INNER JOIN user u ON u.id = q.educatorID
        LEFT JOIN quizquestion qq ON qq.quizID = q.id
        GROUP BY q.id, t.topicName, educatorName, educatorPhoto
        ORDER BY t.topicName, q.id";
    $stmt = $pdo->query($quizQuery);
}
$quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);


$recommendedQuery = "
    SELECT 
        rq.id, rq.question, rq.questionFigureFileName,
        rq.answerA, rq.answerB, rq.answerC, rq.answerD,
        rq.correctAnswer, rq.status, rq.comments,
        t.topicName,
        CONCAT(u.firstName,' ',u.lastName) AS educatorName,
        u.photoFileName AS educatorPhoto
    FROM recommendedquestion rq
    INNER JOIN quiz q ON q.id = rq.quizID
    INNER JOIN topic t ON t.id = q.topicID
    INNER JOIN user u ON u.id = q.educatorID
    WHERE rq.learnerID = ?
    ORDER BY rq.id DESC
";

$stmt = $pdo->prepare($recommendedQuery);
$stmt->execute([$learnerId]);
$recommended = $stmt->fetchAll(PDO::FETCH_ASSOC);


// helpers
function e($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
$photo = trim($learner['photofileName'] ?? '') !== '' ? $learner['photofileName'] : 'man.jpeg';
$welcomeName = trim(($learner['firstName'] ?? '').' '.($learner['lastName'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Learner Home</title>
  <link rel="stylesheet" href="style.css" />
  <!-- Font Awesome for icons (for footer icons / badges if needed) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php
if (isset($_GET['error']) && $_GET['error'] !== '') {
    echo '<div style="
        background-color:#ffdddd;
        color:#a00;
        border:1px solid #a00;
        padding:10px;
        margin:15px;
        border-radius:5px;
        font-family: Arial, sans-serif;
        ">
        ⚠ ' . htmlspecialchars($_GET['error']) . '
    </div>';
}
?>

  <!-- Header (matches target HTML) -->
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

  <!-- Main Content wrapper (matches target HTML) -->
  <main class="wrap">

    <!-- 7.b — Welcome + Learner info -->
    <h2 id="welcome">Welcome, <?= e($welcomeName) ?></h2>

    <section class="card">
      <h3>Learner Information</h3>
      <div class="info">
        <img id="uPhoto" class="avatar" 
     src="uploads/<?php echo htmlspecialchars($learner['photoFileName'] ?? 'default_avatar.svg'); ?>" 
     alt="Learner Photo">

        <ul id="uInfo" style="list-style:none; padding:0; margin:0;">
          <li><b>Name:</b> <?= e($welcomeName) ?></li>
          <li><b>Email:</b> <?= e($learner['emailAddress']) ?></li>
        </ul>
      </div>
    </section>

    <!-- 7.c / 7.d / 7.e — All Available Quizzes with filter -->
    <section class="card">
      <div class="row between center">
        <h3>All Available Quizzes</h3>

        <!-- 7.c — Filter form (POST to same page) -->
        <div class="filters">
  <select name="topic_id" id="topicFilter">
    <option value="">All topics</option>
    <?php foreach ($topics as $t): ?>
      <option value="<?= (int)$t['id'] ?>" <?= ($selectedTopic===(int)$t['id'])?'selected':'' ?>>
        <?= e($t['topicName']) ?>
      </option>
    <?php endforeach; ?>
  </select>
</div>
      </div>

      <!-- 7.d & 7.e — Quizzes table (4 columns) -->
      <table class="table" id="quizTbl">
  <colgroup>
    <col style="width:34%">   <!-- Topic -->
    <col style="width:34%">   <!-- Educator -->
    <col style="width:16%">   <!-- # Questions -->
    <col style="width:16%">   <!-- Actions -->
  </colgroup>
  <thead>
    <tr>
      <th>Topic</th>
      <th>Educator</th>
      <th class="col-num">Number of Questions</th>
      <th class="col-actions">Actions</th>
    </tr>
  </thead>
  <tbody id="quizTableBody">
  <?php foreach ($quizzes as $q): ?>
    <tr>
      <td><?= e($q['topicName']) ?></td>
      <td>
        <div class="eduCell">
          <img src="uploads/<?php echo htmlspecialchars($q['educatorPhoto'] ?? 'default_avatar.svg'); ?>" 
               alt="Educator Photo" 
               style="width:30px;height:30px;border-radius:50%;">
          <span><?= e($q['educatorName']) ?></span>
        </div>
      </td>
      <td class="col-num"><?= (int)$q['questionCount'] ?></td>
      <td class="col-actions">
        <?php if ((int)$q['questionCount'] > 0): ?>
          <a class="btn btn-table" href="take_quiz.php?quiz_id=<?= (int)$q['id'] ?>">Take Quiz</a>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
</tbody>
</table>

    </section>

    <!-- 7.f — Recommended Questions -->
    <section class="card">
      <div class="row between center">
        <h3>Recommended Questions</h3>
        <a class="ghost btn" href="recommend_question.php">Recommend a Question</a>

      </div>

      <table class="table" id="recTbl">
        <thead>
          <tr>
            <th>Topic</th>
            <th>Educator</th>
            <th>Question</th>
            <th>Status</th>
            <th>Comments</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($recommended)): ?>
            <tr><td colspan="5">No recommended questions found.</td></tr>
          <?php else: ?>
            <?php foreach ($recommended as $r): ?>
              <tr>
                <td><?= e($r['topicName']) ?></td>
                <td>
  <div class="eduCell">
    <img src="uploads/<?= e(($r['educatorPhoto'] ?? '') ?: 'default_avatar.svg') ?>" alt="Educator photo">

    <span><?= e($r['educatorName']) ?></span>
  </div>
</td>

                <td>
                  <div class="qblock">
                    <?php if (!empty($r['questionFigureFileName'])): ?>
                      <div class="figure">
                        <img src="uploads/<?= e($r['questionFigureFileName']) ?>" alt="question figure">

                      </div>
                    <?php endif; ?>
                    <div class="text"><?= nl2br(e($r['question'])) ?></div>
                    <div class="opts">
                      <div<?= ($r['correctAnswer'] === 'A' ? ' class="correct"' : '') ?>>A) <?= e($r['answerA']) ?></div>
                      <div<?= ($r['correctAnswer'] === 'B' ? ' class="correct"' : '') ?>>B) <?= e($r['answerB']) ?></div>
                      <div<?= ($r['correctAnswer'] === 'C' ? ' class="correct"' : '') ?>>C) <?= e($r['answerC']) ?></div>
                      <div<?= ($r['correctAnswer'] === 'D' ? ' class="correct"' : '') ?>>D) <?= e($r['answerD']) ?></div>
                    </div>
                  </div>
                </td>
               <td>
  <?php
    $status = trim(strtolower($r['status'] ?? ''));
    $badgeClass = match ($status) {
      'approved'    => 'ok',       // green
      'pending'     => 'pending',  // orange
      'disapproved' => 'no',       // red
      default       => '',
    };
  ?>
  <span class="badge <?= e($badgeClass) ?>"><?= e(ucfirst($status)) ?></span>
</td>

                <td><?= e($r['comments'] ?? '-') ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </section>

  </main>

  <!-- Footer (matches your HTML footer) -->
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
        <p><i class="fa-solid fa-phone"></i> 123-4567</p>
        <p><i class="fa-solid fa-envelope"></i> Email</p>
        <p><i class="fa-solid fa-location-dot"></i> Riyadh, Saudi Arabia</p>
      </div>

      <p class="copyright">&copy; 2025 BrightPath. All Rights Reserved.</p>
    </div>
  </footer>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $('#topicFilter').change(function() {
        var topicId = $(this).val();
        
        $.ajax({
            url: 'ajax_quizzes_by_topic.php',
            type: 'POST',
            data: { topic_id: topicId },
            dataType: 'json',
            success: function(response) {
    if (response.error) {
        alert('Error: ' + response.error);
        return;
    }
    updateQuizTable(response);
},
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                alert('Error loading quizzes. Please try again.');
            }
        });
    });
    
    function updateQuizTable(quizzes) {
        var tbody = $('#quizTableBody');
        tbody.empty();
        
        if (quizzes.length === 0) {
            tbody.append('<tr><td colspan="4">No quizzes found for this topic.</td></tr>');
            return;
        }
        
        $.each(quizzes, function(index, q) {
            var takeQuizBtn = '';
            if (parseInt(q.questionCount) > 0) {
                takeQuizBtn = '<a class="btn btn-table" href="take_quiz.php?quiz_id=' + q.id + '">Take Quiz</a>';
            }
            
            var row = '<tr>' +
                '<td>' + escapeHtml(q.topicName) + '</td>' +
                '<td>' +
                '<div class="eduCell">' +
                '<img src="uploads/' + escapeHtml(q.educatorPhoto || 'default_avatar.svg') + '" alt="Educator Photo" style="width:30px;height:30px;border-radius:50%;">' +
                '<span>' + escapeHtml(q.educatorName) + '</span>' +
                '</div>' +
                '</td>' +
                '<td class="col-num">' + q.questionCount + '</td>' +
                '<td class="col-actions">' + takeQuizBtn + '</td>' +
                '</tr>';
            
            tbody.append(row);
        });
    }
    
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
});
</script>
</body>

</html>

