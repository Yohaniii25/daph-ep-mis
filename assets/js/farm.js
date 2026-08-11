/**
 * assets/js/farm.js - Farm Module Interactive Scripts
 * Handles DataTables initialization with CSV/PDF/Print export support, modal population, live calculations, and SweetAlert dialogs.
 */

/**
 * Reusable Helper for Initializing DataTables in Farm Module with CSV, PDF, and Print export support.
 * @param {string} selector - CSS selector of table
 * @param {object} customOptions - Overriding options
 */
function initFarmDataTable(selector, customOptions = {}) {
    if (!$(selector).length) return;

    // Avoid reinitialization error by checking if DataTables is already initialized
    if ($.fn.DataTable.isDataTable(selector)) {
        $(selector).DataTable().destroy();
    }

    const defaultButtons = [
        {
            extend: 'csvHtml5',
            text: '<i class="bi bi-file-earmark-csv me-1"></i>CSV',
            className: 'btn btn-sm btn-success me-1 shadow-sm fw-bold',
            exportOptions: { columns: ':visible:not(.no-export)' }
        },
        {
            extend: 'pdfHtml5',
            text: '<i class="bi bi-file-earmark-pdf me-1"></i>PDF',
            className: 'btn btn-sm btn-danger me-1 shadow-sm fw-bold',
            orientation: 'landscape',
            pageSize: 'A4',
            exportOptions: { columns: ':visible:not(.no-export)' }
        },
        {
            extend: 'print',
            text: '<i class="bi bi-printer me-1"></i>Print',
            className: 'btn btn-sm btn-secondary me-1 shadow-sm fw-bold',
            exportOptions: { columns: ':visible:not(.no-export)' }
        }
    ];

    const defaultDom = "<'row mb-3 align-items-center'<'col-md-7'B><'col-md-5 text-end'f>>" +
                       "<'row'<'col-sm-12'tr>>" +
                       "<'row mt-3 align-items-center'<'col-md-4'l><'col-md-8 text-end'p>>";

    const config = $.extend(true, {
        responsive: true,
        dom: defaultDom,
        buttons: defaultButtons,
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search table..."
        }
    }, customOptions);

    return $(selector).DataTable(config);
}

$(document).ready(function () {
    // 1. Initialize DataTables for all Farm Module Tables
    initFarmDataTable('#cattleDisposalTable', {
        order: [[0, 'desc']],
        language: { emptyTable: "No Cattle disposal records found for the selected filter period." }
    });
    initFarmDataTable('#whiteCattleDisposalTable', {
        order: [[0, 'desc']],
        language: { emptyTable: "No White Cattle disposal records found for the selected filter period." }
    });
    initFarmDataTable('#buffaloDisposalTable', {
        order: [[0, 'desc']],
        language: { emptyTable: "No Buffalo disposal records found for the selected filter period." }
    });
    initFarmDataTable('#goatDisposalTable', {
        order: [[0, 'desc']],
        language: { emptyTable: "No Goat disposal records found for the selected filter period." }
    });

    initFarmDataTable('#dailyFeedTable', { order: [[0, 'desc']] });
    initFarmDataTable('#mashTable', { paging: false, searching: true, info: false });

    initFarmDataTable('#eggSalesTable', { order: [[0, 'desc']] });

    initFarmDataTable('#drugLedgerTable', {
        order: [[0, 'asc']],
        language: {
            emptyTable: "No stock movement entries found for this drug item. Click 'Receive Order' or 'Issue Order' to add one."
        }
    });

    initFarmDataTable('#manageDrugItemsTable', { order: [[0, 'asc']] });

    initFarmDataTable('#produceRegisterTable', {
        order: [[0, 'desc']],
        language: {
            emptyTable: "No produce entries found for this commodity. Click 'Receive Produce' or 'Issue Produce' to add one."
        }
    });

    initFarmDataTable('#manageCommoditiesTable', { order: [[0, 'asc']] });

    initFarmDataTable('#fuelLedgerTable', {
        order: [[0, 'asc']],
        language: {
            emptyTable: "No fuel stock entries found for this item. Click 'Log Fuel Movement' to add one."
        }
    });

    initFarmDataTable('#fuelSummaryTable', { order: [[0, 'asc']] });
    initFarmDataTable('#accountsRegisterTable', { order: [[0, 'desc']] });
    initFarmDataTable('#hatcheryTable', { order: [[0, 'desc']], pageLength: 25 });

    initFarmDataTable('#dayOldTable', { order: [[0, 'desc']] });
    initFarmDataTable('#growthTable', { order: [[0, 'desc']] });
    initFarmDataTable('#monthOldTable', { order: [[0, 'desc']] });
    initFarmDataTable('#issuingTable', { order: [[0, 'desc']] });

    // Office Assets DataTables Initialization (PDF, CSV, Print export enabled)
    const landsTable = initFarmDataTable('#landsTable', { order: [[0, 'asc']] });
    initFarmDataTable('#inventoryTable', { order: [[0, 'asc']] });
    initFarmDataTable('#vehiclesTable', { order: [[0, 'asc']] });
    initFarmDataTable('#repairsTable', { order: [[0, 'desc']] });
    initFarmDataTable('#furnitureTable', { order: [[0, 'asc']] });
    initFarmDataTable('#machineryTable', { order: [[0, 'asc']] });
    initFarmDataTable('#instrumentsTable', { order: [[0, 'asc']] });
    initFarmDataTable('#counterfoilTable', { order: [[0, 'asc']] });
    initFarmDataTable('#employeeTable', { order: [[0, 'asc']] });

    // Filter Land Status on #landsTable (Column index 3)
    $('#filterLandStatus').on('change', function () {
        const val = $.trim($(this).val());
        if (landsTable) {
            landsTable.column(3).search(val ? val : '').draw();
        }
    });

    // 2. Filter Month / Form Apply Handler
    $('#btn_apply_filter').on('click', function (e) {
        const form = $(this).closest('form');
        if (form.length) {
            form.submit();
            return;
        }
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

    // 2b. Live Search Handler for Egg Sales Table
    $('#egg_sales_search').on('keyup search input', function() {
        if ($.fn.DataTable.isDataTable('#eggSalesTable')) {
            $('#eggSalesTable').DataTable().search($(this).val()).draw();
        }
    });

    // 2c. Export Dropdown Event Triggers for Egg Sales Table
    $(document).on('click', '.export-csv', function(e) {
        e.preventDefault();
        if ($.fn.DataTable.isDataTable('#eggSalesTable')) {
            $('#eggSalesTable').DataTable().button('.buttons-csv').trigger();
        }
    });
    $(document).on('click', '.export-pdf', function(e) {
        e.preventDefault();
        if ($.fn.DataTable.isDataTable('#eggSalesTable')) {
            $('#eggSalesTable').DataTable().button('.buttons-pdf').trigger();
        }
    });
    $(document).on('click', '.export-print', function(e) {
        e.preventDefault();
        if ($.fn.DataTable.isDataTable('#eggSalesTable')) {
            $('#eggSalesTable').DataTable().button('.buttons-print').trigger();
        }
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
        $('#edit_order_no').val(btn.data('order_no'));
        $('#edit_record_date').val(btn.data('record_date'));
        $('#edit_received_from').val(btn.data('received_from'));
        $('#edit_issued_to').val(btn.data('issued_to'));
        $('#edit_party_name').val(btn.data('party_name'));
        $('#edit_ref_doc_no').val(btn.data('ref_doc_no'));
        $('#edit_exp_date').val(btn.data('exp_date'));
        $('#edit_received_qty').val(btn.data('received_qty'));
        $('#edit_issued_qty').val(btn.data('issued_qty'));
        $('#edit_balance_qty').val(btn.data('balance_qty'));
        $('#edit_remarks').val(btn.data('remarks'));
    });

    // 6b. Edit Master Drug Item Event Listener
    $(document).on('click', '.btn-edit-master-item', function () {
        const btn = $(this);
        $('#edit_item_id').val(btn.data('id'));
        $('#edit_item_name').val(btn.data('item_name'));
        $('#edit_item_unit').val(btn.data('unit_of_measure'));
        $('#edit_item_exp_date').val(btn.data('exp_date'));
        $('#edit_item_desc').val(btn.data('description'));
    });

    // 7. Edit Produce Register Entry Event Listener
    $(document).on('click', '.btn-edit-produce', function () {
        const btn = $(this);
        $('#edit_produce_id').val(btn.data('id'));
        $('#edit_record_date').val(btn.data('record_date'));
        $('#edit_received_from').val(btn.data('received_from'));
        $('#edit_issued_to').val(btn.data('issued_to'));
        $('#edit_plot_no').val(btn.data('plot_no'));
        $('#edit_received_qty').val(btn.data('received_qty'));
        $('#edit_issued_qty').val(btn.data('issued_qty'));
        $('#edit_produce_qty').val(btn.data('quantity'));
        $('#edit_disposal_method').val(btn.data('disposal_method'));
        $('#edit_produce_unit_price').val(btn.data('unit_price'));
        $('#edit_produce_full_sum').val(btn.data('full_sum_realized'));
        $('#edit_receipt_no_or_page').val(btn.data('receipt_no_or_page'));
        $('#edit_initials').val(btn.data('initials'));
        $('#edit_remarks').val(btn.data('remarks'));
    });

    // 7b. Edit Master Commodity Item Event Listener
    $(document).on('click', '.btn-edit-master-commodity', function () {
        const btn = $(this);
        $('#edit_commodity_id').val(btn.data('id'));
        $('#edit_commodity_name').val(btn.data('commodity_name'));
        $('#edit_commodity_unit').val(btn.data('unit_of_measure'));
        $('#edit_commodity_desc').val(btn.data('description'));
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

    function calcReceiveOrderBalance() {
        const qtyEl = document.getElementById('receive_order_qty');
        const calcEl = document.getElementById('receive_order_calc_balance');
        if (qtyEl && calcEl) {
            const baseBal = (typeof currentItemBalance !== 'undefined') ? parseFloat(currentItemBalance) : 0;
            const recVal = parseFloat(qtyEl.value) || 0;
            calcEl.value = (baseBal + recVal).toFixed(2);
        }
    }

    function calcIssueOrderBalance() {
        const qtyEl = document.getElementById('issue_order_qty');
        const calcEl = document.getElementById('issue_order_calc_balance');
        if (qtyEl && calcEl) {
            const baseBal = (typeof currentItemBalance !== 'undefined') ? parseFloat(currentItemBalance) : 0;
            const issVal = parseFloat(qtyEl.value) || 0;
            calcEl.value = Math.max(0, baseBal - issVal).toFixed(2);
        }
    }

    const addRecInput = document.getElementById('add_received_qty');
    const addIssInput = document.getElementById('add_issued_qty');
    if (addRecInput) addRecInput.addEventListener('input', calcDrugLiveBalance);
    if (addIssInput) addIssInput.addEventListener('input', calcDrugLiveBalance);

    const recOrderInput = document.getElementById('receive_order_qty');
    const issOrderInput = document.getElementById('issue_order_qty');
    if (recOrderInput) recOrderInput.addEventListener('input', calcReceiveOrderBalance);
    if (issOrderInput) issOrderInput.addEventListener('input', calcIssueOrderBalance);

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

    function calcReceiveProduceBalance() {
        const qtyEl = document.getElementById('receive_produce_qty');
        const calcEl = document.getElementById('receive_produce_calc_balance');
        if (qtyEl && calcEl) {
            const baseBal = (typeof currentCommodityBalance !== 'undefined') ? parseFloat(currentCommodityBalance) : 0;
            const recVal = parseFloat(qtyEl.value) || 0;
            calcEl.value = (baseBal + recVal).toFixed(2);
        }
    }

    function calcIssueProduceBalance() {
        const qtyEl = document.getElementById('issue_produce_qty');
        const priceEl = document.getElementById('issue_produce_unit_price');
        const sumEl = document.getElementById('issue_produce_full_sum');
        const calcEl = document.getElementById('issue_produce_calc_balance');

        if (qtyEl && calcEl) {
            const baseBal = (typeof currentCommodityBalance !== 'undefined') ? parseFloat(currentCommodityBalance) : 0;
            const issVal = parseFloat(qtyEl.value) || 0;
            const priceVal = (priceEl) ? (parseFloat(priceEl.value) || 0) : 0;

            calcEl.value = Math.max(0, baseBal - issVal).toFixed(2);
            if (sumEl) {
                sumEl.value = (issVal * priceVal).toFixed(2);
            }
        }
    }

    const recProduceInput = document.getElementById('receive_produce_qty');
    if (recProduceInput) recProduceInput.addEventListener('input', calcReceiveProduceBalance);

    document.querySelectorAll('.produce-issue-calc').forEach(function (el) {
        el.addEventListener('input', calcIssueProduceBalance);
    });

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

    // Automatic Collection Data Fetching on Date, Cage, or Batch Change
    function autoFetchCollectionData() {
        const saleDate = $('#add_sale_date').val();
        const cageId = $('#add_cage_id').val();
        const batchId = $('#add_batch_id').val();

        const msgEl = $('#add_autofetch_msg');
        const spinner = $('#add_autofetch_spinner');

        if (!saleDate || !cageId || !batchId) {
            msgEl.html('<i class="bi bi-info-circle me-1"></i>Select Date, Cage, and Batch to automatically fetch collection numbers.');
            return;
        }

        spinner.show();
        msgEl.html('<i class="bi bi-arrow-repeat me-1"></i>Fetching collection data from Parent Stock Register...');

        $.ajax({
            url: 'processors/egg_sales_crud.php',
            method: 'POST',
            data: {
                action: 'get_collection',
                sale_date: saleDate,
                cage_id: cageId,
                batch_id: batchId
            },
            dataType: 'json',
            success: function (res) {
                spinner.hide();
                if (res.status === 'success') {
                    $('#add_table_eggs_no').val(res.table_eggs);
                    $('#add_table_eggs_kg').val(parseFloat(res.table_eggs_kg).toFixed(2));
                    $('#add_cracked_eggs_no').val(res.cracked_eggs);
                    $('#add_cracked_eggs_kg').val(parseFloat(res.cracked_eggs_kg).toFixed(2));

                    if (res.source === 'sales') {
                        if (res.table_eggs_unit_price > 0) {
                            $('#add_table_eggs_unit_price').val(parseFloat(res.table_eggs_unit_price).toFixed(2));
                        }
                        if (res.cracked_eggs_unit_price > 0) {
                            $('#add_cracked_eggs_unit_price').val(parseFloat(res.cracked_eggs_unit_price).toFixed(2));
                        }
                    }

                    calcTableEggsSales('add_');
                    calcCrackedEggsSales('add_');

                    const srcText = (res.source === 'sales') ? 'existing sales entry' : 'collection register';
                    msgEl.html('<i class="bi bi-check-circle-fill text-success me-1"></i><b>Auto-Fetched!</b> Data populated from ' + srcText + ' for ' + saleDate + '.');
                } else {
                    msgEl.html('<i class="bi bi-info-circle text-muted me-1"></i>No collection or sales record found for this Date, Cage & Batch. Enter numbers manually.');
                }
            },
            error: function () {
                spinner.hide();
                msgEl.html('<i class="bi bi-exclamation-triangle text-danger me-1"></i>Failed to query collection data.');
            }
        });
    }

    $(document).on('change input', '#add_sale_date, #add_cage_id, #add_batch_id', function () {
        autoFetchCollectionData();
    });

    $(document).on('shown.bs.modal', '#addEggSalesModal', function () {
        autoFetchCollectionData();
    });
});
