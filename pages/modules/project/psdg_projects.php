<?php
require_once '../../../includes/header.php';
if (!in_array($_SESSION['role'], ['planning_officer', 'provincial_director'])) die("Access denied");
require_once '../../../includes/sidebar.php';
?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Development Projects Management</h2>

        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between">
                <h5>PSDG / CBG / NGO / Line Ministry Projects</h5>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProjectModal">
                    Add New Project
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Project Code</th>
                                <th>Title</th>
                                <th>Funding Source</th>
                                <th>Budget (Rs)</th>
                                <th>Start Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Sample rows - replace with DB query -->
                            <tr>
                                <td>PSDG-2025-01</td>
                                <td>Dairy Hub Development - Amparai</td>
                                <td>PSDG</td>
                                <td>45,000,000</td>
                                <td>2025-01-01</td>
                                <td><span class="badge bg-success">Ongoing</span></td>
                                <td>
                                    <a href="progress_physical_financial.php?project=1" class="btn btn-sm btn-info">View Progress</a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once '../../../includes/footer.php'; ?>