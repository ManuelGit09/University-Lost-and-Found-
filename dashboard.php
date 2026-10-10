<?php
session_start();
require 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - UB Find</title>
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
            <a class="navbar-brand" href="index.php">UB Find</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">Report Item</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="search.php">Search Items</a>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link" id="userRole"></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-light px-4 ms-2" href="index.php?logout" id="logoutBtn">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <script>
            window.UBF_ROLE = <?php echo json_encode($_SESSION['role'] ?? ''); ?>;
        </script>

        <div class="dashboard-hero mb-4">
            <div class="card hero-card shadow-sm dashboard-hero-card">
                <div class="hero-media">
                    <img src="background/Homepage Picture.png" alt="Homepage Picture" class="img-fluid">
                    <div class="hero-overlay">
                        <h1>Lost Something?</h1>
                        <p>We'll help you find it across the University of Batangas campus.</p>
                        <div class="hero-actions">
                            <button type="button" class="btn btn-primary btn-lg me-2" onclick="scrollToReport()">Report Item</button>
                            <button type="button" class="btn btn-outline-light btn-lg" onclick="window.location.href='search.php'">Search Items</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

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

        <!-- Admin Section -->
        <div id="adminSection" class="d-none mt-4">
            <hr>
            <h4>Claims Management</h4>
            <div id="claimsList"></div>
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
                <div class="col-md-4 footer-col text-center">
                    <div class="footer-block">
                        <img src="assets/icons/email.svg" class="footer-icon" alt="Email">
                        <div class="footer-heading">Contact</div>
                        <p class="mb-0">
                            <span>
                                <svg class="footer-inline-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M22 6l-10 7L2 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                ubfind@ub.edu.ph
                            </span>
                            <span>
                                <svg class="footer-inline-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/>
                                    <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" stroke="currentColor" stroke-width="1.5"/>
                                    <path d="M2 12h20" stroke="currentColor" stroke-width="1.5"/>
                                </svg>
                                www.ub.edu.ph
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/dashboard.js"></script>
</body>
</html>


