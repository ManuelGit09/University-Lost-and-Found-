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
$isAdminReviewer = canReviewClaims();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status - UB FIND</title>
    <link rel="icon" href="favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicon-16.png">
    <link rel="apple-touch-icon" href="assets/apple-touch-icon.png">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>
<body <?= getThemeBodyAttributes('status-page') ?>>
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
            <a class="main-nav-link active" href="status.php">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.7"/><path d="m8 12 2.5 2.5L16 9" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Status
            </a>
            <a class="main-nav-link" href="catalog.php">
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

    <main class="main-menu-shell status-shell">
        <header class="main-topbar">
            <h1>Status</h1>
            <div class="main-top-actions">
                <?= renderNotificationWidget() ?>
                <?= renderMessageWidget() ?>
                <?= renderAccountWidget($userName) ?>
            </div>
        </header>

        <section class="status-hero">
            <div>
                <span>Lost and Found Center</span>
                <h2>Track Item Status</h2>
                <p>Monitor your lost and found reports in real time.</p>
            </div>
        </section>

        <section class="status-summary-grid" aria-label="Status summary">
            <article class="status-summary-card summary-total">
                <span class="status-summary-icon">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 3h7l3 3v15H7V3Z" stroke="currentColor" stroke-width="1.7"/><path d="M14 3v4h4M10 11h4M10 15h4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                </span>
                <div>
                    <small>Total Reports</small>
                    <strong id="totalReportsCount">0</strong>
                    <p>All time reports</p>
                </div>
            </article>
            <article class="status-summary-card summary-open">
                <span class="status-summary-icon">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 4h8M8 20h8M9 4c0 4 6 4 6 8s-6 4-6 8M15 4c0 4-6 4-6 8s6 4 6 8" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                </span>
                <div>
                    <small>Open Cases</small>
                    <strong id="openCasesCount">0</strong>
                    <p>Currently open</p>
                </div>
            </article>
            <article class="status-summary-card summary-found">
                <span class="status-summary-icon">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.7"/><path d="m8.5 12 2.2 2.2 4.8-5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <div>
                    <small>Found Items</small>
                    <strong id="foundItemsCount">0</strong>
                    <p>Successfully found</p>
                </div>
            </article>
            <article class="status-summary-card summary-claimed">
                <span class="status-summary-icon">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 9h10l1 11H6L7 9Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M9 9V7a3 3 0 0 1 6 0v2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                </span>
                <div>
                    <small>Claimed Items</small>
                    <strong id="claimedItemsCount">0</strong>
                    <p>Returned to owners</p>
                </div>
            </article>
        </section>

        <section class="status-card" aria-labelledby="statusTitle">
            <h2 class="sr-only" id="statusTitle">Item status records</h2>
            <div class="status-card-header">
                <label class="status-search-field">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.8"/><path d="m16 16 4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    <input type="search" id="statusSearch" placeholder="Search by Item ID, Item Type..." autocomplete="off">
                </label>
                <div class="status-actions">
                    <select id="statusFilter" aria-label="Filter status">
                        <option value="">Filter</option>
                        <option value="open">Open</option>
                        <option value="pending">Pending</option>
                        <option value="claimed">Claimed</option>
                        <option value="closed">Closed</option>
                    </select>
                    <button id="downloadStatusReport" type="button">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3v12m0 0 4-4m-4 4-4-4M5 20h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Download Report
                    </button>
                </div>
            </div>

            <div class="status-table-wrap">
                <table class="status-table">
                    <thead>
                        <tr>
                            <th>Item ID</th>
                            <th>Item Type</th>
                            <th>Report Type</th>
                            <th>Date Submitted</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="statusTableBody">
                        <tr><td colspan="6">Loading status...</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="status-table-footer">
                <span id="statusEntryCount">Showing 0 entries</span>
                <div class="status-pagination" aria-label="Pagination">
                    <button type="button" aria-label="Previous page">&lsaquo;</button>
                    <button type="button" class="active">1</button>
                    <button type="button">2</button>
                    <button type="button">3</button>
                    <span>...</span>
                    <button type="button">5</button>
                    <button type="button" aria-label="Next page">&rsaquo;</button>
                </div>
            </div>
        </section>

        <?php if (!$isAdminReviewer): ?>
            <section class="status-card claim-message-card" aria-labelledby="claimMessagesTitle">
                <div class="status-card-header">
                    <div>
                        <h2 id="claimMessagesTitle">Claim Messages</h2>
                        <p>Messages between claimants and administrators about submitted claims.</p>
                    </div>
                </div>
                <div id="claimMessagesList" class="claim-messages-list" aria-live="polite">
                    Loading claim messages...
                </div>
            </section>
        <?php else: ?>
            <section class="status-card claim-message-card" aria-labelledby="claimMessagesTitle">
                <div class="status-card-header">
                    <div>
                        <h2 id="claimMessagesTitle">Admin Claim Review</h2>
                        <p>Claim messages are managed from the admin claim verification panel.</p>
                    </div>
                    <a class="status-admin-review-link" href="admin.php#claim-verification">Open Claim Verification</a>
                </div>
            </section>
        <?php endif; ?>
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

    <div class="status-modal" id="statusModal" hidden>
        <button class="status-modal-backdrop" type="button" aria-label="Close status details"></button>
        <article class="status-modal-card" role="dialog" aria-modal="true" aria-labelledby="statusModalTitle">
            <button class="status-modal-close" type="button" aria-label="Close status details">&times;</button>
            <h2 id="statusModalTitle">Item Details</h2>
            <div id="statusModalBody"></div>
        </article>
    </div>

    <script>
        const statusTableBody = document.getElementById('statusTableBody');
        const statusFilter = document.getElementById('statusFilter');
        const statusSearch = document.getElementById('statusSearch');
        const downloadStatusReport = document.getElementById('downloadStatusReport');
        const totalReportsCount = document.getElementById('totalReportsCount');
        const openCasesCount = document.getElementById('openCasesCount');
        const foundItemsCount = document.getElementById('foundItemsCount');
        const claimedItemsCount = document.getElementById('claimedItemsCount');
        const statusEntryCount = document.getElementById('statusEntryCount');
        const statusModal = document.getElementById('statusModal');
        const statusModalBody = document.getElementById('statusModalBody');
        const claimMessagesList = document.getElementById('claimMessagesList');
        const isAdminReviewer = <?= json_encode($isAdminReviewer) ?>;
        let statusItems = [];
        let filteredStatusItems = [];
        let currentStatusPage = 1;
        const statusPageSize = 5;

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, char => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[char]));
        }

        function formatDate(value) {
            if (!value) return '';
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) return value;
            return date.toLocaleDateString('en-US', { month: '2-digit', day: '2-digit', year: '2-digit' });
        }

        function renderStatusRows() {
            const query = statusSearch.value.trim().toLowerCase();
            filteredStatusItems = statusItems.filter(item => {
                const status = (item.status || 'open').toLowerCase();
                const id = `UBLC-${String(item.id || 0).padStart(4, '0')}`;
                const text = `${id} ${item.name || ''} ${item.category || ''} ${item.type || ''} ${status}`.toLowerCase();
                return (!statusFilter.value || status === statusFilter.value) && (!query || text.includes(query));
            });

            const totalPages = Math.max(1, Math.ceil(filteredStatusItems.length / statusPageSize));
            currentStatusPage = Math.min(currentStatusPage, totalPages);
            const startIndex = (currentStatusPage - 1) * statusPageSize;
            const visible = filteredStatusItems.slice(startIndex, startIndex + statusPageSize);

            statusTableBody.innerHTML = visible.map(item => {
                const id = `UBLC-${String(item.id || 0).padStart(4, '0')}`;
                const reportType = item.type === 'found' ? 'Found' : 'Lost';
                const itemName = item.name || item.category || 'Item';
                const status = item.status || 'open';
                return `
                    <tr>
                        <td>
                            <span class="status-item-cell">
                                <span class="status-item-icon">${escapeHtml(itemName.charAt(0).toUpperCase())}</span>
                                <strong>${escapeHtml(id)}</strong>
                            </span>
                        </td>
                        <td>${escapeHtml(itemName)}</td>
                        <td>${reportType}</td>
                        <td><span class="status-date-cell">${escapeHtml(formatDate(item.created_at || item.date_event))}</span></td>
                        <td><span class="status-badge status-badge-${escapeHtml(status.toLowerCase())}">${escapeHtml(status)}</span></td>
                        <td>
                            <span class="status-row-actions">
                                <button type="button" data-status-action="view" data-item-id="${escapeHtml(item.id || '')}" aria-label="View ${escapeHtml(itemName)}">
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="1.7"/><circle cx="12" cy="12" r="2.6" stroke="currentColor" stroke-width="1.7"/></svg>
                                </button>
                                <button type="button" data-status-action="more" data-item-id="${escapeHtml(item.id || '')}" aria-label="More actions">
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 6h.01M12 12h.01M12 18h.01" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                                </button>
                            </span>
                        </td>
                    </tr>
                `;
            }).join('') || '<tr><td colspan="6">No status records found.</td></tr>';

            const visibleStart = visible.length ? startIndex + 1 : 0;
            const visibleEnd = startIndex + visible.length;
            statusEntryCount.textContent = `Showing ${visibleStart} to ${visibleEnd} of ${filteredStatusItems.length} entries`;
            renderStatusPagination(totalPages);
        }

        function renderStatusPagination(totalPages) {
            const pagination = document.querySelector('.status-pagination');
            let buttons = `<button type="button" data-page-action="prev" ${currentStatusPage === 1 ? 'disabled' : ''} aria-label="Previous page">&lsaquo;</button>`;
            for (let page = 1; page <= totalPages; page += 1) {
                if (totalPages > 5 && page > 3 && page < totalPages) {
                    if (page === 4) buttons += '<span>...</span>';
                    continue;
                }
                buttons += `<button type="button" data-page="${page}" class="${page === currentStatusPage ? 'active' : ''}">${page}</button>`;
            }
            buttons += `<button type="button" data-page-action="next" ${currentStatusPage === totalPages ? 'disabled' : ''} aria-label="Next page">&rsaquo;</button>`;
            pagination.innerHTML = buttons;
        }

        function findStatusItem(id) {
            return statusItems.find(item => String(item.id) === String(id));
        }

        function openStatusModal(item) {
            if (!item) return;
            const status = item.status || 'open';
            statusModalBody.innerHTML = `
                <dl class="status-detail-list">
                    <div><dt>Item ID</dt><dd>UBLC-${String(item.id || 0).padStart(4, '0')}</dd></div>
                    <div><dt>Item Type</dt><dd>${escapeHtml(item.name || item.category || 'Item')}</dd></div>
                    <div><dt>Report Type</dt><dd>${item.type === 'found' ? 'Found' : 'Lost'}</dd></div>
                    <div><dt>Date Submitted</dt><dd>${escapeHtml(formatDate(item.created_at || item.date_event))}</dd></div>
                    <div><dt>Status</dt><dd><span class="status-badge status-badge-${escapeHtml(status.toLowerCase())}">${escapeHtml(status)}</span></dd></div>
                    <div><dt>Location</dt><dd>${escapeHtml(item.location || 'N/A')}</dd></div>
                    <div><dt>Description</dt><dd>${escapeHtml(item.description || 'No description provided.')}</dd></div>
                </dl>
            `;
            statusModal.hidden = false;
        }

        function closeStatusModal() {
            statusModal.hidden = true;
        }

        function showMoreActions(button, item) {
            document.querySelectorAll('.status-more-menu').forEach(menu => menu.remove());
            if (!item) return;
            const menu = document.createElement('div');
            menu.className = 'status-more-menu';
            menu.innerHTML = `
                <button type="button" data-more-action="copy">Copy Item ID</button>
                <button type="button" data-more-action="details">View Details</button>
            `;
            button.closest('.status-row-actions').appendChild(menu);
            menu.querySelector('[data-more-action="copy"]').addEventListener('click', async () => {
                await navigator.clipboard.writeText(`UBLC-${String(item.id || 0).padStart(4, '0')}`);
                menu.remove();
            });
            menu.querySelector('[data-more-action="details"]').addEventListener('click', () => {
                menu.remove();
                openStatusModal(item);
            });
        }

        function updateStatusSummary() {
            totalReportsCount.textContent = statusItems.length;
            openCasesCount.textContent = statusItems.filter(item => (item.status || 'open').toLowerCase() === 'open').length;
            foundItemsCount.textContent = statusItems.filter(item => item.type === 'found').length;
            claimedItemsCount.textContent = statusItems.filter(item => (item.status || '').toLowerCase() === 'claimed').length;
        }

        async function loadStatusItems() {
            try {
                const response = await fetch('api/items.php?action=list');
                const result = await response.json();
                statusItems = Array.isArray(result) ? result : [];
                updateStatusSummary();
                renderStatusRows();
            } catch (error) {
                statusTableBody.innerHTML = '<tr><td colspan="6">Could not load status.</td></tr>';
            }
        }

        function renderClaimMessages(claims) {
            if (!Array.isArray(claims) || !claims.length) {
                claimMessagesList.innerHTML = '<p class="claim-message-empty">No claim conversations yet.</p>';
                return;
            }

            claimMessagesList.innerHTML = claims.map((claim) => {
                const itemName = claim.name || claim.item?.name || `Claim #${claim.id}`;
                const status = (claim.status || 'pending').toLowerCase();
                const messages = Array.isArray(claim.messages) ? claim.messages : [];
                const thread = messages.length
                    ? messages.map((message) => `
                        <p class="claim-message-bubble">
                            <strong>${escapeHtml(message.sender_name || 'User')} <span>${escapeHtml(message.sender_role || '')}</span></strong>
                            ${escapeHtml(message.message || '')}
                            <small>${escapeHtml(formatDate(message.created_at || ''))}</small>
                        </p>
                    `).join('')
                    : '<p class="claim-message-empty">No messages for this claim yet.</p>';

                return `
                    <article class="claim-message-thread">
                        <div class="claim-message-heading">
                            <div>
                                <strong>${escapeHtml(itemName)}</strong>
                                <span>Status: ${escapeHtml(claim.status || 'pending')}</span>
                            </div>
                        </div>
                        <div class="claim-message-thread-body">${thread}</div>
                        ${['approved', 'rejected'].includes(status)
                            ? `<p class="claim-message-closed">Conversation closed because this claim was ${escapeHtml(status)}.</p>`
                            : `<form class="claim-message-form" data-claim-id="${escapeHtml(claim.id || '')}">
                                <textarea name="message" rows="2" placeholder="Reply to admin"></textarea>
                                <button type="submit">Send Reply</button>
                            </form>`
                        }
                    </article>
                `;
            }).join('');
        }

        async function loadClaimMessages() {
            try {
                const response = await fetch('api/claims.php?action=list');
                const claims = await response.json();
                renderClaimMessages(Array.isArray(claims) ? claims : []);
            } catch (error) {
                claimMessagesList.innerHTML = '<p class="claim-message-empty">Could not load claim messages.</p>';
            }
        }

        if (claimMessagesList) claimMessagesList.addEventListener('submit', async (event) => {
            const form = event.target.closest('.claim-message-form');
            if (!form) return;
            event.preventDefault();

            const textarea = form.querySelector('textarea');
            const message = textarea.value.trim();
            if (!message) {
                textarea.focus();
                return;
            }

            const data = new FormData();
            data.append('claim_id', form.dataset.claimId);
            data.append('message', message);
            form.querySelector('button').disabled = true;

            try {
                const response = await fetch('api/claims.php?action=message', {
                    method: 'POST',
                    body: data
                });
                const result = await response.json();
                if (!result.success) {
                    throw new Error(result.error || 'Could not send reply.');
                }
                textarea.value = '';
                loadClaimMessages();
            } catch (error) {
                alert(error.message || 'Could not send reply.');
            } finally {
                form.querySelector('button').disabled = false;
            }
        });

        downloadStatusReport.addEventListener('click', () => {
            const rows = [['Item ID', 'Item Type', 'Report Type', 'Date Submitted', 'Status']];
            statusItems.forEach(item => {
                rows.push([
                    `UBLC-${String(item.id || 0).padStart(4, '0')}`,
                    item.name || item.category || 'Item',
                    item.type === 'found' ? 'Found' : 'Lost',
                    formatDate(item.created_at || item.date_event),
                    item.status || 'open'
                ]);
            });

            const csv = rows.map(row => row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(',')).join('\n');
            const link = document.createElement('a');
            link.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv' }));
            link.download = 'ub-find-status-report.csv';
            link.click();
            URL.revokeObjectURL(link.href);
        });

        statusTableBody.addEventListener('click', (event) => {
            const button = event.target.closest('[data-status-action]');
            if (!button) return;
            const item = findStatusItem(button.dataset.itemId);
            if (button.dataset.statusAction === 'view') {
                openStatusModal(item);
            } else {
                showMoreActions(button, item);
            }
        });

        document.querySelector('.status-pagination').addEventListener('click', (event) => {
            const button = event.target.closest('button');
            if (!button || button.disabled) return;
            const totalPages = Math.max(1, Math.ceil(filteredStatusItems.length / statusPageSize));
            if (button.dataset.pageAction === 'prev') currentStatusPage -= 1;
            if (button.dataset.pageAction === 'next') currentStatusPage += 1;
            if (button.dataset.page) currentStatusPage = Number(button.dataset.page);
            currentStatusPage = Math.max(1, Math.min(currentStatusPage, totalPages));
            renderStatusRows();
        });

        statusModal.addEventListener('click', (event) => {
            if (event.target.matches('.status-modal-backdrop, .status-modal-close')) closeStatusModal();
        });

        document.addEventListener('click', (event) => {
            if (!event.target.closest('.status-row-actions')) {
                document.querySelectorAll('.status-more-menu').forEach(menu => menu.remove());
            }
        });

        statusFilter.addEventListener('change', () => {
            currentStatusPage = 1;
            renderStatusRows();
        });
        statusSearch.addEventListener('input', () => {
            currentStatusPage = 1;
            renderStatusRows();
        });
        loadStatusItems();
        if (!isAdminReviewer) {
            loadClaimMessages();
        }
    </script>
</body>
</html>
