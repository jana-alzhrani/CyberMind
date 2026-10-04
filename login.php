<?php
require_once 'config.php';
session_start(); 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    
    $stmt = $pdo->prepare("SELECT * FROM user WHERE emailAddress = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_type'] = $user['userType'];
        $_SESSION['first_name'] = $user['firstName'];
        $_SESSION['email'] = $user['emailAddress'];

        if ($user['userType'] == 'learner') {
            header('Location: learner_home.php');
        } else {
            header('Location: educator.php');
        }
        exit();
    } else {
        
        header('Location: login.html?error=invalid_credentials&email=' . urlencode($email));
        exit();
    }
} else {
    header('Location: login.html');
    exit();
}
?>