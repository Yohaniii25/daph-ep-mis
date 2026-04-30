<?php
session_start();
require_once '../../../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'employee') {
    header("Location: ../../../index.php");
    exit();
}

$current_user = $_SESSION['user_id'];
$user_query = "SELECT * FROM users WHERE id = $current_user"; 
$result = $mysqli->query($user_query);
$user_data = $result->fetch_assoc();

$profile_img = !empty($user_data['profile_image']) 
    ? '../../../assets/uploads/profile_images/' . htmlspecialchars($user_data['profile_image']) 
    : "https://ui-avatars.com/api/?name=" . urlencode($user_data['full_name'] ?? $_SESSION['username']) . "&background=1e40af&color=fff&size=128";

require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';
?>

<script src="https://cdn.tailwindcss.com"></script>

<div id="layoutSidenav_content" class="bg-slate-50 min-h-screen">
    <main class="max-w-8xl mx-auto px-4 py-8">

        <!-- ── Hero card ── -->
        <div style="background-color: #370709;" class="rounded-2xl p-6 mb-6 flex flex-col sm:flex-row sm:items-center gap-5">

            <!-- Avatar -->
            <div class="relative flex-shrink-0 group cursor-pointer"
                 onclick="document.getElementById('profile_image_input').click()">
                <img src="<?= $profile_img ?>"
                     class="w-24 h-24 rounded-full border-4 border-white/30 object-cover"
                     alt="Profile">
                <div class="absolute inset-0 rounded-full bg-black/40 flex items-center justify-center
                            opacity-0 group-hover:opacity-100 transition-opacity">
                    <i class="bi bi-camera text-white text-xl"></i>
                </div>
                <span class="absolute bottom-1 right-1 w-4 h-4 rounded-full border-2 border-white
                             <?= $user_data['is_active'] ? 'bg-emerald-400' : 'bg-red-400' ?>"></span>
            </div>

            <form id="imageUploadForm" action="processors/update_profile_image.php"
                  method="POST" enctype="multipart/form-data" class="hidden">
                <input type="file" id="profile_image_input" name="profile_image" accept="image/*"
                       onchange="document.getElementById('imageUploadForm').submit()">
            </form>

            <!-- Name / meta -->
            <div class="flex-1">
                <p style="color: white;" class="text-xs font-semibold uppercase tracking-widest mb-1">
                    <?= htmlspecialchars($user_data['emp_id'] ?? 'Employee') ?>
                </p>
                <h1 style="color: white;" class="text-2xl font-bold leading-tight">
                    <?= htmlspecialchars($user_data['full_name'] ?? $_SESSION['username']) ?>
                </h1>
                <p style="color: white;" class="text-sm mt-1">
                    <?= htmlspecialchars($user_data['designation'] ?? ucwords(str_replace('_', ' ', $user_data['role']))) ?>
                </p>
                <div class="flex flex-wrap gap-4 mt-3">
                    <span style="color: white;" class="flex items-center gap-1.5 text-xs">
                        <i class="bi bi-envelope"></i>
                        <?= htmlspecialchars($user_data['email']) ?>
                    </span>
                    <span style="color: white;" class="flex items-center gap-1.5 text-xs">
                        <i class="bi bi-geo-alt"></i>
                        <?= htmlspecialchars($user_data['district'] ?? 'Provincial') ?> District
                    </span>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-2 sm:flex-col sm:items-end flex-wrap">
                <button class="flex items-center gap-2 bg-white text-blue-900 text-sm font-semibold
                               px-4 py-2 rounded-lg hover:bg-blue-50 transition">
                    <i class="bi bi-pencil-square"></i> Edit Profile
                </button>
                <button class="flex items-center gap-2 bg-white text-blue-900 text-sm font-semibold
                               px-4 py-2 rounded-lg hover:bg-blue-50 transition">
                    <i class="bi bi-file-earmark-text"></i> ID Card
                </button>
            </div>
        </div>

        <!-- ── Stat strip ── -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl p-4 border border-slate-100 shadow-sm border-l-4 border-l-blue-500">
                <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide mb-1">Employee ID</p>
                <p class="text-base font-bold text-slate-800"><?= htmlspecialchars($user_data['emp_id'] ?? 'N/A') ?></p>
            </div>
            <div class="bg-white rounded-xl p-4 border border-slate-100 shadow-sm border-l-4 border-l-emerald-500">
                <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide mb-1">Registered</p>
                <p class="text-base font-bold text-slate-800">
                    <?= !empty($user_data['registered_date']) ? date('M Y', strtotime($user_data['registered_date'])) : 'Unknown' ?>
                </p>
            </div>
            <div class="bg-white rounded-xl p-4 border border-slate-100 shadow-sm border-l-4 border-l-amber-500">
                <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide mb-1">System Role</p>
                <p class="text-base font-bold text-slate-800"><?= ucwords(str_replace('_', ' ', $user_data['role'])) ?></p>
            </div>
            <div class="bg-white rounded-xl p-4 border border-slate-100 shadow-sm border-l-4 border-l-violet-500">
                <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide mb-1">Status</p>
                <p class="text-base font-bold <?= $user_data['is_active'] ? 'text-emerald-600' : 'text-red-600' ?>">
                    <?= $user_data['is_active'] ? 'Active' : 'Inactive' ?>
                </p>
            </div>
        </div>

        <!-- ── Body grid ── -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Left (main) -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Personal Info -->
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
                        <span style="background-color: #370709;" class="w-7 h-7 rounded-lg flex items-center justify-center">
                            <i style="color: white;" class="bi bi-person-lines-fill text-xs"></i>
                        </span>
                        <h3 style="color: #370709;" class="font-semibold text-slate-800 text-sm">Personal Information</h3>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 divide-y sm:divide-y-0 sm:divide-x divide-slate-100">
                        <div class="p-5 space-y-4">
                            <div>
                                <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide mb-1">Full Name</p>
                                <p class="text-sm font-medium text-slate-800"><?= htmlspecialchars($user_data['full_name']) ?></p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide mb-1">Email Address</p>
                                <p class="text-sm font-medium text-slate-800"><?= htmlspecialchars($user_data['email']) ?></p>
                            </div>
                        </div>
                        <div class="p-5 space-y-4">
                            <div>
                                <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide mb-1">Username</p>
                                <p class="text-sm font-medium text-slate-800"><?= htmlspecialchars($user_data['username']) ?></p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide mb-1">Phone Number</p>
                                <p class="text-sm font-medium text-slate-800"><?= htmlspecialchars($user_data['phone'] ?? 'Not provided') ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Service Record -->
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span style="background-color: #370709;" class="w-7 h-7 rounded-lg flex items-center justify-center">
                            <i style="color: white;" class="bi bi-person-lines-fill text-xs"></i>
                        </span>
                        <h3 style="color: #370709;" class="font-semibold text-slate-800 text-sm">Service Record</h3>
                        </div>
                        <a href="#" class="text-xs font-semibold text-blue-700 hover:text-blue-900 flex items-center gap-1">
                            View All <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm" id="serviceTable">
                            <thead class="bg-slate-50 text-xs text-slate-500 font-semibold uppercase tracking-wide">
                                <tr>
                                    <th class="px-5 py-3 text-left">Station</th>
                                    <th class="px-5 py-3 text-left">Period</th>
                                    <th class="px-5 py-3 text-left">Designation</th>
                                    <th class="px-5 py-3 text-left">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="px-5 py-3.5 font-semibold text-slate-800">Ampara District</td>
                                    <td class="px-5 py-3.5 text-slate-500">2024 – Present</td>
                                    <td class="px-5 py-3.5 text-slate-700">LDO</td>
                                    <td class="px-5 py-3.5">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Current
                                        </span>
                                    </td>
                                </tr>
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="px-5 py-3.5 font-semibold text-slate-800">Batticaloa Range</td>
                                    <td class="px-5 py-3.5 text-slate-500">2022 – 2024</td>
                                    <td class="px-5 py-3.5 text-slate-700">Assistant LDO</td>
                                    <td class="px-5 py-3.5">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-600">
                                            Transferred
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <!-- Right sidebar -->
            <div class="space-y-6">

                <!-- Employment Details -->
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
                        <span style="background-color: #370709;" class="w-7 h-7 rounded-lg flex items-center justify-center">
                            <i style="color: white;" class="bi bi-buildings-fill text-xs"></i>
                        </span>
                        <h3 style="color: #370709;" class="font-semibold text-slate-800 text-sm">Employment Details</h3>
                    </div>
                    <ul class="divide-y divide-slate-100 text-sm">
                        <li class="flex justify-between items-center px-5 py-3">
                            <span class="text-slate-500">Appointment</span>
                            <span class="font-semibold text-slate-800">
                                <?= !empty($user_data['registered_date']) ? date('M d, Y', strtotime($user_data['registered_date'])) : 'N/A' ?>
                            </span>
                        </li>
                        <li class="flex justify-between items-center px-5 py-3">
                            <span class="text-slate-500">Type</span>
                            <span class="font-semibold text-blue-700">Permanent</span>
                        </li>
                        <li class="flex justify-between items-center px-5 py-3">
                            <span class="text-slate-500">Designation</span>
                            <span class="font-semibold text-slate-800"><?= htmlspecialchars($user_data['designation'] ?? 'Not set') ?></span>
                        </li>
                        <li class="flex justify-between items-center px-5 py-3">
                            <span class="text-slate-500">District</span>
                            <span class="font-semibold text-slate-800"><?= htmlspecialchars($user_data['district'] ?? 'N/A') ?></span>
                        </li>
                    </ul>
                </div>



            </div>
        </div>

    </main>
</div>

<script>
    $(document).ready(function() {
        $('#serviceTable').DataTable({
            searching: false,
            paging: false,
            info: false,
            ordering: false
        });
    });
</script>

<?php require_once '../../../includes/footer.php'; ?>