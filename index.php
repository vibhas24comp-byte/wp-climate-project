<?php
// index.php : Primary Application Controller

// 1. Establish dependencies
require_once 'includes/db.php';
require_once 'includes/header.php';

// 2. Formulate and execute the data retrieval query
// The results are ordered chronologically descending to surface the newest stories first.
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if (!empty($search)) {
    $stmt = $conn->prepare("SELECT * FROM stories WHERE title LIKE ? OR category LIKE ? ORDER BY created_at DESC");
    $param = "%{$search}%";
    $stmt->bind_param("ss", $param, $param);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $query = "SELECT * FROM stories ORDER BY created_at DESC";
    $result = $conn->query($query);
}

// Collect stories for map and modal
$stories_data = [];
$result_rows = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $result_rows[] = $row;
        $stories_data[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'location' => $row['location'],
            'category' => $row['category'],
            'excerpt' => $row['excerpt'],
            'stats' => $row['stats'],
            'image_path' => $row['image_path']
        ];
    }
}
?>

<main class="container">
    <section style="text-align: center; margin-bottom: 4rem; margin-top: 2rem;">
        <span
            style="background: #dcfce7; color: #166534; padding: 0.35rem 1.25rem; border-radius: 999px; font-size: 0.85rem; font-weight: 700; display: inline-flex; align-items: center; gap: 8px; border: 1px solid rgba(22, 163, 74, 0.2);">
            <span
                style="display:inline-block; width:8px; height:8px; background:#22c55e; border-radius:50%; box-shadow: 0 0 8px #22c55e; animation: pulse 2s infinite;"></span>
            Verified Climate Solutions Network
        </span>
        <h1
            style="font-size: clamp(3rem, 5vw, 4.5rem); line-height: 1.1; margin-top: 1.5rem; margin-bottom: 1.5rem; font-weight: 800; letter-spacing: -0.02em;">
            Real solutions for a <br />
            <span
                style="background: -webkit-linear-gradient(0deg, #16a34a, #14b8a6); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">sustainable
                future.</span>
        </h1>
        <p style="color: var(--stone-muted); font-size: 1.2rem; max-width: 600px; margin: 0 auto;">
            Discover how urban centers, sovereign nations, and grassroots communities are systematically reversing
            climate change.
        </p>
    </section>

    <div id="aqi-widget">
        <i data-lucide="loader" class="spin" style="margin-bottom: 10px;"></i>
        <p>Initializing environmental telemetry...</p>
    </div>

    <!-- Map Container -->
    <div id="map"
        style="height: 400px; width: 100%; border-radius: 1.5rem; border: 1px solid rgba(255, 255, 255, 0.6); box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07); margin-bottom: 3rem; background: rgba(255, 255, 255, 0.45); z-index: 10;">
    </div>

    <!-- Search Bar -->
    <div style="margin-bottom: 2rem; display: flex; justify-content: center;">
        <form method="GET" action="index.php" style="display: flex; gap: 10px; width: 100%; max-width: 600px;">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search stories by title or tags/category..." class="form-control" style="flex: 1; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid #d6d3d1;">
            <button type="submit" class="btn-primary" style="padding: 0.75rem 1.5rem; display: flex; align-items: center; justify-content: center;"><i data-lucide="search" style="width: 18px; height: 18px; margin-right: 5px;"></i> Search</button>
            <?php if(!empty($search)) { echo '<a href="index.php" class="btn-primary" style="background: transparent; color: #1c1917; border: 1px solid #d6d3d1; padding: 0.75rem 1.5rem; text-decoration: none; display: flex; align-items: center; justify-content: center;">Clear</a>'; } ?>
        </form>
    </div>

    <div class="grid">
        <?php
        // 3. Iterative rendering of the MySQL Result Set
        if (count($result_rows) > 0) {
            foreach ($result_rows as $row) {

                // Conditional logic to assign contextual iconography based on the database category string
                $icon = 'leaf'; // Fallback default
                if ($row['category'] === 'Urban')
                    $icon = 'building';
                if ($row['category'] === 'Energy')
                    $icon = 'zap';
                if ($row['category'] === 'Tech')
                    $icon = 'sun';
                if ($row['category'] === 'Policy')
                    $icon = 'book-open';

                // Heredoc or concatenated string generation for the HTML card
                echo '
                <article class="glass-card" onclick="openStoryModal(' . htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') . ')" style="cursor: pointer;">
                    <div class="card-image-wrapper">
                        <div class="category-badge">
                            <i data-lucide="' . $icon . '" style="width: 14px; height: 14px; color: var(--green-primary);"></i>
                            ' . htmlspecialchars($row['category']) . '
                        </div>
                        <img src="' . htmlspecialchars($row['image_path']) . '" alt="Thumbnail for ' . htmlspecialchars($row['title']) . '" class="card-image">
                    </div>
                    <div class="card-content">
                        <div class="card-location">
                            <i data-lucide="map-pin" style="width: 14px; height: 14px;"></i>
                            ' . htmlspecialchars($row['location']) . '
                        </div>
                        <h3 class="card-title">' . htmlspecialchars($row['title']) . '</h3>
                        <p class="card-excerpt">' . htmlspecialchars($row['excerpt']) . '</p>
                        <div class="card-footer">
                            <div>
                                <div class="impact-label">Measured Impact</div>
                                <div class="impact-stat">' . htmlspecialchars($row['stats']) . '</div>
                            </div>
                            <div class="icon-action">
                                <i data-lucide="arrow-right" style="width: 18px; height: 18px;"></i>
                            </div>
                        </div>
                    </div>
                </article>';
            }
        } else {
            // Empty state architectural handling
            echo '
            <div style="grid-column: 1 / -1; text-align: center; padding: 4rem; background: rgba(255,255,255,0.5); border-radius: 1rem; border: 1px dashed rgba(0,0,0,0.1);">
                <i data-lucide="inbox" style="width: 48px; height: 48px; color: #a8a29e; margin-bottom: 1rem;"></i>
                <h3 style="font-size: 1.5rem; margin-bottom: 0.5rem;">No Data Points Available</h3>
                <p style="color: var(--stone-muted);">The persistence layer contains no records. Be the first to submit a victory.</p>
            </div>';
        }
        ?>
    </div>
</main>

<!-- Story Expansion Modal -->
<div id="story-modal-overlay" class="modal-overlay">
    <div class="modal-content glass-card">
        <button id="close-modal-btn" class="close-btn"><i data-lucide="x"></i></button>
        <div id="modal-image-wrapper" class="card-image-wrapper" style="height: 300px;">
            <img id="modal-image" src="" alt="" class="card-image">
            <div id="modal-category" class="category-badge"></div>
        </div>
        <div class="card-content" style="padding: 2.5rem; flex-grow: 0;">
            <div id="modal-location" class="card-location" style="font-size: 1rem; margin-bottom: 1rem;"></div>
            <h2 id="modal-title" style="font-size: 2rem; font-weight: 800; margin-bottom: 1.5rem;"></h2>
            <p id="modal-excerpt"
                style="font-size: 1.15rem; line-height: 1.8; color: var(--stone-text); margin-bottom: 2rem;"></p>
            <div style="background: var(--green-light); padding: 1.5rem; border-radius: 1rem; margin-bottom: 1.5rem;">
                <div class="impact-label" style="color: var(--green-primary);">Measured Impact</div>
                <div id="modal-stats" class="impact-stat" style="font-size: 1.25rem;"></div>
            </div>

            <!-- Interactivity Actions -->
            <div
                style="display: flex; gap: 1rem; margin-bottom: 2rem; border-bottom: 1px solid var(--stone-light); padding-bottom: 1rem;">
                <button onclick="handleLike()" id="like-btn" class="btn-primary"
                    style="background: transparent; border: 1px solid #d6d3d1; color: #1c1917; gap: 5px;">
                    <i data-lucide="thumbs-up" style="width:18px;height:18px;"></i> <span id="like-text">Like</span>
                </button>
                <button onclick="handleWatchLater()" id="watch-btn" class="btn-primary"
                    style="background: transparent; border: 1px solid #d6d3d1; color: #1c1917; gap: 5px;">
                    <i data-lucide="bookmark-plus" style="width:18px;height:18px;"></i> <span id="watch-text">Watch
                        Later</span>
                </button>
            </div>

            <!-- Comments Section -->
            <div>
                <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 1rem;">Comments</h3>
                <div style="display: flex; gap: 10px; margin-bottom: 1.5rem;">
                    <input type="text" id="comment-input" class="form-control" placeholder="Add a comment..."
                        style="flex: 1; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid #d6d3d1;">
                    <button onclick="submitComment()" class="btn-primary" style="padding: 0.75rem 1.5rem;">Post</button>
                </div>
                <div id="comments-container"
                    style="display: flex; flex-direction: column; gap: 1rem; max-height: 200px; overflow-y: auto;">
                    <!-- Comments injected by JS -->
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    // Expose stories collection to client-side application state
    window.climateStories = <?php echo json_encode($stories_data); ?>;
</script>

<?php require_once 'includes/footer.php'; ?>