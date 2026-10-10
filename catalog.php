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
$canManageCatalog = getRole() === ROLE_ADMIN && hasBootedAdminPrivileges();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalog - UB FIND</title>
    <link rel="icon" href="favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicon-16.png">
    <link rel="apple-touch-icon" href="assets/apple-touch-icon.png">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>
<body <?= getThemeBodyAttributes('catalog-page') ?>>
    <aside class="main-sidebar">
        <div class="main-seal">
            <img src="background/ub.png" alt="University of Batangas seal" class="main-seal-logo">
            <span>University of Batangas</span>
            <strong>1946</strong>
        </div>

        <nav class="main-nav" aria-label="Main menu">
            <a class="main-nav-link" href="MainMenu.php">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 11 12 3l9 8v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1v-9Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                Home
            </a>
            <a class="main-nav-link" href="MainMenu.php#lostReport">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 3h9l3 3v15H6V3Z" stroke="currentColor" stroke-width="1.7"/><path d="M15 3v4h4M9 11h6M9 15h6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                Report item
            </a>
            <a class="main-nav-link" href="status.php">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.7"/><path d="m8 12 2.5 2.5L16 9" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Status
            </a>
            <a class="main-nav-link active" href="catalog.php">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="4" y="5" width="16" height="14" rx="2" stroke="currentColor" stroke-width="1.7"/><path d="M8 9h8M8 13h4M16 13l2 2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                Catalog
            </a>
            <a class="main-nav-link" href="user.php">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.7"/><path d="M4 21a7 7 0 0 1 16 0" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                User
            </a>
            <?= renderAdminSidebarLink() ?>
            <a class="main-nav-link" href="settings.php">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="3.4" stroke="currentColor" stroke-width="1.7"/><path d="M19 12a7 7 0 0 0-.1-1.2l2-1.5-2-3.4-2.4 1a7 7 0 0 0-2-1.2L14.2 3h-4.4l-.4 2.7a7 7 0 0 0-2 1.2l-2.4-1-2 3.4 2 1.5a7.2 7.2 0 0 0 0 2.4l-2 1.5 2 3.4 2.4-1a7 7 0 0 0 2 1.2l.4 2.7h4.4l.4-2.7a7 7 0 0 0 2-1.2l2.4 1 2-3.4-2-1.5c.07-.4.1-.8.1-1.2Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/></svg>
                Settings
            </a>
        </nav>

        <?= renderSidebarShortcuts() ?>
    </aside>

    <main class="main-menu-shell catalog-shell">
        <header class="main-topbar">
            <h1>Lost and Found Center</h1>
            <div class="main-top-actions">
                <?= renderNotificationWidget() ?>
                <?= renderMessageWidget() ?>
                <?= renderAccountWidget($userName) ?>
            </div>
        </header>

        <section class="catalog-layout" aria-label="Item catalog">
            <div class="catalog-content">
                <div class="catalog-heading-row">
                    <div>
                        <h2>Item Catalog</h2>
                        <p>Browse lost and found items reported in the campus.</p>
                    </div>
                    <div class="catalog-actions">
                        <?php if ($canManageCatalog): ?>
                            <a class="catalog-action-secondary" href="#adminAddCatalogItem">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/></svg>
                                Add Item
                            </a>
                        <?php endif; ?>
                        <a class="catalog-action-secondary" href="status.php">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 4h10v16H7V4Z" stroke="currentColor" stroke-width="1.7"/><path d="M10 8h4M10 12h4M10 16h2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                            My Claims
                        </a>
                        <a class="catalog-action-primary" href="MainMenu.php#lostReport">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/></svg>
                            Report Item
                        </a>
                    </div>
                </div>

                <?php if ($canManageCatalog): ?>
                    <section class="catalog-admin-add" id="adminAddCatalogItem" aria-label="Add new catalog item">
                        <div class="catalog-admin-add-heading">
                            <div>
                                <h3>Add New Item</h3>
                                <p>Fill in the details of the item to add it to the catalog.</p>
                            </div>
                            <a href="#catalogGrid">Back to Catalog</a>
                        </div>

                        <form class="catalog-admin-form" id="catalogAdminForm" enctype="multipart/form-data" novalidate>
                            <div class="catalog-admin-field">
                                <label for="adminItemName">Item Name <span>*</span></label>
                                <input id="adminItemName" name="name" type="text" placeholder="e.g. Black Wallet" required>
                            </div>
                            <div class="catalog-admin-field">
                                <label for="adminItemColor">Color</label>
                                <input id="adminItemColor" name="color" type="text" placeholder="e.g. Black">
                            </div>
                            <div class="catalog-admin-field">
                                <label for="adminItemCategory">Category <span>*</span></label>
                                <select id="adminItemCategory" name="category" required>
                                    <option value="">Select category</option>
                                    <option>Personal</option>
                                    <option>Document</option>
                                    <option>Electronics</option>
                                    <option>Bag</option>
                                    <option>Clothing</option>
                                    <option>Keys</option>
                                    <option>Other</option>
                                </select>
                            </div>
                            <div class="catalog-admin-field">
                                <label for="adminItemMaterial">Material</label>
                                <input id="adminItemMaterial" name="material" type="text" placeholder="e.g. Leather">
                            </div>
                            <div class="catalog-admin-field">
                                <label for="adminItemLocation">Location Found <span>*</span></label>
                                <select id="adminItemLocation" name="location" required>
                                    <option value="">Select location</option>
                                    <option>Cafeteria</option>
                                    <option>Library</option>
                                    <option>Main Building</option>
                                    <option>Gym</option>
                                    <option>Auditorium</option>
                                    <option>Parking Area</option>
                                    <option>Admin Office</option>
                                    <option>Outside the Campus</option>
                                </select>
                            </div>
                            <div class="catalog-admin-field">
                                <label for="adminItemType">Item Type</label>
                                <select id="adminItemType" name="item_type">
                                    <option value="">Select item type</option>
                                    <option>Wallet</option>
                                    <option>ID Card</option>
                                    <option>Phone</option>
                                    <option>Keys</option>
                                    <option>Bag</option>
                                    <option>Document</option>
                                    <option>Other</option>
                                </select>
                            </div>
                            <div class="catalog-admin-split">
                                <div class="catalog-admin-field">
                                    <label for="adminItemDate">Date Found <span>*</span></label>
                                    <input id="adminItemDate" name="date_event" type="date" required>
                                </div>
                                <div class="catalog-admin-field">
                                    <label for="adminItemTime">Time Found</label>
                                    <input id="adminItemTime" name="time_event" type="time">
                                </div>
                            </div>
                            <div class="catalog-admin-field">
                                <label for="adminItemStatus">Status <span>*</span></label>
                                <select id="adminItemStatus" name="admin_status" required>
                                    <option value="found">Found</option>
                                    <option value="lost">Lost</option>
                                </select>
                            </div>
                            <div class="catalog-admin-field catalog-admin-description">
                                <label for="adminItemDescription">Description</label>
                                <textarea id="adminItemDescription" name="description" rows="7" maxlength="500" placeholder="Provide details about the item (color, brand, unique marks, etc.)"></textarea>
                                <small><span id="adminDescriptionCount">0</span> / 500</small>
                            </div>
                            <div class="catalog-admin-images">
                                <label for="adminItemImages">Item Images <span>*</span></label>
                                <p>Upload clear images of the item. You can upload up to 5 images.</p>
                                <label class="catalog-admin-dropzone" for="adminItemImages">
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 16V4M7 9l5-5 5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M5 16v3h14v-3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                                    <strong>Click to upload or drag and drop</strong>
                                    <small>PNG, JPG, GIF, or JPEG (Max. 5MB each)</small>
                                </label>
                                <input id="adminItemImages" name="item_images[]" type="file" accept="image/png,image/jpeg,image/gif,image/webp" multiple hidden>
                                <div class="catalog-admin-preview" id="adminImagePreview"></div>
                            </div>
                            <small class="catalog-admin-status" id="catalogAdminStatus" role="status" aria-live="polite"></small>
                            <div class="catalog-admin-form-actions">
                                <button type="reset">Cancel</button>
                                <button type="submit">Save Item</button>
                            </div>
                        </form>
                    </section>
                <?php endif; ?>

                <div class="catalog-filterbar">
                    <label class="catalog-search">
                        <span class="sr-only">Search catalog</span>
                        <input type="search" id="catalogSearch" placeholder="Search items (e.g. wallet, phone, ID card)">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="6.5" stroke="currentColor" stroke-width="1.8"/><path d="m16 16 4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    </label>
                    <select id="catalogCategoryFilter" aria-label="Filter by category">
                        <option value="">All Categories</option>
                    </select>
                    <select id="catalogLocationFilter" aria-label="Filter by location">
                        <option value="">All Locations</option>
                    </select>
                    <select id="catalogStatusFilter" aria-label="More filters">
                        <option value="">More Filters</option>
                        <option value="lost">Lost</option>
                        <option value="found">Found</option>
                        <option value="open">Open</option>
                        <option value="claimed">Claimed</option>
                        <option value="pending">Pending Approval</option>
                    </select>
                </div>

                <div id="catalogGrid" class="catalog-grid" aria-live="polite"></div>

                <div class="catalog-pagination">
                    <span id="catalogResultCount">Showing 0 items</span>
                    <div class="catalog-pages" id="catalogPages" aria-label="Catalog pages"></div>
                </div>
            </div>

            <aside class="catalog-details-panel" aria-label="Item details">
                <div class="catalog-details-card" id="catalogDetailsCard">
                    <button class="catalog-details-close" type="button" id="catalogDetailsClose" aria-label="Close item details">&times;</button>
                    <h2>Item Details</h2>
                    <div class="catalog-details-empty" id="catalogDetailsEmpty">Select an item to view details.</div>
                    <div class="catalog-details-body" id="catalogDetailsBody" hidden>
                        <div class="catalog-details-top">
                            <div class="catalog-details-media" id="catalogDetailsMedia"></div>
                            <div class="catalog-details-summary">
                                <span class="catalog-status-badge" id="catalogDetailsBadge"></span>
                                <h3 id="catalogDetailsName"></h3>
                                <p id="catalogDetailsLocation"></p>
                                <p id="catalogDetailsDate"></p>
                            </div>
                        </div>

                        <section class="catalog-info-box">
                            <h3>Item Information</h3>
                            <dl>
                                <div><dt>Category</dt><dd id="catalogDetailsCategory"></dd></div>
                                <div><dt>Color</dt><dd id="catalogDetailsColor"></dd></div>
                                <div><dt>Material</dt><dd id="catalogDetailsMaterial"></dd></div>
                                <div><dt>Description</dt><dd id="catalogDetailsDescription"></dd></div>
                                <div><dt>Status</dt><dd id="catalogDetailsStatus"></dd></div>
                                <div id="catalogDetailsClaimedRow" hidden><dt>Claimed By</dt><dd id="catalogDetailsClaimedBy"></dd></div>
                                <div id="catalogDetailsIssuedRow" hidden><dt>Issued At</dt><dd id="catalogDetailsIssuedAt"></dd></div>
                                <div><dt>Reported By</dt><dd id="catalogDetailsReporter"></dd></div>
                            </dl>
                        </section>

                        <section class="catalog-claim-box">
                            <h3>Claim this item</h3>
                            <p>Think this item is yours? Submit a claim and our admin will review it.</p>
                            <button type="button" id="catalogClaimButton">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 4h10v16H7V4Z" stroke="currentColor" stroke-width="1.7"/><path d="M10 8h4M10 12h4M12 16h5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                                Claim Item
                            </button>
                            <small id="catalogClaimStatus" class="catalog-claim-status" role="status" aria-live="polite"></small>
                        </section>

                        <?php if ($canManageCatalog): ?>
                            <section class="catalog-admin-remove-box">
                                <h3>Admin Item Control</h3>
                                <p>Issue the item to its owner or remove it from the active catalog. Removed items are stored in item history.</p>
                                <button type="button" id="catalogClaimOwnerButton" class="catalog-admin-claim-owner">
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="9" cy="8" r="4" stroke="currentColor" stroke-width="1.7"/><path d="M3 21a6 6 0 0 1 12 0M15 14l2.2 2.2L21 11" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    Mark Claimed by Owner
                                </button>
                                <button type="button" id="catalogArchiveButton">
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 7h14M10 11v6M14 11v6M8 7l1-3h6l1 3M7 7l1 14h8l1-14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    Remove from Catalog
                                </button>
                                <small id="catalogClaimOwnerStatus" class="catalog-claim-status" role="status" aria-live="polite"></small>
                                <small id="catalogArchiveStatus" class="catalog-claim-status" role="status" aria-live="polite"></small>
                            </section>
                        <?php endif; ?>

                        <section class="catalog-process-box">
                            <h3>Claim Process</h3>
                            <div class="catalog-process-steps">
                                <div><span>1</span><strong>Submit Claim</strong><small>Fill out the claim form with details and proof.</small></div>
                                <div><span>2</span><strong>Admin Review</strong><small>Our admin will review your claim.</small></div>
                                <div><span>3</span><strong>Approval</strong><small>You will be notified once approved.</small></div>
                            </div>
                        </section>
                    </div>
                </div>
            </aside>
        </section>
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

    <div class="catalog-claim-modal" id="catalogClaimModal" hidden>
        <button class="catalog-claim-backdrop" type="button" data-claim-close aria-label="Close claim form"></button>
        <form class="catalog-claim-form" id="catalogClaimForm" role="dialog" aria-modal="true" aria-labelledby="catalogClaimTitle" novalidate>
            <button class="catalog-claim-close" type="button" data-claim-close aria-label="Close claim form">&times;</button>
            <h2 id="catalogClaimTitle">Claim Item</h2>
            <p id="catalogClaimItemName">Submit proof that this item belongs to you.</p>
            <label>
                Written Proof
                <textarea id="catalogClaimProof" name="proof" rows="4" placeholder="Example: describe marks, contents, ID details, or where you lost it."></textarea>
            </label>
            <label>
                Proof Picture
                <input type="file" id="catalogClaimProofImage" name="proof_image" accept="image/png,image/jpeg,image/gif,image/webp">
            </label>
            <small id="catalogClaimFormStatus" class="catalog-claim-form-status" role="status" aria-live="polite"></small>
            <button type="submit">Submit Claim</button>
        </form>
    </div>

    <script>
        const catalogGrid = document.getElementById('catalogGrid');
        const catalogSearch = document.getElementById('catalogSearch');
        const catalogCategoryFilter = document.getElementById('catalogCategoryFilter');
        const catalogLocationFilter = document.getElementById('catalogLocationFilter');
        const catalogStatusFilter = document.getElementById('catalogStatusFilter');
        const catalogResultCount = document.getElementById('catalogResultCount');
        const catalogPages = document.getElementById('catalogPages');
        const catalogDetailsEmpty = document.getElementById('catalogDetailsEmpty');
        const catalogDetailsBody = document.getElementById('catalogDetailsBody');
        const catalogDetailsMedia = document.getElementById('catalogDetailsMedia');
        const catalogDetailsBadge = document.getElementById('catalogDetailsBadge');
        const catalogDetailsName = document.getElementById('catalogDetailsName');
        const catalogDetailsLocation = document.getElementById('catalogDetailsLocation');
        const catalogDetailsDate = document.getElementById('catalogDetailsDate');
        const catalogDetailsCategory = document.getElementById('catalogDetailsCategory');
        const catalogDetailsColor = document.getElementById('catalogDetailsColor');
        const catalogDetailsMaterial = document.getElementById('catalogDetailsMaterial');
        const catalogDetailsDescription = document.getElementById('catalogDetailsDescription');
        const catalogDetailsStatus = document.getElementById('catalogDetailsStatus');
        const catalogDetailsClaimedRow = document.getElementById('catalogDetailsClaimedRow');
        const catalogDetailsClaimedBy = document.getElementById('catalogDetailsClaimedBy');
        const catalogDetailsIssuedRow = document.getElementById('catalogDetailsIssuedRow');
        const catalogDetailsIssuedAt = document.getElementById('catalogDetailsIssuedAt');
        const catalogDetailsReporter = document.getElementById('catalogDetailsReporter');
        const catalogDetailsClose = document.getElementById('catalogDetailsClose');
        const catalogClaimButton = document.getElementById('catalogClaimButton');
        const catalogClaimStatus = document.getElementById('catalogClaimStatus');
        const catalogClaimOwnerButton = document.getElementById('catalogClaimOwnerButton');
        const catalogClaimOwnerStatus = document.getElementById('catalogClaimOwnerStatus');
        const catalogArchiveButton = document.getElementById('catalogArchiveButton');
        const catalogArchiveStatus = document.getElementById('catalogArchiveStatus');
        const catalogClaimModal = document.getElementById('catalogClaimModal');
        const catalogClaimForm = document.getElementById('catalogClaimForm');
        const catalogClaimProof = document.getElementById('catalogClaimProof');
        const catalogClaimProofImage = document.getElementById('catalogClaimProofImage');
        const catalogClaimItemName = document.getElementById('catalogClaimItemName');
        const catalogClaimFormStatus = document.getElementById('catalogClaimFormStatus');
        const canManageCatalog = <?= json_encode($canManageCatalog) ?>;
        const catalogAdminForm = document.getElementById('catalogAdminForm');
        const adminItemStatus = document.getElementById('adminItemStatus');
        const adminItemImages = document.getElementById('adminItemImages');
        const adminImagePreview = document.getElementById('adminImagePreview');
        const catalogAdminStatus = document.getElementById('catalogAdminStatus');
        const adminItemDescription = document.getElementById('adminItemDescription');
        const adminDescriptionCount = document.getElementById('adminDescriptionCount');
        let catalogItems = [];
        let filteredCatalogItems = [];
        let selectedCatalogItem = null;
        let catalogPage = 1;
        const catalogPageSize = 6;

        const fallbackItems = [
            { id: 'sample-wallet', name: 'Black Wallet', category: 'Wallet', description: 'Black leather wallet with school ID, cards and some cash.', date_event: '2024-10-01', location: 'Cafeteria', type: 'lost', status: 'open', reporter_id: 'John D.' },
            { id: 'sample-id', name: 'ID Card', category: 'Document', description: 'Student identification card.', date_event: '2024-09-30', location: 'Library', type: 'lost', status: 'pending', reporter_id: 'Admin' },
            { id: 'sample-bag', name: 'Black Backpack', category: 'Bag', description: 'Black backpack found near the main building.', date_event: '2024-09-28', location: 'Main Building', type: 'found', status: 'open', reporter_id: 'Guard Desk' },
            { id: 'sample-bottle', name: 'Water Bottle', category: 'Bottle', description: 'Silver insulated bottle.', date_event: '2024-09-27', location: 'Gym', type: 'found', status: 'open', reporter_id: 'PE Office' },
            { id: 'sample-keys', name: 'Keys with Keychain', category: 'Keys', description: 'Set of keys with a small keychain.', date_event: '2024-09-26', location: 'Parking Area', type: 'lost', status: 'open', reporter_id: 'Student' },
            { id: 'sample-jacket', name: 'Gray Jacket', category: 'Clothing', description: 'Gray hoodie jacket left in the auditorium.', date_event: '2024-09-25', location: 'Auditorium', type: 'found', status: 'claimed', reporter_id: 'Admin' }
        ];

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, char => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[char]));
        }

        function visualClass(item) {
            const text = `${item.name || ''} ${item.category || ''}`.toLowerCase();
            if (text.includes('phone') || text.includes('iphone')) return 'visual-phone';
            if (text.includes('glass')) return 'visual-glasses';
            if (text.includes('key')) return 'visual-keys';
            if (text.includes('wallet')) return 'visual-wallet';
            if (text.includes('shirt')) return 'visual-shirt';
            if (text.includes('jacket') || text.includes('cloth')) return 'visual-shirt';
            if (text.includes('bag')) return 'visual-bag';
            if (text.includes('bottle')) return 'visual-bottle';
            if (text.includes('id') || text.includes('document')) return 'visual-id';
            if (text.includes('watch')) return 'visual-watch';
            if (text.includes('book')) return 'visual-book';
            return 'visual-keys';
        }

        function displayStatus(item) {
            const status = String(item.status || '').toLowerCase();
            if (status === 'claimed') return 'Claimed';
            if (status === 'pending') return 'Pending Approval';
            return item.type === 'found' ? 'Found' : 'Lost';
        }

        function statusClass(item) {
            return displayStatus(item).toLowerCase().replace(/\s+/g, '-');
        }

        function formatDate(value) {
            if (!value) return 'Not provided';
            const date = new Date(`${value}`.includes('T') ? value : `${value}T00:00:00`);
            if (Number.isNaN(date.getTime())) return value;
            return date.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
        }

        function imageUrl(filename) {
            if (!filename) return '';
            const value = String(filename);
            if (/^(https?:)?\/\//i.test(value) || value.startsWith('uploads/')) {
                return value;
            }
            return `uploads/${value}`;
        }

        function itemImageMarkup(item) {
            const image = item.image || (Array.isArray(item.images) ? item.images[0] : '');
            return image ? `<img src="${escapeHtml(imageUrl(image))}" alt="${escapeHtml(item.name || 'Item')}">` : '';
        }

        function populateCatalogFilters(items) {
            const selectedCategory = catalogCategoryFilter.value;
            const selectedLocation = catalogLocationFilter.value;
            const categories = [...new Set(items.map(item => item.category).filter(Boolean))].sort();
            const locations = [...new Set(items.map(item => item.location).filter(Boolean))].sort();

            catalogCategoryFilter.innerHTML = '<option value="">All Categories</option>' + categories.map(category => `<option value="${escapeHtml(category)}">${escapeHtml(category)}</option>`).join('');
            catalogLocationFilter.innerHTML = '<option value="">All Locations</option>' + locations.map(location => `<option value="${escapeHtml(location)}">${escapeHtml(location)}</option>`).join('');
            catalogCategoryFilter.value = selectedCategory;
            catalogLocationFilter.value = selectedLocation;
        }

        function selectCatalogItem(item) {
            selectedCatalogItem = item;
            catalogDetailsEmpty.hidden = true;
            catalogDetailsBody.hidden = false;
            catalogClaimStatus.textContent = '';
            catalogClaimStatus.classList.remove('is-error', 'is-success');
            if (catalogArchiveStatus) {
                catalogArchiveStatus.textContent = '';
                catalogArchiveStatus.classList.remove('is-error', 'is-success');
            }
            if (catalogClaimOwnerStatus) {
                catalogClaimOwnerStatus.textContent = '';
                catalogClaimOwnerStatus.classList.remove('is-error', 'is-success');
            }
            catalogDetailsMedia.className = `catalog-details-media ${visualClass(item)}`;
            catalogDetailsMedia.innerHTML = itemImageMarkup(item);
            catalogDetailsBadge.className = `catalog-status-badge ${statusClass(item)}`;
            catalogDetailsBadge.textContent = displayStatus(item);
            catalogDetailsName.textContent = item.name || 'Item';
            catalogDetailsLocation.textContent = item.location || 'Location not provided';
            catalogDetailsDate.textContent = `Reported on ${formatDate(item.date_event || item.created_at)}`;
            catalogDetailsCategory.textContent = item.category || 'Not provided';
            catalogDetailsColor.textContent = item.color || 'Not provided';
            catalogDetailsMaterial.textContent = item.material || 'Not provided';
            catalogDetailsDescription.textContent = item.description || 'No description provided.';
            catalogDetailsStatus.innerHTML = `<span class="catalog-status-badge ${statusClass(item)}">${displayStatus(item)}</span>`;
            const claimedBy = item.claimed_by_owner || item.claimed_by_name || '';
            const issuedAt = item.claimed_issued_at || item.claimed_at || '';
            catalogDetailsClaimedRow.hidden = !claimedBy;
            catalogDetailsClaimedBy.textContent = claimedBy || '';
            catalogDetailsIssuedRow.hidden = !issuedAt;
            catalogDetailsIssuedAt.textContent = issuedAt ? formatDate(issuedAt) : '';
            catalogDetailsReporter.textContent = item.reporter_name || item.reporter || item.reporter_id || 'Not provided';
            const itemStatus = String(item.status || 'open').toLowerCase();
            const itemId = Number(item.id);
            const canClaim = Number.isInteger(itemId) && itemId > 0 && itemStatus !== 'claimed';
            catalogClaimButton.disabled = !canClaim;
            catalogClaimButton.title = canClaim ? '' : 'This item cannot be claimed from the catalog.';
            if (catalogClaimOwnerButton) {
                catalogClaimOwnerButton.disabled = !Number.isInteger(itemId) || itemId <= 0 || itemStatus === 'claimed';
                catalogClaimOwnerButton.title = catalogClaimOwnerButton.disabled
                    ? (itemStatus === 'claimed' ? 'This item is already marked as claimed.' : 'Sample items cannot be issued.')
                    : '';
            }
            if (catalogArchiveButton) {
                catalogArchiveButton.disabled = !Number.isInteger(itemId) || itemId <= 0;
                catalogArchiveButton.title = catalogArchiveButton.disabled ? 'Sample items cannot be removed.' : '';
            }
            renderCatalog();
        }

        function closeCatalogDetails() {
            selectedCatalogItem = null;
            catalogDetailsEmpty.hidden = false;
            catalogDetailsBody.hidden = true;
            catalogClaimStatus.textContent = '';
            catalogClaimButton.disabled = false;
            renderCatalog();
        }

        async function archiveSelectedCatalogItem() {
            if (!canManageCatalog || !selectedCatalogItem || !catalogArchiveButton) {
                return;
            }

            const itemId = Number(selectedCatalogItem.id);
            if (!Number.isInteger(itemId) || itemId <= 0) {
                catalogArchiveStatus.textContent = 'This item cannot be removed.';
                catalogArchiveStatus.classList.add('is-error');
                return;
            }

            const confirmed = window.confirm(`Remove "${selectedCatalogItem.name || 'this item'}" from the active catalog? It will be saved in item history.`);
            if (!confirmed) {
                return;
            }

            catalogArchiveButton.disabled = true;
            catalogArchiveStatus.textContent = 'Removing item...';
            catalogArchiveStatus.classList.remove('is-error', 'is-success');

            try {
                const data = new FormData();
                data.append('id', itemId);
                data.append('reason', 'Removed from catalog by admin');
                const response = await fetch('api/items.php?action=archive', {
                    method: 'POST',
                    body: data
                });
                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.error || 'Could not remove item.');
                }

                catalogArchiveStatus.textContent = 'Item removed and saved to history.';
                catalogArchiveStatus.classList.add('is-success');
                selectedCatalogItem = null;
                await loadCatalogItems();
            } catch (error) {
                catalogArchiveStatus.textContent = error.message || 'Could not remove item.';
                catalogArchiveStatus.classList.add('is-error');
                catalogArchiveButton.disabled = false;
            }
        }

        async function markSelectedCatalogItemClaimed() {
            if (!canManageCatalog || !selectedCatalogItem || !catalogClaimOwnerButton) {
                return;
            }

            const itemId = Number(selectedCatalogItem.id);
            if (!Number.isInteger(itemId) || itemId <= 0) {
                catalogClaimOwnerStatus.textContent = 'This item cannot be issued.';
                catalogClaimOwnerStatus.classList.add('is-error');
                return;
            }

            const confirmed = window.confirm(`Mark "${selectedCatalogItem.name || 'this item'}" as claimed by the owner?`);
            if (!confirmed) {
                return;
            }

            catalogClaimOwnerButton.disabled = true;
            catalogClaimOwnerStatus.textContent = 'Issuing item...';
            catalogClaimOwnerStatus.classList.remove('is-error', 'is-success');

            try {
                const data = new FormData();
                data.append('id', itemId);
                const response = await fetch('api/items.php?action=claim_owner', {
                    method: 'POST',
                    body: data
                });
                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.error || 'Could not issue item.');
                }

                catalogClaimOwnerStatus.textContent = 'Item marked as claimed by owner.';
                catalogClaimOwnerStatus.classList.add('is-success');
                await loadCatalogItems();
                const updatedItem = catalogItems.find(item => Number(item.id) === itemId);
                if (updatedItem) {
                    selectCatalogItem(updatedItem);
                    catalogClaimOwnerStatus.textContent = 'Item marked as claimed by owner.';
                    catalogClaimOwnerStatus.classList.add('is-success');
                }
            } catch (error) {
                catalogClaimOwnerStatus.textContent = error.message || 'Could not issue item.';
                catalogClaimOwnerStatus.classList.add('is-error');
                catalogClaimOwnerButton.disabled = false;
            }
        }

        function openClaimModal() {
            if (!selectedCatalogItem || catalogClaimButton.disabled) {
                return;
            }

            catalogClaimForm.reset();
            catalogClaimFormStatus.textContent = '';
            catalogClaimProof.classList.remove('is-invalid');
            catalogClaimProofImage.classList.remove('is-invalid');
            catalogClaimItemName.textContent = `Submit proof that "${selectedCatalogItem.name || 'this item'}" belongs to you.`;
            catalogClaimModal.hidden = false;
            catalogClaimProof.focus();
        }

        function closeClaimModal() {
            catalogClaimModal.hidden = true;
            catalogClaimForm.reset();
            catalogClaimFormStatus.textContent = '';
            catalogClaimProof.classList.remove('is-invalid');
            catalogClaimProofImage.classList.remove('is-invalid');
        }

        async function submitCatalogClaim(event) {
            event.preventDefault();
            if (!selectedCatalogItem) {
                return;
            }

            const itemId = Number(selectedCatalogItem.id);
            if (!Number.isInteger(itemId) || itemId <= 0) {
                catalogClaimStatus.textContent = 'This item cannot be claimed yet.';
                catalogClaimStatus.classList.add('is-error');
                return;
            }

            const proof = catalogClaimProof.value;
            const proofImage = catalogClaimProofImage.files && catalogClaimProofImage.files[0];
            if ((!proof || !proof.trim()) && !proofImage) {
                catalogClaimFormStatus.textContent = 'Add written proof or upload a proof picture.';
                catalogClaimProof.classList.add('is-invalid');
                catalogClaimProofImage.classList.add('is-invalid');
                catalogClaimProof.focus();
                return;
            }

            catalogClaimFormStatus.textContent = '';
            catalogClaimProof.classList.remove('is-invalid');
            catalogClaimProofImage.classList.remove('is-invalid');
            const data = new FormData(catalogClaimForm);
            data.append('item_id', itemId);
            data.set('proof', proof.trim());
            catalogClaimButton.disabled = true;
            catalogClaimStatus.textContent = 'Submitting claim...';
            catalogClaimStatus.classList.remove('is-error', 'is-success');

            try {
                const response = await fetch('api/claims.php?action=create', {
                    method: 'POST',
                    body: data
                });
                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.error || 'Could not submit claim.');
                }

                catalogClaimStatus.textContent = 'Claim submitted for admin review.';
                catalogClaimStatus.classList.add('is-success');
                closeClaimModal();
            } catch (error) {
                catalogClaimStatus.textContent = error.message || 'Could not submit claim.';
                catalogClaimStatus.classList.add('is-error');
                catalogClaimButton.disabled = false;
            }
        }

        function renderAdminImagePreview() {
            if (!adminItemImages || !adminImagePreview) return;
            const files = Array.from(adminItemImages.files || []).slice(0, 5);
            adminImagePreview.classList.toggle('is-empty', files.length === 0);
            adminImagePreview.innerHTML = files.map((file, index) => `
                <figure>
                    <img src="${escapeHtml(URL.createObjectURL(file))}" alt="Selected item image ${index + 1}">
                    ${index === 0 ? '<figcaption>Primary</figcaption>' : ''}
                </figure>
            `).join('') + '<label for="adminItemImages"><span>+</span><small>Add more</small></label>';
        }

        async function submitAdminCatalogItem(event) {
            event.preventDefault();
            if (!catalogAdminForm) return;

            const data = new FormData(catalogAdminForm);
            data.set('type', adminItemStatus.value || 'found');
            if (!data.get('name') || !data.get('category') || !data.get('location') || !data.get('date_event')) {
                catalogAdminStatus.textContent = 'Complete the required item fields.';
                catalogAdminStatus.className = 'catalog-admin-status is-error';
                return;
            }

            const imageCount = adminItemImages ? adminItemImages.files.length : 0;
            if (imageCount > 5) {
                catalogAdminStatus.textContent = 'Upload up to 5 images only.';
                catalogAdminStatus.className = 'catalog-admin-status is-error';
                return;
            }

            catalogAdminStatus.textContent = 'Saving item...';
            catalogAdminStatus.className = 'catalog-admin-status';

            try {
                const response = await fetch('api/items.php?action=create', {
                    method: 'POST',
                    body: data
                });
                const result = await response.json();
                if (!result.success) {
                    throw new Error(result.error || 'Could not save item.');
                }

                catalogAdminStatus.textContent = 'Item saved and published to the catalog.';
                catalogAdminStatus.className = 'catalog-admin-status is-success';
                catalogAdminForm.reset();
                if (adminImagePreview) {
                    adminImagePreview.classList.add('is-empty');
                    adminImagePreview.innerHTML = '<label for="adminItemImages"><span>+</span><small>Add images</small></label>';
                }
                if (adminDescriptionCount) adminDescriptionCount.textContent = '0';
                await loadCatalogItems();
                const savedItem = catalogItems.find(item => Number(item.id) === Number(result.id));
                if (savedItem) selectCatalogItem(savedItem);
            } catch (error) {
                catalogAdminStatus.textContent = error.message || 'Could not save item.';
                catalogAdminStatus.className = 'catalog-admin-status is-error';
            }
        }

        function renderCatalog() {
            const source = catalogItems.length ? catalogItems : fallbackItems;
            const query = catalogSearch.value.trim().toLowerCase();
            const category = catalogCategoryFilter.value;
            const location = catalogLocationFilter.value;
            const status = catalogStatusFilter.value;
            filteredCatalogItems = source.filter((item) => {
                const haystack = `${item.name || ''} ${item.category || ''} ${item.location || ''} ${item.description || ''}`.toLowerCase();
                const matchesQuery = !query || haystack.includes(query);
                const matchesCategory = !category || item.category === category;
                const matchesLocation = !location || item.location === location;
                const matchesStatus = !status || item.type === status || item.status === status;
                return matchesQuery && matchesCategory && matchesLocation && matchesStatus;
            });

            const pageCount = Math.max(1, Math.ceil(filteredCatalogItems.length / catalogPageSize));
            if (catalogPage > pageCount) {
                catalogPage = pageCount;
            }

            const start = (catalogPage - 1) * catalogPageSize;
            const visibleItems = filteredCatalogItems.slice(start, start + catalogPageSize);

            catalogGrid.innerHTML = visibleItems.map((item) => {
                const image = itemImageMarkup(item);
                const isSelected = selectedCatalogItem && String(selectedCatalogItem.id ?? selectedCatalogItem.name) === String(item.id ?? item.name);
                return `
                    <button class="catalog-card ${isSelected ? 'is-selected' : ''}" type="button" data-catalog-id="${escapeHtml(item.id ?? item.name)}">
                        <div class="catalog-card-media ${visualClass(item)}">
                            ${image}
                        </div>
                        <span class="catalog-status-badge ${statusClass(item)}">${escapeHtml(displayStatus(item))}</span>
                        <h3>${escapeHtml(item.name || 'Item')}</h3>
                        <p class="catalog-card-location">${escapeHtml(item.location || 'Location not provided')}</p>
                        <p class="catalog-card-date">${escapeHtml(formatDate(item.date_event || item.created_at))}</p>
                        <span class="catalog-card-link">View Details</span>
                    </button>
                `;
            }).join('') || '<p class="catalog-empty">No catalog items found.</p>';

            const total = filteredCatalogItems.length;
            const end = Math.min(start + visibleItems.length, total);
            catalogResultCount.textContent = total ? `Showing ${start + 1} to ${end} of ${total} items` : 'Showing 0 items';
            catalogPages.innerHTML = `
                <button type="button" data-page="${Math.max(1, catalogPage - 1)}" ${catalogPage === 1 ? 'disabled' : ''} aria-label="Previous page">‹</button>
                ${Array.from({length: pageCount}, (_, index) => `<button type="button" class="${catalogPage === index + 1 ? 'active' : ''}" data-page="${index + 1}">${index + 1}</button>`).join('')}
                <button type="button" data-page="${Math.min(pageCount, catalogPage + 1)}" ${catalogPage === pageCount ? 'disabled' : ''} aria-label="Next page">›</button>
            `;
        }

        async function loadCatalogItems() {
            try {
                const response = await fetch('api/items.php?action=list');
                const result = await response.json();
                catalogItems = Array.isArray(result) ? result : [];
                populateCatalogFilters(catalogItems.length ? catalogItems : fallbackItems);
                selectCatalogItem((catalogItems.length ? catalogItems : fallbackItems)[0]);
                renderCatalog();
            } catch (error) {
                catalogItems = [];
                populateCatalogFilters(fallbackItems);
                selectCatalogItem(fallbackItems[0]);
                renderCatalog();
            }
        }

        [catalogSearch, catalogCategoryFilter, catalogLocationFilter, catalogStatusFilter].forEach((control) => {
            control.addEventListener('input', () => {
                catalogPage = 1;
                renderCatalog();
            });
            control.addEventListener('change', () => {
                catalogPage = 1;
                renderCatalog();
            });
        });
        catalogGrid.addEventListener('click', (event) => {
            const card = event.target.closest('[data-catalog-id]');
            if (!card) return;
            const item = filteredCatalogItems.find(entry => String(entry.id ?? entry.name) === card.dataset.catalogId);
            if (item) {
                selectCatalogItem(item);
            }
        });
        catalogPages.addEventListener('click', (event) => {
            const button = event.target.closest('[data-page]');
            if (!button || button.disabled) return;
            catalogPage = Number(button.dataset.page);
            renderCatalog();
        });
        catalogDetailsClose.addEventListener('click', closeCatalogDetails);
        catalogClaimButton.addEventListener('click', openClaimModal);
        if (catalogArchiveButton) {
            catalogArchiveButton.addEventListener('click', archiveSelectedCatalogItem);
        }
        if (catalogClaimOwnerButton) {
            catalogClaimOwnerButton.addEventListener('click', markSelectedCatalogItemClaimed);
        }
        catalogClaimForm.addEventListener('submit', submitCatalogClaim);
        document.querySelectorAll('[data-claim-close]').forEach((button) => {
            button.addEventListener('click', closeClaimModal);
        });
        if (canManageCatalog && catalogAdminForm) {
            catalogAdminForm.addEventListener('submit', submitAdminCatalogItem);
            catalogAdminForm.addEventListener('reset', () => {
                window.setTimeout(() => {
                    if (adminImagePreview) {
                        adminImagePreview.classList.add('is-empty');
                        adminImagePreview.innerHTML = '<label for="adminItemImages"><span>+</span><small>Add images</small></label>';
                    }
                    if (catalogAdminStatus) {
                        catalogAdminStatus.textContent = '';
                        catalogAdminStatus.className = 'catalog-admin-status';
                    }
                    if (adminDescriptionCount) adminDescriptionCount.textContent = '0';
                }, 0);
            });
            if (adminItemImages) {
                adminItemImages.addEventListener('change', renderAdminImagePreview);
            }
            if (adminItemDescription && adminDescriptionCount) {
                adminItemDescription.addEventListener('input', () => {
                    adminDescriptionCount.textContent = String(adminItemDescription.value.length);
                });
            }
            if (adminImagePreview) {
                adminImagePreview.classList.add('is-empty');
                adminImagePreview.innerHTML = '<label for="adminItemImages"><span>+</span><small>Add images</small></label>';
            }
        }
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !catalogClaimModal.hidden) {
                closeClaimModal();
                return;
            }
            if (event.key === 'Escape' && selectedCatalogItem) closeCatalogDetails();
        });
        loadCatalogItems();
    </script>
</body>
</html>
