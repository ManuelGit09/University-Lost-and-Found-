<?php
require 'config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$userEmail = $_SESSION['email'] ?? 'User';
$userName = trim($_SESSION['name'] ?? '');
if (!$userName) {
    $userName = preg_replace('/@.*/', '', $userEmail);
    $userName = trim(str_replace(['.', '_', '-'], ' ', $userName));
}
$userName = $userName ? ucwords($userName) : 'User';

$fallbackItems = [
    ['name' => 'Keys', 'category' => 'keys', 'location' => 'Lipa Campus', 'date_event' => '2025-04-24', 'image' => null],
    ['name' => 'Iphone', 'category' => 'phone', 'location' => 'Lipa Campus', 'date_event' => '2025-04-23', 'image' => null],
    ['name' => 'Book', 'category' => 'book', 'location' => 'Lipa Campus', 'date_event' => '2025-04-22', 'image' => null],
    ['name' => 'Watch', 'category' => 'watch', 'location' => 'Lipa Campus', 'date_event' => '2025-04-21', 'image' => null],
    ['name' => 'Bag', 'category' => 'bag', 'location' => 'Lipa Campus', 'date_event' => '2025-04-20', 'image' => null],
    ['name' => 'Wallet', 'category' => 'wallet', 'location' => 'Lipa Campus', 'date_event' => '2025-04-19', 'image' => null],
    ['name' => 'Glasses', 'category' => 'glasses', 'location' => 'Lipa Campus', 'date_event' => '2025-04-18', 'image' => null],
    ['name' => 'Shirt', 'category' => 'shirt', 'location' => 'Lipa Campus', 'date_event' => '2025-04-17', 'image' => null],
];

$itemsPath = __DIR__ . '/db/json_data/items.json';
$storedItems = [];
if (file_exists($itemsPath)) {
    $decodedItems = json_decode(file_get_contents($itemsPath), true);
    if (is_array($decodedItems)) {
        $storedItems = $decodedItems;
    }
}

usort($storedItems, function ($a, $b) {
    return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
});

$previewItems = array_slice(array_merge($fallbackItems, $storedItems), 0, 8);
$categories = [];
foreach ($previewItems as $item) {
    $category = trim((string)($item['category'] ?? ''));
    if ($category !== '') {
        $categories[strtolower($category)] = ucwords($category);
    }
}
asort($categories);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Main Menu - UB FIND</title>
    <link rel="icon" href="favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicon-16.png">
    <link rel="apple-touch-icon" href="assets/apple-touch-icon.png">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>
<body <?= getThemeBodyAttributes() ?>>
    <aside class="main-sidebar">
        <div class="main-seal">
            <img src="background/ub.png" alt="University of Batangas seal" class="main-seal-logo">
            <span>University of Batangas</span>
            <strong>1946</strong>
        </div>

        <nav class="main-nav" aria-label="Main menu">
            <a class="main-nav-link active" href="MainMenu.php" data-section="home">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 11 12 3l9 8v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1v-9Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                Home
            </a>
            <a class="main-nav-link" href="MainMenu.php#lostReport" data-section="report">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 3h9l3 3v15H6V3Z" stroke="currentColor" stroke-width="1.7"/><path d="M15 3v4h4M9 11h6M9 15h6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                Report item
            </a>
            <a class="main-nav-link" href="status.php">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.7"/><path d="m8 12 2.5 2.5L16 9" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Status
            </a>
            <a class="main-nav-link" href="catalog.php">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="4" y="5" width="16" height="14" rx="2" stroke="currentColor" stroke-width="1.7"/><path d="M8 9h8M8 13h4M16 13l2 2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                Catalog
            </a>
            <a class="main-nav-link" href="user.php">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.7"/><path d="M4 21a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                User
            </a>
            <?= renderAdminSidebarLink() ?>
            <a class="main-nav-link" href="settings.php">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z" stroke="currentColor" stroke-width="1.7"/><path d="M19.4 15a1.8 1.8 0 0 0 .36 1.98l.05.05a2.1 2.1 0 0 1-2.97 2.97l-.05-.05a1.8 1.8 0 0 0-1.98-.36 1.8 1.8 0 0 0-1.1 1.66V21a2.1 2.1 0 0 1-4.2 0v-.08a1.8 1.8 0 0 0-1.1-1.66 1.8 1.8 0 0 0-1.98.36l-.05.05a2.1 2.1 0 0 1-2.97-2.97l.05-.05A1.8 1.8 0 0 0 4.6 15a1.8 1.8 0 0 0-1.66-1.1H3a2.1 2.1 0 0 1 0-4.2h.08A1.8 1.8 0 0 0 4.74 8a1.8 1.8 0 0 0-.36-1.98l-.05-.05A2.1 2.1 0 0 1 7.3 3l.05.05a1.8 1.8 0 0 0 1.98.36A1.8 1.8 0 0 0 10.4 1.8V1.7a2.1 2.1 0 0 1 4.2 0v.08a1.8 1.8 0 0 0 1.1 1.66 1.8 1.8 0 0 0 1.98-.36l.05-.05a2.1 2.1 0 0 1 2.97 2.97l-.05.05A1.8 1.8 0 0 0 19.4 8c.3.67.95 1.1 1.66 1.1H21a2.1 2.1 0 0 1 0 4.2h-.08A1.8 1.8 0 0 0 19.4 15Z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"/></svg>
                Settings
            </a>
        </nav>

        <?= renderSidebarShortcuts() ?>
    </aside>

    <main class="main-menu-shell">
        <header class="main-topbar">
            <h1>
                <span>UB FIND</span>
                Lost and Found Center
            </h1>
            <div class="main-top-actions">
                <?= renderNotificationWidget() ?>
                <?= renderMessageWidget() ?>
                <?= renderAccountWidget($userName) ?>
            </div>
        </header>

        <section class="main-hero">
            <div class="main-hero-copy">
                <span class="main-hero-kicker">Lost and Found System</span>
                <h2>Lost Something?</h2>
                <h3>We'll Help You Find It.</h3>
                <div class="main-hero-rule"></div>
                <p>UB FIND is a centralized system for reporting, searching, and recovering lost items within the University of Batangas.</p>
                <div class="main-hero-actions">
                    <a class="main-pill-btn" href="MainMenu.php#lostReport">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 3h9l3 3v15H6V3Z" stroke="currentColor" stroke-width="1.7"/><path d="M9 12h6M9 16h6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                        Report Item
                    </a>
                    <a class="main-pill-btn" href="search.php">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.8"/><path d="m16 16 4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                        Search Item
                    </a>
                </div>
            </div>

            <div class="main-hero-art">
                <img src="background/Homepage Picture.png" alt="UB FIND lost and found items">
            </div>
        </section>

        <form class="main-discovery" id="mainDiscoveryForm" aria-label="Search lost and found items">
            <label class="main-search-field">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.8"/><path d="m16 16 4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                <input type="search" id="mainItemSearch" placeholder="Search items..." autocomplete="off">
            </label>
            <label class="main-category-field">
                <span class="sr-only">Category</span>
                <select id="mainCategoryFilter">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $categoryValue => $categoryLabel): ?>
                        <option value="<?= htmlspecialchars($categoryValue) ?>"><?= htmlspecialchars($categoryLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button type="submit" class="main-discovery-btn" id="mainSearchButton">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.8"/><path d="m16 16 4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                Search
            </button>
        </form>

        <section class="main-report-section" id="lostReport" aria-labelledby="lostReportTitle">
            <form class="main-report-card" id="lostReportForm">
                <div class="main-report-header">
                    <div>
                        <h2 id="lostReportTitle">Create a Lost Item Report</h2>
                        <div class="main-report-mode" aria-label="Report type">
                            <button type="button" class="active" data-report-mode="lost">Lost</button>
                            <button type="button" data-report-mode="found">Found</button>
                        </div>
                    </div>
                    <div class="main-report-actions">
                        <button type="submit" class="main-submit-btn" id="reportSubmitButton">Submit Lost Item Report</button>
                        <button type="reset" class="main-clear-btn">Clear Form</button>
                    </div>
                </div>

                <div class="main-report-rule"></div>

                <div class="main-report-grid">
                    <fieldset class="main-report-fieldset">
                        <legend>1. Item Description</legend>
                        <div class="main-form-grid">
                            <label>
                                <span>Item</span>
                                <input type="text" name="name" placeholder="Item name" required>
                            </label>
                            <label>
                                <span>Brand</span>
                                <input type="text" name="category" placeholder="Brand or category">
                            </label>
                            <label>
                                <span>Color</span>
                                <input type="text" name="color" placeholder="Color">
                            </label>
                            <label>
                                <span>Specification</span>
                                <input type="text" name="specification" placeholder="Model, serial number, markings">
                            </label>
                        </div>
                    </fieldset>

                    <fieldset class="main-report-fieldset">
                        <legend id="reportDetailsLegend">2. Last Seen Details</legend>
                        <div class="main-form-grid main-form-grid-small">
                            <label>
                                <span>Date</span>
                                <input type="date" name="date_event" required>
                            </label>
                            <label>
                                <span>Time</span>
                                <input type="time" name="time_event">
                            </label>
                            <label class="main-form-wide">
                                <span id="reportLocationLabel">Last known location</span>
                                <input type="text" name="location" placeholder="Location" required>
                            </label>
                        </div>
                    </fieldset>

                    <fieldset class="main-report-fieldset" id="contactDetailsFieldset">
                        <legend>3. Contact Details</legend>
                        <div class="main-form-stack">
                            <label>
                                <span>Name :</span>
                                <input type="text" name="contact_name" placeholder="Full name" required>
                            </label>
                            <label>
                                <span>ID :</span>
                                <input type="text" name="contact_id" placeholder="Student/Employee ID">
                            </label>
                            <label>
                                <span>email:</span>
                                <input type="email" name="contact_email" placeholder="your.email@example.com" required>
                            </label>
                            <label>
                                <span>Phone:</span>
                                <span class="main-phone-field">
                                    <span>+63</span>
                                    <input type="tel" name="contact_phone" placeholder="9XX XXX XXXX" inputmode="numeric" maxlength="10" pattern="[0-9]{10}">
                                </span>
                            </label>
                        </div>
                    </fieldset>

                    <fieldset class="main-report-fieldset main-photo-fieldset" id="photoDetailsFieldset" hidden>
                        <legend>Photo:</legend>
                        <label class="main-photo-upload">
                            <span id="photoPreviewLabel">Add photo</span>
                            <input type="file" name="image" accept="image/*">
                            <img id="photoPreview" alt="">
                        </label>
                    </fieldset>
                </div>

                <input type="hidden" name="type" value="lost" id="reportTypeInput">
                <input type="hidden" name="description" id="lostReportDescription">
                <p class="main-report-status" id="lostReportStatus" aria-live="polite"></p>
            </form>
        </section>

        <section class="main-catalog" id="mainCatalogGrid" aria-label="Catalog examples">
            <?php foreach ($previewItems as $item): ?>
                <?php
                    $itemName = trim((string)($item['name'] ?? 'Item')) ?: 'Item';
                    $itemCategory = strtolower(trim((string)($item['category'] ?? $itemName)));
                    $visualClass = preg_replace('/[^a-z0-9-]/', '', str_replace(' ', '-', $itemCategory));
                    $itemImage = $item['image'] ?? null;
                    $dateValue = $item['date_event'] ?? '';
                    $displayDate = $dateValue ? date('F j, Y', strtotime($dateValue)) : 'Recently reported';
                    $location = trim((string)($item['location'] ?? 'Lipa Campus')) ?: 'Lipa Campus';
                    $searchText = strtolower($itemName . ' ' . $itemCategory . ' ' . $location);
                ?>
                <article class="main-item-card" data-search-text="<?= htmlspecialchars($searchText) ?>" data-category="<?= htmlspecialchars($itemCategory) ?>">
                    <div class="main-item-visual visual-<?= htmlspecialchars($visualClass ?: 'item') ?>">
                        <?php if ($itemImage): ?>
                            <img src="uploads/<?= htmlspecialchars($itemImage) ?>" alt="<?= htmlspecialchars($itemName) ?>">
                        <?php else: ?>
                            <span><?= htmlspecialchars($itemName) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="main-item-body">
                        <h4><?= htmlspecialchars($itemName) ?></h4>
                        <p class="main-item-location">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 21s7-4.5 7-11a7 7 0 1 0-14 0c0 6.5 7 11 7 11Z" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="10" r="2.5" stroke="currentColor" stroke-width="1.8"/></svg>
                            <?= htmlspecialchars($location) ?>
                        </p>
                        <p class="main-item-date"><?= htmlspecialchars($displayDate) ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
            <p class="main-catalog-empty" id="mainCatalogEmpty" hidden>No items found.</p>
        </section>

        <div class="main-view-more-wrap">
            <a class="main-view-more" href="catalog.php">View More Items</a>
        </div>
    </main>

    <footer class="main-footer">
        <div class="main-footer-brand">
            <img src="background/ub.png" alt="University of Batangas seal" class="main-footer-seal">
            <strong>University Of<br>Batangas</strong>
            <small>Lipa Campus</small>
        </div>
        <div class="main-footer-block">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 21s7-4.5 7-11a7 7 0 1 0-14 0c0 6.5 7 11 7 11Z" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="10" r="2.5" stroke="currentColor" stroke-width="1.8"/></svg>
            <span>University Of Batangas<br>Lipa City Batangas<br>Philippines</span>
        </div>
        <div class="main-footer-block">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5h16v14H4V5Z" stroke="currentColor" stroke-width="1.8"/><path d="m4 7 8 6 8-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            <span>UBFind.edu.ph<br><br>www.ub.edu.ph</span>
        </div>
        <small class="main-copy">Copyright &copy; 2026 Group 1. All rights reserved.</small>
    </footer>
    <script>
        const lostReportForm = document.getElementById('lostReportForm');
        const lostReportStatus = document.getElementById('lostReportStatus');
        const dateInput = lostReportForm.querySelector('[name="date_event"]');
        const timeInput = lostReportForm.querySelector('[name="time_event"]');
        const reportTitle = document.getElementById('lostReportTitle');
        const reportTypeInput = document.getElementById('reportTypeInput');
        const reportSubmitButton = document.getElementById('reportSubmitButton');
        const reportDetailsLegend = document.getElementById('reportDetailsLegend');
        const reportLocationLabel = document.getElementById('reportLocationLabel');
        const contactDetailsFieldset = document.getElementById('contactDetailsFieldset');
        const photoDetailsFieldset = document.getElementById('photoDetailsFieldset');
        const photoInput = lostReportForm.querySelector('[name="image"]');
        const photoPreview = document.getElementById('photoPreview');
        const photoPreviewLabel = document.getElementById('photoPreviewLabel');
        const reportModeButtons = document.querySelectorAll('[data-report-mode]');
        const mainNavLinks = document.querySelectorAll('.main-nav-link[data-section]');
        const lostReportSection = document.getElementById('lostReport');
        const contactPhoneInput = lostReportForm.querySelector('[name="contact_phone"]');
        let currentReportMode = 'lost';

        function setActiveMainNav(section) {
            mainNavLinks.forEach((link) => {
                link.classList.toggle('active', link.dataset.section === section);
            });
        }

        function setMainView(view) {
            const isReportView = view === 'report';
            document.body.classList.toggle('main-report-view', isReportView);
            setActiveMainNav(isReportView ? 'report' : 'home');
            if (isReportView) {
                lostReportSection.scrollIntoView({ block: 'start' });
            }
        }

        function syncMainNavActive() {
            setMainView(window.location.hash === '#lostReport' ? 'report' : 'home');
        }

        mainNavLinks.forEach((link) => {
            link.addEventListener('click', () => setMainView(link.dataset.section));
        });

        window.addEventListener('hashchange', syncMainNavActive);
        syncMainNavActive();

        function setReportMode(mode) {
            const isFound = mode === 'found';
            currentReportMode = isFound ? 'found' : 'lost';
            reportTitle.textContent = `Create a ${isFound ? 'Found' : 'Lost'} Item Report`;
            reportTypeInput.value = isFound ? 'found' : 'lost';
            reportSubmitButton.textContent = `Submit ${isFound ? 'Found' : 'Lost'} Item Report`;
            reportDetailsLegend.textContent = `2. ${isFound ? 'Found' : 'Last Seen'} Details`;
            reportLocationLabel.textContent = isFound ? 'Found location' : 'Last known location';
            contactDetailsFieldset.hidden = isFound;
            photoDetailsFieldset.hidden = !isFound;
            reportModeButtons.forEach((button) => {
                button.classList.toggle('active', button.dataset.reportMode === mode);
            });
            lostReportStatus.textContent = '';
            lostReportStatus.className = 'main-report-status';
        }

        reportModeButtons.forEach((button) => {
            button.addEventListener('click', () => setReportMode(button.dataset.reportMode));
        });

        photoInput.addEventListener('change', () => {
            const file = photoInput.files && photoInput.files[0];
            if (!file) {
                photoPreview.removeAttribute('src');
                photoPreviewLabel.textContent = 'Add photo';
                return;
            }
            photoPreview.src = URL.createObjectURL(file);
            photoPreviewLabel.textContent = file.name;
        });

        contactPhoneInput.addEventListener('input', () => {
            contactPhoneInput.value = contactPhoneInput.value.replace(/\D/g, '').slice(0, 10);
        });

        lostReportForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const formData = new FormData(lostReportForm);
            const phoneDigits = (formData.get('contact_phone') || '').replace(/\D/g, '');
            if (phoneDigits) {
                formData.set('contact_phone', `+63${phoneDigits}`);
            }
            const reportType = formData.get('type') === 'found' ? 'found' : 'lost';
            const detailTimeLabel = reportType === 'found' ? 'Time found' : 'Time last seen';
            const details = [
                `Brand: ${formData.get('category') || 'N/A'}`,
                `Color: ${formData.get('color') || 'N/A'}`,
                `Specification: ${formData.get('specification') || 'N/A'}`,
                `${detailTimeLabel}: ${formData.get('time_event') || 'N/A'}`
            ];
            if (reportType === 'lost') {
                details.push(
                    `Contact: ${formData.get('contact_name') || 'N/A'} / ${formData.get('contact_email') || 'N/A'} / ${formData.get('contact_phone') || 'N/A'}`,
                    `ID: ${formData.get('contact_id') || 'N/A'}`
                );
            }
            formData.set('description', details.join('\n'));

            lostReportStatus.textContent = 'Submitting report...';
            lostReportStatus.className = 'main-report-status';

            try {
                const response = await fetch('api/items.php?action=create', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    lostReportStatus.textContent = `${reportType === 'found' ? 'Found' : 'Lost'} item report submitted.`;
                    lostReportStatus.classList.add('is-success');
                } else {
                    lostReportStatus.textContent = result.error || 'Could not submit report.';
                    lostReportStatus.classList.add('is-error');
                }
            } catch (error) {
                lostReportStatus.textContent = 'Could not submit report.';
                lostReportStatus.classList.add('is-error');
            }
        });

        lostReportForm.addEventListener('reset', () => {
            window.setTimeout(() => {
                setReportMode(currentReportMode);
                photoPreview.removeAttribute('src');
                photoPreviewLabel.textContent = 'Add photo';
                lostReportStatus.textContent = '';
                lostReportStatus.className = 'main-report-status';
            }, 0);
        });

        const mainItemSearch = document.getElementById('mainItemSearch');
        const mainCategoryFilter = document.getElementById('mainCategoryFilter');
        const mainDiscoveryForm = document.getElementById('mainDiscoveryForm');
        const mainCatalogCards = Array.from(document.querySelectorAll('.main-item-card'));
        const mainCatalogEmpty = document.getElementById('mainCatalogEmpty');

        function filterMainCatalog() {
            const query = (mainItemSearch.value || '').trim().toLowerCase();
            const category = (mainCategoryFilter.value || '').trim().toLowerCase();
            let visibleCount = 0;

            mainCatalogCards.forEach((card) => {
                const matchesSearch = !query || card.dataset.searchText.includes(query);
                const matchesCategory = !category || card.dataset.category === category;
                const isVisible = matchesSearch && matchesCategory;
                card.hidden = !isVisible;
                if (isVisible) visibleCount += 1;
            });

            mainCatalogEmpty.hidden = visibleCount !== 0;
        }

        mainItemSearch.addEventListener('input', filterMainCatalog);
        mainCategoryFilter.addEventListener('change', filterMainCatalog);
        mainDiscoveryForm.addEventListener('submit', (event) => {
            event.preventDefault();
            filterMainCatalog();
        });
    </script>
</body>
</html>
