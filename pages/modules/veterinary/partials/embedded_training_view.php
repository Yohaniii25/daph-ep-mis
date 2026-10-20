<?php
/**
 * Embedded Operational Training View Component
 * Expected Variables:
 * @var string $tr_context      ('projects' or 'dairy_hub')
 * @var string $tr_title        Display title for the section
 * @var string $tr_subtitle     Sub-heading context
 * @var array  $tr_records      Array of operational_trainings rows
 * @var string $tr_theme_color  Theme accent color (e.g. #0d6efd or #820100)
 * @var string $tr_table_id     HTML ID for data table
 * @var string $tr_export_id    HTML ID for export buttons container
 */

$total_sessions   = count($tr_records);
$total_attendance = 0;
$photos_count     = 0;

foreach ($tr_records as $rec) {
    $total_attendance += intval($rec['attendance'] ?? 0);
    if (!empty($rec['photo_path'])) {
        $photos_count++;
    }
}

$avg_attendance = $total_sessions > 0 ? round($total_attendance / $total_sessions, 1) : 0;
?>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom">
        <div class="d-flex align-items-center">
            <div class="rounded-circle p-2 me-3 text-white d-flex align-items-center justify-content-center shadow-sm" style="background-color: <?= htmlspecialchars($tr_theme_color) ?>; width: 44px; height: 44px;">
                <i class="bi bi-mortarboard-fill fs-5"></i>
            </div>
            <div>
                <h5 class="mb-0 fw-bold text-dark"><?= htmlspecialchars($tr_title) ?></h5>
                <small class="text-muted"><?= htmlspecialchars($tr_subtitle) ?></small>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div id="<?= htmlspecialchars($tr_export_id) ?>" class="btn-group"></div>
            <button type="button" 
                    class="btn text-white fw-semibold shadow-sm px-3" 
                    style="background-color: <?= htmlspecialchars($tr_theme_color) ?>; border-color: <?= htmlspecialchars($tr_theme_color) ?>;"
                    onclick="TrainingManager.openCreateModal('<?= htmlspecialchars($tr_context) ?>', '<?= htmlspecialchars(addslashes($tr_title)) ?>')">
                <i class="bi bi-plus-circle me-1"></i> Log Training Session
            </button>
        </div>
    </div>

    <div class="card-body p-4">
        <!-- KPI Metrics Strip -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="p-3 bg-light rounded-3 border-start border-4 shadow-sm" style="border-start-color: <?= htmlspecialchars($tr_theme_color) ?> !important;">
                    <div class="text-muted small fw-bold text-uppercase">Total Sessions</div>
                    <div class="fs-4 fw-bold text-dark mt-1"><?= number_format($total_sessions) ?></div>
                    <small class="text-muted">Conducted in Range</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 bg-light rounded-3 border-start border-4 shadow-sm border-success">
                    <div class="text-muted small fw-bold text-uppercase">Total Attendance</div>
                    <div class="fs-4 fw-bold text-success mt-1"><?= number_format($total_attendance) ?></div>
                    <small class="text-muted">Total Participants Trained</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 bg-light rounded-3 border-start border-4 shadow-sm border-info">
                    <div class="text-muted small fw-bold text-uppercase">Avg. Attendees</div>
                    <div class="fs-4 fw-bold text-info mt-1"><?= number_format($avg_attendance, 1) ?></div>
                    <small class="text-muted">Per Training Session</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 bg-light rounded-3 border-start border-4 shadow-sm border-warning">
                    <div class="text-muted small fw-bold text-uppercase">Photo Documented</div>
                    <div class="fs-4 fw-bold text-warning mt-1"><?= number_format($photos_count) ?> / <?= $total_sessions ?></div>
                    <small class="text-muted">Field Evidence Uploaded</small>
                </div>
            </div>
        </div>

        <!-- Training Data Grid -->
        <div class="table-responsive">
            <table class="table table-hover align-middle border" id="<?= htmlspecialchars($tr_table_id) ?>" style="width: 100%;">
                <thead class="table-light small text-uppercase fw-bold">
                    <tr>
                        <th class="text-center" style="width: 50px;">#</th>
                        <th style="width: 110px;">Date</th>
                        <th>Topic &amp; Session Scope</th>
                        <th>Target Group</th>
                        <th>Location / Venue</th>
                        <th class="text-center" style="width: 110px;">Attendance</th>
                        <th class="text-center" style="width: 100px;">Photo Evidence</th>
                        <th>Resource Person</th>
                        <th class="text-center" style="width: 100px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $sn = 1;
                    foreach ($tr_records as $row): 
                        $has_photo = !empty($row['photo_path']);
                        $photo_url = $has_photo ? '../../../../' . ltrim($row['photo_path'], '/') : '';
                    ?>
                        <tr>
                            <td class="text-center fw-bold text-secondary"><?= $sn++ ?></td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($row['training_date']) ?></div>
                                <small class="text-muted"><?= date('D, M j', strtotime($row['training_date'])) ?></small>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($row['topic']) ?></div>
                                <?php if (!empty($row['remarks'])): ?>
                                    <div class="small text-muted text-truncate" style="max-width: 280px;" title="<?= htmlspecialchars($row['remarks']) ?>">
                                        <i class="bi bi-chat-left-text me-1 text-secondary"></i><?= htmlspecialchars($row['remarks']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge rounded-pill bg-light text-dark border px-2 py-1">
                                    <i class="bi bi-diagram-3 me-1 text-primary"></i><?= htmlspecialchars($row['target_group']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="small fw-semibold text-secondary">
                                    <i class="bi bi-geo-alt-fill text-danger me-1"></i><?= htmlspecialchars($row['location']) ?>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-3 py-2 fs-6 fw-bold">
                                    <i class="bi bi-people-fill me-1"></i><?= number_format(intval($row['attendance'])) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php if ($has_photo): ?>
                                    <div class="position-relative d-inline-block">
                                        <img src="<?= htmlspecialchars($photo_url) ?>" 
                                             alt="Photo" 
                                             class="rounded border shadow-sm" 
                                             style="width: 48px; height: 36px; object-fit: cover; cursor: pointer;"
                                             onclick="TrainingManager.viewPhoto('<?= htmlspecialchars($photo_url) ?>', '<?= htmlspecialchars(addslashes($row['topic'] . ' - ' . $row['training_date'])) ?>')"
                                             title="Click to view full photo">
                                        <button type="button" 
                                                class="btn btn-sm btn-dark position-absolute bottom-0 end-0 p-0 rounded-circle d-flex align-items-center justify-content-center" 
                                                style="width: 18px; height: 18px; opacity: 0.85;"
                                                onclick="TrainingManager.viewPhoto('<?= htmlspecialchars($photo_url) ?>', '<?= htmlspecialchars(addslashes($row['topic'] . ' - ' . $row['training_date'])) ?>')">
                                            <i class="bi bi-arrows-fullscreen" style="font-size: 9px;"></i>
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary small py-1 px-2 border border-secondary-subtle">
                                        <i class="bi bi-camera me-1"></i>No Photo
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="small fw-bold text-dark"><?= htmlspecialchars($row['trainer_name'] ?: 'Department Officer') ?></div>
                                <small class="text-muted"><i class="bi bi-shield-check me-1 text-success"></i>Verified</small>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" 
                                            class="btn btn-outline-primary" 
                                            title="Edit Session"
                                            onclick="TrainingManager.editTraining(<?= intval($row['id']) ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" 
                                            class="btn btn-outline-danger" 
                                            title="Delete Session"
                                            onclick="TrainingManager.deleteTraining(<?= intval($row['id']) ?>)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
