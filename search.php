<?php
session_start();
require 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Items - UB Find</title>
    <link rel="icon" href="favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicon-16.png">
    <link rel="apple-touch-icon" href="assets/apple-touch-icon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg custom-navbar">
        <div class="container">
            <a class="navbar-brand" href="MainMenu.php">UB Find</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="MainMenu.php">Home</a>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">Report Item</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link active" href="search.php">Search Items</a>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <span class="nav-link" id="userRole"></span>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-outline-light px-4 ms-2" href="index.php?logout">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <button type="button" class="nav-link btn btn-login px-4 ms-2" id="loginBtn" data-bs-toggle="modal" data-bs-target="#loginModal">Login</button>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5 mb-5">
        <div class="row">
            <div class="col-lg-8 offset-lg-2">
                <h2 class="mb-4">Search Lost & Found Items</h2>

                <form id="searchForm" class="card p-4 shadow-sm mb-4">
                    <h5 class="mb-3">Filter Items</h5>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <input type="text" class="form-control" placeholder="Keyword" name="keyword">
                        </div>
                        <div class="col-md-3">
                            <select class="form-control" name="type">
                                <option value="">All Types</option>
                                <option value="lost">Lost</option>
                                <option value="found">Found</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-control" name="status">
                                <option value="">All Status</option>
                                <option value="open">Open</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">Search</button>
                        </div>
                    </div>
                </form>

                <div id="itemsList" class="mb-5">
                    <p class="text-muted">Enter search terms to find items.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content login-modal">
                <div class="modal-header border-0">
                    <div>
                        <h5 class="modal-title">Admin Login</h5>
                        <p class="modal-subtitle mb-0">Admin portal access only</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="login-description">Enter admin credentials to access dashboard, reporting, and claim management.</p>
                    <form id="loginForm">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" placeholder="your@ub.edu.ph" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" placeholder="Password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                    <div class="login-features mt-4">
                        <p class="mb-2 fw-bold">Included features:</p>
                        <ul class="feature-list mb-0">
                            <li>Report lost or found items</li>
                            <li>Search the campus item database</li>
                            <li>Manage claims and update item status</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="app-footer text-white py-5">
        <div class="container">
            <div class="row gy-4 align-items-center">
                <div class="col-md-4 footer-col text-center text-md-start">
                    <div class="footer-brand">
                        <img src="assets/icons/location.svg" class="footer-icon" alt="UB">
                        <div class="footer-brand-title">University of Batangas</div>
                        <div class="footer-brand-subtitle">Lipa City</div>
                    </div>
                </div>
                <div class="col-md-4 footer-col text-center">
                    <div class="footer-block">
                        <img src="assets/icons/location.svg" class="footer-icon" alt="Location">
                        <div class="footer-heading">Location</div>
                        <p class="mb-0">University of Batangas<br>Lipa City, Batangas<br>Philippines</p>
                    </div>
                </div>
                <div class="col-md-4 footer-col text-center text-md-end">
                    <div class="footer-block">
                        <img src="assets/icons/email.svg" class="footer-icon" alt="Email">
                        <div class="footer-heading">Contact</div>
                        <p class="mb-0">ubfind@ub.edu.ph<br>www.ub.edu.ph</p>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const API_BASE = 'api/';
        const userRole = '<?php echo $_SESSION['role'] ?? ''; ?>';
        
        document.addEventListener('DOMContentLoaded', () => {
            if (userRole) {
                document.getElementById('userRole').textContent = ` | ${userRole.toUpperCase()}`;
            }
            
            document.getElementById('searchForm').addEventListener('submit', handleSearch);
            
            const loginForm = document.getElementById('loginForm');
            if (loginForm) {
                loginForm.addEventListener('submit', handleLogin);
            }
        });

        async function apiCall(endpoint, data = null, method = 'GET') {
            const opts = { method };
            if (data) {
                opts.body = data;
            }
            const res = await fetch(API_BASE + endpoint, opts);
            return await res.json();
        }

        async function handleSearch(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const params = new URLSearchParams(formData).toString();
            try {
                const res = await apiCall(`search.php?${params}`);
                renderItems(res, document.getElementById('itemsList'));
            } catch (err) {
                document.getElementById('itemsList').innerHTML = '<p class="text-danger">Error loading items. Please try again.</p>';
            }
        }

        async function handleLogin(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const res = await apiCall('auth.php?action=login', formData, 'POST');
            if (res.success) {
                window.location.href = 'MainMenu.php';
            } else {
                alert(res.error || 'Login failed');
            }
        }

        function renderItems(items, container) {
            if (!Array.isArray(items)) {
                items = (items && items.items) ? items.items : [];
            }
            if (items.length === 0) {
                container.innerHTML = '<p class="text-muted">No items found matching your search.</p>';
                return;
            }
            container.innerHTML = items.map(item => `
                <div class="card mb-3 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h5 class="card-title">${item.name}</h5>
                                <p class="card-text mb-2">${item.description || ''}</p>
                                <p class="mb-2">
                                    <span class="badge bg-${item.type === 'lost' ? 'danger' : 'success'}">
                                        ${item.type === 'lost' ? '❌ Lost' : '✅ Found'}
                                    </span>
                                </p>
                                <small class="text-muted">
                                    <strong>Location:</strong> ${item.location}<br>
                                    <strong>Date:</strong> ${item.date_event || 'N/A'}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
        }
    </script>
</body>
</html>
