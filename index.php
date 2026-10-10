<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require_once 'config.php';

if (isset($_GET['logout'])) {
    header('Location: logout.php');
    exit;
}

if (isLoggedIn() && getRole() === ROLE_GUEST) {
    header('Location: MainMenu.php');
    exit;
}

if (!isset($_GET['intro']) || $_GET['intro'] !== 'done') {
    header('Location: loading_page.php?next=index.php%3Fintro%3Ddone');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UB FIND - Centralized Campus Item Management</title>
    <link rel="icon" href="favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicon-16.png">
    <link rel="apple-touch-icon" href="assets/apple-touch-icon.png">
    <script>
        if (window.location.search === '?intro=done') {
            window.history.replaceState(null, '', 'index.php');
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>
<body>
    <div class="page-body">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="background/ub.png" alt="University of Batangas seal" class="brand-icon">
                <span class="brand-copy">
                    <span class="brand-title">UB FIND</span>
                    <span class="navbar-caption">Lost and Found System</span>
                </span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <a class="nav-link active" href="#" id="homeBtn">Home</a>
                    <a class="nav-link" href="#" id="reportItemsNavBtn">Report Item</a>
                    <a class="nav-link d-none" href="#" id="dashboardBtn">Dashboard</a>
                    <a class="nav-link" href="#" id="searchItemsNavBtn">Search Items</a>
                    <a class="nav-link" href="#" id="loginBtn">Login</a>
                    <a class="nav-link d-none" href="#" id="logoutBtn">Logout</a>
                    <span class="nav-link d-none" id="userRole"></span>
                </div>
            </div>
        </div>
    </nav>

    <!-- View Mode Content (shown by default for guests) -->
    <div id="viewMode">
        <!-- Hero + search bar: centered content -->
        <div class="container page-main">
            <div class="row align-items-center gy-4 bg-white p-4 p-md-5 mb-4">
                    <div class="col-lg-7 hero-text">
                        <h2>Lost Something?</h2>
                        <p class="hero-tagline mb-3">We'll Help You Find It.</p>
                        <div class="hero-underline mb-4"></div>
                        <p>UB FIND is a centralized system for reporting, searching, and recovering lost items within the University of Batangas.</p>
                        <div class="hero-buttons mt-4">
                            <button class="btn btn-danger btn-lg" id="reportItemBtn">
                                <span class="btn-icon">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 3H14L18 7V21H6V3Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 3V7H18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 13H15" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M9 17H15" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M9 9H12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                                </span>
                                REPORT ITEM
                            </button>
                            <button class="btn btn-outline-danger btn-lg" id="searchItemsBtn">
                                <span class="btn-icon">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.8"/><path d="M16.5 16.5L20 20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                                </span>
                                SEARCH ITEMS
                            </button>
                        </div>
                    </div>
                    <div class="col-lg-5 hero-visual text-center">
                        <div class="hero-image-card">
                            <img id="heroImage" src="background/Homepage Picture.png" alt="Homepage" class="img-fluid hero-image">
                        </div>
                    </div>
            </div>
        </div><!-- /page-main -->

        <!-- WHAT YOU CAN DO – edge-to-edge, grows to fill available space -->
        <div class="app-features">
            <div class="container">
                <p class="feature-label">WHAT YOU CAN DO</p>
                <div class="row g-2">
                    <div class="col-6 col-md-3">
                        <div class="feature-card">
                            <div class="feature-card-icon">
                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 2v6h6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 13h6" stroke="currentColor" stroke-width="1.6"/></svg>
                            </div>
                            <h5>Report Items</h5>
                            <p>Easily report lost or found.</p>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="feature-card">
                            <div class="feature-card-icon">
                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.6"/><path d="M16.5 16.5L20 20" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                            </div>
                            <h5>Search Items</h5>
                            <p>Find items in the database.</p>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="feature-card">
                            <div class="feature-card-icon">
                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.4"/><path d="M8 12l2.5 2.5L16 9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                            </div>
                            <h5>Track Status</h5>
                            <p>Check item status updates.</p>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="feature-card">
                            <div class="feature-card-icon">
                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="3" y="11" width="18" height="10" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="M7 11V8a5 5 0 0 1 10 0v3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                            </div>
                            <h5>Secure &amp; Reliable</h5>
                            <p>Your data is safe with us.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <footer class="app-footer text-white">
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
                    <div class="col-md-4 footer-col text-center">
                        <div class="footer-block">
                            <img src="assets/icons/email.svg" class="footer-icon" alt="Email">
                            <div class="footer-heading">Contact</div>
                            <p class="mb-0">
                                <span>
                                    <svg class="footer-inline-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M22 6l-10 7L2 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    ubfind@ub.edu.ph
                                </span>
                                <span>
                                    <svg class="footer-inline-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" stroke="currentColor" stroke-width="1.5"/><path d="M2 12h20" stroke="currentColor" stroke-width="1.5"/></svg>
                                    www.ub.edu.ph
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    </div><!-- /viewMode -->

    <!-- Dashboard (hidden until login) -->
    <div id="dashboard" class="d-none container mt-4">
        <div class="dashboard-hero mb-5">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h2>Lost Something?</h2>
                    <h3>We'll Help You Find It.</h3>
                    <p>UB FIND helps students, administrators, and campus visitors report lost or found items, search the database, and recover belongings with ease.</p>
                    <div class="hero-buttons">
                        <button class="btn btn-danger" id="searchDashBtn">SEARCH ITEMS</button>
                        <button class="btn btn-outline-danger" id="reportDashBtn">REPORT ITEM</button>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <div class="dashboard-logo">
                        <h1>UB FIND</h1>
                        <p>we find way</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="dashboard-content">
            <h3 id="dashTitle">Dashboard</h3>
            <div class="row">
                <div class="col-md-6">
                    <h4>Report Item</h4>
                    <form id="reportForm">
                        <select class="form-select mb-2" name="type" required>
                            <option value="">Lost or Found?</option>
                            <option value="lost">Lost</option>
                            <option value="found">Found</option>
                        </select>
                        <input type="text" class="form-control mb-2" placeholder="Item Name" name="name" required>
                        <input type="text" class="form-control mb-2" placeholder="Category" name="category">
                        <textarea class="form-control mb-2" placeholder="Description" name="description" rows="3"></textarea>
                        <input type="date" class="form-control mb-2" name="date_event" required>
                        <input type="text" class="form-control mb-2" placeholder="Location" name="location" required>
                        <input type="file" class="form-control mb-2" accept="image/*" name="image">
                        <button type="submit" class="btn btn-success">Report</button>
                    </form>
                </div>
                <div class="col-md-6">
                    <h4>Items List</h4>
                    <div id="fullItemsList"></div>
                </div>
</div>
            <div id="adminSection" class="d-none mt-4">
                <h4>Claims</h4>
                <div id="claimsList"></div>
            </div>
        </div>
    </div>

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Login with UB Email</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="loginForm">
                        <div class="mb-3">
                            <input type="email" class="form-control" placeholder="your@ub.edu.ph" name="email" required>
                        </div>
                        <div class="mb-3">
                            <input type="password" class="form-control" placeholder="Password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/script.js"></script>
</body>
</html>
