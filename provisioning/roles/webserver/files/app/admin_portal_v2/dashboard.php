<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
$page = isset($_GET['page']) ? $_GET['page'] : 'home.php';
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>TaskManager Dashboard</title></head>
<body>
  <h1>Welcome, <?php echo htmlspecialchars($_SESSION['user']); ?></h1>
  <nav>
    <a href="dashboard.php?page=home.php">Home</a> |
    <a href="dashboard.php?page=tasks.php">Tasks</a> |
    <a href="dashboard.php?page=profile.php">Profile</a> |
    <a href="logout.php">Logout</a>
  </nav>
  <hr>
  <div id="content">
<?php
// VULNERABLE ON PURPOSE: the app's own navigation always passes a
// filename ending in .php (home.php, tasks.php, profile.php), so the
// developer never bothered to validate the "page" parameter beyond a
// naive attempt to strip directory traversal sequences.
$safe_page = str_replace(['../', '..\\'], '', $page);
include($safe_page);
?>
  </div>
</body>
</html>
