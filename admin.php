<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

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
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch(PDOException $e) {
    die('Ошибка БД: ' . $e->getMessage());
}

// Проверка существования таблицы admins
$tableExists = $pdo->query("SHOW TABLES LIKE 'admins'")->rowCount() > 0;
if (!$tableExists) {
    die('Таблица admins не существует. Создайте её сначала.');
}

// HTTP авторизация
$auth_realm = 'Admin Panel';

if (!isset($_SESSION['admin_id'])) {
    if (!isset($_SERVER['PHP_AUTH_USER'])) {
        header('WWW-Authenticate: Basic realm="' . $auth_realm . '"');
        header('HTTP/1.0 401 Unauthorized');
        echo 'Требуется авторизация';
        exit;
    } else {
        $username = $_SERVER['PHP_AUTH_USER'];
        $password = $_SERVER['PHP_AUTH_PW'];
        
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password_hash'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
        } else {
            header('WWW-Authenticate: Basic realm="' . $auth_realm . '"');
            header('HTTP/1.0 401 Unauthorized');
            echo 'Неверный логин или пароль';
            exit;
        }
    }
}

// Простой вывод для проверки
echo "<h1>Админ-панель работает!</h1>";
echo "<p>Вы авторизованы как: " . htmlspecialchars($_SESSION['admin_username']) . "</p>";
echo "<p><a href='?logout=1'>Выйти</a></p>";

// Выход
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit();
}
?>
