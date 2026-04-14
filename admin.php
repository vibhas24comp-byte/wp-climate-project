<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

// Verify Admin Access
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    echo "<main class='container' style='margin-top: 5rem;'><div class='glass-card' style='padding: 2.5rem; text-align: center;'><h2 style='color: #991b1b;'>Access Denied</h2><p>You do not have permission to view this page.</p></div></main>";
    require_once 'includes/footer.php';
    exit();
}

$message = '';

// Handle actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        if ($_POST['action'] == 'delete_story') {
            $stmt = $conn->prepare("DELETE FROM stories WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $message = "Story deleted successfully.";
            } else {
                $message = "Error deleting story.";
            }
        } elseif ($_POST['action'] == 'ban_user') {
            // "Banning" user logic -> we can just delete the user or mark them as banned. 
            // In the absence of an 'is_banned' column, let's delete the user for now so they cannot login.
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND id != ?");
            $stmt->bind_param("ii", $id, $_SESSION['user_id']); // prevent self deletion
            if ($stmt->execute()) {
                $message = "User deleted successfully.";
            } else {
                $message = "Error deleting user.";
            }
        }
    }
}

// Fetch all users
$users = $conn->query("SELECT id, username, profile_icon, created_at, is_admin FROM users ORDER BY created_at DESC");

// Fetch all stories
$stories = $conn->query("SELECT id, title, location, category, created_at FROM stories ORDER BY created_at DESC");

?>

<main class="container" style="margin-top: 3rem; margin-bottom: 3rem;">
    <h1 style="font-size: 2.5rem; margin-bottom: 2rem; display: flex; align-items: center; gap: 10px;">
        <i data-lucide="shield-check" style="width: 36px; height: 36px; color: var(--green-primary);"></i> 
        Admin Dashboard
    </h1>

    <?php if ($message): ?>
        <div style="background: #dcfce7; color: #166534; padding: 1rem; border-radius: 0.5rem; border: 1px solid rgba(22, 163, 74, 0.2); margin-bottom: 2rem;">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr; gap: 2rem;">
        
        <!-- Manage Users Section -->
        <div class="glass-card" style="padding: 2rem;">
            <h2 style="font-size: 1.5rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--stone-light); padding-bottom: 0.5rem;">Manage Users</h2>
            <div style="overflow-x: auto;">
                <table style="width: 100%; text-align: left; border-collapse: collapse;">
                    <thead>
                        <tr style="background: rgba(255,255,255,0.5);">
                            <th style="padding: 0.75rem;">ID</th>
                            <th style="padding: 0.75rem;">Username</th>
                            <th style="padding: 0.75rem;">Role</th>
                            <th style="padding: 0.75rem;">Joined</th>
                            <th style="padding: 0.75rem;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($u = $users->fetch_assoc()): ?>
                        <tr style="border-bottom: 1px solid var(--stone-light);">
                            <td style="padding: 0.75rem;"><?php echo $u['id']; ?></td>
                            <td style="padding: 0.75rem; display: flex; align-items: center; gap: 8px;">
                                <i data-lucide="<?php echo htmlspecialchars($u['profile_icon']); ?>" style="width: 16px; height: 16px;"></i>
                                <?php echo htmlspecialchars($u['username']); ?>
                            </td>
                            <td style="padding: 0.75rem;">
                                <?php if ($u['is_admin']) echo '<span style="color: var(--green-primary); font-weight: bold;">Admin</span>'; else echo 'User'; ?>
                            </td>
                            <td style="padding: 0.75rem; color: var(--stone-muted);"><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                            <td style="padding: 0.75rem;">
                                <?php if (!$u['is_admin'] && $u['id'] != $_SESSION['user_id']): ?>
                                <form method="POST" action="admin.php" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                    <input type="hidden" name="action" value="ban_user">
                                    <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                    <button type="submit" style="background: transparent; border: none; color: #991b1b; cursor: pointer; display: flex; align-items: center; gap: 4px;">
                                        <i data-lucide="trash-2" style="width: 16px; height: 16px;"></i> Delete/Ban
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Manage Stories Section -->
        <div class="glass-card" style="padding: 2rem;">
            <h2 style="font-size: 1.5rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--stone-light); padding-bottom: 0.5rem;">Manage Stories</h2>
            <div style="overflow-x: auto;">
                <table style="width: 100%; text-align: left; border-collapse: collapse;">
                    <thead>
                        <tr style="background: rgba(255,255,255,0.5);">
                            <th style="padding: 0.75rem;">ID</th>
                            <th style="padding: 0.75rem;">Title</th>
                            <th style="padding: 0.75rem;">Location</th>
                            <th style="padding: 0.75rem;">Category</th>
                            <th style="padding: 0.75rem;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($s = $stories->fetch_assoc()): ?>
                        <tr style="border-bottom: 1px solid var(--stone-light);">
                            <td style="padding: 0.75rem;"><?php echo $s['id']; ?></td>
                            <td style="padding: 0.75rem; font-weight: 500;"><?php echo htmlspecialchars($s['title']); ?></td>
                            <td style="padding: 0.75rem;"><?php echo htmlspecialchars($s['location']); ?></td>
                            <td style="padding: 0.75rem;"><span class="category-badge" style="display: inline-block; padding: 2px 8px; font-size: 0.8rem;"><?php echo htmlspecialchars($s['category']); ?></span></td>
                            <td style="padding: 0.75rem;">
                                <form method="POST" action="admin.php" onsubmit="return confirm('Are you sure you want to delete this story?');">
                                    <input type="hidden" name="action" value="delete_story">
                                    <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                                    <button type="submit" style="background: transparent; border: none; color: #991b1b; cursor: pointer; display: flex; align-items: center; gap: 4px;">
                                        <i data-lucide="trash-2" style="width: 16px; height: 16px;"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</main>

<script>
    lucide.createIcons();
</script>

<?php require_once 'includes/footer.php'; ?>
