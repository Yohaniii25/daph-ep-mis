<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../../index.php");
    exit();
}

if ($_SESSION['role'] !== 'farms_dd') {
    die("Access denied");
}

require_once '../../../config/db_connect.php';

$message = '';



// Now include header after POST processing
require_once '../../../includes/header.php';



?>



<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Livestock Operations</h2>

        <?= $message ?>



        <!-- Employees Table -->

    </main>
</div>





<?php require_once '../../../includes/footer.php'; ?>