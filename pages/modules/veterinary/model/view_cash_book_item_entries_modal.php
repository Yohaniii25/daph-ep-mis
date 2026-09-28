<?php
/**
 * Modal: View Itemized Transactions for an Aggregated Revenue Item
 */
?>
<div class="modal fade" id="viewItemEntriesModal" tabindex="-1" aria-labelledby="viewItemEntriesLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-light py-3" style="background-color: #370709;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-receipt-cutoff fs-5 text-warning"></i>
                    <div>
                        <h6 class="modal-title fw-bold mb-0" id="viewItemEntriesLabel">Item Transactions Breakdown</h6>
                        <span class="small text-light opacity-75" id="viewItemEntriesPeriod"></span>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-light-subtle">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <span class="text-muted small">Revenue Stream:</span>
                        <h6 class="fw-bold text-dark mb-0" id="viewItemNameHeading">—</h6>
                    </div>
                    <button type="button" class="btn btn-sm text-white fw-bold shadow-sm btn-quick-add-for-item" style="background-color: #820100;">
                        <i class="bi bi-plus-circle me-1"></i>+ Add Entry For This Item
                    </button>
                </div>

                <div class="table-responsive bg-white rounded-3 border">
                    <table class="table table-hover align-middle mb-0" id="tableItemEntries">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th>Month</th>
                                <th class="text-end">Quantity Sold</th>
                                <th class="text-end">Unit Price (Rs.)</th>
                                <th class="text-end">Total Amount (Rs.)</th>
                                <th class="text-end">Amount Deposited (Rs.)</th>
                                <th class="text-end" style="width: 100px;">Actions</th>
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
