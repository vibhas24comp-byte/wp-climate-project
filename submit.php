<?php
// submit.php : Ingestion Interface Controller
require_once 'includes/header.php';
?>

<main class="container" style="max-width: 700px; padding-top: 4rem;">
    <div style="text-align: center; margin-bottom: 3rem;">
        <h1 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 0.5rem;">Submit a Climate Victory</h1>
        <p style="color: var(--stone-muted);">Document localized success to inspire global action. All submissions are appended to the public ledger.</p>
    </div>
    
    <form action="api/save_story.php" method="POST" enctype="multipart/form-data" class="glass-card" style="padding: 2.5rem;">
        
        <div class="form-group">
            <label for="title">Initiative Title</label>
            <input type="text" id="title" name="title" class="form-control" placeholder="e.g., Bogota Urban Reforestation" required>
        </div>

        <div class="form-group">
            <label for="location">Geographic Node (City, Country)</label>
            <input type="text" id="location" name="location" class="form-control" placeholder="e.g., Bogota, Colombia" required>
        </div>

        <div class="form-group">
            <label for="category">Strategic Category</label>
            <select id="category" name="category" class="form-control" required>
                <option value="Nature">Nature & Ecology</option>
                <option value="Urban">Urban Design</option>
                <option value="Energy">Renewable Energy</option>
                <option value="Tech">Technological Innovation</option>
                <option value="Policy">Legislation & Policy</option>
            </select>
        </div>

        <div class="form-group">
            <label for="excerpt">Executive Summary <span id="word-count" style="float: right; font-size: 0.85em; font-weight: normal; color: var(--stone-muted);">0 / 200 words</span></label>
            <textarea id="excerpt" name="excerpt" rows="5" class="form-control" placeholder="Briefly detail the mechanism of action and implementation strategy..." required></textarea>
        </div>

        <div class="form-group">
            <label for="stats">Quantifiable Impact</label>
            <input type="text" id="stats" name="stats" class="form-control" placeholder="e.g., Planted 250,000 native trees in 18 months" required>
        </div>

        <div class="form-group">
            <label for="image">Photographic Evidence</label>
            <input type="file" id="image" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp" style="background: rgba(255,255,255,0.3);" required>
            <small style="color: var(--stone-muted); display: block; margin-top: 0.5rem; font-size: 0.85rem;">
                <i data-lucide="shield-check" style="width: 12px; height: 12px; display: inline-block; vertical-align: middle;"></i>
                Maximum payload size: 2 Megabytes. Permitted binary types: JPG, PNG, WebP.
            </small>
        </div>

        <button type="submit" name="submit" class="btn-primary" style="width: 100%; margin-top: 1.5rem; padding: 1rem; font-size: 1.1rem;">
            Commit to Ledger
        </button>
    </form>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const excerpt = document.getElementById('excerpt');
    const wordCountDisplay = document.getElementById('word-count');
    const imageInput = document.getElementById('image');
    const form = document.querySelector('form');
    
    // Word limit of 200 words
    const maxWords = 200;

    excerpt.addEventListener('input', () => {
        let words = excerpt.value.trim().split(/\s+/);
        // If textarea is empty, words array is still length 1 ('')
        let count = excerpt.value.trim() === '' ? 0 : words.length;
        
        if (count > maxWords) {
            // Trim to maxWords
            excerpt.value = words.slice(0, maxWords).join(" ");
            count = maxWords;
            wordCountDisplay.style.color = "red";
        } else {
            wordCountDisplay.style.color = "var(--stone-muted)";
        }
        wordCountDisplay.textContent = `${count} / ${maxWords} words`;
    });

    form.addEventListener('submit', (e) => {
        // Image size limit
        if (imageInput.files.length > 0) {
            const fileSize = imageInput.files[0].size;
            const maxSize = 2 * 1024 * 1024; // 2MB
            if (fileSize > maxSize) {
                e.preventDefault();
                alert('The selected image is too large. Maximum size is 2MB.');
                return;
            }
        }
        
        let words = excerpt.value.trim().split(/\s+/);
        let count = excerpt.value.trim() === '' ? 0 : words.length;
        if (count > maxWords) {
            e.preventDefault();
            alert('Your executive summary exceeds the maximum word limit of ' + maxWords + ' words.');
            return;
        }
    });
});
</script>

<?php require_once 'includes/footer.php';?>