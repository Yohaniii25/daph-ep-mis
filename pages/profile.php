<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch detailed user profile information
$stmt = $mysqli->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_res = $stmt->get_result();

if ($user_res->num_rows === 0) {
    die("User record not found.");
}

$user_data = $user_res->fetch_assoc();

// Resolve profile picture
$profile_img_src = !empty($user_data['profile_image']) && file_exists(__DIR__ . '/../assets/uploads/profile_images/' . $user_data['profile_image'])
    ? '../assets/uploads/profile_images/' . htmlspecialchars($user_data['profile_image'])
    : "https://ui-avatars.com/api/?name=" . urlencode($user_data['full_name'] ?? $_SESSION['username'] ?? 'User') . "&background=500707&color=fff&size=128";

// Fetch additional location entity names if assigned
$farm_name = null;
if (!empty($user_data['farm_id'])) {
    $farm_stmt = $mysqli->prepare("SELECT farm_name FROM regional_farms WHERE id = ?");
    if ($farm_stmt) {
        $farm_stmt->bind_param("i", $user_data['farm_id']);
        $farm_stmt->execute();
        $f_res = $farm_stmt->get_result();
        if ($f_res && $f_res->num_rows > 0) {
            $farm_name = $f_res->fetch_assoc()['farm_name'];
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Custom Styling for Profile Page -->
<style>
    .profile-hero {
        background: linear-gradient(135deg, #500707 0%, #2b0404 100%);
        border-radius: 1rem;
        color: #fff;
        padding: 2.5rem 2rem;
        position: relative;
        box-shadow: 0 10px 25px rgba(80, 7, 7, 0.2);
    }
    
    .avatar-wrapper {
        position: relative;
        display: inline-block;
        cursor: pointer;
    }
    
    .avatar-img {
        width: 110px;
        height: 110px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid rgba(255, 255, 255, 0.4);
        box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        transition: transform 0.2s ease, border-color 0.2s ease;
    }
    
    .avatar-wrapper:hover .avatar-img {
        transform: scale(1.03);
        border-color: #fff;
    }
    
    .avatar-overlay {
        position: absolute;
        inset: 0;
        border-radius: 50%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 1.5rem;
        opacity: 0;
        transition: opacity 0.2s ease;
    }
    
    .avatar-wrapper:hover .avatar-overlay {
        opacity: 1;
    }

    .card-custom {
        border: none;
        border-radius: 0.85rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        background: #fff;
    }

    .card-header-custom {
        background: transparent;
        border-bottom: 1px solid #f0f0f0;
        padding: 1.25rem 1.5rem;
        font-weight: 700;
        font-size: 1.1rem;
        color: #333;
    }

    .info-label {
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #888;
        margin-bottom: 0.25rem;
    }

    .info-value {
        font-size: 1rem;
        font-weight: 600;
        color: #222;
    }

    .password-toggle-btn {
        cursor: pointer;
        border-left: none;
        background: transparent;
    }
</style>

<!-- Notification Flash Alerts -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                <?= htmlspecialchars($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                <?= htmlspecialchars($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Hero Header -->
        <div class="profile-hero mb-4">
            <div class="d-flex flex-column flex-md-row align-items-center gap-4">
                
                <!-- Avatar with Upload Click -->
                <div class="avatar-wrapper" title="Click to change profile picture" onclick="document.getElementById('profile_image_file').click();">
                    <img src="<?= $profile_img_src ?>" class="avatar-img" alt="Profile Picture">
                    <div class="avatar-overlay">
                        <i class="bi bi-camera-fill"></i>
                    </div>
                </div>

                <!-- Hidden form for profile image upload -->
                <form id="profile_image_form" action="../auth/update_profile_image.php" method="POST" enctype="multipart/form-data" class="d-none">
                    <input type="file" id="profile_image_file" name="profile_image" accept="image/jpeg,image/png,image/gif,image/webp" onchange="document.getElementById('profile_image_form').submit();">
                </form>

                <!-- User Information Banner -->
                <div class="flex-grow-1 text-center text-md-start">
                    <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-2 mb-1">
                        <span class="badge bg-danger bg-opacity-75 text-light px-3 py-1 rounded-pill fs-7 fw-semibold">
                            <?= ucwords(str_replace('_', ' ', $user_data['role'] ?? $_SESSION['role'])) ?>
                        </span>
                        <?php if (isset($user_data['is_active']) && $user_data['is_active']): ?>
                            <span class="badge bg-success px-2 py-1 rounded-pill fs-7">
                                <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i> Active
                            </span>
                        <?php endif; ?>
                    </div>
                    <h2 class="fw-bold mb-1 text-light">
                        <?= htmlspecialchars($user_data['full_name'] ?? $_SESSION['full_name'] ?? 'User Profile') ?>
                    </h2>
                    <p class="mb-2 text-light-50">
                        <i class="bi bi-person-badge me-1"></i> Username: <strong><?= htmlspecialchars($user_data['username'] ?? $_SESSION['username']) ?></strong>
                        <span class="mx-2">•</span>
                        <i class="bi bi-envelope me-1"></i> <?= htmlspecialchars($user_data['email'] ?? 'No email set') ?>
                    </p>
                    <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-md-start gap-3 fs-7 text-light-50">
                        <?php if (!empty($user_data['district'])): ?>
                            <span><i class="bi bi-geo-alt-fill text-warning me-1"></i> District: <?= htmlspecialchars($user_data['district']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($farm_name)): ?>
                            <span><i class="bi bi-house-door-fill text-info me-1"></i> Farm: <?= htmlspecialchars($farm_name) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            
            <!-- Left Column: User Account Information -->
            <div class="col-lg-6">
                <div class="card card-custom h-100">
                    <div class="card-header card-header-custom d-flex align-items-center justify-content-between">
                        <div>
                            <i class="bi bi-person-lines-fill me-2 text-danger"></i> User Profile Details
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            
                            <div class="col-sm-6">
                                <div class="p-3 bg-light rounded-3">
                                    <div class="info-label">Full Name</div>
                                    <div class="info-value"><?= htmlspecialchars($user_data['full_name'] ?? 'N/A') ?></div>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="p-3 bg-light rounded-3">
                                    <div class="info-label">Username</div>
                                    <div class="info-value"><?= htmlspecialchars($user_data['username'] ?? 'N/A') ?></div>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="p-3 bg-light rounded-3">
                                    <div class="info-label">Email Address</div>
                                    <div class="info-value"><?= htmlspecialchars($user_data['email'] ?? 'N/A') ?></div>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="p-3 bg-light rounded-3">
                                    <div class="info-label">System Role</div>
                                    <div class="info-value text-capitalize"><?= str_replace('_', ' ', htmlspecialchars($user_data['role'] ?? 'User')) ?></div>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="p-3 bg-light rounded-3">
                                    <div class="info-label">District Region</div>
                                    <div class="info-value"><?= htmlspecialchars($user_data['district'] ?? 'Provincial / HQ') ?></div>
                                </div>
                            </div>

                            <?php if (!empty($farm_name)): ?>
                            <div class="col-sm-6">
                                <div class="p-3 bg-light rounded-3">
                                    <div class="info-label">Assigned Farm</div>
                                    <div class="info-value"><?= htmlspecialchars($farm_name) ?></div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="col-sm-6">
                                <div class="p-3 bg-light rounded-3">
                                    <div class="info-label">Last Login</div>
                                    <div class="info-value">
                                        <?= !empty($user_data['last_login']) ? date('M d, Y - h:i A', strtotime($user_data['last_login'])) : 'Recent session' ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="p-3 bg-light rounded-3">
                                    <div class="info-label">Account Status</div>
                                    <div class="info-value text-success">
                                        <i class="bi bi-shield-check me-1"></i> Active & Verified
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Change Password -->
            <div class="col-lg-6">
                <div class="card card-custom h-100">
                    <div class="card-header card-header-custom d-flex align-items-center justify-content-between">
                        <div>
                            <i class="bi bi-key-fill me-2 text-danger"></i> Change Password
                        </div>
                        <span class="badge bg-light text-secondary border">Security</span>
                    </div>
                    <div class="card-body p-4">
                        
                        <form id="changePasswordForm" action="../auth/change_password.php" method="POST">
                            
                            <!-- Current Password -->
                            <div class="mb-3">
                                <label for="current_password" class="form-label fw-semibold">Current Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control border-start-0 border-end-0" id="current_password" name="current_password" placeholder="Enter current password" required>
                                    <button class="btn btn-outline-secondary password-toggle-btn" type="button" onclick="togglePasswordVisibility('current_password', this)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- New Password -->
                            <div class="mb-3">
                                <label for="new_password" class="form-label fw-semibold">New Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-shield-lock"></i></span>
                                    <input type="password" class="form-control border-start-0 border-end-0" id="new_password" name="new_password" placeholder="Enter new password (min. 6 chars)" minlength="6" required onkeyup="checkPasswordStrength(this.value)">
                                    <button class="btn btn-outline-secondary password-toggle-btn" type="button" onclick="togglePasswordVisibility('new_password', this)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div class="progress mt-2 d-none" id="strength_progress_bar" style="height: 5px;">
                                    <div class="progress-bar bg-danger" id="strength_bar" role="progressbar" style="width: 0%"></div>
                                </div>
                                <div class="form-text text-muted" id="strength_text">Password should be at least 6 characters long.</div>
                            </div>

                            <!-- Confirm New Password -->
                            <div class="mb-4">
                                <label for="confirm_password" class="form-label fw-semibold">Confirm New Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-shield-check"></i></span>
                                    <input type="password" class="form-control border-start-0 border-end-0" id="confirm_password" name="confirm_password" placeholder="Re-enter new password" minlength="6" required onkeyup="checkPasswordMatch()">
                                    <button class="btn btn-outline-secondary password-toggle-btn" type="button" onclick="togglePasswordVisibility('confirm_password', this)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text" id="match_feedback"></div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-danger btn-lg py-2 fw-semibold" style="background-color: #500707; border-color: #500707;">
                                    <i class="bi bi-check-circle me-2"></i> Update Password
                                </button>
                            </div>

                        </form>

                    </div>
                </div>
            </div>

        </div>

<!-- Script for Interactive Password Features -->
<script>
function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}

function checkPasswordStrength(password) {
    const progressBarContainer = document.getElementById('strength_progress_bar');
    const progressBar = document.getElementById('strength_bar');
    const strengthText = document.getElementById('strength_text');

    if (!password) {
        progressBarContainer.classList.add('d-none');
        strengthText.innerText = "Password should be at least 6 characters long.";
        strengthText.className = "form-text text-muted";
        return;
    }

    progressBarContainer.classList.remove('d-none');

    let strength = 0;
    if (password.length >= 6) strength += 30;
    if (password.length >= 10) strength += 20;
    if (/[A-Z]/.test(password)) strength += 20;
    if (/[0-9]/.test(password)) strength += 15;
    if (/[^A-Za-z0-9]/.test(password)) strength += 15;

    progressBar.style.width = strength + '%';

    if (strength < 40) {
        progressBar.className = 'progress-bar bg-danger';
        strengthText.innerText = 'Weak password';
        strengthText.className = 'form-text text-danger';
    } else if (strength < 75) {
        progressBar.className = 'progress-bar bg-warning';
        strengthText.innerText = 'Medium strength password';
        strengthText.className = 'form-text text-warning';
    } else {
        progressBar.className = 'progress-bar bg-success';
        strengthText.innerText = 'Strong password';
        strengthText.className = 'form-text text-success';
    }
}

function checkPasswordMatch() {
    const newPass = document.getElementById('new_password').value;
    const confirmPass = document.getElementById('confirm_password').value;
    const feedback = document.getElementById('match_feedback');

    if (!confirmPass) {
        feedback.innerText = '';
        return;
    }

    if (newPass === confirmPass) {
        feedback.innerText = '✓ Passwords match';
        feedback.className = 'form-text text-success fw-semibold';
    } else {
        feedback.innerText = '✕ Passwords do not match';
        feedback.className = 'form-text text-danger fw-semibold';
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
