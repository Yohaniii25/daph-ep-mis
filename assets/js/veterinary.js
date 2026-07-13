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
    Chart.register(centerTotalTextPlugin);

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
            ethnicities: JSON.stringify(targetEthnicities)
        });

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
                if (humanDatasetTableInstance) {
                    humanDatasetTableInstance.clear().rows.add(processedTableRows).draw();
                } else {
                    const tableEl = $('#humanPopulationTable');
                    if (tableEl.length) {
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

                if (humanPieChartInstance) {
                    humanPieChartInstance.data.labels = chartLabels;
                    humanPieChartInstance.data.datasets[0].data = chartValues;
                    humanPieChartInstance.options.plugins.centerTotalText.text = targetPopType;
                    humanPieChartInstance.options.plugins.centerTotalText.value = runningTotalSum;
                    humanPieChartInstance.update();
                } else {
                    const canvasEl = document.getElementById('humanPopulationPieChart');
                    if (canvasEl) {
                        const ctxCanvas = canvasEl.getContext('2d');
                        humanPieChartInstance = new Chart(ctxCanvas, {
                            type: 'doughnut',
                            data: {
                                labels: chartLabels,
                                datasets: [{
                                    data: chartValues,
                                    backgroundColor: ['#370709', '#a07174', '#e2e8f0'],
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
            btn.textContent = 'All Animals Selected (6)';
        } else {
            btn.textContent = selected.join(', ');
        }
    }

    function fetchFilteredAnimalPopulationData() {
        const filterYearAnimalEl = document.getElementById('filterYearAnimal');
        if (!filterYearAnimalEl) return;

        const targetYear = filterYearAnimalEl.value;
        const targetAnimals = getSelectedAnimals();

        const urlParams = new URLSearchParams({
            year: targetYear,
            animals: JSON.stringify(targetAnimals)
        });

        fetch(`get_animal_population_data.php?${urlParams.toString()}`)
            .then(response => response.json())
            .then(data => {
                let runningTotalSum = 0;
                data.forEach(item => {
                    runningTotalSum += item.count;
                });

                const processedTableRows = data.map(item => [
                    item.year,
                    item.animal_type,
                    item.count.toLocaleString(),
                    runningTotalSum.toLocaleString()
                ]);

                if (animalDatasetTableInstance) {
                    animalDatasetTableInstance.clear().rows.add(processedTableRows).draw();
                } else {
                    const tableEl = $('#animalPopulationTable');
                    if (tableEl.length) {
                        animalDatasetTableInstance = tableEl.DataTable({
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

                const chartLabels = data.map(item => item.animal_type);
                const chartValues = data.map(item => item.count);

                if (animalPieChartInstance) {
                    animalPieChartInstance.data.labels = chartLabels;
                    animalPieChartInstance.data.datasets[0].data = chartValues;
                    animalPieChartInstance.options.plugins.centerTotalText.text = 'Total Population';
                    animalPieChartInstance.options.plugins.centerTotalText.value = runningTotalSum;
                    animalPieChartInstance.update();
                } else {
                    const canvasEl = document.getElementById('animalPopulationPieChart');
                    if (canvasEl) {
                        const ctxCanvas = canvasEl.getContext('2d');
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