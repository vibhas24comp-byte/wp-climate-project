<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);
$public_pages = ['login.php', 'register.php'];

if (!isset($_SESSION['user_id']) && !in_array($current_page, $public_pages)) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClimateWins - Global Success Stories</title>
    
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <nav class="glass-nav">
        <div class="logo-container">
            <i data-lucide="globe" class="highlight"></i>
            <span>Climate<span class="highlight">Wins</span></span>
        </div>
        <div class="nav-links">
            <a href="index.php">View Map</a>
            <a href="index.php">Stories</a>
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="submit.php" class="btn-primary">Submit a Win</a>
                <?php if(isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                    <a href="admin.php" style="display:flex; align-items:center; gap:5px;"><i data-lucide="shield" style="width:18px;height:18px;"></i> Admin Dashboard</a>
                <?php endif; ?>
                <a href="profile.php" style="display:flex; align-items:center; gap:5px;"><i data-lucide="<?php echo htmlspecialchars($_SESSION['profile_icon'] ?? 'user'); ?>" style="width:18px;height:18px;"></i> Profile</a>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php" class="btn-primary">Login</a>
            <?php endif; ?>
        </div>
    </nav>