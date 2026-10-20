<!-- Modal: Statutory Schedule - 06 Monthly Cattle Voucher Return & Register -->
<div class="modal fade" id="modalSchedule06Return" tabindex="-1" aria-labelledby="modalSchedule06ReturnLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-fullscreen-lg-down">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-light py-3" style="background-color: #370709;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-ruled fs-4 text-warning"></i>
                    <div>
                        <h6 class="modal-title fw-bold mb-0" id="modalSchedule06ReturnLabel">Schedule - 06: Monthly Cattle Voucher Return</h6>
                        <small class="text-white-50">Department of Animal Production &amp; Health — Statutory Return of Cattle Vouchers Issued</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-light" id="btnPrintSchedule06">
                        <i class="bi bi-printer me-1"></i>Print Schedule - 06
                    </button>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            <div class="modal-body p-4" id="schedule06PrintArea">
                <!-- Statutory Form Header -->
                <div class="border-bottom pb-3 mb-3 text-center">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge bg-dark text-white font-monospace px-2 py-1">SCHEDULE - 06</span>
                        <div class="text-center flex-grow-1">
                            <h5 class="fw-bold text-uppercase mb-0 tracking-wide text-dark" style="letter-spacing: 0.5px;">DEPARTMENT OF ANIMAL PRODUCTION &amp; HEALTH</h5>
                            <h6 class="fw-bold text-uppercase text-secondary mb-1">Return of Cattle Vouchers Issued &amp; Animal Identification Particulars</h6>
                            <small class="text-muted">Prescribed under the Animals Act &amp; Ear Tagging / Voucher Automation Regulations</small>
                        </div>
                        <span class="badge bg-secondary text-white px-2 py-1" id="sched06CurrentDate"><?= date('d/m/Y') ?></span>
                    </div>

                    <div class="row g-2 mt-2 pt-2 border-top text-start small">
                        <div class="col-md-3">
                            <span class="text-muted d-block">Veterinary Surgeon Division / Range:</span>
                            <strong class="text-dark fs-6" id="sched06RangeName"><?= htmlspecialchars($range_name) ?> (Code: <?= htmlspecialchars($range_code) ?>)</strong>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted d-block">District:</span>
                            <strong class="text-dark fs-6" id="sched06DistrictName"><?= htmlspecialchars($district_name) ?></strong>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted d-block">Return Month &amp; Year:</span>
                            <strong class="text-primary fs-6" id="sched06MonthYearDisplay">—</strong>
                        </div>
                        <div class="col-md-3 text-md-end">
                            <span class="text-muted d-block">Total Vouchers in Return:</span>
                            <span class="badge bg-dark fs-6" id="sched06TotalCountBadge">0</span>
                        </div>
                    </div>
                </div>

                <!-- Schedule - 06 Summary KPI Pills -->
                <div class="row g-2 mb-3 d-print-none">
                    <div class="col-md-3">
                        <div class="p-2 border rounded bg-light text-center">
                            <small class="text-muted d-block">Total Issued / Registered</small>
                            <span class="h6 fw-bold font-monospace text-dark mb-0" id="sched06KpiTotal">0</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-2 border rounded bg-success-subtle text-center">
                            <small class="text-success-emphasis d-block fw-semibold">Active Registrations</small>
                            <span class="h6 fw-bold font-monospace text-success mb-0" id="sched06KpiActive">0</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-2 border rounded bg-warning-subtle text-center">
                            <small class="text-warning-emphasis d-block fw-semibold">Sold / Transferred</small>
                            <span class="h6 fw-bold font-monospace text-warning mb-0" id="sched06KpiTransferred">0</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-2 border rounded bg-danger-subtle text-center">
                            <small class="text-danger-emphasis d-block fw-semibold">Damaged Tags (Retained)</small>
                            <span class="h6 fw-bold font-monospace text-danger mb-0" id="sched06KpiDamaged">0</span>
                        </div>
                    </div>
                </div>

                <!-- Schedule - 06 Statutory Table -->
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-hover align-middle mb-0" id="tblSchedule06" style="font-size: 0.82rem;">
                        <thead class="table-dark small text-center align-middle">
                            <tr>
                                <th rowspan="2" style="width: 3%;">#</th>
                                <th rowspan="2" style="width: 9%;">Voucher Serial No.</th>
                                <th rowspan="2" style="width: 8%;">Issue Date</th>
                                <th colspan="3" class="bg-secondary text-white py-1">Owner / Farm Particulars</th>
                                <th colspan="7" class="bg-secondary text-white py-1">Animal Description &amp; Identification Particulars</th>
                                <th rowspan="2" style="width: 7%;">IO No.</th>
                                <th rowspan="2" style="width: 9%;">Present Status</th>
                            </tr>
                            <tr>
                                <th style="width: 11%;">Farmer / Owner Name</th>
                                <th style="width: 8%;">Owner NIC</th>
                                <th style="width: 9%;">Farm Reg. No.</th>
                                <th style="width: 10%;">Ear Tag No.</th>
                                <th style="width: 6%;">Species</th>
                                <th style="width: 7%;">Breed</th>
                                <th style="width: 5%;">Sex</th>
                                <th style="width: 4%;">Age</th>
                                <th style="width: 6%;">Colour</th>
                                <th style="width: 6%;">Purpose</th>
                            </tr>
                        </thead>
                        <tbody id="tblSchedule06Body">
                            <tr>
                                <td colspan="15" class="text-center py-4 text-muted">
                                    <i class="bi bi-arrow-repeat spin me-2"></i>Loading live cattle voucher return entries...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Statutory Certification Signature Section (Print Only) -->
                <div class="row mt-5 pt-4 text-center d-none d-print-flex">
                    <div class="col-4 border-top pt-2">
                        <p class="mb-0 fw-bold small">Prepared By (Data Entry Officer / VS Clerk)</p>
                        <small class="text-muted">Date: .......................................</small>
                    </div>
                    <div class="col-4 border-top pt-2">
                        <p class="mb-0 fw-bold small">Checked &amp; Verified By</p>
                        <small class="text-muted">Date: .......................................</small>
                    </div>
                    <div class="col-4 border-top pt-2">
                        <p class="mb-0 fw-bold small">Government Veterinary Surgeon (Seal &amp; Signature)</p>
                        <small class="text-muted">Date: .......................................</small>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light border-0 py-2 d-print-none">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-sm btn-primary" id="btnExportSchedule06Csv">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export Schedule - 06 (CSV)
                </button>
            </div>
        </div>
    </div>
</div>
