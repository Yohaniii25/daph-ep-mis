/**
 * assets/js/farm.js - Farm Module Interactive Scripts
 * Handles DataTables initialization, modal population, live stock balance calculation, auto-calculations, and SweetAlert dialogs.
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

    if ($('#eggSalesTable').length) {
        $('#eggSalesTable').DataTable({
            responsive: true,
            order: [[0, 'desc']]
        });
    }

    if ($('#drugLedgerTable').length) {
        $('#drugLedgerTable').DataTable({
            responsive: true,
            order: [[0, 'asc']],
            language: {
                emptyTable: "No stock movement entries found for this drug item. Click 'Log Stock Movement' to add one."
            }
        });
    }

    if ($('#produceRegisterTable').length) {
        $('#produceRegisterTable').DataTable({
            responsive: true,
            order: [[0, 'desc']],
            language: {
                emptyTable: "No produce entries found for this commodity. Click 'Log Production & Disposal' to add one."
            }
        });
    }

    if ($('#fuelLedgerTable').length) {
        $('#fuelLedgerTable').DataTable({
            responsive: true,
            order: [[0, 'asc']],
            language: {
                emptyTable: "No fuel stock entries found for this item. Click 'Log Fuel Movement' to add one."
            }
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

    // 5. Edit Egg Sales Modal Population Event Listener
    $(document).on('click', '.btn-edit-egg-sale', function () {
        const btn = $(this);
        $('#edit_egg_sale_id').val(btn.data('id'));
        $('#edit_egg_sale_date').val(btn.data('sale_date'));
        $('#edit_egg_sale_cage_id').val(btn.data('cage_id'));
        $('#edit_egg_sale_batch_id').val(btn.data('batch_id'));

        $('#edit_table_eggs_no').val(btn.data('table_eggs_no'));
        $('#edit_table_eggs_kg').val(btn.data('table_eggs_kg'));
        $('#edit_table_eggs_unit_price').val(btn.data('table_eggs_unit_price'));
        $('#edit_table_eggs_total_sales').val(btn.data('table_eggs_total_sales'));

        $('#edit_cracked_eggs_no').val(btn.data('cracked_eggs_no'));
        $('#edit_cracked_eggs_kg').val(btn.data('cracked_eggs_kg'));
        $('#edit_cracked_eggs_unit_price').val(btn.data('cracked_eggs_unit_price'));
        $('#edit_cracked_eggs_total_sales').val(btn.data('cracked_eggs_total_sales'));

        $('#edit_egg_sale_remarks').val(btn.data('remarks'));
    });

    // 6. Edit Drug Stock Ledger Entry Event Listener
    $(document).on('click', '.btn-edit-drug-ledger', function () {
        const btn = $(this);
        $('#edit_drug_ledger_id').val(btn.data('id'));
        $('#edit_record_date').val(btn.data('record_date'));
        $('#edit_party_name').val(btn.data('party_name'));
        $('#edit_ref_doc_no').val(btn.data('ref_doc_no'));
        $('#edit_received_qty').val(btn.data('received_qty'));
        $('#edit_issued_qty').val(btn.data('issued_qty'));
        $('#edit_balance_qty').val(btn.data('balance_qty'));
        $('#edit_remarks').val(btn.data('remarks'));
    });

    // 7. Edit Produce Register Entry Event Listener
    $(document).on('click', '.btn-edit-produce', function () {
        const btn = $(this);
        $('#edit_produce_id').val(btn.data('id'));
        $('#edit_record_date').val(btn.data('record_date'));
        $('#edit_plot_no').val(btn.data('plot_no'));
        $('#edit_produce_qty').val(btn.data('quantity'));
        $('#edit_disposal_method').val(btn.data('disposal_method'));
        $('#edit_produce_unit_price').val(btn.data('unit_price'));
        $('#edit_produce_full_sum').val(btn.data('full_sum_realized'));
        $('#edit_receipt_no_or_page').val(btn.data('receipt_no_or_page'));
        $('#edit_initials').val(btn.data('initials'));
        $('#edit_remarks').val(btn.data('remarks'));
    });

    // 8. Edit Fuel Stock Ledger Entry Event Listener
    $(document).on('click', '.btn-edit-fuel-ledger', function () {
        const btn = $(this);
        $('#edit_fuel_ledger_id').val(btn.data('id'));
        $('#edit_fuel_record_date').val(btn.data('record_date'));
        $('#edit_fuel_party_name').val(btn.data('party_name'));
        $('#edit_fuel_ref_doc_no').val(btn.data('ref_doc_no'));
        $('#edit_fuel_received_qty').val(btn.data('received_qty'));
        $('#edit_fuel_issued_qty').val(btn.data('issued_qty'));
        $('#edit_fuel_balance_qty').val(btn.data('balance_qty'));
        $('#edit_fuel_remarks').val(btn.data('remarks'));
    });

    // 9. Edit Monthly Fuel Summary Event Listener
    $(document).on('click', '.btn-edit-fuel-summary', function () {
        const btn = $(this);
        $('#edit_fuel_summary_id').val(btn.data('id'));
        $('#edit_fuel_type_display').val(btn.data('fuel_type'));
        $('#edit_fuel_opening_stock').val(btn.data('opening_stock'));
        $('#edit_fuel_purchased').val(btn.data('purchased'));
        $('#edit_fuel_consumption').val(btn.data('consumption'));
        $('#edit_fuel_summary_balance').val(btn.data('balance'));
        $('#edit_fuel_summary_remarks').val(btn.data('remarks'));
    });

    // 10. Delete Confirmation SweetAlert
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

// 11. Live Calculation Utilities
document.addEventListener('DOMContentLoaded', function () {
    // Annex 4 Mash Balance Live Calculation
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

    // Drug Register Balance Live Calculation
    function calcDrugLiveBalance() {
        const recEl = document.getElementById('add_received_qty');
        const issEl = document.getElementById('add_issued_qty');
        const calcEl = document.getElementById('add_calculated_balance');

        if (recEl && issEl && calcEl) {
            const baseBal = (typeof currentItemBalance !== 'undefined') ? parseFloat(currentItemBalance) : 0;
            const recVal = parseFloat(recEl.value) || 0;
            const issVal = parseFloat(issEl.value) || 0;
            const newBal = (baseBal + recVal) - issVal;
            calcEl.value = newBal.toFixed(2);
        }
    }

    const addRecInput = document.getElementById('add_received_qty');
    const addIssInput = document.getElementById('add_issued_qty');
    if (addRecInput) addRecInput.addEventListener('input', calcDrugLiveBalance);
    if (addIssInput) addIssInput.addEventListener('input', calcDrugLiveBalance);

    // Fuel Register Balance Live Calculation
    function calcFuelLiveBalance() {
        const recEl = document.getElementById('add_fuel_received_qty');
        const issEl = document.getElementById('add_fuel_issued_qty');
        const calcEl = document.getElementById('add_fuel_calculated_balance');

        if (recEl && issEl && calcEl) {
            const baseBal = (typeof currentFuelBalance !== 'undefined') ? parseFloat(currentFuelBalance) : 0;
            const recVal = parseFloat(recEl.value) || 0;
            const issVal = parseFloat(issEl.value) || 0;
            const newBal = (baseBal + recVal) - issVal;
            calcEl.value = newBal.toFixed(2);
        }
    }

    const addFuelRecInput = document.getElementById('add_fuel_received_qty');
    const addFuelIssInput = document.getElementById('add_fuel_issued_qty');
    if (addFuelRecInput) addFuelRecInput.addEventListener('input', calcFuelLiveBalance);
    if (addFuelIssInput) addFuelIssInput.addEventListener('input', calcFuelLiveBalance);

    // Monthly Fuel Summary Live Calculation: (Opening Stock + Purchased) - Consumption
    function calcFuelSummaryLiveBalance() {
        const openingEl = document.getElementById('edit_fuel_opening_stock');
        const purchasedEl = document.getElementById('edit_fuel_purchased');
        const consumptionEl = document.getElementById('edit_fuel_consumption');
        const balanceEl = document.getElementById('edit_fuel_summary_balance');

        if (openingEl && purchasedEl && consumptionEl && balanceEl) {
            const opening = parseFloat(openingEl.value) || 0;
            const purchased = parseFloat(purchasedEl.value) || 0;
            const consumption = parseFloat(consumptionEl.value) || 0;
            const balance = (opening + purchased) - consumption;
            balanceEl.value = balance.toFixed(2);
        }
    }

    document.querySelectorAll('.fuel-summary-calc').forEach(function (el) {
        el.addEventListener('input', calcFuelSummaryLiveBalance);
    });

    const editFuelSummaryModal = document.getElementById('editMonthlyFuelModal');
    if (editFuelSummaryModal) {
        editFuelSummaryModal.addEventListener('shown.bs.modal', calcFuelSummaryLiveBalance);
    }

    // Produce Register Full Sum Realized Live Calculation (Quantity * Price per Unit)
    function calcProduceFullSum(prefix) {
        const qtyEl = document.getElementById(prefix + 'produce_qty');
        const priceEl = document.getElementById(prefix + 'produce_unit_price');
        const sumEl = document.getElementById(prefix + 'produce_full_sum');

        if (qtyEl && priceEl && sumEl) {
            const qtyVal = parseFloat(qtyEl.value) || 0;
            const priceVal = parseFloat(priceEl.value) || 0;
            const fullSum = qtyVal * priceVal;
            sumEl.value = fullSum.toFixed(2);
        }
    }

    document.querySelectorAll('.produce-calc').forEach(function (el) {
        el.addEventListener('input', function () { calcProduceFullSum('add_'); });
    });

    document.querySelectorAll('.edit-produce-calc').forEach(function (el) {
        el.addEventListener('input', function () { calcProduceFullSum('edit_'); });
    });

    // Egg Sales Frontend Live Auto-Calculations (Table & Cracked Eggs)
    function calcTableEggsSales(prefix) {
        const noEl = document.getElementById(prefix + 'table_eggs_no');
        const kgEl = document.getElementById(prefix + 'table_eggs_kg');
        const priceEl = document.getElementById(prefix + 'table_eggs_unit_price');
        const totalEl = document.getElementById(prefix + 'table_eggs_total_sales');

        if (priceEl && totalEl) {
            const noVal = parseFloat(noEl ? noEl.value : 0) || 0;
            const kgVal = parseFloat(kgEl ? kgEl.value : 0) || 0;
            const priceVal = parseFloat(priceEl.value) || 0;

            const qty = (noVal > 0) ? noVal : kgVal;
            const total = qty * priceVal;
            totalEl.value = total.toFixed(2);
        }
    }

    function calcCrackedEggsSales(prefix) {
        const noEl = document.getElementById(prefix + 'cracked_eggs_no');
        const kgEl = document.getElementById(prefix + 'cracked_eggs_kg');
        const priceEl = document.getElementById(prefix + 'cracked_eggs_unit_price');
        const totalEl = document.getElementById(prefix + 'cracked_eggs_total_sales');

        if (priceEl && totalEl) {
            const noVal = parseFloat(noEl ? noEl.value : 0) || 0;
            const kgVal = parseFloat(kgEl ? kgEl.value : 0) || 0;
            const priceVal = parseFloat(priceEl.value) || 0;

            const qty = (noVal > 0) ? noVal : kgVal;
            const total = qty * priceVal;
            totalEl.value = total.toFixed(2);
        }
    }

    // Add Modal Event Listeners
    document.querySelectorAll('.calc-table-egg').forEach(function(el) {
        el.addEventListener('input', function() { calcTableEggsSales('add_'); });
    });
    document.querySelectorAll('.calc-cracked-egg').forEach(function(el) {
        el.addEventListener('input', function() { calcCrackedEggsSales('add_'); });
    });

    // Edit Modal Event Listeners
    document.querySelectorAll('.edit-calc-table-egg').forEach(function(el) {
        el.addEventListener('input', function() { calcTableEggsSales('edit_'); });
    });
    document.querySelectorAll('.edit-calc-cracked-egg').forEach(function(el) {
        el.addEventListener('input', function() { calcCrackedEggsSales('edit_'); });
    });
});
