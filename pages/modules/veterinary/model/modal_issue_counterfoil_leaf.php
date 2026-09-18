<!-- Issue Individual Certificate / Counterfoil Leaf Modal -->
<div class="modal fade" id="issueCounterfoilLeafModal" tabindex="-1" aria-labelledby="issueCounterfoilLeafModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <!-- Modal Header -->
            <div class="modal-header text-white border-0 py-3 px-4" style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-2 d-flex align-items-center justify-content-center" style="background: rgba(255,255,255,0.15); width: 44px; height: 44px;">
                        <i class="bi bi-file-earmark-person fs-4 text-white"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="issueCounterfoilLeafModalLabel">Issue Individual Certificate / Leaf</h5>
                        <small class="text-white-50" style="font-size: 12px;">Issue audited certificate/leaf from counterfoil book directly to farmer</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="formIssueCounterfoilLeaf" action="processors/save_counterfoil_leaf_issue.php" method="POST">
                <input type="hidden" name="district_id" value="<?= htmlspecialchars($district_id ?? '') ?>">
                <input type="hidden" name="range_id" value="<?= htmlspecialchars($range_id ?? '') ?>">
                <input type="hidden" name="animal_counts_summary" id="leaf_animal_counts_summary" value="">

                <div class="modal-body p-4 bg-light-subtle">
                    <!-- SECTION 1: Book & Leaf Reference -->
                    <div class="card border-0 shadow-sm rounded-3 mb-3">
                        <div class="card-body p-3">
                            <h6 class="text-uppercase fw-bold text-muted small mb-3" style="letter-spacing: 0.5px;">
                                <i class="bi bi-book me-1 text-primary"></i> Parent Counterfoil Book &amp; Leaf Serial
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-7">
                                    <label class="form-label small fw-bold text-dark">
                                        Source Counterfoil Book <span class="text-danger">*</span>
                                    </label>
                                    <select name="counterfoil_id" id="leaf_counterfoil_id" class="form-select" required>
                                        <option value="" disabled selected>-- Select Authorized Farmer Book --</option>
                                        <?php
                                        if (!function_exists('isFarmerRelatedBook')) {
                                            function isFarmerRelatedBook($type) {
                                                $clean = strtolower(preg_replace('/[^a-z0-9]/i', '', $type));
                                                $valid_keys = [
                                                    'airegister',
                                                    'animaltransport',
                                                    'aicertificatebook',
                                                    'aicertificate',
                                                    'cashreceiptbook',
                                                    'cashreceipt',
                                                    'certificateforslaughterofbuffalo',
                                                    'healthcertificate',
                                                    'ownershipvoucher',
                                                    'registerofcattlebranded',
                                                    'cattlebranded',
                                                    'registerforanimalidentificationschedule08',
                                                    'registerforanimalidentification',
                                                    'schedule08',
                                                    'pivschedule08',
                                                    'arvregister',
                                                    'animalbirthcontrolregister',
                                                    'produceregister'
                                                ];
                                                foreach ($valid_keys as $vk) {
                                                    if ($clean === $vk || strpos($clean, $vk) !== false || strpos($vk, $clean) !== false) {
                                                        return true;
                                                    }
                                                }
                                                return false;
                                            }
                                        }

                                        $matching_books = [];
                                        if (!empty($range_id)) {
                                            $b_stmt = $mysqli->prepare("SELECT id, counterfoil_type, book_serial_no, page_count, available_quantity FROM counterfoil_assets WHERE range_id = ? AND is_active = 1 ORDER BY counterfoil_type ASC, id DESC");
                                            if ($b_stmt) {
                                                $b_stmt->bind_param("i", $range_id);
                                                $b_stmt->execute();
                                                $b_res = $b_stmt->get_result();
                                                while ($bk = $b_res->fetch_assoc()) {
                                                    if (isFarmerRelatedBook($bk['counterfoil_type'])) {
                                                        $matching_books[] = $bk;
                                                    }
                                                }
                                                $b_stmt->close();
                                            }
                                        }

                                        if (!empty($matching_books)) {
                                            echo '<optgroup label="Authorized Farmer Counterfoil Books in Custody">';
                                            foreach ($matching_books as $bk) {
                                                $label = htmlspecialchars($bk['counterfoil_type']);
                                                if (!empty($bk['book_serial_no'])) $label .= " (Serial: " . htmlspecialchars($bk['book_serial_no']) . ")";
                                                if (!empty($bk['page_count'])) $label .= " - " . htmlspecialchars($bk['page_count']) . " Leaves";
                                                $label .= " [Available: " . intval($bk['available_quantity']) . "]";
                                                echo "<option value=\"{$bk['id']}\" data-type=\"" . htmlspecialchars($bk['counterfoil_type']) . "\">{$label}</option>";
                                            }
                                            echo '</optgroup>';
                                        } else {
                                            echo '<option value="" disabled>No farmer-related counterfoil books currently registered in custody</option>';
                                        }
                                        ?>
                                    </select>
                                    <div class="mt-1 d-flex align-items-center gap-1">
                                        <span class="badge bg-success-subtle text-success border border-success-subtle py-1 px-2" style="font-size: 11px;">
                                            <i class="bi bi-shield-check me-1"></i>Restricted View: Limited to 12 Authorized Farmer Book Types
                                        </span>
                                    </div>
                                    <small class="text-muted d-block mt-1" style="font-size: 11px;">
                                        Only displays AI Register, Animal Transport, AI cert, Cash receipt, Slaughter cert, Health cert, Ownership voucher, Cattle branded, Animal ID (Schedule 08), ARV, Animal birth control, &amp; Produce register.
                                    </small>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label small fw-bold text-dark">
                                        Specific Leaf / Serial Number <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="leaf_serial_no" id="leaf_serial_no" class="form-control font-monospace fw-bold" placeholder="e.g. 001024 or HC/2026/045" required>
                                    <small class="text-muted" style="font-size: 11px;">Unique leaf number issued from the book</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: Farmer NIC & Auto-Pull Details -->
                    <div class="card border-0 shadow-sm rounded-3 mb-3 border-start border-primary border-4">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="text-uppercase fw-bold text-primary small mb-0" style="letter-spacing: 0.5px;">
                                    <i class="bi bi-person-badge-fill me-1"></i> Farmer Identity &amp; Auto-Pull
                                </h6>
                                <span id="leaf_nic_status_badge" class="badge bg-secondary-subtle text-secondary small" style="display: none;"></span>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">
                                        Farmer Identity Card Number (NIC) <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-card-heading"></i></span>
                                        <input type="text" 
                                               name="farmer_nic" 
                                               id="leaf_farmer_nic" 
                                               class="form-control border-start-0 font-monospace fw-bold" 
                                               placeholder="e.g. 198214502391 or 765421980V" 
                                               autocomplete="off" 
                                               required>
                                        <button class="btn btn-outline-secondary" type="button" id="btn_lookup_leaf_nic" title="Query Farmer Database">
                                            <i class="bi bi-search"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted" style="font-size: 11px;">
                                        Type NIC to instantly pull Farm Reg No., Address, and Live Animal Counts.
                                    </small>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">Farm Registration No.</label>
                                    <input type="text" name="farm_registration_no" id="leaf_farm_registration_no" class="form-control font-monospace" placeholder="Auto-filled or enter FRN">
                                    <small class="text-muted" style="font-size: 11px;">Departmental Farm Registration ID</small>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">Farmer Full Name <span class="text-danger">*</span></label>
                                    <input type="text" name="farmer_name" id="leaf_farmer_name" class="form-control" placeholder="Auto-filled farmer name" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">Issue Date <span class="text-danger">*</span></label>
                                    <input type="date" name="issue_date" id="leaf_issue_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                </div>

                                <div class="col-12">
                                    <label class="form-label small fw-bold text-dark">Location / Farm Address</label>
                                    <textarea name="location_address" id="leaf_location_address" class="form-control" rows="2" placeholder="Auto-filled farm address or enter location"></textarea>
                                </div>

                                <!-- Dynamic Animal Counts Panel -->
                                <div class="col-12" id="leaf_animal_counts_container" style="display: none;">
                                    <div class="p-3 bg-light rounded-3 border">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <label class="form-label small fw-bold text-dark mb-0">
                                                <i class="bi bi-pie-chart-fill text-success me-1"></i>Registered Animal Population Breakdown
                                            </label>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 small">
                                                <i class="bi bi-database-check me-1"></i>Verified Master Registry
                                            </span>
                                        </div>
                                        <div class="d-flex flex-wrap gap-2" id="leaf_animal_badges">
                                            <!-- Dynamically injected -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 3: Purpose & Remarks -->
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-body p-3">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">Purpose of Issuance</label>
                                    <input type="text" name="purpose" class="form-control" placeholder="e.g. Animal Health Clearance, Transport, AI Service Receipt">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">Additional Remarks / Officer Notes</label>
                                    <input type="text" name="remarks" class="form-control" placeholder="e.g. Inspected on-site, fee paid, etc.">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer bg-white border-top py-3 px-4 d-flex justify-content-between align-items-center">
                    <div class="small text-muted">
                        <i class="bi bi-shield-check me-1 text-success"></i>Issued leaf permanently recorded to counterfoil audit history
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary px-3 fw-semibold rounded-pill" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="btn_submit_issue_leaf" class="btn text-white px-4 fw-semibold rounded-pill shadow-sm" style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);">
                            <i class="bi bi-check2-circle me-1"></i> Issue Certificate
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    function lookupLeafFarmerNIC() {
        var nic = $('#leaf_farmer_nic').val().trim();
        if (!nic) {
            $('#leaf_nic_status_badge').hide();
            $('#leaf_animal_counts_container').slideUp();
            return;
        }

        $('#leaf_nic_status_badge').removeClass('bg-success text-white bg-warning text-dark bg-danger').addClass('bg-secondary-subtle text-secondary').html('<span class="spinner-border spinner-border-sm me-1"></span>Verifying...').show();

        $.ajax({
            url: 'processors/get_farmer_by_nic.php',
            type: 'GET',
            data: { nic: nic },
            dataType: 'json',
            success: function(resp) {
                if (resp.success && resp.found && resp.farmer) {
                    var f = resp.farmer;
                    var frnText = f.farm_registration_no ? ' | FRN: ' + f.farm_registration_no : '';
                    var srcTitle = f.source || 'Animal Health Farm Registration';
                    $('#leaf_nic_status_badge')
                        .removeClass('bg-secondary-subtle text-secondary bg-warning text-dark bg-danger')
                        .addClass('bg-success-subtle text-success border border-success-subtle fw-semibold')
                        .html('<i class="bi bi-shield-fill-check me-1"></i>Pulled from ' + srcTitle + ': ' + (f.full_name || '') + frnText);

                    // Auto-fill fields
                    $('#leaf_farmer_name').val(f.full_name || '');
                    $('#leaf_farm_registration_no').val(f.farm_registration_no || '');
                    $('#leaf_location_address').val(f.location_address || '');

                    // Safe format animal counts breakdown
                    var animals = f.animal_counts || {};
                    var c_cattle = (animals.cattle !== undefined) ? animals.cattle : (f.cattle_count || 0);
                    var c_buffalo = (animals.buffalo !== undefined) ? animals.buffalo : (f.buffalo_count || 0);
                    var c_goat = (animals.goat !== undefined) ? animals.goat : (f.goat_count || 0);
                    var c_swine = (animals.swine !== undefined) ? animals.swine : (f.swine_count || 0);
                    var c_poultry = (animals.poultry !== undefined) ? animals.poultry : (f.poultry_count || 0);
                    var totalAnimals = f.total_animal_count || (c_cattle + c_buffalo + c_goat + c_swine + c_poultry);

                    var summaryText = "Cattle: " + c_cattle + ", Buffalo: " + c_buffalo + ", Goat: " + c_goat + ", Swine: " + c_swine + ", Poultry: " + c_poultry + " (Total: " + totalAnimals + ")";
                    $('#leaf_animal_counts_summary').val(summaryText);

                    var badges = '' +
                        '<span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2"><i class="bi bi-circle-fill me-1" style="font-size:8px;"></i>Cattle: <strong>' + c_cattle + '</strong></span>' +
                        '<span class="badge bg-info-subtle text-info border border-info-subtle px-3 py-2"><i class="bi bi-circle-fill me-1" style="font-size:8px;"></i>Buffalo: <strong>' + c_buffalo + '</strong></span>' +
                        '<span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2"><i class="bi bi-circle-fill me-1" style="font-size:8px;"></i>Goat: <strong>' + c_goat + '</strong></span>' +
                        '<span class="badge bg-secondary-subtle text-secondary border px-3 py-2"><i class="bi bi-circle-fill me-1" style="font-size:8px;"></i>Swine: <strong>' + c_swine + '</strong></span>' +
                        '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2"><i class="bi bi-circle-fill me-1" style="font-size:8px;"></i>Poultry: <strong>' + c_poultry + '</strong></span>' +
                        '<span class="badge bg-dark text-white px-3 py-2"><i class="bi bi-calculator me-1"></i>Total Livestock: <strong>' + totalAnimals + '</strong></span>';

                    $('#leaf_animal_badges').html(badges);
                    $('#leaf_animal_counts_container').slideDown();
                } else {
                    $('#leaf_nic_status_badge').removeClass('bg-secondary-subtle text-secondary bg-success text-white').addClass('bg-warning-subtle text-dark border border-warning-subtle').html('<i class="bi bi-exclamation-triangle me-1"></i>Unregistered NIC in Farm Registration (Enter Details Manually)');
                    $('#leaf_animal_counts_container').slideUp();
                    $('#leaf_animal_counts_summary').val('');
                }
            },
            error: function() {
                $('#leaf_nic_status_badge').removeClass('bg-secondary-subtle text-secondary').addClass('bg-danger text-white').text('Lookup error');
            }
        });
    }

    var leafNicTimer = null;
    $('#leaf_farmer_nic').on('input', function() {
        clearTimeout(leafNicTimer);
        leafNicTimer = setTimeout(lookupLeafFarmerNIC, 300);
    });
    $('#leaf_farmer_nic').on('blur', lookupLeafFarmerNIC);
    $('#btn_lookup_leaf_nic').on('click', lookupLeafFarmerNIC);

    // Form submission
    $('#formIssueCounterfoilLeaf').on('submit', function(e) {
        e.preventDefault();
        var submitBtn = $('#btn_submit_issue_leaf');
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Processing...');

        $.ajax({
            url: 'processors/save_counterfoil_leaf_issue.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                submitBtn.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i> Issue Certificate');
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Certificate / Leaf Issued',
                        text: res.message,
                        confirmButtonColor: '#1e3c72'
                    }).then(function() {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            },
            error: function(xhr, status, err) {
                submitBtn.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i> Issue Certificate');
                Swal.fire('Processing Failed', 'Server error: ' + err, 'error');
            }
        });
    });
});
</script>
