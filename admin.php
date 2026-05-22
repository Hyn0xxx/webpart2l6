<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

header('Content-Type: text/html; charset=UTF-8');

// Подключение к БД
$db_user = 'u82464';
$db_pass = '8104996';
$db_name = 'u82464';
$db_host = 'localhost';

try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch(PDOException $e) {
    die('Ошибка подключения к базе данных: ' . $e->getMessage());
}

// HTTP АВТОРИЗАЦИЯ
$auth_realm = 'Admin Panel';

// Проверка, авторизован ли уже пользователь
if (!isset($_SESSION['admin_id'])) {
    // Проверяем HTTP авторизацию
    if (!isset($_SERVER['PHP_AUTH_USER'])) {
        // Запрашиваем авторизацию
        header('WWW-Authenticate: Basic realm="' . $auth_realm . '"');
        header('HTTP/1.0 401 Unauthorized');
        echo 'Требуется авторизация для доступа к панели администратора';
        exit;
    } else {
        // Проверяем логин и пароль
        $username = $_SERVER['PHP_AUTH_USER'];
        $password = $_SERVER['PHP_AUTH_PW'];
        
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password_hash'])) {
            // Авторизация успешна
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            
            // Перенаправляем, чтобы убрать данные авторизации из URL
            header('Location: admin.php');
            exit;
        } else {
            // Неверный логин или пароль
            header('WWW-Authenticate: Basic realm="' . $auth_realm . '"');
            header('HTTP/1.0 401 Unauthorized');
            echo 'Неверный логин или пароль';
            exit;
        }
    }
}

// Выход из админ-панели
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit();
}

// Дальше идет остальной код админ-панели
// ... (весь остальной код из предыдущего admin.php)

// Для теста выведем сообщение об успешной авторизации
echo "<h1>Добро пожаловать в админ-панель, " . htmlspecialchars($_SESSION['admin_username']) . "!</h1>";
echo "<p><a href='?logout=1'>Выйти</a></p>";

// Здесь будет остальной функционал админ-панели
?>
