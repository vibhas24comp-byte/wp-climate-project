<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch Watch Later
$watch_later = [];
$stmt = $conn->prepare("SELECT s.* FROM stories s JOIN watch_later w ON s.id = w.story_id WHERE w.user_id = ? ORDER BY w.created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
while($row = $res->fetch_assoc()) $watch_later[] = $row;
$stmt->close();

// Fetch History
$history = [];
$stmt = $conn->prepare("SELECT s.*, v.viewed_at FROM stories s JOIN views_history v ON s.id = v.story_id WHERE v.user_id = ? ORDER BY v.viewed_at DESC LIMIT 10");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
while($row = $res->fetch_assoc()) $history[] = $row;
$stmt->close();

function renderStories($stories) {
    if (count($stories) === 0) {
        echo "<p style='color: var(--stone-muted);'>No stories found.</p>";
        return;
    }
    echo '<div class="grid" style="grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1rem;">';
    foreach($stories as $row) {
        $icon = 'leaf';
        if($row['category'] === 'Urban') $icon = 'building';
        if($row['category'] === 'Energy') $icon = 'zap';
        if($row['category'] === 'Tech') $icon = 'sun';
        if($row['category'] === 'Policy') $icon = 'book-open';
        
        echo '
        <article class="glass-card" style="padding: 1rem;">
            <div style="font-weight: 600; margin-bottom: 0.5rem;"><i data-lucide="'.$icon.'" style="width: 14px; height: 14px;"></i> '.htmlspecialchars($row['title']).'</div>
            <p style="font-size: 0.9rem; color: var(--stone-muted);">'.htmlspecialchars(substr($row['excerpt'], 0, 80)).'...</p>
        </article>';
    }
    echo '</div>';
}
?>

<main class="container" style="margin-top: 3rem;">
    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 3rem;">
        <div style="background: var(--green-light); padding: 1.5rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--green-primary);">
            <i data-lucide="<?php echo htmlspecialchars($_SESSION['profile_icon']); ?>" style="width: 48px; height: 48px;"></i>
        </div>
        <div>
            <h1 style="font-size: 2.5rem; font-weight: 800; margin: 0;"><?php echo htmlspecialchars($_SESSION['username']); ?></h1>
            <p style="color: var(--stone-muted); margin: 0;">Member</p>
        </div>
    </div>

    <section style="margin-bottom: 4rem;">
        <h2 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;"><i data-lucide="bookmark"></i> Watch Later</h2>
        <?php renderStories($watch_later); ?>
    </section>

    <section style="margin-bottom: 4rem;">
        <h2 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;"><i data-lucide="clock"></i> Recently Viewed</h2>
        <?php renderStories($history); ?>
    </section>
</main>

<script>
    lucide.createIcons();
</script>
<?php require_once 'includes/footer.php'; ?>
