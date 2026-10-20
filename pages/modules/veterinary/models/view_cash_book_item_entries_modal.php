<?php
/**
 * Modal: View Itemized Transactions for an Aggregated Revenue Item
 */
?>
<div class="modal fade" id="viewItemEntriesModal" tabindex="-1" aria-labelledby="viewItemEntriesLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-light py-3" style="background-color: #370709;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-receipt-cutoff fs-5 text-warning"></i>
                    <div>
                        <h6 class="modal-title fw-bold mb-0" id="viewItemEntriesLabel">Counterfoil Receipts &amp; Transactions Breakdown</h6>
                        <span class="small text-light opacity-75" id="viewItemEntriesPeriod"></span>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-light-subtle">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                    <div>
                        <span class="text-muted small">Revenue Stream:</span>
                        <h6 class="fw-bold text-dark mb-0 fs-5" id="viewItemNameHeading">—</h6>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-warning text-dark border border-warning px-2.5 py-1.5 fw-bold" id="viewItemReceiptsCount">
                            <i class="bi bi-collection me-1"></i>0 Receipts Logged
                        </span>
                        <button type="button" class="btn btn-sm text-white fw-bold shadow-sm btn-quick-add-for-item" style="background-color: #820100;">
                            <i class="bi bi-plus-circle me-1"></i>+ Issue Receipt For This Item
                        </button>
                    </div>
                </div>

                <div class="table-responsive bg-white rounded-3 border">
                    <table class="table table-hover align-middle mb-0" id="tableItemEntries">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th style="width: 110px;">Date</th>
                                <th style="width: 140px;">Receipt / Leaf #</th>
                                <th style="min-width: 170px;">Owner Name (Client)</th>
                                <th style="min-width: 180px;">Purpose / Activity Item</th>
                                <th class="text-end" style="width: 80px;">Qty</th>
                                <th class="text-end" style="width: 120px;">Unit Price (Rs.)</th>
                                <th class="text-end" style="width: 130px;">Total (Rs.)</th>
                                <th class="text-end" style="width: 130px;">Deposited (Rs.)</th>
                                <th class="text-center" style="width: 90px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="itemEntriesTbody">
                            <!-- Populated via JavaScript -->
                        </tbody>
                        <tfoot class="table-light fw-bold border-top" id="itemEntriesTfoot">
                            <!-- Populated via JavaScript -->
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer py-2 bg-light border-0">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
