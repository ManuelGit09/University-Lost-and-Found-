<?php
require_once 'config.php';

if (appMaintenanceActive()) {
    header('Location: login.php?error=maintenance');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UB FIND - Guest Mode</title>
    <link rel="icon" href="favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicon-16.png">
    <link rel="apple-touch-icon" href="assets/apple-touch-icon.png">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>
<body class="guest-page guest-dashboard-page">
    <nav class="guest-nav">
        <div class="guest-nav-inner">
            <a class="guest-brand" href="#guestReport" aria-label="UB FIND home">
                <img src="background/ub.png" alt="University of Batangas seal" class="guest-brand-seal">
                <span class="guest-brand-copy">
                    <span class="guest-brand-title">UB FIND</span>
                    <span class="guest-brand-subtitle">Lost and Found System</span>
                </span>
            </a>

            <div class="guest-nav-actions">
                <button type="button" class="guest-back-btn" id="guestBackBtn">
                    <span aria-hidden="true">&larr;</span>
                    Back
                </button>
                <a class="guest-nav-link" href="#guestReport">Report Item</a>
                <a class="guest-nav-link" href="#guestRecentItems">Search Items</a>
                <span class="guest-mode-link" aria-label="Current access mode">Guest Mode</span>
            </div>
        </div>
    </nav>

    <main class="guest-main guest-dashboard-main">
        <section class="guest-dashboard-grid" aria-label="Guest mode dashboard">
            <section class="guest-panel guest-mode-alert" aria-label="Guest mode information">
                <span class="guest-mode-alert-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="3.5" stroke="currentColor" stroke-width="2"/><path d="M5 21a7 7 0 0 1 14 0" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                </span>
                <div class="guest-mode-alert-copy">
                    <h2>You are in Guest Mode</h2>
                    <p>You can search and view lost &amp; found items.</p>
                    <p>To claim an item, please sign up or login to your account.</p>
                </div>
                <a class="guest-login-claim" href="login.php?claim=required">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="m10 17 5-5-5-5M15 12H3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Sign Up / Login to Claim
                </a>
            </section>

            <div class="guest-dashboard-column">
                <section class="guest-panel guest-report-card" id="guestReport">
                    <div class="guest-panel-heading">
                        <h1>Guest Report</h1>
                        <span>Login required to submit</span>
                    </div>
                    <form id="guestReportForm" class="guest-report-form">
                        <select id="guestReportType" required>
                            <option value="">Lost or Found?</option>
                            <option value="lost">Lost</option>
                            <option value="found">Found</option>
                        </select>
                        <input id="guestReportName" type="text" placeholder="Item Name" required>
                        <select id="guestReportCategory">
                            <option value="">Category</option>
                            <option value="Document">Document</option>
                            <option value="Electronics">Electronics</option>
                            <option value="Personal">Personal</option>
                            <option value="Bag">Bag</option>
                            <option value="Keys">Keys</option>
                            <option value="Clothing">Clothing</option>
                            <option value="Other">Other</option>
                        </select>
                        <textarea id="guestReportDescription" placeholder="Description" rows="4"></textarea>
                        <input id="guestReportDate" type="date" required>
                        <input id="guestReportLocation" type="text" placeholder="Location" required>
                        <input id="guestReportImage" type="file" accept="image/*">
                        <button type="submit">Continue to Sign Up / Login</button>
                        <p id="guestReportMessage" class="guest-report-message" hidden></p>
                    </form>
                </section>

                <section class="guest-panel guest-how-card" aria-label="How it works">
                    <h2>How It Works</h2>
                    <div class="guest-steps">
                        <article>
                            <span class="guest-step-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none"><path d="M14 4h6v6M13 11l7-7M20 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </span>
                            <h3>1. Report Item</h3>
                            <p>Fill out the form with details about your lost or found item.</p>
                        </article>
                        <article>
                            <span class="guest-step-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2"/><path d="M4 21a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            </span>
                            <h3>2. Sign Up / Login</h3>
                            <p>Create an account or login to submit your report.</p>
                        </article>
                        <article>
                            <span class="guest-step-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none"><path d="M12 3 5 6v5c0 4.6 2.8 8.7 7 10 4.2-1.3 7-5.4 7-10V6l-7-3Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="m9 12 2 2 4-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </span>
                            <h3>3. Admin Reviews</h3>
                            <p>Our team will review and verify your report.</p>
                        </article>
                        <article>
                            <span class="guest-step-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none"><rect x="4" y="7" width="16" height="13" rx="2" stroke="currentColor" stroke-width="2"/><path d="M8 7V5m8 2V5M4 11h16M9 15h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            </span>
                            <h3>4. Claim Item</h3>
                            <p>Once matched, you will be notified to claim your item.</p>
                        </article>
                    </div>
                </section>
            </div>

            <div class="guest-dashboard-column">
                <section class="guest-panel guest-recent-card" id="guestRecentItems">
                    <div class="guest-panel-heading">
                        <h2>Recently Reported Items</h2>
                        <button type="button" id="viewMoreBtn" class="guest-view-all">View all <span aria-hidden="true">&rsaquo;</span></button>
                    </div>
                    <div class="guest-search-mini" aria-label="Search lost and found items">
                        <input type="text" id="searchInput" placeholder="Search items...">
                        <select id="typeFilter" aria-label="Filter by type">
                            <option value="">All</option>
                            <option value="lost">Lost</option>
                            <option value="found">Found</option>
                        </select>
                        <select id="statusFilter" aria-label="Filter by status">
                            <option value="">Any Status</option>
                            <option value="open">Open</option>
                            <option value="claimed">Claimed</option>
                            <option value="closed">Closed</option>
                        </select>
                        <button id="searchBtn" type="button">Search</button>
                    </div>
                    <div id="itemsList" class="guest-recent-list" aria-live="polite"></div>
                </section>

                <section class="guest-panel guest-stats-card" aria-label="Quick statistics">
                    <h2>Quick Statistics</h2>
                    <div class="guest-stats-grid">
                        <article>
                            <span class="guest-stat-icon guest-stat-red" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none"><path d="M6 8h12v12H6V8Z" stroke="currentColor" stroke-width="2"/><path d="M9 8a3 3 0 0 1 6 0M9 13h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            </span>
                            <strong id="statRecovered">0</strong>
                            <span>Items Recovered</span>
                        </article>
                        <article>
                            <span class="guest-stat-icon guest-stat-blue" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none"><path d="M7 3h7l4 4v14H7V3Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M14 3v5h4M10 13h5M10 17h5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            </span>
                            <strong id="statActive">0</strong>
                            <span>Active Reports</span>
                        </article>
                        <article>
                            <span class="guest-stat-icon guest-stat-green" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="m8 12 3 3 5-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </span>
                            <strong id="statSuccess">0%</strong>
                            <span>Claim Success Rate</span>
                        </article>
                        <article>
                            <span class="guest-stat-icon guest-stat-orange" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none"><path d="M5 20V9M10 20V4M15 20v-7M20 20H3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            </span>
                            <strong id="statWeek">0</strong>
                            <span>Items Found This Week</span>
                        </article>
                    </div>
                </section>

                <section class="guest-panel guest-tips-card" aria-label="Tips for guests">
                    <div>
                        <h2>Tips for Guests</h2>
                        <ul>
                            <li>Provide as much detail as possible.</li>
                            <li>Include unique marks or characteristics.</li>
                            <li>Upload clear photos if you have any.</li>
                            <li>You will be notified once there is a match.</li>
                        </ul>
                    </div>
                    <div class="guest-tips-visual" aria-hidden="true">
                        <svg viewBox="0 0 112 92" fill="none">
                            <rect x="24" y="12" width="64" height="68" rx="7" fill="#fff7ed" stroke="#c8a47a" stroke-width="3"/>
                            <path d="M42 13h28l-2-7H44l-2 7Z" fill="#272c36"/>
                            <path d="M39 33h29M39 48h24M39 63h19" stroke="#9b7b58" stroke-width="4" stroke-linecap="round"/>
                            <path d="m28 32 5 5 8-10M28 47l5 5 8-10M28 62l5 5 8-10" stroke="#24b466" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="79" cy="61" r="16" stroke="#8f1624" stroke-width="5"/>
                            <path d="m91 73 14 14" stroke="#8f1624" stroke-width="8" stroke-linecap="round"/>
                        </svg>
                    </div>
                </section>

                <section class="guest-panel guest-locations-card" aria-label="Common found locations">
                    <div class="guest-panel-heading">
                        <h2>Common Found Locations</h2>
                        <a href="#guestRecentItems">View Map</a>
                    </div>
                    <div class="guest-location-grid">
                        <article>
                            <span class="guest-location-icon guest-location-red" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none"><path d="M12 21s7-4.5 7-11a7 7 0 1 0-14 0c0 6.5 7 11 7 11Z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="10" r="2.5" stroke="currentColor" stroke-width="2"/></svg>
                            </span>
                            <strong>Cafeteria</strong>
                        </article>
                        <article>
                            <span class="guest-location-icon guest-location-blue" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none"><path d="M12 21s7-4.5 7-11a7 7 0 1 0-14 0c0 6.5 7 11 7 11Z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="10" r="2.5" stroke="currentColor" stroke-width="2"/></svg>
                            </span>
                            <strong>Library</strong>
                        </article>
                        <article>
                            <span class="guest-location-icon guest-location-yellow" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none"><path d="M12 21s7-4.5 7-11a7 7 0 1 0-14 0c0 6.5 7 11 7 11Z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="10" r="2.5" stroke="currentColor" stroke-width="2"/></svg>
                            </span>
                            <strong>Gym</strong>
                        </article>
                        <article>
                            <span class="guest-location-icon guest-location-green" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none"><path d="M12 21s7-4.5 7-11a7 7 0 1 0-14 0c0 6.5 7 11 7 11Z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="10" r="2.5" stroke="currentColor" stroke-width="2"/></svg>
                            </span>
                            <strong>Parking Area</strong>
                        </article>
                        <article>
                            <span class="guest-location-icon guest-location-purple" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none"><path d="M12 21s7-4.5 7-11a7 7 0 1 0-14 0c0 6.5 7 11 7 11Z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="10" r="2.5" stroke="currentColor" stroke-width="2"/></svg>
                            </span>
                            <strong>Admin Office</strong>
                        </article>
                    </div>
                </section>

                <section class="guest-help-card" aria-label="Lost and found office help">
                    <div class="guest-help-panel">
                        <strong>Need Help?</strong>
                        <span>Contact the Lost and Found Office</span>
                        <button type="button" class="guest-help-contact" data-help-center-open>
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 13v-1a7 7 0 0 1 14 0v1" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M5 13h3v5H6a1 1 0 0 1-1-1v-4ZM19 13h-3v5h2a1 1 0 0 0 1-1v-4Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M16 18c0 1.5-1.3 2.5-3 2.5h-1" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            Contact Us
                        </button>
                    </div>
                    <div class="guest-help-box" aria-hidden="true">
                        <svg viewBox="0 0 128 112" fill="none">
                            <path d="M26 47h76l-10 46H36L26 47Z" fill="#d8944b"/>
                            <path d="M26 47 64 65l38-18-16-18H42L26 47Z" fill="#efc07a"/>
                            <path d="M36 93h56l10-46-38 18-38-18 10 46Z" fill="#c57937"/>
                            <path d="M64 65v36" stroke="#9f5f2f" stroke-width="4" stroke-linecap="round"/>
                            <path d="M44 43c-1-17 10-27 20-18 7 6-1 15-8 11" stroke="#733c2d" stroke-width="5" stroke-linecap="round"/>
                            <path d="M85 42c1-16 13-22 20-13 4 6-3 12-9 8" stroke="#a05b24" stroke-width="5" stroke-linecap="round"/>
                            <path d="M53 49 47 24M75 51l6-31M62 50l-1-22" stroke="#305f53" stroke-width="6" stroke-linecap="round"/>
                            <path d="M49 28h8M77 25h8M57 32h8" stroke="#a92537" stroke-width="5" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <p>Let's keep our campus safe and organized.</p>
                </section>
            </div>
        </section>
    </main>

    <div class="help-center-modal" data-help-center-modal hidden>
        <button type="button" class="help-center-backdrop" data-help-center-close aria-label="Close help center"></button>
        <section class="help-center-panel" role="dialog" aria-modal="true" aria-labelledby="helpCenterTitle">
            <div class="help-center-header">
                <h2 id="helpCenterTitle">Help center</h2>
                <button type="button" class="help-center-close" data-help-center-close aria-label="Close">&times;</button>
            </div>
            <div class="help-center-grid">
                <a class="help-center-option" href="https://ub.edu.ph/" target="_blank" rel="noopener">
                    <span class="help-center-icon help-center-icon-book" aria-hidden="true">?</span>
                    <span><strong>Knowledge base</strong><small>Browse help articles or search for specific topics.</small></span>
                </a>
                <a class="help-center-option" href="mailto:lostfound@ub.edu.ph">
                    <span class="help-center-icon help-center-icon-chat" aria-hidden="true">...</span>
                    <span><strong>Student help desk</strong><small>If you're a student, post your help desk questions here.</small></span>
                </a>
                <a class="help-center-option" href="https://ub.edu.ph/" target="_blank" rel="noopener">
                    <span class="help-center-icon help-center-icon-news" aria-hidden="true">!</span>
                    <span><strong>Product news</strong><small>Discover the latest platform features and updates.</small></span>
                </a>
                <a class="help-center-option" href="https://ub.edu.ph/" target="_blank" rel="noopener">
                    <span class="help-center-icon help-center-icon-demo" aria-hidden="true">&#9654;</span>
                    <span><strong>Product demos</strong><small>Demonstrations that provide step-by-step guidance.</small></span>
                </a>
                <a class="help-center-option" href="https://ub.edu.ph/" target="_blank" rel="noopener">
                    <span class="help-center-icon help-center-icon-guide" aria-hidden="true">&#10003;</span>
                    <span><strong>Getting started guides</strong><small>Download or print guides to help you get started.</small></span>
                </a>
                <a class="help-center-option" href="https://ub.edu.ph/" target="_blank" rel="noopener">
                    <span class="help-center-icon help-center-icon-shortcuts" aria-hidden="true">#</span>
                    <span><strong>Shortcuts</strong><small>Open the University of Batangas website for quick access.</small></span>
                </a>
            </div>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    const visibleLimit = 4;
    let itemsCache = [];
    let showingAll = false;

    const itemVisuals = {
        wallet: 'wallet',
        key: 'keys',
        keys: 'keys',
        backpack: 'bag',
        bag: 'bag',
        phone: 'phone',
        iphone: 'phone',
        smartphone: 'phone',
        id: 'document',
        card: 'document',
        document: 'document'
    };

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, char => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[char]));
    }

    function getVisualType(item) {
        const text = `${item.name || ''} ${item.category || ''} ${item.item_type || ''}`.toLowerCase();
        const key = Object.keys(itemVisuals).find(candidate => text.includes(candidate));
        return key ? itemVisuals[key] : 'default';
    }

    function formatDate(value) {
        if (!value) return 'Date not provided';
        const date = new Date(`${value}T00:00:00`);
        if (Number.isNaN(date.getTime())) return value;
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    function formatTime(value, fallback) {
        if (value) {
            const date = new Date(`2000-01-01T${value}`);
            if (!Number.isNaN(date.getTime())) {
                return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
            }
            return value;
        }

        if (fallback) {
            const date = new Date(fallback);
            if (!Number.isNaN(date.getTime())) {
                return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
            }
        }

        return '';
    }

    function imageUrl(item) {
        const image = item.image || (Array.isArray(item.images) ? item.images[0] : '');
        if (!image) return '';
        return /^(https?:)?\/\//i.test(image) || image.startsWith('uploads/') ? image : `uploads/${image}`;
    }

    function renderThumb(item) {
        const image = imageUrl(item);
        if (image) {
            return `<img src="${escapeHtml(image)}" alt="${escapeHtml(item.name || 'Item')}">`;
        }

        return `<span class="guest-thumb-visual guest-thumb-${getVisualType(item)}" aria-hidden="true"></span>`;
    }

    function renderItems() {
        const list = document.getElementById('itemsList');
        const viewMoreBtn = document.getElementById('viewMoreBtn');
        const visibleItems = showingAll ? itemsCache : itemsCache.slice(0, visibleLimit);

        viewMoreBtn.hidden = !itemsCache.length;
        viewMoreBtn.innerHTML = showingAll ? 'Show less <span aria-hidden="true">&rsaquo;</span>' : 'View all <span aria-hidden="true">&rsaquo;</span>';

        if (!visibleItems.length) {
            list.innerHTML = '<div class="guest-empty">No items found.</div>';
            return;
        }

        list.innerHTML = visibleItems.map(item => {
            const type = String(item.type || 'item').toLowerCase();
            const time = formatTime(item.time_event, item.created_at);
            return `
                <article class="guest-recent-item">
                    <div class="guest-recent-thumb">${renderThumb(item)}</div>
                    <div class="guest-recent-body">
                        <h3>${escapeHtml(item.name || 'Unnamed Item')}</h3>
                        <p class="guest-recent-location">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 21s7-4.5 7-11a7 7 0 1 0-14 0c0 6.5 7 11 7 11Z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="10" r="2.5" stroke="currentColor" stroke-width="2"/></svg>
                            ${escapeHtml(item.location || 'Location not provided')}
                        </p>
                        <p class="guest-recent-date">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="4" y="5" width="16" height="15" rx="2" stroke="currentColor" stroke-width="2"/><path d="M8 3v4M16 3v4M4 10h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            ${escapeHtml(formatDate(item.date_event))}${time ? ` - ${escapeHtml(time)}` : ''}
                        </p>
                    </div>
                    <span class="guest-type-pill guest-type-${escapeHtml(type)}">${escapeHtml(type)}</span>
                </article>
            `;
        }).join('');
    }

    function updateStats(items) {
        const total = items.length;
        const recovered = items.filter(item => ['claimed', 'closed', 'recovered'].includes(String(item.status || '').toLowerCase())).length;
        const active = items.filter(item => String(item.status || 'open').toLowerCase() === 'open').length;
        const weekStart = new Date();
        weekStart.setDate(weekStart.getDate() - 7);
        const foundThisWeek = items.filter(item => {
            if (String(item.type || '').toLowerCase() !== 'found') return false;
            const created = new Date(item.created_at || item.date_event || '');
            return !Number.isNaN(created.getTime()) && created >= weekStart;
        }).length;
        const success = total ? Math.round((recovered / total) * 100) : 0;

        document.getElementById('statRecovered').textContent = recovered;
        document.getElementById('statActive').textContent = active;
        document.getElementById('statSuccess').textContent = `${success}%`;
        document.getElementById('statWeek').textContent = foundThisWeek;
    }

    function fetchItems() {
        const keyword = document.getElementById('searchInput').value;
        const type = document.getElementById('typeFilter').value;
        const status = document.getElementById('statusFilter').value;
        const params = new URLSearchParams({ keyword, type, status, limit: 50 });
        const list = document.getElementById('itemsList');

        showingAll = false;
        list.innerHTML = '<div class="guest-empty">Loading items...</div>';

        fetch('api/search.php?' + params)
            .then(response => response.json())
            .then(items => {
                itemsCache = Array.isArray(items) ? items : [];
                renderItems();
                updateStats(itemsCache);
            })
            .catch(() => {
                itemsCache = [];
                updateStats(itemsCache);
                document.getElementById('viewMoreBtn').hidden = true;
                list.innerHTML = '<div class="guest-empty guest-empty-error">Error loading items.</div>';
            });
    }

    document.getElementById('searchBtn').addEventListener('click', fetchItems);
    document.getElementById('searchInput').addEventListener('keydown', event => {
        if (event.key === 'Enter') fetchItems();
    });
    document.getElementById('typeFilter').addEventListener('change', fetchItems);
    document.getElementById('statusFilter').addEventListener('change', fetchItems);
    document.getElementById('viewMoreBtn').addEventListener('click', () => {
        showingAll = !showingAll;
        renderItems();
    });
    document.getElementById('guestBackBtn').addEventListener('click', () => {
        window.location.href = 'login.php';
    });
    document.getElementById('guestReportForm').addEventListener('submit', event => {
        event.preventDefault();
        const requiredFields = [
            document.getElementById('guestReportType'),
            document.getElementById('guestReportName'),
            document.getElementById('guestReportDate'),
            document.getElementById('guestReportLocation')
        ];
        const message = document.getElementById('guestReportMessage');
        const missingField = requiredFields.find(field => !field.value.trim());

        if (missingField) {
            message.hidden = false;
            message.textContent = 'Please complete the type, item name, date, and location before continuing.';
            missingField.focus();
            return;
        }

        const reportDraft = {
            type: document.getElementById('guestReportType').value,
            name: document.getElementById('guestReportName').value,
            category: document.getElementById('guestReportCategory').value,
            description: document.getElementById('guestReportDescription').value,
            date_event: document.getElementById('guestReportDate').value,
            location: document.getElementById('guestReportLocation').value
        };
        sessionStorage.setItem('ubfindGuestReportDraft', JSON.stringify(reportDraft));
        message.hidden = false;
        message.textContent = 'Report details saved for this browser. Please sign up or login to submit it.';
        setTimeout(() => {
            window.location.href = 'login.php?signup=1&report=required';
        }, 850);
    });

    document.addEventListener('click', event => {
        const modal = document.querySelector('[data-help-center-modal]');
        if (!modal) return;

        if (event.target.closest('[data-help-center-open]')) {
            modal.hidden = false;
            document.body.classList.add('help-center-open');
            modal.querySelector('[data-help-center-close]')?.focus();
        }

        if (event.target.closest('[data-help-center-close]')) {
            modal.hidden = true;
            document.body.classList.remove('help-center-open');
        }
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            document.querySelectorAll('[data-help-center-modal]').forEach(modal => {
                modal.hidden = true;
            });
            document.body.classList.remove('help-center-open');
        }
    });

    fetchItems();
    </script>
</body>
</html>
