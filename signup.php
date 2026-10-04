<?php
require_once 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action']) && $_GET['action'] == 'get_topics') {
    header('Content-Type: application/json');
    try {
        $stmt = $pdo->query("SELECT id, topicName FROM Topic ORDER BY topicName");
        $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($topics);
    } catch(PDOException $e) {
        echo json_encode(['error' => 'Database error']);
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
   
    $firstName = trim($_POST['firstName']);
    $lastName = trim($_POST['lastName']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $userType = $_POST['userType'];
    $topics = isset($_POST['topics']) ? $_POST['topics'] : [];

    
    $stmt = $pdo->prepare("SELECT id FROM user WHERE emailAddress = ?");
    $stmt->execute([$email]);
    
if ($stmt->rowCount() > 0) {
    header('Location: login.html?error=email_exists&email=' . urlencode($email) . '&userType=' . $userType);
    exit();
}
    
    $photoFileName = 'default_avatar.svg';

    
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    
    $stmt = $pdo->prepare("INSERT INTO user (firstName, lastName, emailAddress, password, photoFileName, userType) 
                          VALUES (?, ?, ?, ?, 'default_avatar.svg', ?)");
    $stmt->execute([$firstName, $lastName, $email, $hashedPassword, $userType]);

    $userID = $pdo->lastInsertId();

    
    if (isset($_FILES['profileImage']) && $_FILES['profileImage']['error'] == 0) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = $_FILES['profileImage']['type'];
        
        if (in_array($fileType, $allowedTypes)) {
            
            $extension = strtolower(pathinfo($_FILES['profileImage']['name'], PATHINFO_EXTENSION));
            $uniqueFileName = "user_{$userID}_" . time() . "." . $extension;
            $uploadPath = "uploads/" . $uniqueFileName;
            
            
            if (!file_exists('uploads')) {
                mkdir('uploads', 0755, true);
            }
            
            
            if (move_uploaded_file($_FILES['profileImage']['tmp_name'], $uploadPath)) {
                
                $stmt = $pdo->prepare("UPDATE user SET photoFileName = ? WHERE id = ?");
                $stmt->execute([$uniqueFileName, $userID]);
                $photoFileName = $uniqueFileName;
            }
        }
    }

    
    if ($userType == 'educator' && !empty($topics)) {
        foreach ($topics as $topicID) {
            $stmt = $pdo->prepare("INSERT INTO quiz (educatorID, topicID) VALUES (?, ?)");
            $stmt->execute([$userID, $topicID]);
        }
    }

    
    $_SESSION['user_id'] = $userID;
    $_SESSION['user_type'] = $userType;
    $_SESSION['first_name'] = $firstName;
    $_SESSION['email'] = $email;

    
    if ($userType == 'learner') {
        header('Location: learner_home.php');
    } else {
        header('Location: educator.php');
    }
    exit();
} else {
    header('Location: signup.html');
    exit();
}
?>