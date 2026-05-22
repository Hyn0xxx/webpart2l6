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

// Получаем данные авторизации из разных источников
$username = null;
$password = null;

// Способ 1: Стандартная HTTP Auth
if (isset($_SERVER['PHP_AUTH_USER'])) {
    $username = $_SERVER['PHP_AUTH_USER'];
    $password = $_SERVER['PHP_AUTH_PW'];
}
// Способ 2: Альтернативный способ (для некоторых хостингов)
elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $auth = $_SERVER['HTTP_AUTHORIZATION'];
    if (preg_match('/Basic\s+(.*)$/i', $auth, $matches)) {
        $credentials = base64_decode($matches[1]);
        list($username, $password) = explode(':', $credentials, 2);
    }
}
// Способ 3: POST форма (если HTTP Auth не работает)
elseif (isset($_POST['login_submit'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
}

// Проверка авторизации
$auth_error = '';
$is_authenticated = false;

if (!isset($_SESSION['admin_id'])) {
    if ($username && $password) {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password_hash'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $is_authenticated = true;
            
            // Перенаправляем, чтобы убрать данные из URL
            header('Location: admin.php');
            exit;
        } else {
            $auth_error = 'Неверный логин или пароль';
        }
    }
    
    // Если не авторизован, показываем форму
    if (!isset($_SESSION['admin_id'])) {
        ?>
        <!DOCTYPE html>
        <html lang="ru">
        <head>
            <meta charset="UTF-8">
            <title>Вход в админ-панель</title>
            <style>
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    margin: 0;
                    min-height: 100vh;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                }
                .login-container {
                    background: white;
                    border-radius: 20px;
                    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                    width: 400px;
                    padding: 40px;
                    animation: slideIn 0.5s ease-out;
                }
                @keyframes slideIn {
                    from {
                        opacity: 0;
                        transform: translateY(-30px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
                h2 {
                    color: #333;
                    margin-bottom: 30px;
                    text-align: center;
                }
                .form-group {
                    margin-bottom: 20px;
                }
                label {
                    display: block;
                    margin-bottom: 8px;
                    color: #555;
                    font-weight: 500;
                }
                input {
                    width: 100%;
                    padding: 12px;
                    border: 2px solid #e0e0e0;
                    border-radius: 10px;
                    font-size: 1em;
                    transition: all 0.3s;
                    box-sizing: border-box;
                }
                input:focus {
                    outline: none;
                    border-color: #667eea;
                }
                button {
                    width: 100%;
                    padding: 12px;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    border: none;
                    border-radius: 10px;
                    font-size: 1em;
                    cursor: pointer;
                    transition: transform 0.3s;
                }
                button:hover {
                    transform: translateY(-2px);
                }
                .error {
                    background: #f8d7da;
                    color: #721c24;
                    padding: 10px;
                    border-radius: 8px;
                    margin-bottom: 20px;
                    text-align: center;
                }
                .info {
                    background: #d1ecf1;
                    color: #0c5460;
                    padding: 10px;
                    border-radius: 8px;
                    margin-bottom: 20px;
                    font-size: 0.9em;
                    text-align: center;
                }
            </style>
        </head>
        <body>
            <div class="login-container">
                <h2>🔐 Вход в админ-панель</h2>
                <?php if ($auth_error): ?>
                    <div class="error">❌ <?= htmlspecialchars($auth_error) ?></div>
                <?php endif; ?>
                <div class="info">
                    ℹ️ Используйте логин: <strong>admin</strong><br>
                    Пароль: <strong>admin123</strong>
                </div>
                <form method="POST">
                    <div class="form-group">
                        <label>Логин</label>
                        <input type="text" name="username" required autofocus>
                    </div>
                    <div class="form-group">
                        <label>Пароль</label>
                        <input type="password" name="password" required>
                    </div>
                    <button type="submit" name="login_submit">Войти</button>
                </form>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// Если мы здесь, значит пользователь авторизован
// Дальше идет основной код админ-панели
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель администратора</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #1a1a2e;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .admin-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 20px;
            border-radius: 8px;
            text-decoration: none;
        }
        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        .welcome {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            text-align: center;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="admin-header">
            <h1> Панель администратора</h1>
            <a href="?logout=1" class="logout-btn">🚪 Выйти</a>
        </div>
        
        <div class="welcome">
            <h2>Добро пожаловать, <?= htmlspecialchars($_SESSION['admin_username']) ?>!</h2>
            <p>Вы успешно авторизовались в панели администратора.</p>
            <p>Здесь будет отображаться список пользователей, статистика и другие функции.</p>
        </div>
        
        <!-- Здесь будет остальной функционал -->
    </div>
</body>
</html>

<?php
// Выход
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit();
}
?>
