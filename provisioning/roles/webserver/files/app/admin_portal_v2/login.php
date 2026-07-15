<?php
session_start();
require_once('config.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = $_POST['user'] ?? '';
    $p = $_POST['pass'] ?? '';

    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        $error = 'Database connection failed.';
    } else {
        // VULNERABLE ON PURPOSE: raw string concatenation, no prepared statement.
        $query = "SELECT * FROM users WHERE user='$u' AND pass='$p'";
        $result = $conn->query($query);

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $_SESSION['user'] = $row['user'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid credentials';
        }
        $conn->close();
    }
}
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>TaskManager Admin Portal</title></head>
<body>
  <h1>TaskManager &mdash; Admin Portal</h1>
  <?php if ($error): ?><p style="color:red;"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
  <form method="post" action="login.php">
    <label>Username: <input type="text" name="user"></label><br>
    <label>Password: <input type="password" name="pass"></label><br>
    <button type="submit">Log in</button>
  </form>
</body>
</html>
