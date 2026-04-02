<?php
// login.php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    
    // Direct SQL query
    $stmt = $pdo->prepare("
        SELECT u.*, l.name as location_name 
        FROM users u
        LEFT JOIN locations l ON u.location_id = l.id
        WHERE u.username = ? AND u.password = ? AND u.role = ?
    ");
    $stmt->execute([$username, $password, $role]);
    $user = $stmt->fetch();
    
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['location'] = $user['location_name'] ?? 'all';
        
        header('Location: index.php');
        exit;
    } else {
        $error = "Invalid username or password";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <style>
        body { background: #0f172a; font-family: Arial; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .login-box { background: #1e293b; padding: 2rem; border-radius: 1rem; width: 350px; }
        h2 { color: white; text-align: center; }
        input, select { width: 100%; padding: 0.8rem; margin: 0.5rem 0; border-radius: 2rem; border: none; background: #0f172a; color: white; }
        button { width: 100%; padding: 0.8rem; background: #2563eb; color: white; border: none; border-radius: 2rem; cursor: pointer; }
        .error { color: #ef4444; text-align: center; margin-top: 1rem; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Production Login</h2>
        <form method="POST">
           <input type="text" id="username" placeholder="Username" required >
                <input type="password" id="password" placeholder="Password" required>
            <select name="role" required>
                <option value="operator">Operator</option>
                <option value="manager">Manager</option>
            </select>
            <button type="submit">Login</button>
            <?php if (isset($error)) echo "<div class='error'>$error</div>"; ?>
        </form>
        <div style="margin-top:1rem; color:#94a3b8; text-align:center; font-size:0.8rem;">
            <div>1111/1111 (Modan) | 2222/2222 (Baldeya) | 3333/3333 (Al-Khraj) | Admin/12345</div>
        </div>
    </div>
</body>
</html>