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
    <title>DAPH Eastern Province | Login</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="./assets/css/style.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
    <style>
        img.logo-img {

            max-width: 740px;
            filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.15));
            margin-bottom: 0.5rem;
        }

        .dynamic-field-group {
            display: none;
        }

        .login-wrapper {
            max-width: 440px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .login-box {
            background: #ffffff;
            border-radius: 16px;
            padding: 2.5rem 2.25rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.12), 0 2px 8px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(0, 0, 0, 0.04);
        }

        .login-box .form-label {
            letter-spacing: 0.02em;
            margin-bottom: 0.4rem;
        }

        .login-box .form-select,
        .login-box .form-control {
            border-radius: 10px;
            border: 1px solid #d8dde3;
            padding: 0.65rem 1rem;
            font-size: 0.95rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .login-box .form-select:focus,
        .login-box .form-control:focus {
            border-color: #370709;
            box-shadow: 0 0 0 0.2rem rgba(55, 7, 9, 0.15);
        }

        .login-box .form-control::placeholder {
            color: #9aa3ad;
        }

        .dynamic-field-group {
            transition: all 0.3s ease;
        }

        .btn-login {
            background: linear-gradient(135deg, #820100, #370709);
            color: #fff;
            font-weight: 600;
            letter-spacing: 0.03em;
            border-radius: 10px;
            padding: 0.7rem 1rem;
            border: none;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            box-shadow: 0 4px 14px rgba(61, 18, 1, 0.3);
        }

        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(43, 10, 1, 0.4);
            background: linear-gradient(135deg, #820100, #370709);
            color: #fff;
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .login-box .alert {
            border-radius: 10px;
            font-size: 0.9rem;
            border: none;
            background-color: #fdecea;
            color: #b3261e;
        }

        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.78rem;
            color: rgba(255, 255, 255, 0.85);
            letter-spacing: 0.02em;
        }
    </style>
    </style>
</head>

<body class="gov-login">

    <div class="login-wrapper">

        <div class="text-center mb-4">
            <img src="assets/img/animal_health_logo.png" alt="DAPH Logo" class="logo-img">
        </div>

        <div class="login-box">

            <?php if ($login_error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= htmlspecialchars($login_error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form action="auth/login_process.php" method="POST" autocomplete="off">

                <div class="mb-4">
                    <label class="form-label fw-bold text-secondary small">User Category</label>
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

                <div id="districtGroup" class="mb-4 dynamic-field-group">
                    <label class="form-label fw-bold text-secondary small">Select District</label>
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

                <div id="rangeGroup" class="mb-4 dynamic-field-group">
                    <label class="form-label fw-bold text-secondary small">VS Range Office</label>
                    <select name="range_id" id="rangeSelect" class="form-select">
                        <option value="">-- Select Range Office --</option>
                    </select>
                </div>

                <div id="trainingCenterGroup" class="mb-4 dynamic-field-group">
                    <label class="form-label fw-bold text-secondary small">Center Name</label>
                    <select name="training_center_id" class="form-select">
                        <option value="">-- Select Training Center --</option>
                        <?php
                        $tc_res = $mysqli->query("SELECT id, center_name FROM training_centers WHERE is_active = 1 ORDER BY id ASC");
                        if ($tc_res) {
                            while ($row = $tc_res->fetch_assoc()) {
                                echo "<option value='" . $row['id'] . "'>" . htmlspecialchars($row['center_name']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <div id="farmGroup" class="mb-4 dynamic-field-group">
                    <label class="form-label fw-bold text-secondary small">Farm Name</label>
                    <select name="farm_id" class="form-select">
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

                <div class="mb-4">
                    <input type="text" name="login_id" class="form-control" placeholder="Username or Email" required>
                </div>

                <div class="mb-4">
                    <input type="password" name="password" class="form-control" placeholder="Password" required>
                </div>

                <button type="submit" class="btn btn-login w-100">Log In</button>
            </form>
        </div>

        <div class="login-footer">
            © 2026 Copyright SLTDIGITAL | All Rights Reserved
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {

            // Listen for category selection updates
            $('#userCategory').change(function() {
                var selectedVal = $(this).val();

                // Reset and hide all contextual container blocks initially
                $('.dynamic-field-group').hide().find('select').val('').prop('required', false);

                if (selectedVal === 'deputy_director_district') {
                    //Show only District Dropdown
                    $('#districtGroup').fadeIn().find('select').prop('required', true);
                } else if (selectedVal === 'range_veterinary_officer') {
                    //Show District & Range dropdown structures
                    $('#districtGroup').fadeIn().find('select').prop('required', true);
                    $('#rangeGroup').fadeIn().find('select').prop('required', true);
                } else if (selectedVal === 'training_centers') {
                    //Show Training Center selector lists
                    $('#trainingCenterGroup').fadeIn().find('select').prop('required', true);
                } else if (selectedVal === 'regional_farms') {
                    //Show Regional Farm selector lists
                    $('#farmGroup').fadeIn().find('select').prop('required', true);
                }
            });

            // Handle Dependent Selection filtering for VS Ranges based on District
            $('#districtSelect').change(function() {
                var districtId = $(this).val();
                var rangeSelect = $('#rangeSelect');

                // Clear previous options
                rangeSelect.html('<option value="">-- Select Range Office --</option>');

                if (districtId && $('#userCategory').val() === 'range_veterinary_officer') {
                    // Request dynamic ranges belonging to chosen district via a simple lightweight inline look-up fetch
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