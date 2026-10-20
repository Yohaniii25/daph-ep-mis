<!-- Modal: Manage Archived / Discontinued Subcategories -->
<div class="modal fade" id="manageArchivedModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white" style="background-color: #370709;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-archive-fill text-warning fs-5"></i>
                    <h6 class="modal-title fw-bold mb-0">Archived & Discontinued Subcategories</h6>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-light-subtle">
                <div class="alert alert-info py-2 px-3 small d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-info-circle-fill fs-5 text-info"></i>
                    <div>
                        Items listed here are discontinued for upcoming reporting years to keep data entry clean. Their historical entries in previous years remain completely preserved. You can <strong>Reactivate</strong> an item anytime if it resumes operations.
                    </div>
                </div>

                <div class="table-responsive bg-white rounded-3 border">
                    <table class="table table-hover align-middle mb-0" id="tableArchivedItems">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th>Category</th>
                                <th>Item / Company Name</th>
                                <th>Unit</th>
                                <th>Archived Year</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody id="archivedItemsBody">
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <div class="spinner-border spinner-border-sm text-secondary me-2"></div> Loading archived items...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
