document.addEventListener("DOMContentLoaded", function () {
    let humanDatasetTableInstance = null;
    let animalDatasetTableInstance = null;
    let humanPieChartInstance = null;
    let animalPieChartInstance = null;

    // Register Center Text Plugin Layout Rules for Chart.js
    const centerTotalTextPlugin = {
        id: 'centerTotalText',
        afterDraw: function (chart) {
            if (chart.config.options.plugins.centerTotalText) {
                const ctx = chart.ctx;
                const chartArea = chart.chartArea;
                const configOptions = chart.config.options.plugins.centerTotalText;

                ctx.save();
                ctx.font = "bold 11px system-ui, sans-serif";
                ctx.fillStyle = "#64748b";
                ctx.textAlign = "center";
                ctx.textBaseline = "middle";
                const centerX = (chartArea.left + chartArea.right) / 2;
                const centerY = (chartArea.top + chartArea.bottom) / 2;
                ctx.fillText(configOptions.text.toUpperCase(), centerX, centerY - 10);

                ctx.font = "bold 20px system-ui, sans-serif";
                ctx.fillStyle = "#370709"; // Maroon Accent Indicator Text
                ctx.fillText(configOptions.value.toLocaleString(), centerX, centerY + 12);
                ctx.restore();
            }
        }
    };
    if (typeof Chart !== 'undefined') {
        Chart.register(centerTotalTextPlugin);
    }

    // Returns the currently checked ethnicity values (excludes the "All" master checkbox)
    function getSelectedEthnicities() {
        return Array.from(document.querySelectorAll(".ethnicity-option:checked"))
            .map(checkbox => checkbox.value);
    }

    // Keeps the dropdown button label in sync with what's checked
    function updateEthnicityButtonLabel() {
        const btn = document.getElementById("ethnicityDropdownBtn");
        const selected = getSelectedEthnicities();
        const totalOptions = document.querySelectorAll(".ethnicity-option").length;

        if (selected.length === 0) {
            btn.textContent = "None Selected";
        } else if (selected.length === totalOptions) {
            btn.textContent = "All Ethnicities";
        } else {
            btn.textContent = selected.join(", ");
        }
    }

    // Primary Core Database Fetcher Implementation
    function fetchFilteredPopulationData() {
        const filterYearEl = document.getElementById("filterYear");
        const filterPopTypeEl = document.getElementById("filterPopType");
        if (!filterYearEl || !filterPopTypeEl) return;

        const targetYear = filterYearEl.value;
        const targetPopType = filterPopTypeEl.value;
        const targetEthnicities = getSelectedEthnicities();

        const urlParams = new URLSearchParams({
            year: targetYear,
            pop_type: targetPopType,
            ethnicities: JSON.stringify(targetEthnicities),
            _t: Date.now()
        });
        if (typeof CURRENT_RANGE_ID !== 'undefined' && CURRENT_RANGE_ID) {
            urlParams.set('range_id', CURRENT_RANGE_ID);
        }

        fetch(`get_population_data.php?${urlParams.toString()}`)
            .then(response => response.json())
            .then(data => {
                let runningTotalSum = 0;

                // Calculate runtime column sum
                data.forEach(item => {
                    runningTotalSum += item.count;
                });

                // Restructure values into DataTable rows
                const processedTableRows = data.map(item => [
                    item.year,
                    item.ethnicity,
                    item.count.toLocaleString(),
                    runningTotalSum.toLocaleString()
                ]);

                // Sync data rows seamlessly into your existing DataTables instance
                const tableEl = $('#humanPopulationTable');
                if (tableEl.length) {
                    if ($.fn.DataTable.isDataTable('#humanPopulationTable')) {
                        humanDatasetTableInstance = tableEl.DataTable();
                        humanDatasetTableInstance.clear().rows.add(processedTableRows).draw();
                    } else {
                        humanDatasetTableInstance = tableEl.DataTable({
                            data: processedTableRows,
                            responsive: true,
                            dom: "<'row mb-2'<'col-sm-12 col-md-6'B><'col-sm-12 col-md-6'f>>" +
                                "<'row'<'col-sm-12'tr>>" +
                                "<'row mt-2'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                            buttons: [{
                                    extend: 'excelHtml5',
                                    className: 'btn btn-sm btn-success',
                                    text: '<i class="bi bi-file-earmark-spreadsheet"></i> CSV'
                                },
                                {
                                    extend: 'print',
                                    className: 'btn btn-sm btn-danger',
                                    text: '<i class="bi bi-printer me-1"></i> Print'
                                },
                                {
                                    extend: 'pdfHtml5',
                                    className: 'btn btn-sm btn-dark',
                                    text: '<i class="bi bi-file-earmark-pdf me-1"></i> PDF'
                                }
                            ],
                            pageLength: 5,
                            lengthChange: false,
                            ordering: false,
                            language: {
                                search: "_INPUT_",
                                searchPlaceholder: "Search records..."
                            }
                        });
                    }
                }

                // Isolate vectors to map directly onto the Chart labels object tracking matrices
                const chartLabels = data.map(item => item.ethnicity);
                const chartValues = data.map(item => item.count);
                const ethColorMap = {
                    'Sinhala': '#370709', // Deep Maroon
                    'Tamil': '#d97706',   // Amber / Orange
                    'Muslim': '#059669'   // Emerald Green
                };
                const chartColors = chartLabels.map(label => ethColorMap[label] || '#64748b');

                const canvasEl = document.getElementById('humanPopulationPieChart');
                if (canvasEl && typeof Chart !== 'undefined') {
                    if (!humanPieChartInstance) {
                        humanPieChartInstance = Chart.getChart(canvasEl) || null;
                    }

                    if (humanPieChartInstance) {
                        humanPieChartInstance.data.labels = chartLabels;
                        humanPieChartInstance.data.datasets[0].data = chartValues;
                        humanPieChartInstance.data.datasets[0].backgroundColor = chartColors;
                        if (humanPieChartInstance.options.plugins && humanPieChartInstance.options.plugins.centerTotalText) {
                            humanPieChartInstance.options.plugins.centerTotalText.text = targetPopType;
                            humanPieChartInstance.options.plugins.centerTotalText.value = runningTotalSum;
                        }
                        humanPieChartInstance.update();
                    } else {
                        const ctxCanvas = canvasEl.getContext('2d');
                        humanPieChartInstance = new Chart(ctxCanvas, {
                            type: 'doughnut',
                            data: {
                                labels: chartLabels,
                                datasets: [{
                                    data: chartValues,
                                    backgroundColor: chartColors,
                                    borderWidth: 2,
                                    borderColor: '#ffffff'
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                cutout: '70%',
                                plugins: {
                                    legend: {
                                        position: 'bottom',
                                        labels: {
                                            boxWidth: 12
                                        }
                                    },
                                    centerTotalText: {
                                        text: targetPopType,
                                        value: runningTotalSum
                                    }
                                }
                            }
                        });
                    }
                }
            })
            .catch(error => console.error('Error fetching dynamic dashboard profiles:', error));
    }
    window.fetchFilteredPopulationData = fetchFilteredPopulationData;

    // Attach simple listener hooks for the filter controls
    const filterYearEl = document.getElementById("filterYear");
    if (filterYearEl) {
        filterYearEl.addEventListener("change", fetchFilteredPopulationData);
    }
    const filterPopTypeEl = document.getElementById("filterPopType");
    if (filterPopTypeEl) {
        filterPopTypeEl.addEventListener("change", fetchFilteredPopulationData);
    }

    const ethnicityDropdownBtn = document.getElementById("ethnicityDropdownBtn");
    const ethnicityDropdownMenu = document.getElementById("ethnicityDropdownMenu");
    const ethnicityDropdownWrapper = document.getElementById("ethnicityDropdownWrapper");

    if (ethnicityDropdownBtn && ethnicityDropdownMenu && ethnicityDropdownWrapper) {
        ethnicityDropdownBtn.addEventListener("click", function (event) {
            event.stopPropagation();
            const isOpen = ethnicityDropdownMenu.classList.toggle("show");
            ethnicityDropdownBtn.setAttribute("aria-expanded", isOpen ? "true" : "false");
        });

        ethnicityDropdownMenu.addEventListener("click", function (event) {
            event.stopPropagation();
        });

        document.addEventListener("click", function (event) {
            if (!ethnicityDropdownWrapper.contains(event.target)) {
                ethnicityDropdownMenu.classList.remove("show");
                ethnicityDropdownBtn.setAttribute("aria-expanded", "false");
            }
        });
    }

    // "All" master checkbox toggles every ethnicity option
    const ethAllEl = document.getElementById("ethAll");
    if (ethAllEl) {
        ethAllEl.addEventListener("change", function () {
            document.querySelectorAll(".ethnicity-option").forEach(cb => cb.checked = this.checked);
            updateEthnicityButtonLabel();
            fetchFilteredPopulationData();
        });
    }

    // Individual ethnicity checkboxes keep "All" in sync and trigger a refetch
    document.querySelectorAll(".ethnicity-option").forEach(function (checkbox) {
        checkbox.addEventListener("change", function () {
            const allChecked = Array.from(document.querySelectorAll(".ethnicity-option")).every(cb => cb.checked);
            const ethAll = document.getElementById("ethAll");
            if (ethAll) {
                ethAll.checked = allChecked;
            }
            updateEthnicityButtonLabel();
            fetchFilteredPopulationData();
        });
    });

    function getSelectedAnimals() {
        return Array.from(document.querySelectorAll('.animal-option:checked'))
            .map(checkbox => checkbox.value);
    }

    function updateAnimalButtonLabel() {
        const btn = document.getElementById('animalDropdownBtn');
        if (!btn) return;
        const selected = getSelectedAnimals();
        const totalOptions = document.querySelectorAll('.animal-option').length;

        if (selected.length === 0) {
            btn.textContent = 'None Selected';
        } else if (selected.length === totalOptions) {
            btn.textContent = `All Animals Selected (${totalOptions})`;
        } else {
            const displaySelected = selected.map(s => s === 'Chicken' ? 'Poultry' : s);
            btn.textContent = displaySelected.join(', ');
        }
    }

    function fetchFilteredAnimalPopulationData() {
        const filterYearAnimalEl = document.getElementById('filterYearAnimal');
        if (!filterYearAnimalEl) return;

        const targetYear = filterYearAnimalEl.value;
        const targetAnimals = getSelectedAnimals();

        const urlParams = new URLSearchParams({
            year: targetYear,
            animals: JSON.stringify(targetAnimals),
            _t: Date.now()
        });
        if (typeof CURRENT_RANGE_ID !== 'undefined' && CURRENT_RANGE_ID) {
            urlParams.set('range_id', CURRENT_RANGE_ID);
        }

        fetch(`get_animal_population_data.php?${urlParams.toString()}`)
            .then(response => response.json())
            .then(data => {
                let runningTotalSum = 0;
                data.forEach(item => {
                    runningTotalSum += item.count;
                });

                const processedTableRows = data.map(item => [
                    item.year,
                    item.animal_type === 'Chicken' ? 'Poultry' : item.animal_type,
                    item.count.toLocaleString(),
                    runningTotalSum.toLocaleString()
                ]);

                const animalTableEl = $('#animalPopulationTable');
                if (animalTableEl.length) {
                    if ($.fn.DataTable.isDataTable('#animalPopulationTable')) {
                        animalDatasetTableInstance = animalTableEl.DataTable();
                        animalDatasetTableInstance.clear().rows.add(processedTableRows).draw();
                    } else {
                        animalDatasetTableInstance = animalTableEl.DataTable({
                            data: processedTableRows,
                            responsive: true,
                            dom: "<'row mb-2'<'col-sm-12 col-md-6'B><'col-sm-12 col-md-6'f>>" +
                                "<'row'<'col-sm-12'tr>>" +
                                "<'row mt-2'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                            buttons: [{
                                    extend: 'excelHtml5',
                                    className: 'btn btn-sm btn-success',
                                    text: '<i class="bi bi-file-earmark-spreadsheet"></i> CSV'
                                },
                                {
                                    extend: 'print',
                                    className: 'btn btn-sm btn-danger',
                                    text: '<i class="bi bi-printer me-1"></i> Print'
                                },
                                {
                                    extend: 'pdfHtml5',
                                    className: 'btn btn-sm btn-dark',
                                    text: '<i class="bi bi-file-earmark-pdf me-1"></i> PDF'
                                }
                            ],
                            pageLength: 5,
                            lengthChange: false,
                            ordering: false,
                            language: {
                                search: '_INPUT_',
                                searchPlaceholder: 'Search records...'
                            }
                        });
                    }
                }

                const chartLabels = data.map(item => item.animal_type === 'Chicken' ? 'Poultry' : item.animal_type);
                const chartValues = data.map(item => item.count);

                const animalCanvasEl = document.getElementById('animalPopulationPieChart');
                if (animalCanvasEl && typeof Chart !== 'undefined') {
                    if (!animalPieChartInstance) {
                        animalPieChartInstance = Chart.getChart(animalCanvasEl) || null;
                    }

                    if (animalPieChartInstance) {
                        animalPieChartInstance.data.labels = chartLabels;
                        animalPieChartInstance.data.datasets[0].data = chartValues;
                        if (animalPieChartInstance.options.plugins && animalPieChartInstance.options.plugins.centerTotalText) {
                            animalPieChartInstance.options.plugins.centerTotalText.text = 'Total Population';
                            animalPieChartInstance.options.plugins.centerTotalText.value = runningTotalSum;
                        }
                        animalPieChartInstance.update();
                    } else {
                        const ctxCanvas = animalCanvasEl.getContext('2d');
                        animalPieChartInstance = new Chart(ctxCanvas, {
                            type: 'doughnut',
                            data: {
                                labels: chartLabels,
                                datasets: [{
                                    data: chartValues,
                                    backgroundColor: ['#370709', '#a07174', '#e2e8f0', '#94a3b8', '#f59e0b', '#10b981'],
                                    borderWidth: 2,
                                    borderColor: '#ffffff'
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                cutout: '70%',
                                plugins: {
                                    legend: {
                                        position: 'bottom',
                                        labels: {
                                            boxWidth: 12
                                        }
                                    },
                                    centerTotalText: {
                                        text: 'Total Population',
                                        value: runningTotalSum
                                    }
                                }
                            }
                        });
                    }
                }
            })
            .catch(error => console.error('Error fetching animal dashboard profiles:', error));
    }
    window.fetchFilteredAnimalPopulationData = fetchFilteredAnimalPopulationData;

    const filterYearAnimalEl = document.getElementById('filterYearAnimal');
    if (filterYearAnimalEl) {
        filterYearAnimalEl.addEventListener('change', fetchFilteredAnimalPopulationData);
    }

    const animalDropdownBtn = document.getElementById('animalDropdownBtn');
    const animalDropdownMenu = document.getElementById('animalDropdownMenu');
    const animalDropdownWrapper = document.getElementById('animalDropdownWrapper');

    if (animalDropdownBtn && animalDropdownMenu && animalDropdownWrapper) {
        animalDropdownBtn.addEventListener('click', function (event) {
            event.stopPropagation();
            const isOpen = animalDropdownMenu.classList.toggle('show');
            animalDropdownBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        animalDropdownMenu.addEventListener('click', function (event) {
            event.stopPropagation();
        });

        document.addEventListener('click', function (event) {
            if (!animalDropdownWrapper.contains(event.target)) {
                animalDropdownMenu.classList.remove('show');
                animalDropdownBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }

    const animAllEl = document.getElementById('animAll');
    if (animAllEl) {
        animAllEl.addEventListener('change', function () {
            document.querySelectorAll('.animal-option').forEach(cb => cb.checked = this.checked);
            updateAnimalButtonLabel();
            fetchFilteredAnimalPopulationData();
        });
    }

    document.querySelectorAll('.animal-option').forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            const allChecked = Array.from(document.querySelectorAll('.animal-option')).every(cb => cb.checked);
            const animAll = document.getElementById('animAll');
            if (animAll) {
                animAll.checked = allChecked;
            }
            updateAnimalButtonLabel();
            fetchFilteredAnimalPopulationData();
        });
    });

    if (document.getElementById('animalDropdownBtn')) {
        updateAnimalButtonLabel();
        fetchFilteredAnimalPopulationData();
    }
    if (document.getElementById('ethnicityDropdownBtn')) {
        updateEthnicityButtonLabel();
        fetchFilteredPopulationData();
    }
});

/* =============================================================
   MODULE: CROP RETURNS (crop_returns.php)
   ============================================================= */
/**
 * DAPH EP-MIS - Crop Returns Module Script
 * Location: assets/js/crop_returns.js
 */

$(document).ready(function() {
    if (!$('#cropGridForm').length && !window.CropReturnsConfig) return;
    var cfg = window.CropReturnsConfig || {};

    var officerName        = cfg.officerName || 'Veterinary Surgeon';
    var officerDesignation = cfg.officerDesignation || 'Government Veterinary Surgeon';
    var ministryDepartment = cfg.ministryDepartment || '';
    var reportPeriod       = cfg.reportPeriod || '';
    var rangeName          = cfg.rangeName || '';
    var districtName       = cfg.districtName || '';
    var selectedYear       = cfg.selectedYear || new Date().getFullYear();
    var fromMonth          = cfg.fromMonth || 1;
    var toMonth            = cfg.toMonth || 3;

    var chartMonths        = cfg.chartMonths || [];
    var chartMonthlyIssued = cfg.chartMonthlyIssued || [];
    var chartCumulativeIssued = cfg.chartCumulativeIssued || [];

    var annualCropData     = cfg.annualCropData || {};
    var standardItems      = cfg.standardItems || [];
    var prevBalances       = cfg.prevBalances || {};
    var monthNames         = cfg.monthNames || {};
    var monthShorts        = cfg.monthShorts || {};

    // -------------------------------------------------------------
    // 1. INITIALIZE CHART.JS (PROGRESSIVE CUMULATIVE YTD TREND)
    // -------------------------------------------------------------
    var ctxCrop = document.getElementById('cropYtdChart');
    if (ctxCrop && typeof Chart !== 'undefined') {
        var cropYtdChart = new Chart(ctxCrop.getContext('2d'), {
            type: 'bar',
            data: {
                labels: chartMonths,
                datasets: [
                    {
                        label: 'Monthly Items Issued',
                        data: chartMonthlyIssued,
                        backgroundColor: 'rgba(130, 1, 0, 0.45)',
                        borderColor: '#820100',
                        borderWidth: 1,
                        order: 2
                    },
                    {
                        label: 'Progressive Cumulative YTD Issued',
                        data: chartCumulativeIssued,
                        type: 'line',
                        borderColor: '#2b3a4a',
                        backgroundColor: 'rgba(43, 58, 74, 0.1)',
                        fill: false,
                        tension: 0.3,
                        borderWidth: 2.5,
                        pointRadius: 4,
                        pointBackgroundColor: '#820100',
                        order: 1
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return Number(value).toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }

    // -------------------------------------------------------------
    // 2. INITIALIZE CROP RETURN SUMMARY MATRIX DATATABLE
    // -------------------------------------------------------------
    var cropSummaryTable = null;
    if ($('#cropSummaryTable').length && $.fn.DataTable) {
        cropSummaryTable = $('#cropSummaryTable').DataTable({
            "pageLength": 25,
            "dom": '<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
            "buttons": [
                {
                    extend: 'csv',
                    className: 'btn btn-sm btn-success me-2',
                    text: '<i class="bi bi-file-earmark-spreadsheet"></i> CSV'
                },
                {
                    extend: 'pdf',
                    className: 'btn btn-sm btn-danger me-2',
                    text: '<i class="bi bi-file-earmark-pdf"></i> PDF',
                    orientation: 'landscape',
                    pageSize: 'A4',
                    title: 'Crop Return Summary - ' + selectedYear
                },
                {
                    extend: 'print',
                    className: 'btn btn-sm btn-dark',
                    text: '<i class="bi bi-printer"></i> Print'
                }
            ],
            "language": {
                "search": "Search summary:"
            }
        });

        // Mid-Year and Annual Export Triggers
        $('#btnExportMidYearCrop').on('click', function() {
            cropSummaryTable.button('.buttons-pdf').trigger();
        });

        $('#btnExportAnnualCrop').on('click', function() {
            cropSummaryTable.button('.buttons-csv').trigger();
        });
    }

    // -------------------------------------------------------------
    // 3. DYNAMIC MULTI-MONTH RANGE SUMMARY LOGIC (e.g. Jan – March)
    // -------------------------------------------------------------
    var rangeTableInstance = null;

    function renderRangeSummary(fromM, toM) {
        fromM = parseInt(fromM) || 1;
        toM   = parseInt(toM) || 12;
        if (toM < fromM) {
            toM = fromM;
            $('#rangeToMonth').val(toM);
        }

        var rangeLabel = (monthNames[fromM] || ('Month ' + fromM)) + ' – ' + (monthNames[toM] || ('Month ' + toM)) + ' ' + selectedYear;
        $('#rangeLabelText').text(rangeLabel);
        $('#pillInterval').text(rangeLabel);
        $('#printPeriodRangeText').text(rangeLabel);
        $('#printPeriodSub').text(rangeLabel);
        $('.lblFromMonth').text(monthShorts[fromM] || fromM);
        $('.lblToMonth').text(monthShorts[toM] || toM);

        var totalPeriodReceived = 0;
        var totalPeriodIssued = 0;
        var activeItemCount = 0;

        var screenHtml = '';
        var printHtml = '';

        standardItems.forEach(function(itemName, idx) {
            var rowIdx = idx + 1;
            var itemData = (annualCropData && annualCropData[itemName]) ? annualCropData[itemName] : {};

            // 1. Opening Balance at start of fromM
            var opening = 0;
            if (itemData[fromM] && itemData[fromM].prev !== undefined) {
                opening = parseInt(itemData[fromM].prev) || 0;
            } else if (fromM === 1) {
                opening = parseInt(prevBalances[itemName]) || 0;
            } else {
                for (var pm = fromM - 1; pm >= 1; pm--) {
                    if (itemData[pm] && itemData[pm].balance !== undefined) {
                        opening = parseInt(itemData[pm].balance) || 0;
                        break;
                    }
                }
            }

            // 2. Total Received & Total Issued in [fromM..toM]
            var receivedSum = 0;
            var issuedSum = 0;
            var breakdownParts = [];

            for (var m = fromM; m <= toM; m++) {
                var mRec = (itemData[m] && itemData[m].received !== undefined) ? parseInt(itemData[m].received) : 0;
                var mIss = (itemData[m] && itemData[m].issued !== undefined) ? parseInt(itemData[m].issued) : 0;
                receivedSum += mRec;
                issuedSum += mIss;
                var mLabel = monthShorts[m] || ('M' + m);
                breakdownParts.push('<span class="badge bg-light text-dark border me-1 mb-1 font-monospace" style="font-size:0.75rem;">' + mLabel + ': ' + mIss + '</span>');
            }

            totalPeriodReceived += receivedSum;
            totalPeriodIssued += issuedSum;
            if (receivedSum > 0 || issuedSum > 0 || opening > 0) {
                activeItemCount++;
            }

            // 3. Closing Balance at end of toM
            var closing = 0;
            if (itemData[toM] && itemData[toM].balance !== undefined) {
                closing = parseInt(itemData[toM].balance) || 0;
            } else {
                closing = opening + receivedSum - issuedSum;
            }

            var netChange = closing - opening;
            var netBadge = '';
            if (netChange > 0) {
                netBadge = ' <small class="text-success fw-bold">(+' + netChange + ')</small>';
            } else if (netChange < 0) {
                netBadge = ' <small class="text-danger fw-bold">(' + netChange + ')</small>';
            }

            // Screen Table row
            screenHtml += '<tr>' +
                '<td class="text-center text-muted fw-semibold">' + rowIdx + '</td>' +
                '<td><strong>' + itemName + '</strong>' + netBadge + '</td>' +
                '<td class="text-end font-monospace bg-light fw-bold">' + opening.toLocaleString() + '</td>' +
                '<td class="text-end font-monospace text-success fw-bold">' + receivedSum.toLocaleString() + '</td>' +
                '<td class="text-end font-monospace text-danger fw-bold">' + issuedSum.toLocaleString() + '</td>' +
                '<td class="text-end font-monospace bg-warning-subtle text-dark fw-bold">' + closing.toLocaleString() + '</td>' +
                '<td class="text-center">' + breakdownParts.join('') + '</td>' +
                '</tr>';

            // Print Table row
            printHtml += '<tr>' +
                '<td class="text-center">' + rowIdx + '</td>' +
                '<td><strong>' + itemName + '</strong></td>' +
                '<td class="text-end font-monospace">' + opening.toLocaleString() + '</td>' +
                '<td class="text-end font-monospace">' + receivedSum.toLocaleString() + '</td>' +
                '<td class="text-end font-monospace">' + issuedSum.toLocaleString() + '</td>' +
                '<td class="text-end font-monospace fw-bold">' + closing.toLocaleString() + '</td>' +
                '</tr>';
        });

        // Update pills
        $('#pillTotalReceived').text(totalPeriodReceived.toLocaleString());
        $('#pillTotalIssued').text(totalPeriodIssued.toLocaleString());
        $('#pillActiveItems').text(activeItemCount + ' / ' + standardItems.length + ' with activity');

        // Populate print table
        $('#printRangeBody').html(printHtml);

        // Update screen table with DataTables
        if (rangeTableInstance) {
            rangeTableInstance.destroy();
        }
        $('#rangeSummaryBody').html(screenHtml);

        if ($('#rangeSummaryTable').length && $.fn.DataTable) {
            rangeTableInstance = $('#rangeSummaryTable').DataTable({
                "pageLength": 25,
                "dom": '<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
                "buttons": [
                    {
                        extend: 'csv',
                        className: 'btn btn-sm btn-success me-2',
                        text: '<i class="bi bi-file-earmark-spreadsheet"></i> CSV',
                        title: 'Crop Return Period Summary (' + rangeLabel + ')'
                    },
                    {
                        extend: 'pdf',
                        className: 'btn btn-sm btn-danger me-2',
                        text: '<i class="bi bi-file-earmark-pdf"></i> PDF',
                        orientation: 'landscape',
                        pageSize: 'A4',
                        title: 'Crop Return Period Summary (' + rangeLabel + ')'
                    },
                    {
                        extend: 'print',
                        className: 'btn btn-sm btn-dark',
                        text: '<i class="bi bi-printer"></i> Print'
                    }
                ],
                "language": {
                    "search": "Search period items:"
                }
            });
        }
    }

    // Trigger initial render for range summary if table exists
    if ($('#rangeSummaryTable').length) {
        renderRangeSummary(fromMonth, toMonth);
    }

    // Apply button click
    $('#btnApplyRange').on('click', function() {
        var f = parseInt($('#rangeFromMonth').val()) || 1;
        var t = parseInt($('#rangeToMonth').val()) || 12;
        $('.btn-range-preset').removeClass('active fw-bold');
        renderRangeSummary(f, t);
    });

    // Preset buttons click
    $('.btn-range-preset').on('click', function() {
        var f = parseInt($(this).data('from')) || 1;
        var t = parseInt($(this).data('to')) || 12;
        $('#rangeFromMonth').val(f);
        $('#rangeToMonth').val(t);
        $('.btn-range-preset').removeClass('active fw-bold');
        $(this).addClass('active fw-bold');
        renderRangeSummary(f, t);
    });

    // Range export triggers
    $('#btnExportRangeCsv').on('click', function() {
        if (rangeTableInstance) rangeTableInstance.button('.buttons-csv').trigger();
    });

    $('#btnExportRangePdf').on('click', function() {
        if (rangeTableInstance) rangeTableInstance.button('.buttons-pdf').trigger();
    });

    $('#btnPrintRangeReport').on('click', function() {
        $('#printableSummaryReport').show();
        window.print();
        setTimeout(function() {
            $('#printableSummaryReport').hide();
        }, 1000);
    });

    // Adjust DataTable column widths when switching tabs
    $('button[data-bs-toggle="pill"]').on('shown.bs.tab', function(e) {
        if ($.fn.dataTable) {
            $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
        }
    });

    // -------------------------------------------------------------
    // 4. LIVE BALANCE RECALCULATION
    // Formula: Balance = Balance Previous Month + Received - Issued
    // -------------------------------------------------------------
    function updateRowBalance(idx) {
        var prev     = parseInt($('#prev_' + idx).val()) || 0;
        var received = parseInt($('#received_' + idx).val()) || 0;
        var issued   = parseInt($('#issued_' + idx).val()) || 0;
        var bal      = prev + received - issued;

        var $balInput = $('#balance_' + idx);
        $balInput.val(bal);

        if (bal < 0) {
            $balInput.addClass('is-invalid text-danger').removeClass('bg-light text-dark');
        } else {
            $balInput.removeClass('is-invalid text-danger').addClass('bg-light text-dark');
        }
    }

    $(document).on('input', '.input-prev, .input-received, .input-issued', function() {
        var idx = $(this).data('idx');
        updateRowBalance(idx);
    });

    // -------------------------------------------------------------
    // 5. AJAX BATCH SAVE
    // -------------------------------------------------------------
    $('#cropGridForm').on('submit', function(e) {
        e.preventDefault();
        var $btn = $(this).find('button[type="submit"]');
        var originalBtnHtml = $btn.html();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

        $.ajax({
            url: 'processors/save_crop_returns_grid.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                $btn.prop('disabled', false).html(originalBtnHtml);
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Crop Return Saved!',
                        text: 'All 20 standard items for ' + reportPeriod + ' have been successfully saved.',
                        confirmButtonColor: '#820100'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Could not save crop return records.',
                        confirmButtonColor: '#820100'
                    });
                }
            },
            error: function() {
                $btn.prop('disabled', false).html(originalBtnHtml);
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: 'Failed to communicate with the server.',
                    confirmButtonColor: '#820100'
                });
            }
        });
    });

    // -------------------------------------------------------------
    // 6. CHECK URL STATUS PARAMETERS
    // -------------------------------------------------------------
    var urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('status') === 'saved' || urlParams.get('status') === 'added') {
        Swal.fire({
            icon: 'success',
            title: 'Saved!',
            text: 'Crop Return records updated successfully.',
            confirmButtonColor: '#820100'
        });
        window.history.replaceState({}, document.title, window.location.pathname + window.location.search.replace(/[\?&]status=[^&]+/, ''));
    }
});


/* =============================================================
   MODULE: SECTION E PRODUCTION BALANCES (section_e.php)
   ============================================================= */
/**
 * DAPH EP-MIS - Section E: Production Details Script
 * Location: assets/js/section_e.js
 */

$(document).ready(function() {
    if (!$('#productionGridForm').length && !window.SectionEConfig) return;
    var cfg = window.SectionEConfig || {};

    var officerName        = cfg.officerName || 'Veterinary Surgeon';
    var officerDesignation = cfg.officerDesignation || 'Government Veterinary Surgeon';
    var ministryDepartment = cfg.ministryDepartment || '';
    var rangeName          = cfg.rangeName || '';
    var districtName       = cfg.districtName || '';
    var selectedYear       = parseInt(cfg.selectedYear) || new Date().getFullYear();
    var fromMonth          = parseInt(cfg.fromMonth) || 1;
    var toMonth            = parseInt(cfg.toMonth) || 3;
    var activeMonth        = parseInt(cfg.activeMonth) || (new Date().getMonth() + 1);

    var monthNames         = cfg.monthNames || {};
    var monthShorts        = cfg.monthShorts || {};
    var categories         = cfg.categories || [];
    var itemsByCat         = cfg.itemsByCat || {};
    var monthlyData        = cfg.monthlyData || {}; // [itemId][month] => amount

    var consolidatedTableInstance = null;

    // -------------------------------------------------------------
    // 1. DYNAMIC MULTI-MONTH RANGE AGGREGATION ENGINE
    // -------------------------------------------------------------
    function renderConsolidatedRangeSummary(fromM, toM) {
        fromM = parseInt(fromM) || 1;
        toM   = parseInt(toM) || 12;
        if (toM < fromM) {
            toM = fromM;
            $('#rangeToMonth').val(toM);
        }

        var rangeLabel = monthNames[fromM] + ' – ' + monthNames[toM] + ' ' + selectedYear;
        $('#kpiPeriodText').text(rangeLabel);
        $('#pillPeriodLabel').text(rangeLabel);
        $('#printRangeLabel').text(rangeLabel);
        $('.lblPeriodFrom').text(monthShorts[fromM]);
        $('.lblPeriodTo').text(monthShorts[toM]);

        var activeItemCount = 0;
        var grandOutputSum  = 0.0;
        var screenHtml      = '';
        var printHtml       = '';
        var globalRowIdx    = 1;

        categories.forEach(function(cat) {
            var catId   = parseInt(cat.id);
            var catName = cat.category_name;
            var items   = itemsByCat[catId] || [];

            if (items.length === 0) return;

            var catPeriodSum = 0.0;

            items.forEach(function(it) {
                var itemId   = parseInt(it.id);
                var itemName = it.item_name;
                var unit     = it.unit || '';
                var itData   = monthlyData[itemId] || {};

                var periodSum = 0.0;
                var monthPills = [];

                for (var m = fromM; m <= toM; m++) {
                    var mVal = parseFloat(itData[m]) || 0.0;
                    periodSum += mVal;
                    monthPills.push('<span class="badge bg-light text-dark border me-1 mb-1 font-monospace" style="font-size:0.75rem;">' + monthShorts[m] + ': ' + mVal.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 2}) + '</span>');
                }

                catPeriodSum += periodSum;
                grandOutputSum += periodSum;

                if (periodSum > 0) {
                    activeItemCount++;
                }

                // Screen Row (Exactly 6 columns to match the 6 thead th elements)
                screenHtml += '<tr>' +
                    '<td class="text-center text-muted fw-semibold">' + globalRowIdx + '</td>' +
                    '<td><span class="badge bg-light text-dark border"><i class="bi bi-tag-fill me-1 text-danger"></i>' + catName + '</span></td>' +
                    '<td><strong>' + itemName + '</strong></td>' +
                    '<td class="text-center"><span class="badge bg-light text-dark border badge-unit">' + unit + '</span></td>' +
                    '<td class="text-end font-monospace fw-bold text-danger table-period-sum">' + periodSum.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</td>' +
                    '<td class="text-center">' + monthPills.join('') + '</td>' +
                '</tr>';

                // Print Row (Exactly 5 columns to match the 5 print thead th elements)
                printHtml += '<tr>' +
                    '<td class="text-center">' + globalRowIdx + '</td>' +
                    '<td>' + catName + '</td>' +
                    '<td><strong>' + itemName + '</strong></td>' +
                    '<td class="text-center">' + unit + '</td>' +
                    '<td class="text-end font-monospace fw-bold">' + periodSum.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</td>' +
                '</tr>';

                globalRowIdx++;
            });
        });

        // Update KPI Badges
        $('#kpiItemsActive').text(activeItemCount);
        $('#kpiGrandTotal').text(grandOutputSum.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));

        // Populate print table body
        $('#printConsolidatedBody').html(printHtml);

        // Update Screen DataTable (Destroy previous instance first to avoid tn/18 warning)
        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#consolidatedSummaryTable')) {
            $('#consolidatedSummaryTable').DataTable().destroy();
            consolidatedTableInstance = null;
        }
        $('#consolidatedSummaryBody').html(screenHtml);

        if ($.fn.DataTable) {
            consolidatedTableInstance = $('#consolidatedSummaryTable').DataTable({
                "pageLength": 25,
                "ordering": true,
                "order": [[0, 'asc']],
                "dom": '<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
                "buttons": [
                    {
                        extend: 'csv',
                        className: 'btn btn-sm btn-success me-2',
                        text: '<i class="bi bi-file-earmark-spreadsheet"></i> CSV',
                        title: 'Section E Consolidated Production (' + rangeLabel + ')'
                    },
                    {
                        extend: 'pdf',
                        className: 'btn btn-sm btn-danger me-2',
                        text: '<i class="bi bi-file-earmark-pdf"></i> PDF',
                        orientation: 'landscape',
                        pageSize: 'A4',
                        title: 'Section E Consolidated Production (' + rangeLabel + ')'
                    },
                    {
                        extend: 'print',
                        className: 'btn btn-sm btn-dark',
                        text: '<i class="bi bi-printer"></i> Print'
                    }
                ],
                "language": {
                    "search": "Search consolidated metrics:"
                }
            });
        }
    }

    // Initial render of Consolidated Range Summary
    renderConsolidatedRangeSummary(fromMonth, toMonth);

    // Filter controls
    $('#btnApplyRange').on('click', function() {
        var f = parseInt($('#rangeFromMonth').val()) || 1;
        var t = parseInt($('#rangeToMonth').val()) || 12;
        $('.btn-period-preset').removeClass('active fw-bold');
        renderConsolidatedRangeSummary(f, t);
    });

    $('.btn-period-preset').on('click', function() {
        var f = parseInt($(this).data('from')) || 1;
        var t = parseInt($(this).data('to')) || 12;
        $('#rangeFromMonth').val(f);
        $('#rangeToMonth').val(t);
        $('.btn-period-preset').removeClass('active fw-bold');
        $(this).addClass('active fw-bold');
        renderConsolidatedRangeSummary(f, t);
    });

    $('#rangeYear').on('change', function() {
        var y = $(this).val();
        var f = $('#rangeFromMonth').val();
        var t = $('#rangeToMonth').val();
        window.location.href = 'section_e.php?year=' + y + '&from_month=' + f + '&to_month=' + t + '&month=' + activeMonth;
    });

    // -------------------------------------------------------------
    // 2. LIVE SEMEN BALANCE AUTO-CALCULATION (TAB 10)
    // Formula:
    // Balance available end of month (Item 103) =
    //   Available at beginning (97)
    // + Received Semen (100)
    // - Used semen (98)
    // - Spoiled or damage semen (99)
    // - Issued semen (101)
    // -------------------------------------------------------------
    function updateSemenBalanceLive() {
        var availBeginning = parseFloat($('input[data-item-name="Available Semen at beginning of the month"]').val()) || 0.0;
        var used           = parseFloat($('input[data-item-name="Used semen"]').val()) || 0.0;
        var spoiled        = parseFloat($('input[data-item-name="Spoiled or damage semen"]').val()) || 0.0;
        var received       = parseFloat($('input[data-item-name="Received Semen"]').val()) || 0.0;
        var issued         = parseFloat($('input[data-item-name="Issued semen"]').val()) || 0.0;

        var endingBalance = availBeginning + received - used - spoiled - issued;
        var $balInput = $('input[data-item-name="Balance available end of the month"]');
        if ($balInput.length) {
            $balInput.val(endingBalance.toFixed(2));
            if (endingBalance < 0) {
                $balInput.addClass('is-invalid text-danger').removeClass('bg-light text-dark');
            } else {
                $balInput.removeClass('is-invalid text-danger').addClass('bg-light text-dark');
            }
        }
    }

    $(document).on('input', '.semen-item-input', function() {
        updateSemenBalanceLive();
    });

    // Run initial semen calculation check
    updateSemenBalanceLive();

    // -------------------------------------------------------------
    // 3. TAB SWITCHING COLUMN ADJUSTMENT & DATATABLES
    // -------------------------------------------------------------
    $('button[data-bs-toggle="pill"]').on('shown.bs.tab', function(e) {
        if ($.fn.DataTable) {
            $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
        }
    });

    // -------------------------------------------------------------
    // 4. QUICK ADD SUBCATEGORY MODAL
    // -------------------------------------------------------------
    $(document).on('click', '.btn-add-subcat-quick', function() {
        var catId = $(this).data('cat-id');
        if (catId) {
            $('#add_subcat_category_id').val(catId);
        }
        if (typeof bootstrap !== 'undefined') {
            var modalEl = document.getElementById('addSubCategoryModal');
            if (modalEl) {
                new bootstrap.Modal(modalEl).show();
            }
        }
    });

    // -------------------------------------------------------------
    // 5. AJAX BATCH SAVE
    // -------------------------------------------------------------
    $('#productionGridForm').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('button[type="submit"]');
        var origHtml = $btn.html();

        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');

        $.ajax({
            url: 'processors/save_production_grid.php',
            type: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            success: function(resp) {
                $btn.prop('disabled', false).html(origHtml);
                if (resp.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Production Saved!',
                        text: 'All Section E production values for ' + (monthNames[activeMonth] || 'the month') + ' ' + selectedYear + ' have been successfully saved.',
                        confirmButtonColor: '#820100'
                    }).then(function() {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Save Failed',
                        text: resp.message || 'Could not save production records.',
                        confirmButtonColor: '#820100'
                    });
                }
            },
            error: function() {
                $btn.prop('disabled', false).html(origHtml);
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: 'Failed to communicate with the server.',
                    confirmButtonColor: '#820100'
                });
            }
        });
    });

    // -------------------------------------------------------------
    // 6. ISOLATED OFFICIAL REPORT PRINT & EXPORT HANDLERS
    // -------------------------------------------------------------
    $('#btnPrintSectionEReport').on('click', function() {
        $('#printableSectionEReport').show();
        window.print();
        setTimeout(function() {
            $('#printableSectionEReport').hide();
        }, 1000);
    });

    // Consolidated Table Export Triggers
    $('#btnTopExportCsv, #btnConsolidatedCsv').on('click', function() {
        if (consolidatedTableInstance) {
            consolidatedTableInstance.button('.buttons-csv').trigger();
        } else {
            Swal.fire('Notice', 'Consolidated table is initializing, please try again.', 'info');
        }
    });

    $('#btnTopExportPdf, #btnConsolidatedPdf').on('click', function() {
        if (consolidatedTableInstance) {
            consolidatedTableInstance.button('.buttons-pdf').trigger();
        } else {
            Swal.fire('Notice', 'Consolidated table is initializing, please try again.', 'info');
        }
    });

    $('#btnConsolidatedPrint').on('click', function() {
        if (consolidatedTableInstance) {
            consolidatedTableInstance.button('.buttons-print').trigger();
        }
    });

    // Category Tab CSV Export
    $(document).on('click', '.btn-export-cat-csv', function() {
        var catId = $(this).data('cat-id');
        var catName = $(this).data('cat-name') || ('Category_' + catId);
        var $tab = $('#tab_cat_' + catId);
        var csvContent = [];

        // Headers
        var headers = [];
        $tab.find('thead th').each(function() {
            var txt = $(this).text().replace(/\r?\n|\r/g, ' ').replace(/\s+/g, ' ').trim();
            headers.push('"' + txt.replace(/"/g, '""') + '"');
        });
        csvContent.push(headers.join(','));

        // Rows
        $tab.find('tbody tr').each(function() {
            var rowData = [];
            $(this).find('td').each(function() {
                var $input = $(this).find('input[type="number"]');
                var val = $input.length ? $input.val() : $(this).text();
                val = val.replace(/\r?\n|\r/g, ' ').replace(/\s+/g, ' ').trim();
                rowData.push('"' + val.replace(/"/g, '""') + '"');
            });
            if (rowData.length) {
                csvContent.push(rowData.join(','));
            }
        });

        var blob = new Blob([csvContent.join('\n')], { type: 'text/csv;charset=utf-8;' });
        var link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.setAttribute('download', catName.replace(/[^a-z0-9]/gi, '_').toLowerCase() + '_' + selectedYear + '.csv');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });

    // Category Tab PDF Export
    $(document).on('click', '.btn-export-cat-pdf', function() {
        var catId = $(this).data('cat-id');
        var catName = $(this).data('cat-name') || ('Category ' + catId);
        var $tab = $('#tab_cat_' + catId);

        if (typeof pdfMake === 'undefined') {
            Swal.fire('Notice', 'PDF export library is loading, please try again.', 'info');
            return;
        }

        var headerRow = [];
        $tab.find('thead th').each(function() {
            var txt = $(this).text().replace(/\r?\n|\r/g, ' ').replace(/\s+/g, ' ').trim();
            headerRow.push({ text: txt, style: 'tableHeader' });
        });

        var bodyRows = [headerRow];
        $tab.find('tbody tr').each(function() {
            var row = [];
            $(this).find('td').each(function() {
                var $input = $(this).find('input[type="number"]');
                var val = $input.length ? $input.val() : $(this).text();
                val = val.replace(/\r?\n|\r/g, ' ').replace(/\s+/g, ' ').trim();
                row.push({ text: val, fontSize: 8 });
            });
            if (row.length === headerRow.length) {
                bodyRows.push(row);
            }
        });

        var docDefinition = {
            pageOrientation: 'landscape',
            pageSize: 'A4',
            pageMargins: [20, 20, 20, 20],
            content: [
                { text: 'DEPARTMENT OF ANIMAL PRODUCTION & HEALTH - EASTERN PROVINCE', style: 'mainTitle' },
                { text: 'Section E: ' + catName + ' (' + selectedYear + ')', style: 'subTitle' },
                { text: 'Range: ' + rangeName + ' | District: ' + districtName + ' | Officer: ' + officerName + ' (' + officerDesignation + ')', style: 'metaText', margin: [0, 0, 0, 10] },
                {
                    table: {
                        headerRows: 1,
                        widths: Array(headerRow.length).fill('*'),
                        body: bodyRows
                    },
                    layout: 'lightHorizontalLines'
                },
                {
                    margin: [0, 25, 0, 0],
                    columns: [
                        { text: '............................................\nSignature of Veterinary Surgeon', alignment: 'center', fontSize: 9 },
                        { text: '............................................\nOfficial Rubber Stamp', alignment: 'center', fontSize: 9 }
                    ]
                }
            ],
            styles: {
                mainTitle: { fontSize: 13, bold: true, alignment: 'center', color: '#820100', margin: [0, 0, 0, 3] },
                subTitle: { fontSize: 11, bold: true, alignment: 'center', margin: [0, 0, 0, 3] },
                metaText: { fontSize: 8.5, alignment: 'center', color: '#555555' },
                tableHeader: { fontSize: 8.5, bold: true, fillColor: '#f1f5f9', color: '#1e293b' }
            }
        };

        pdfMake.createPdf(docDefinition).download(catName.replace(/[^a-z0-9]/gi, '_').toLowerCase() + '_' + selectedYear + '.pdf');
    });

    // -------------------------------------------------------------
    // 7. CHART.JS PROGRESSIVE CUMULATIVE YTD INITIALIZATION
    // -------------------------------------------------------------
    var ctxSecE = document.getElementById('secEYtdChart');
    if (ctxSecE && typeof Chart !== 'undefined' && cfg.chartMonths) {
        new Chart(ctxSecE.getContext('2d'), {
            type: 'bar',
            data: {
                labels: cfg.chartMonths,
                datasets: [
                    {
                        label: 'Monthly Output Sum',
                        data: cfg.chartMonthlyTotals,
                        backgroundColor: 'rgba(130, 1, 0, 0.45)',
                        borderColor: '#820100',
                        borderWidth: 1,
                        order: 2
                    },
                    {
                        label: 'Progressive Cumulative YTD',
                        data: cfg.chartCumulativeTotals,
                        type: 'line',
                        borderColor: '#2b3a4a',
                        backgroundColor: 'rgba(43, 58, 74, 0.1)',
                        fill: false,
                        tension: 0.3,
                        borderWidth: 2.5,
                        pointRadius: 4,
                        pointBackgroundColor: '#820100',
                        order: 1
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(val) {
                                return Number(val).toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }

    // -------------------------------------------------------------
    // 8. URL STATUS & TAB RESTORATION
    // -------------------------------------------------------------
    var urlParams = new URLSearchParams(window.location.search);
    var activeTabParam = urlParams.get('tab');
    if (activeTabParam) {
        var $targetTabBtn = $('#' + activeTabParam + '-btn');
        if ($targetTabBtn.length) {
            $targetTabBtn.tab('show');
        }
    }

    if (urlParams.get('status') === 'saved' || urlParams.get('status') === 'added' || urlParams.get('status') === 'reactivated') {
        var msg = 'Section E production data updated successfully.';
        if (urlParams.get('status') === 'added') msg = 'Subcategory added successfully!';
        if (urlParams.get('status') === 'reactivated') msg = 'Subcategory reactivated successfully!';
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: msg,
            confirmButtonColor: '#820100'
        });
        window.history.replaceState({}, document.title, window.location.pathname + window.location.search.replace(/[\?&]status=[^&]+/, ''));
    }

    // -------------------------------------------------------------
    // 9. QUICK ADD SUBCATEGORY MODAL
    // -------------------------------------------------------------
    $(document).on('click', '.btn-add-subcat-quick', function() {
        var catId = $(this).data('cat-id');
        var catName = $(this).data('cat-name');
        $('#add_subcat_category_id').val(catId);
        $('#add_subcat_active_tab').val('tab_cat_' + catId);
        var modal = new bootstrap.Modal(document.getElementById('addSubCategoryModal'));
        modal.show();
    });

    // -------------------------------------------------------------
    // 10. SAFE DELETE & ARCHIVE SUBCATEGORY HANDLER
    // -------------------------------------------------------------
    $(document).on('click', '.btn-delete-item', function(e) {
        e.preventDefault();
        var itemId   = $(this).data('item-id');
        var itemName = $(this).data('item-name');
        var currYear = $(this).data('year') || selectedYear;

        // Check historical usage
        Swal.fire({
            title: 'Checking item records...',
            text: 'Please wait while we verify historical data.',
            allowOutsideClick: false,
            didOpen: function() {
                Swal.showLoading();
            }
        });

        $.getJSON('processors/manage_production_item.php', {
            action: 'check_usage',
            item_id: itemId
        }, function(res) {
            Swal.close();
            if (!res.success) {
                Swal.fire('Error', res.message || 'Unable to check item usage.', 'error');
                return;
            }

            if (!res.has_data) {
                // Zero recorded usage -> Safe to permanently delete
                Swal.fire({
                    title: 'Delete Subcategory?',
                    html: 'Subcategory <strong>' + itemName + '</strong> has <strong>no recorded production data</strong> in any year.<br><br>It will be permanently deleted from the system.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="bi bi-trash3 me-1"></i> Delete Permanently',
                    cancelButtonText: 'Cancel'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        $.post('processors/manage_production_item.php', {
                            action: 'delete',
                            item_id: itemId
                        }, function(delRes) {
                            if (delRes.success) {
                                Swal.fire('Deleted!', delRes.message, 'success').then(function() {
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire('Error', delRes.message, 'error');
                            }
                        }, 'json');
                    }
                });
            } else {
                // Has historical entries -> Must archive to protect past records
                Swal.fire({
                    title: 'Archive for Next Year?',
                    html: '<div class="text-start p-2">' +
                          '<p>Subcategory <strong>' + itemName + '</strong> has <strong>' + res.non_zero_rows + ' recorded entry(s)</strong> in year(s): <span class="badge bg-secondary">' + res.recorded_years + '</span>.</p>' +
                          '<div class="alert alert-warning small mb-3">' +
                          '<i class="bi bi-exclamation-triangle-fill me-1"></i> To protect your official government reports and audit history, this item cannot be permanently deleted.' +
                          '</div>' +
                          '<p class="mb-0">Would you like to <strong>Archive / Discontinue</strong> it starting from <strong>' + currYear + '</strong>?<br>' +
                          '<small class="text-muted">It will not appear in future data entry, while all past reports in previous years remain 100% intact.</small></p>' +
                          '</div>',
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonColor: '#820100',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="bi bi-archive me-1"></i> Archive Starting ' + currYear,
                    cancelButtonText: 'Keep Active'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        $.post('processors/manage_production_item.php', {
                            action: 'archive',
                            item_id: itemId,
                            archived_year: currYear
                        }, function(archRes) {
                            if (archRes.success) {
                                Swal.fire('Archived!', archRes.message, 'success').then(function() {
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire('Error', archRes.message, 'error');
                            }
                        }, 'json');
                    }
                });
            }
        }).fail(function() {
            Swal.fire('Error', 'Failed to communicate with the server.', 'error');
        });
    });

    // -------------------------------------------------------------
    // 11. MANAGE ARCHIVED / DISCONTINUED ITEMS MODAL
    // -------------------------------------------------------------
    $(document).on('click', '.btn-manage-archived-quick', function() {
        var catId = $(this).data('cat-id');
        var catName = $(this).data('cat-name');
        var modalEl = document.getElementById('manageArchivedModal');
        var modal = new bootstrap.Modal(modalEl);
        modal.show();

        $('#archivedItemsBody').html('<tr><td colspan="5" class="text-center text-muted py-4"><div class="spinner-border spinner-border-sm text-secondary me-2"></div> Loading archived items for ' + catName + '...</td></tr>');

        $.getJSON('processors/manage_production_item.php', {
            action: 'list_archived',
            category_id: catId
        }, function(res) {
            if (!res.success || !res.archived_items || res.archived_items.length === 0) {
                $('#archivedItemsBody').html('<tr><td colspan="5" class="text-center text-muted py-4"><i class="bi bi-check2-circle text-success me-1"></i> No discontinued or archived items in this category. All items are active.</td></tr>');
                return;
            }

            var rowsHtml = '';
            res.archived_items.forEach(function(item) {
                rowsHtml += '<tr>' +
                    '<td><span class="badge bg-light text-dark border">' + item.category_name + '</span></td>' +
                    '<td><strong>' + item.item_name + '</strong></td>' +
                    '<td><span class="badge bg-secondary-subtle text-secondary">' + (item.unit || '—') + '</span></td>' +
                    '<td><span class="badge bg-warning text-dark font-monospace">' + (item.archived_year || 'Archived') + '</span></td>' +
                    '<td class="text-end">' +
                        '<button type="button" class="btn btn-sm btn-success btn-reactivate-item" data-item-id="' + item.id + '" data-item-name="' + item.item_name + '">' +
                            '<i class="bi bi-arrow-counterclockwise me-1"></i> Reactivate' +
                        '</button>' +
                    '</td>' +
                '</tr>';
            });
            $('#archivedItemsBody').html(rowsHtml);
        }).fail(function() {
            $('#archivedItemsBody').html('<tr><td colspan="5" class="text-center text-danger py-4">Error loading archived items.</td></tr>');
        });
    });

    // -------------------------------------------------------------
    // 12. REACTIVATE SUBCATEGORY
    // -------------------------------------------------------------
    $(document).on('click', '.btn-reactivate-item', function() {
        var itemId   = $(this).data('item-id');
        var itemName = $(this).data('item-name');

        Swal.fire({
            title: 'Reactivate Subcategory?',
            html: 'Do you want to reactivate <strong>' + itemName + '</strong>?<br><br>It will become active again in Section E data entry.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            confirmButtonText: 'Yes, Reactivate',
            cancelButtonText: 'Cancel'
        }).then(function(result) {
            if (result.isConfirmed) {
                $.post('processors/manage_production_item.php', {
                    action: 'reactivate',
                    item_id: itemId
                }, function(res) {
                    if (res.success) {
                        Swal.fire('Reactivated!', res.message, 'success').then(function() {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                }, 'json');
            }
        });
    });
});


/* =============================================================
   MODULE: LETTER 'H' ACCOUNT (letter_h_record.php)
   ============================================================= */
/**
 * DAPH EP-MIS - Letter 'H' Account (Cash Settlement in Bank) Script
 * Location: assets/js/letter_h.js
 */

$(document).ready(function() {
    if (!$('#letterHGridForm').length && !window.LetterHConfig) return;
    var cfg = window.LetterHConfig || {};

    var officerName        = cfg.officerName || 'Veterinary Surgeon';
    var officerDesignation = cfg.officerDesignation || 'Government Veterinary Surgeon';
    var ministryDepartment = cfg.ministryDepartment || '';
    var rangeName          = cfg.rangeName || '';
    var districtName       = cfg.districtName || '';
    var selectedYear       = parseInt(cfg.selectedYear) || new Date().getFullYear();
    var fromMonth          = parseInt(cfg.fromMonth) || 1;
    var toMonth            = parseInt(cfg.toMonth) || 3;
    var activeMonth        = parseInt(cfg.activeMonth) || (new Date().getMonth() + 1);

    var monthNames         = cfg.monthNames || {};
    var monthShorts        = cfg.monthShorts || {};
    var categories         = cfg.categories || [];
    var itemsByCat         = cfg.itemsByCat || {};
    var monthlyData        = cfg.monthlyData || {}; // [itemId][month] => amount

    var consolidatedTableInstance = null;

    // Helper: format currency
    function formatMoney(num) {
        return parseFloat(num || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // -------------------------------------------------------------
    // 1. MULTI-MONTH RANGE AGGREGATION & SUB-TOTALS CALCULATION
    // -------------------------------------------------------------
    function renderLetterHConsolidatedSummary(fromM, toM) {
        fromM = parseInt(fromM) || 1;
        toM   = parseInt(toM) || 12;
        if (toM < fromM) {
            toM = fromM;
            $('#rangeToMonth').val(toM);
        }

        var rangeLabel = monthNames[fromM] + ' – ' + monthNames[toM] + ' ' + selectedYear;
        $('#kpiPeriodInterval').text(rangeLabel);
        $('#pillPeriodLabel').text(rangeLabel);
        $('#printPeriodLabel').text(rangeLabel);
        $('.lblPeriodInterval').text('(' + monthShorts[fromM] + '–' + monthShorts[toM] + ')');

        var grandPeriodTotal = 0.0;
        var grandActiveTotal = 0.0;
        var screenHtml       = '';
        var printHtml        = '';
        var globalRowIdx     = 1;

        // Loop over the 4 main categories
        categories.forEach(function(cat) {
            var catId   = parseInt(cat.id);
            var catName = cat.category_name;
            var items   = itemsByCat[catId] || [];

            var catActiveSum = 0.0;
            var catPeriodSum = 0.0;

            items.forEach(function(it) {
                var itemId   = parseInt(it.id);
                var itemName = it.item_name;
                var itData   = monthlyData[itemId] || {};

                // Active Month Amount (from live input if available or data)
                var $activeInput = $('#amount_' + itemId);
                var activeVal = $activeInput.length && $activeInput.val() !== '' 
                    ? parseFloat($activeInput.val()) || 0.0 
                    : (parseFloat(itData[activeMonth]) || 0.0);

                catActiveSum += activeVal;

                // Period Total across [fromM..toM]
                var periodSum = 0.0;
                for (var m = fromM; m <= toM; m++) {
                    if (m === activeMonth && $activeInput.length && $activeInput.val() !== '') {
                        periodSum += (parseFloat($activeInput.val()) || 0.0);
                    } else {
                        periodSum += (parseFloat(itData[m]) || 0.0);
                    }
                }

                catPeriodSum += periodSum;

                // Update row's period total in the tab table
                $('#period_total_' + itemId).text(formatMoney(periodSum));

                // Screen Row for Consolidated Table (6 explicit columns)
                screenHtml += '<tr>' +
                    '<td class="text-center text-muted fw-semibold">' + globalRowIdx + '</td>' +
                    '<td><span class="badge bg-light text-dark border">' + catName + '</span></td>' +
                    '<td><strong>' + itemName + '</strong></td>' +
                    '<td class="text-end font-monospace text-dark">' + formatMoney(activeVal) + '</td>' +
                    '<td class="text-end font-monospace fw-bold text-danger table-period-total">' + formatMoney(periodSum) + '</td>' +
                '</tr>';

                // Print Row for Isolated Printable Report (5 columns)
                printHtml += '<tr>' +
                    '<td class="text-center">' + globalRowIdx + '</td>' +
                    '<td>' + catName + '</td>' +
                    '<td><strong>' + itemName + '</strong></td>' +
                    '<td class="text-end font-monospace">' + formatMoney(activeVal) + '</td>' +
                    '<td class="text-end font-monospace fw-bold">' + formatMoney(periodSum) + '</td>' +
                '</tr>';

                globalRowIdx++;
            });

            // Update Tab Subtotals
            $('#tab_subtotal_amount_' + catId).text(formatMoney(catActiveSum));
            $('#tab_subtotal_period_' + catId).text(formatMoney(catPeriodSum));

            grandActiveTotal += catActiveSum;
            grandPeriodTotal += catPeriodSum;
        });

        // Update Master Grand Totals (Sum across all 4 tabs)
        $('#kpiGrandTotal').text(formatMoney(grandPeriodTotal));
        $('#kpiActiveMonthTotal').text(formatMoney(grandActiveTotal));
        $('#bannerGrandTotal').text('LKR ' + formatMoney(grandPeriodTotal));
        $('#bannerActiveTotal').text('LKR ' + formatMoney(grandActiveTotal));
        $('#printGrandTotal').text(formatMoney(grandPeriodTotal));
        $('#printActiveTotal').text(formatMoney(grandActiveTotal));

        // Populate print table body
        $('#printLetterHBody').html(printHtml);

        // Update Screen DataTable
        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#consolidatedLetterHTable')) {
            $('#consolidatedLetterHTable').DataTable().destroy();
            consolidatedTableInstance = null;
        }
        $('#consolidatedLetterHBody').html(screenHtml);

        if ($.fn.DataTable) {
            consolidatedTableInstance = $('#consolidatedLetterHTable').DataTable({
                "pageLength": 25,
                "ordering": true,
                "order": [[0, 'asc']],
                "dom": '<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
                "buttons": [
                    {
                        extend: 'csv',
                        className: 'btn btn-sm btn-success me-2',
                        text: '<i class="bi bi-file-earmark-spreadsheet"></i> CSV',
                        title: 'Letter H Consolidated Accounts (' + rangeLabel + ')'
                    },
                    {
                        extend: 'pdf',
                        className: 'btn btn-sm btn-danger me-2',
                        text: '<i class="bi bi-file-earmark-pdf"></i> PDF',
                        orientation: 'portrait',
                        pageSize: 'A4',
                        title: 'Letter H Account - ' + rangeLabel
                    },
                    {
                        extend: 'print',
                        className: 'btn btn-sm btn-dark',
                        text: '<i class="bi bi-printer"></i> Print'
                    }
                ],
                "language": {
                    "search": "Search account items:"
                }
            });
        }
    }

    // Initial render
    renderLetterHConsolidatedSummary(fromMonth, toMonth);

    // -------------------------------------------------------------
    // 2. LIVE INPUT RECALCULATION
    // -------------------------------------------------------------
    $(document).on('input', '.input-revenue-amount', function() {
        var f = parseInt($('#rangeFromMonth').val()) || 1;
        var t = parseInt($('#rangeToMonth').val()) || 12;
        renderLetterHConsolidatedSummary(f, t);
    });

    // -------------------------------------------------------------
    // 3. DATE RANGE CONTROLS & PRESETS
    // -------------------------------------------------------------
    $('#btnApplyRange').on('click', function() {
        var f = parseInt($('#rangeFromMonth').val()) || 1;
        var t = parseInt($('#rangeToMonth').val()) || 12;
        $('.btn-period-preset').removeClass('active fw-bold');
        renderLetterHConsolidatedSummary(f, t);
    });

    $('.btn-period-preset').on('click', function() {
        var f = parseInt($(this).data('from')) || 1;
        var t = parseInt($(this).data('to')) || 12;
        $('#rangeFromMonth').val(f);
        $('#rangeToMonth').val(t);
        $('.btn-period-preset').removeClass('active fw-bold');
        $(this).addClass('active fw-bold');
        renderLetterHConsolidatedSummary(f, t);
    });

    $('#rangeYear').on('change', function() {
        var y = $(this).val();
        var f = $('#rangeFromMonth').val();
        var t = $('#rangeToMonth').val();
        window.location.href = 'letter_h_record.php?year=' + y + '&from_month=' + f + '&to_month=' + t + '&month=' + activeMonth;
    });

    // -------------------------------------------------------------
    // 4. TAB SWITCHING COLUMN ADJUSTMENT
    // -------------------------------------------------------------
    $('button[data-bs-toggle="pill"]').on('shown.bs.tab', function(e) {
        if ($.fn.DataTable) {
            $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
        }
    });

    // -------------------------------------------------------------
    // 5. EXPORT HANDLERS (CSV & PDF)
    // -------------------------------------------------------------
    $('#btnTopExportCsv, #btnConsolidatedCsv').on('click', function() {
        if (consolidatedTableInstance) {
            consolidatedTableInstance.button('.buttons-csv').trigger();
        } else {
            Swal.fire('Notice', 'Consolidated table is initializing, please try again.', 'info');
        }
    });

    $('#btnTopExportPdf, #btnConsolidatedPdf').on('click', function() {
        if (consolidatedTableInstance) {
            consolidatedTableInstance.button('.buttons-pdf').trigger();
        } else {
            Swal.fire('Notice', 'Consolidated table is initializing, please try again.', 'info');
        }
    });

    $('#btnConsolidatedPrint').on('click', function() {
        if (consolidatedTableInstance) {
            consolidatedTableInstance.button('.buttons-print').trigger();
        }
    });

    // Print Official Report
    $('#btnPrintLetterHReport').on('click', function() {
        $('#printableLetterHReport').show();
        window.print();
        setTimeout(function() {
            $('#printableLetterHReport').hide();
        }, 1000);
    });

    // Export Individual Tab to CSV
    $(document).on('click', '.btn-export-tab-csv', function() {
        var tabId = $(this).data('tab-id');
        var tabName = $(this).data('tab-name') || ('Letter_H_' + tabId);
        var $tab = $('#' + tabId);
        var csvContent = [];

        var headers = [];
        $tab.find('thead th').each(function() {
            var txt = $(this).text().replace(/\r?\n|\r/g, ' ').replace(/\s+/g, ' ').trim();
            headers.push('"' + txt.replace(/"/g, '""') + '"');
        });
        csvContent.push(headers.join(','));

        $tab.find('tbody tr').each(function() {
            var rowData = [];
            $(this).find('td').each(function() {
                var $input = $(this).find('input[type="number"]');
                var val = $input.length ? $input.val() : $(this).text();
                val = val.replace(/\r?\n|\r/g, ' ').replace(/\s+/g, ' ').trim();
                rowData.push('"' + val.replace(/"/g, '""') + '"');
            });
            if (rowData.length) {
                csvContent.push(rowData.join(','));
            }
        });

        var blob = new Blob([csvContent.join('\n')], { type: 'text/csv;charset=utf-8;' });
        var link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.setAttribute('download', tabName.replace(/[^a-z0-9]/gi, '_').toLowerCase() + '_' + selectedYear + '.csv');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });

    // Export Individual Tab to PDF
    $(document).on('click', '.btn-export-tab-pdf', function() {
        var tabId = $(this).data('tab-id');
        var tabName = $(this).data('tab-name') || ('Letter H Tab ' + tabId);
        var $tab = $('#' + tabId);

        if (typeof pdfMake === 'undefined') {
            Swal.fire('Notice', 'PDF export library is loading, please try again.', 'info');
            return;
        }

        var headerRow = [];
        $tab.find('thead th').each(function() {
            var txt = $(this).text().replace(/\r?\n|\r/g, ' ').replace(/\s+/g, ' ').trim();
            headerRow.push({ text: txt, style: 'tableHeader' });
        });

        var bodyRows = [headerRow];
        $tab.find('tbody tr').each(function() {
            var row = [];
            $(this).find('td').each(function() {
                var $input = $(this).find('input[type="number"]');
                var val = $input.length ? $input.val() : $(this).text();
                val = val.replace(/\r?\n|\r/g, ' ').replace(/\s+/g, ' ').trim();
                row.push({ text: val, fontSize: 8.5 });
            });
            if (row.length === headerRow.length) {
                bodyRows.push(row);
            }
        });

        var docDefinition = {
            pageOrientation: 'portrait',
            pageSize: 'A4',
            pageMargins: [25, 25, 25, 25],
            content: [
                { text: 'DEPARTMENT OF ANIMAL PRODUCTION & HEALTH', style: 'mainTitle' },
                { text: 'EASTERN PROVINCE, SRI LANKA', style: 'subTitle' },
                { text: 'Letter \'H\' Account: ' + tabName + ' (' + selectedYear + ')', style: 'headerSub' },
                { text: 'Range: ' + rangeName + ' | District: ' + districtName + ' | Officer: ' + officerName + ' (' + officerDesignation + ')', style: 'metaText', margin: [0, 0, 0, 12] },
                {
                    table: {
                        headerRows: 1,
                        widths: [30, '*', 90, 95],
                        body: bodyRows
                    },
                    layout: 'lightHorizontalLines'
                },
                {
                    margin: [0, 35, 0, 0],
                    columns: [
                        { text: '............................................\nSignature of Veterinary Surgeon', alignment: 'center', fontSize: 9 },
                        { text: '............................................\nOfficial Rubber Stamp', alignment: 'center', fontSize: 9 }
                    ]
                }
            ],
            styles: {
                mainTitle: { fontSize: 13, bold: true, alignment: 'center', color: '#820100', margin: [0, 0, 0, 2] },
                subTitle: { fontSize: 10, bold: true, alignment: 'center', margin: [0, 0, 0, 4] },
                headerSub: { fontSize: 11, bold: true, alignment: 'center', margin: [0, 0, 0, 2] },
                metaText: { fontSize: 8.5, alignment: 'center', color: '#555555' },
                tableHeader: { fontSize: 8.5, bold: true, fillColor: '#f1f5f9', color: '#1e293b' }
            }
        };

        pdfMake.createPdf(docDefinition).download(tabName.replace(/[^a-z0-9]/gi, '_').toLowerCase() + '_' + selectedYear + '.pdf');
    });

    // -------------------------------------------------------------
    // 6. AJAX BATCH SAVE
    // -------------------------------------------------------------
    $('#letterHGridForm').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('button[type="submit"]');
        var origHtml = $btn.html();

        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');

        $.ajax({
            url: 'processors/save_letter_h_grid.php',
            type: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            success: function(resp) {
                $btn.prop('disabled', false).html(origHtml);
                if (resp.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Letter \'H\' Saved!',
                        text: 'All revenue settlement records for ' + (monthNames[activeMonth] || 'the month') + ' ' + selectedYear + ' have been successfully saved.',
                        confirmButtonColor: '#820100'
                    }).then(function() {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Save Failed',
                        text: resp.message || 'Could not save accounts records.',
                        confirmButtonColor: '#820100'
                    });
                }
            },
            error: function() {
                $btn.prop('disabled', false).html(origHtml);
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: 'Failed to communicate with the server.',
                    confirmButtonColor: '#820100'
                });
            }
        });
    });

    // URL feedback
    var urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('status') === 'saved') {
        Swal.fire({
            icon: 'success',
            title: 'Saved!',
            text: 'Letter H records updated successfully.',
            confirmButtonColor: '#820100'
        });
        window.history.replaceState({}, document.title, window.location.pathname + window.location.search.replace(/[\?&]status=[^&]+/, ''));
    }
});

