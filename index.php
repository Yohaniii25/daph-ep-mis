<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id']) && $_SESSION['logged_in']) {
    header("Location: dashboard.php");
    exit();
}

require_once './config/db_connect.php';
$login_error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']); // Clear errors on reload
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DAPH Eastern Province | Staff Login Portal</title>

    <link rel="icon" type="image/png" href="assets/img/favicon.png">

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Core CSS & Icons -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --primary-maroon: #370709;
            --primary-accent: #500707;
            --maroon-gradient: linear-gradient(135deg, #6B0F1A 0%, #370709 100%);
            --gold-accent: #d97706;
            --glass-bg: rgba(255, 255, 255, 0.96);
            --glass-border: rgba(255, 255, 255, 0.6);
            --input-border: #cbd5e1;
        }

        * {
            box-sizing: border-box;
        }

        body.gov-portal-body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, rgba(25, 2, 4, 0.88) 0%, rgba(55, 7, 9, 0.82) 50%, rgba(18, 2, 3, 0.94) 100%),
                        url('assets/img/bg.png') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow-x: hidden;
            color: #1e293b;
        }

        /* Ambient Animated Glow Effects */
        .ambient-orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.28;
            pointer-events: none;
            z-index: 0;
        }

        .ambient-orb-1 {
            width: 420px;
            height: 420px;
            background: radial-gradient(circle, #b91c1c, #450a0a);
            top: -100px;
            left: -100px;
            animation: orbFloat1 18s ease-in-out infinite alternate;
        }

        .ambient-orb-2 {
            width: 380px;
            height: 380px;
            background: radial-gradient(circle, #d97706, #78350f);
            bottom: -80px;
            right: -80px;
            animation: orbFloat2 22s ease-in-out infinite alternate;
        }

        @keyframes orbFloat1 {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(80px, 60px) scale(1.12); }
        }

        @keyframes orbFloat2 {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(-60px, -70px) scale(1.15); }
        }

        /* Top Government Bar */
        .top-gov-bar {
            position: relative;
            z-index: 10;
            padding: 0.75rem 1.5rem;
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .gov-emblem-text {
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.03em;
            color: rgba(255, 255, 255, 0.85);
        }

        .ssl-badge {
            font-size: 0.72rem;
            font-weight: 600;
            color: #86efac;
            background: rgba(34, 197, 94, 0.12);
            border: 1px solid rgba(34, 197, 94, 0.25);
            padding: 0.3rem 0.75rem;
            border-radius: 9999px;
            letter-spacing: 0.02em;
        }

        /* Main Container & Glass Card */
        .portal-main-wrapper {
            position: relative;
            z-index: 5;
            flex: 1 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2.5rem 1rem;
        }

        .login-card {
            width: 100%;
            max-width: 480px;
            background: var(--glass-bg);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid var(--glass-border);
            border-radius: 22px;
            padding: 2.5rem 2.25rem;
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.45),
                        0 10px 25px -5px rgba(55, 7, 9, 0.18),
                        inset 0 1px 0 rgba(255, 255, 255, 0.8);
            position: relative;
            animation: cardAppear 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes cardAppear {
            0% {
                opacity: 0;
                transform: translateY(20px) scale(0.98);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Card Header & Branding */
        .login-logo-container {
            text-align: center;
            margin-bottom: 1.25rem;
        }

        .login-logo-img {
            max-width: 100%;
            width: 380px;
            height: auto;
            filter: drop-shadow(0 6px 14px rgba(0, 0, 0, 0.12));
            transition: transform 0.3s ease;
        }

        .login-logo-img:hover {
            transform: scale(1.015);
        }

        .portal-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #b91c1c;
            background: #fee2e2;
            border: 1px solid #fca5a5;
            padding: 0.3rem 0.85rem;
            border-radius: 9999px;
            margin-bottom: 0.75rem;
        }

        .portal-title {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--primary-maroon);
            letter-spacing: -0.02em;
            margin-bottom: 0.25rem;
        }

        .portal-subtitle {
            font-size: 0.84rem;
            color: #64748b;
            margin-bottom: 1.5rem;
        }

        /* Form Labels & Controls */
        .form-label-custom {
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #475569;
            margin-bottom: 0.4rem;
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .form-label-custom i {
            color: var(--primary-accent);
            font-size: 0.9rem;
        }

        .input-group-custom {
            border-radius: 12px;
            transition: all 0.25s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        .input-group-custom .input-group-text {
            background-color: #f8fafc;
            border: 1.5px solid var(--input-border);
            border-right: none;
            border-top-left-radius: 12px;
            border-bottom-left-radius: 12px;
            color: #64748b;
            padding: 0.7rem 0.95rem;
            font-size: 1rem;
            transition: border-color 0.2s ease, background-color 0.2s ease;
        }

        .input-group-custom .form-control,
        .input-group-custom .form-select {
            border: 1.5px solid var(--input-border);
            border-left: none;
            border-top-right-radius: 12px;
            border-bottom-right-radius: 12px;
            padding: 0.7rem 1rem;
            font-size: 0.93rem;
            font-weight: 500;
            color: #1e293b;
            background-color: #ffffff;
            transition: all 0.25s ease;
        }

        .input-group-custom .form-control:focus,
        .input-group-custom .form-select:focus {
            border-color: var(--primary-accent);
            background-color: #ffffff;
            box-shadow: none;
        }

        .input-group-custom:focus-within {
            box-shadow: 0 0 0 4px rgba(80, 7, 7, 0.12);
        }

        .input-group-custom:focus-within .input-group-text {
            border-color: var(--primary-accent);
            background-color: #fff1f2;
            color: var(--primary-accent);
        }

        .input-group-custom:focus-within .form-control,
        .input-group-custom:focus-within .form-select {
            border-color: var(--primary-accent);
        }

        /* Password Show/Hide Toggle */
        .btn-toggle-password {
            background-color: #ffffff;
            border: 1.5px solid var(--input-border);
            border-left: none;
            border-top-right-radius: 12px;
            border-bottom-right-radius: 12px;
            color: #64748b;
            padding: 0 1rem;
            font-size: 1rem;
            transition: color 0.2s ease, background-color 0.2s ease;
        }

        .btn-toggle-password:hover {
            color: var(--primary-accent);
            background-color: #f8fafc;
        }

        .input-group-custom:focus-within .btn-toggle-password {
            border-color: var(--primary-accent);
        }

        /* Dynamic Form Groups Animation */
        .dynamic-field-group {
            display: none;
            animation: slideDownFade 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes slideDownFade {
            from {
                opacity: 0;
                transform: translateY(-8px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Submit Button */
        .btn-portal-submit {
            background: var(--maroon-gradient);
            color: #ffffff;
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            border: none;
            border-radius: 12px;
            padding: 0.85rem 1.5rem;
            box-shadow: 0 10px 22px -6px rgba(107, 15, 26, 0.5);
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            overflow: hidden;
        }

        .btn-portal-submit::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: 0.6s;
        }

        .btn-portal-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 28px -6px rgba(107, 15, 26, 0.6);
            color: #ffffff;
        }

        .btn-portal-submit:hover::after {
            left: 100%;
        }

        .btn-portal-submit:active {
            transform: translateY(0);
        }

        /* Alert Styling */
        .portal-alert-danger {
            border-radius: 12px;
            font-size: 0.88rem;
            font-weight: 500;
            border: 1px solid #fecaca;
            background-color: #fef2f2;
            color: #991b1b;
            padding: 0.85rem 1.15rem;
            margin-bottom: 1.5rem;
            animation: alertShake 0.4s ease-in-out;
        }

        @keyframes alertShake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-6px); }
            40%, 80% { transform: translateX(6px); }
        }

        /* Footer */
        .portal-footer {
            position: relative;
            z-index: 10;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.78rem;
            padding: 0.9rem 1.5rem;
            text-align: center;
        }

        .portal-footer a {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            transition: color 0.2s;
        }

        .portal-footer a:hover {
            color: #f59e0b;
        }

        @media (max-width: 576px) {
            .login-card {
                padding: 1.75rem 1.25rem;
                border-radius: 18px;
            }

            .top-gov-bar {
                padding: 0.5rem 1rem;
            }

            .gov-emblem-text {
                font-size: 0.72rem;
            }

            .ssl-badge {
                display: none;
            }
        }
    </style>
</head>

<body class="gov-portal-body">

    <!-- Ambient Glowing Background Elements -->
    <div class="ambient-orb ambient-orb-1"></div>
    <div class="ambient-orb ambient-orb-2"></div>

    <!-- Top Government Header Banner -->
    <header class="top-gov-bar d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <?php if (file_exists('assets/img/logo.png')): ?>
                <img src="assets/img/logo.png" alt="Emblem" style="height: 24px; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));">
            <?php endif; ?>
            <span class="gov-emblem-text text-uppercase">
                Democratic Socialist Republic of Sri Lanka
            </span>
        </div>
        <div class="d-flex align-items-center gap-3">
        </div>
    </header>

    <!-- Main Login Card Section -->
    <main class="portal-main-wrapper">
        <div class="login-card">

            <!-- Logo & Brand Title -->
            <div class="login-logo-container">
                <img src="assets/img/animal_health_logo.png" alt="Department of Animal Production and Health" class="login-logo-img">
            </div>

            <div class="text-center">
                <div class="portal-badge">
                    <i class="bi bi-shield-lock-fill"></i> Staff Authentication
                </div>
                <h1 class="portal-title">MIS Portal Sign In</h1>
                <p class="portal-subtitle">Access your veterinary, farm, or administrative workstation</p>
            </div>

            <!-- Error Notice Banner -->
            <?php if (!empty($login_error)): ?>
                <div class="alert portal-alert-danger alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
                    <i class="bi bi-exclamation-triangle-fill flex-shrink-0 fs-5"></i>
                    <div><?= htmlspecialchars($login_error) ?></div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form action="auth/login_process.php" method="POST" autocomplete="off" id="portalLoginForm">

                <!-- User Category -->
                <div class="mb-3.5 mb-md-4">
                    <label class="form-label-custom" for="userCategory">
                        <i class="bi bi-person-badge-fill"></i> User Category <span class="text-danger">*</span>
                    </label>
                    <div class="input-group input-group-custom">
                        <span class="input-group-text"><i class="bi bi-grid-fill"></i></span>
                        <select name="user_category" id="userCategory" class="form-select" required autofocus>
                            <option value="">-- Select User Category --</option>
                            <option value="provincial_director">1. Provincial Director</option>
                            <option value="additional_provincial_director">2. Additional Provincial Director</option>
                            <option value="subject_matter_specialist">3. Subject Matter Specialist</option>
                            <option value="deputy_director_hq_1">4. Deputy Director - H/Q-1</option>
                            <option value="deputy_director_hq_2">5. Deputy Director - H/Q-2</option>
                            <option value="deputy_director_district">6. Deputy Director - District</option>
                            <option value="range_veterinary_officer">7. Range Veterinary Officer</option>
                            <option value="training_centers">8. Training Centers</option>
                            <option value="regional_farms">9. Regional Farms</option>
                        </select>
                    </div>
                </div>

                <!-- Dynamic Group: District -->
                <div id="districtGroup" class="mb-3.5 mb-md-4 dynamic-field-group">
                    <label class="form-label-custom" for="districtSelect">
                        <i class="bi bi-geo-alt-fill"></i> Select District <span class="text-danger">*</span>
                    </label>
                    <div class="input-group input-group-custom">
                        <span class="input-group-text"><i class="bi bi-map-fill"></i></span>
                        <select name="district_id" id="districtSelect" class="form-select">
                            <option value="">-- Choose District --</option>
                            <?php
                            $dist_res = $mysqli->query("SELECT id, name FROM districts ORDER BY name ASC");
                            if ($dist_res) {
                                while ($row = $dist_res->fetch_assoc()) {
                                    echo "<option value='" . $row['id'] . "'>" . htmlspecialchars($row['name']) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <!-- Dynamic Group: VS Range Office -->
                <div id="rangeGroup" class="mb-3.5 mb-md-4 dynamic-field-group">
                    <label class="form-label-custom" for="rangeSelect">
                        <i class="bi bi-hospital-fill"></i> VS Range Office <span class="text-danger">*</span>
                    </label>
                    <div class="input-group input-group-custom">
                        <span class="input-group-text"><i class="bi bi-building"></i></span>
                        <select name="range_id" id="rangeSelect" class="form-select">
                            <option value="">-- Select Range Office --</option>
                        </select>
                    </div>
                </div>

                <!-- Dynamic Group: Training Center Location -->
                <div id="trainingCenterLocationGroup" class="mb-3.5 mb-md-4 dynamic-field-group">
                    <label class="form-label-custom" for="trainingCenterLocation">
                        <i class="bi bi-pin-map-fill"></i> Training Center Location <span class="text-danger">*</span>
                    </label>
                    <div class="input-group input-group-custom">
                        <span class="input-group-text"><i class="bi bi-geo"></i></span>
                        <select name="training_center_location" id="trainingCenterLocation" class="form-select">
                            <option value="">-- Select Training Center Location --</option>
                            <?php
                            $tc_loc_res = $mysqli->query("SELECT DISTINCT location FROM training_centers WHERE is_active = 1 AND location IS NOT NULL AND location <> '' ORDER BY location ASC");
                            if ($tc_loc_res) {
                                while ($row = $tc_loc_res->fetch_assoc()) {
                                    echo "<option value='" . htmlspecialchars($row['location'], ENT_QUOTES, 'UTF-8') . "'>" . htmlspecialchars($row['location']) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <!-- Dynamic Group: Training Center -->
                <div id="trainingCenterGroup" class="mb-3.5 mb-md-4 dynamic-field-group">
                    <label class="form-label-custom" for="trainingCenterSelect">
                        <i class="bi bi-mortarboard-fill"></i> Center Name <span class="text-danger">*</span>
                    </label>
                    <div class="input-group input-group-custom">
                        <span class="input-group-text"><i class="bi bi-award-fill"></i></span>
                        <select name="training_center_id" id="trainingCenterSelect" class="form-select">
                            <option value="">-- Select Training Center --</option>
                            <?php
                            $tc_res = $mysqli->query("SELECT id, center_name, location FROM training_centers WHERE is_active = 1 ORDER BY id ASC");
                            if ($tc_res) {
                                while ($row = $tc_res->fetch_assoc()) {
                                    echo "<option value='" . $row['id'] . "' data-location='" . htmlspecialchars($row['location'], ENT_QUOTES, 'UTF-8') . "'>" . htmlspecialchars($row['center_name']) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <!-- Dynamic Group: Regional Farm -->
                <div id="farmGroup" class="mb-3.5 mb-md-4 dynamic-field-group">
                    <label class="form-label-custom" for="farmSelect">
                        <i class="bi bi-tree-fill"></i> Farm Name <span class="text-danger">*</span>
                    </label>
                    <div class="input-group input-group-custom">
                        <span class="input-group-text"><i class="bi bi-flower1"></i></span>
                        <select name="farm_id" id="farmSelect" class="form-select">
                            <option value="">-- Select Farm Name --</option>
                            <?php
                            $farm_res = $mysqli->query("SELECT id, farm_name FROM regional_farms WHERE is_active = 1 ORDER BY id ASC");
                            if ($farm_res) {
                                while ($row = $farm_res->fetch_assoc()) {
                                    echo "<option value='" . $row['id'] . "'>" . htmlspecialchars($row['farm_name']) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <!-- Identity Credential: Username / Email -->
                <div class="mb-3.5 mb-md-4">
                    <label class="form-label-custom" for="loginId">
                        <i class="bi bi-person-fill"></i> Username or Email <span class="text-danger">*</span>
                    </label>
                    <div class="input-group input-group-custom">
                        <span class="input-group-text"><i class="bi bi-envelope-at-fill"></i></span>
                        <input type="text" name="login_id" id="loginId" class="form-control" placeholder="Enter username or email" required>
                    </div>
                </div>

                <!-- Identity Credential: Password with Show/Hide Toggle -->
                <div class="mb-4">
                    <label class="form-label-custom" for="loginPassword">
                        <i class="bi bi-key-fill"></i> Password <span class="text-danger">*</span>
                    </label>
                    <div class="input-group input-group-custom">
                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" name="password" id="loginPassword" class="form-control" placeholder="Enter security password" required>
                        <button type="button" class="btn btn-toggle-password" id="btnTogglePassword" title="Show or hide password" tabindex="-1">
                            <i class="bi bi-eye-fill" id="togglePasswordIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-portal-submit w-100 py-3 d-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-box-arrow-in-right fs-5"></i>
                    <span>Log In to Dashboard</span>
                </button>

            </form>

        </div>
    </main>

    <!-- Portal Footer -->
    <footer class="portal-footer">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 max-w-1200 mx-auto">
            <div>
                © <?= date('Y') ?> <strong> Department of Animal Production & Health</strong> • Eastern Province. All Rights Reserved. Powered By SLT-DIGITAL 
            </div>
            <div class="text-white-50">
                <i class="bi bi-shield-check text-success me-1"></i> Official Government MIS Portal
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {

            // Show/Hide password toggle logic
            $('#btnTogglePassword').on('click', function(e) {
                e.preventDefault();
                const passwordInput = $('#loginPassword');
                const icon = $('#togglePasswordIcon');
                
                if (passwordInput.attr('type') === 'password') {
                    passwordInput.attr('type', 'text');
                    icon.removeClass('bi-eye-fill').addClass('bi-eye-slash-fill');
                } else {
                    passwordInput.attr('type', 'password');
                    icon.removeClass('bi-eye-slash-fill').addClass('bi-eye-fill');
                }
            });

            // Listen for category selection updates
            $('#userCategory').change(function() {
                var selectedVal = $(this).val();

                // Reset and hide all contextual container blocks initially
                $('.dynamic-field-group').hide().find('select').val('').prop('required', false);

                if (selectedVal === 'deputy_director_district') {
                    // Show only District Dropdown
                    $('#districtGroup').fadeIn(250).find('select').prop('required', true);
                } else if (selectedVal === 'range_veterinary_officer') {
                    // Show District & Range dropdown structures
                    $('#districtGroup').fadeIn(250).find('select').prop('required', true);
                    $('#rangeGroup').fadeIn(250).find('select').prop('required', true);
                } else if (selectedVal === 'training_centers') {
                    // Show Training Center selectors and location selector
                    $('#trainingCenterLocationGroup').fadeIn(250).find('select').prop('required', true);
                    $('#trainingCenterGroup').fadeIn(250).find('select').prop('required', true);
                } else if (selectedVal === 'regional_farms') {
                    // Show Regional Farm selector lists
                    $('#farmGroup').fadeIn(250).find('select').prop('required', true);
                }
            });

            // Training center auto-location sync
            $('#trainingCenterSelect').change(function() {
                var selectedOption = $(this).find('option:selected');
                var location = selectedOption.data('location');
                if (location) {
                    $('#trainingCenterLocation').val(location);
                } else {
                    $('#trainingCenterLocation').val('');
                }
            });

            // Handle Dependent Selection filtering for VS Ranges based on District
            $('#districtSelect').change(function() {
                var districtId = $(this).val();
                var rangeSelect = $('#rangeSelect');

                // Clear previous options
                rangeSelect.html('<option value="">-- Select Range Office --</option>');

                if (districtId && $('#userCategory').val() === 'range_veterinary_officer') {
                    $.ajax({
                        url: 'auth/get_ranges_by_district.php',
                        type: 'GET',
                        data: {
                            district_id: districtId
                        },
                        dataType: 'json',
                        success: function(data) {
                            $.each(data, function(index, range) {
                                rangeSelect.append('<option value="' + range.id + '">' + range.name + '</option>');
                            });
                        }
                    });
                }
            });

        });
    </script>
</body>

</html>