/**
 * assets/js/farm.js - Farm Module Interactive Scripts
 * Handles DataTables initialization, modal population, live stock balance calculation, and SweetAlert dialogs.
 */

$(document).ready(function () {
    // 1. Initialize DataTables for Farm Module Tables
    if ($('#dailyFeedTable').length) {
        $('#dailyFeedTable').DataTable({
            responsive: true,
            order: [[0, 'desc']]
        });
    }

    if ($('#mashTable').length) {
        $('#mashTable').DataTable({
            responsive: true,
            paging: false,
            searching: false,
            info: false
        });
    }

    // 2. Filter Month Apply Handler
    $('#btn_apply_filter').on('click', function () {
        const mVal = $('#filter_month').val();
        let activeTabPane = 'daily';
        if ($('.nav-link.active').length) {
            const activeId = $('.nav-link.active').attr('id');
            if (activeId) {
                activeTabPane = activeId.replace('-tab', '');
            }
        }
        const currentUrl = window.location.pathname;
        window.location.href = currentUrl + '?month=' + encodeURIComponent(mVal) + '&tab=' + encodeURIComponent(activeTabPane);
    });

    // 3. Edit Daily Feed Modal Event Listener
    $(document).on('click', '.btn-edit-feed', function () {
        const btn = $(this);
        $('#edit_feed_id').val(btn.data('id'));
        $('#edit_feed_date').val(btn.data('distribution_date'));
        $('#edit_feed_cage_id').val(btn.data('cage_id'));
        $('#edit_feed_batch_no').val(btn.data('batch_no'));
        $('#edit_feed_type').val(btn.data('feed_type'));
        $('#edit_feed_no_of_chicks').val(btn.data('no_of_chicks'));
        $('#edit_feed_amount_needed').val(btn.data('amount_needed_kg'));
        $('#edit_feed_amount_distributed').val(btn.data('amount_distributed_kg'));
        $('#edit_feed_remarks').val(btn.data('remarks'));
    });

    // 4. Edit Annex 4 Mash Stock Modal Event Listener
    $(document).on('click', '.btn-edit-mash', function () {
        const btn = $(this);
        $('#edit_mash_id').val(btn.data('id'));
        $('#edit_mash_feed_type').val(btn.data('feed_type'));
        $('#edit_mash_consumption_kg').val(btn.data('consumption_kg'));
        $('#edit_mash_opening_stock_kg').val(btn.data('opening_stock_kg'));
        $('#edit_mash_received_kg').val(btn.data('received_kg'));
        $('#edit_mash_issued_other_farm_kg').val(btn.data('issued_other_farm_kg'));
        $('#edit_mash_balance_stock_kg').val(btn.data('balance_stock_kg'));
        $('#edit_mash_remarks').val(btn.data('remarks'));
    });

    // 5. Delete Confirmation SweetAlert
    $(document).on('click', '.btn-delete', function (e) {
        e.preventDefault();
        const deleteUrl = $(this).attr('href');
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Are you sure?',
                text: "This entry will be permanently deleted!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4016',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = deleteUrl;
                }
            });
        } else {
            if (confirm("Are you sure you want to delete this entry?")) {
                window.location.href = deleteUrl;
            }
        }
    });
});

// 6. Annex 4 Mash Balance Live Calculation
document.addEventListener('DOMContentLoaded', function () {
    function calcMashBalance() {
        const openingEl = document.getElementById('edit_mash_opening_stock_kg');
        const receivedEl = document.getElementById('edit_mash_received_kg');
        const consumptionEl = document.getElementById('edit_mash_consumption_kg');
        const issuedOtherEl = document.getElementById('edit_mash_issued_other_farm_kg');
        const balanceEl = document.getElementById('edit_mash_balance_stock_kg');

        if (openingEl && receivedEl && consumptionEl && issuedOtherEl && balanceEl) {
            const opening = parseFloat(openingEl.value) || 0;
            const received = parseFloat(receivedEl.value) || 0;
            const consumption = parseFloat(consumptionEl.value) || 0;
            const issuedOther = parseFloat(issuedOtherEl.value) || 0;

            const balance = (opening + received) - (consumption + issuedOther);
            balanceEl.value = balance.toFixed(2);
        }
    }

    const calcInputs = document.querySelectorAll('.mash-calc');
    calcInputs.forEach(function (input) {
        input.addEventListener('input', calcMashBalance);
    });

    const mashModal = document.getElementById('editMashModal');
    if (mashModal) {
        mashModal.addEventListener('shown.bs.modal', calcMashBalance);
    }
});
