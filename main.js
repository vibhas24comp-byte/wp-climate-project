document.addEventListener('DOMContentLoaded', () => {
    // 1. Initialize Lucide Vector Icons
    // The engine scans the initial DOM tree for data-lucide attributes and replaces them with inline SVGs.
    lucide.createIcons();

    lucide.createIcons();

    // 2. Execute Asynchronous Telemetry Fetch
    fetchAirQualityData();

    // 3. Initialize Map and Modals
    initMap();
    initModal();
});

async function fetchAirQualityData() {
    const widgetContainer = document.getElementById('aqi-widget');
    if (!widgetContainer) return;

    // Targeting OpenAQ API v3. 
    // Location ID 8118 represents a reference-grade monitor in New Delhi, India.
    // Location ID 2178 represents a monitor in Albuquerque, USA.
    const locationId = 8118; 
    
    // Utilize our backend PHP proxy to bypass CORS/Preflight blocks
    const apiUrl = `api/aqi_proxy.php`;
    
    try {
        // Await the network resolution without blocking the UI rendering thread
        const response = await fetch(apiUrl);
        if (!response.ok) throw new Error('Network response or OpenAQ gateway rejected the request.');
        
        // Parse the JSON payload
        const data = await response.json();
        
        // The v3 API returns an array of objects inside a "results" key
        if (data.results && data.results.length > 0) {
            
            // Extract the first valid pollutant reading (often PM2.5 or PM10)
            const latestReading = data.results.find(sensor => sensor.value!== null);
            
            if (latestReading) {
                // Format the float precision for aesthetic consistency
                const numericValue = latestReading.value.toFixed(2);
                // Extract timestamp, defaulting to UTC
                const timeData = new Date(latestReading.datetime.utc).toLocaleTimeString();
                
                // Perform dynamic DOM injection to display the telemetry
                widgetContainer.innerHTML = `
                    <div style="display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 5px;">
                        <i data-lucide="wind" style="color: #16a34a; width: 24px; height: 24px;"></i>
                        <h3 style="margin:0; color: #1c1917; font-weight: 800;">Global Air Quality Pulse</h3>
                    </div>
                    <p style="margin-top: 5px; color: #57534e; font-size: 1.1rem;">
                        Latest monitored concentration (Location ID ${locationId}): 
                        <strong style="color: #16a34a; font-size: 1.4rem;">${numericValue} µg/m³</strong>
                    </p>
                    <small style="color: #a8a29e; display: block; margin-top: 8px;">
                        Data synchronized at ${timeData} UTC • Powered by OpenAQ API v3
                    </small>
                `;
                
                // Crucial step: Re-initialize the icon engine to parse the newly injected DOM string
                lucide.createIcons();
            }
        }
    } catch (error) {
        console.error('OpenAQ Telemetry Error:', error);
        widgetContainer.innerHTML = `
            <div style="color: #78716c;">
                <i data-lucide="cloud-off" style="margin-bottom: 10px; opacity: 0.5;"></i>
                <p>Environmental telemetry stream temporarily disconnected.</p>
            </div>
        `;
        lucide.createIcons();
    }
}

let map;

function initMap() {
    const mapContainer = document.getElementById('map');
    if (!mapContainer || !window.climateStories) return;

    // Initialize Leaflet map
    map = L.map('map').setView([20, 0], 2); // Center of the world

    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
        subdomains: 'abcd',
        maxZoom: 19
    }).addTo(map);

    // Create a custom icon
    const customIcon = L.icon({
        iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
        iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
        shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });

    // We will geocode the locations using Nominatim API (with a slight delay to avoid rate limiting)
    window.climateStories.forEach((story, index) => {
        setTimeout(async () => {
            try {
                // Ensure we don't query empty locations
                if(!story.location) return;
                
                const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(story.location)}`);
                const data = await response.json();
                
                if (data && data.length > 0) {
                    const lat = parseFloat(data[0].lat);
                    const lon = parseFloat(data[0].lon);
                    
                    const marker = L.marker([lat, lon], {icon: customIcon}).addTo(map);
                    marker.bindPopup(`
                        <div style="text-align: center; font-family: 'Segoe UI', system-ui, sans-serif;">
                            <img src="${story.image_path}" style="width: 100%; height: 100px; object-fit: cover; border-radius: 8px; margin-bottom: 8px;">
                            <h4 style="margin: 0; font-size: 14px; font-weight: 800;">${story.title}</h4>
                            <p style="margin: 5px 0 10px 0; font-size: 12px; color: #78716c;">${story.category}</p>
                            <button onclick='openStoryModalById(${story.id})' class="btn-primary" style="padding: 5px 10px; font-size: 12px;">Read Story</button>
                        </div>
                    `);
                }
            } catch (err) {
                console.error("Geocoding failed for: ", story.location, err);
            }
        }, index * 1200); // 1.2s delay between requests to comply with Nominatim Acceptable Use Policy
    });
}

function initModal() {
    const modalOverlay = document.getElementById('story-modal-overlay');
    const closeBtn = document.getElementById('close-modal-btn');

    if(!modalOverlay) return;

    closeBtn.addEventListener('click', () => {
        closeStoryModal();
    });

    modalOverlay.addEventListener('click', (e) => {
        if(e.target === modalOverlay) {
            closeStoryModal();
        }
    });
}

function openStoryModalById(id) {
    const obj = window.climateStories.find(s => s.id == id);
    if(obj) openStoryModal(obj);
}

window.currentStoryId = null;

window.openStoryModal = function(storyObj) {
    const modalOverlay = document.getElementById('story-modal-overlay');
    window.currentStoryId = storyObj.id;
    
    document.getElementById('modal-title').textContent = storyObj.title;
    document.getElementById('modal-location').innerHTML = `<i data-lucide="map-pin" style="width: 14px; height: 14px; display:inline-block; vertical-align:middle; margin-right:5px;"></i>${storyObj.location}`;
    document.getElementById('modal-excerpt').textContent = storyObj.excerpt;
    document.getElementById('modal-stats').textContent = storyObj.stats;
    document.getElementById('modal-image').src = storyObj.image_path;
    document.getElementById('modal-category').innerHTML = `<i data-lucide="leaf" style="width: 14px; height: 14px; color: var(--green-primary); display:inline-block; vertical-align:middle; margin-right:5px;"></i>${storyObj.category}`;

    document.body.style.overflow = 'hidden'; // Prevent background scrolling
    modalOverlay.classList.add('active');
    lucide.createIcons(); // Refresh icons for modal content

    // Initialize Interactive Elements
    initializeInteractions(storyObj.id);
}

window.closeStoryModal = function() {
    const modalOverlay = document.getElementById('story-modal-overlay');
    modalOverlay.classList.remove('active');
    document.body.style.overflow = '';
    window.currentStoryId = null;
}

// Interaction API Calls
window.initializeInteractions = function(storyId) {
    // Record View
    postAction('view', storyId);

    // Check Like Status
    postAction('check_like', storyId).then(res => {
        const likeBtn = document.getElementById('like-btn');
        const likeText = document.getElementById('like-text');
        if(res && res.liked) {
            likeBtn.style.color = '#16a34a';
            likeBtn.style.borderColor = '#16a34a';
            likeText.innerText = 'Liked';
            likeBtn.disabled = true; // Like once
        } else {
            likeBtn.style.color = '#1c1917';
            likeBtn.style.borderColor = '#d6d3d1';
            likeText.innerText = 'Like';
            likeBtn.disabled = false;
        }
    });

    // Check Watch Later Status
    postAction('check_watch_later', storyId).then(res => {
        const watchBtn = document.getElementById('watch-btn');
        const watchText = document.getElementById('watch-text');
        if(res && res.watch_later) {
            watchBtn.style.color = '#16a34a';
            watchBtn.style.borderColor = '#16a34a';
            watchText.innerText = 'Saved';
            watchBtn.disabled = true;
        } else {
            watchBtn.style.color = '#1c1917';
            watchBtn.style.borderColor = '#d6d3d1';
            watchText.innerText = 'Watch Later';
            watchBtn.disabled = false;
        }
    });

    // Load Comments
    loadComments(storyId);
}

window.handleLike = function() {
    if(!window.currentStoryId) return;
    postAction('like', window.currentStoryId).then(res => {
        if(res && res.success) {
            const likeBtn = document.getElementById('like-btn');
            const likeText = document.getElementById('like-text');
            likeBtn.style.color = '#16a34a';
            likeBtn.style.borderColor = '#16a34a';
            likeText.innerText = 'Liked';
            likeBtn.disabled = true;
        }
    });
}

window.handleWatchLater = function() {
    if(!window.currentStoryId) return;
    postAction('watch_later', window.currentStoryId).then(res => {
        if(res && res.success) {
            const watchBtn = document.getElementById('watch-btn');
            const watchText = document.getElementById('watch-text');
            watchBtn.style.color = '#16a34a';
            watchBtn.style.borderColor = '#16a34a';
            watchText.innerText = 'Saved';
            watchBtn.disabled = true;
        }
    });
}

window.submitComment = function() {
    if(!window.currentStoryId) return;
    const input = document.getElementById('comment-input');
    const comment = input.value.trim();
    if(!comment) return;

    const formData = new FormData();
    formData.append('action', 'add_comment');
    formData.append('story_id', window.currentStoryId);
    formData.append('comment', comment);

    fetch('api/interact.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if(res && res.success) {
                input.value = '';
                loadComments(window.currentStoryId);
            } else {
                alert(res.error || 'Failed to post comment.');
            }
        }).catch(err => console.error(err));
}

window.loadComments = function(storyId) {
    const formData = new FormData();
    formData.append('action', 'get_comments');
    formData.append('story_id', storyId);

    fetch('api/interact.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if(res && res.success) {
                const container = document.getElementById('comments-container');
                container.innerHTML = '';
                if(res.comments.length === 0) {
                    container.innerHTML = '<p style="color:var(--stone-muted); font-size:0.9rem;">No comments yet. Be the first!</p>';
                } else {
                    res.comments.forEach(c => {
                        container.innerHTML += `
                            <div style="background:var(--stone-light); padding:1rem; border-radius:0.5rem;">
                                <div style="display:flex; align-items:center; gap:8px; margin-bottom:5px;">
                                    <i data-lucide="${c.profile_icon || 'user'}" style="width:16px; height:16px;"></i>
                                    <strong style="font-size:0.9rem;">${c.username}</strong>
                                    <span style="font-size:0.75rem; color:var(--stone-muted);">${new Date(c.created_at).toLocaleDateString()}</span>
                                </div>
                                <p style="margin:0; font-size:0.95rem; color:var(--stone-text);">${c.comment}</p>
                            </div>
                        `;
                    });
                    lucide.createIcons();
                }
            }
        }).catch(err => console.error(err));
}

async function postAction(action, storyId) {
    const formData = new FormData();
    formData.append('action', action);
    formData.append('story_id', storyId);

    try {
        const r = await fetch('api/interact.php', { method: 'POST', body: formData });
        return await r.json();
    } catch (err) {
        console.error("Action error:", err);
        return null;
    }
}