document.addEventListener('DOMContentLoaded', function() {
    let inspectionsByStatusChart, violationsBySeverityChart, inspectionsByPriorityChart;

    // Function to update all dashboard data via AJAX
    async function updateDashboard(range) {
        const totalInspectionsEl = document.getElementById('totalInspectionsStat');
        const activeViolationsEl = document.getElementById('activeViolationsStat');
        const averageComplianceEl = document.getElementById('averageComplianceStat');
        const spinnerHtml = '<i class="fas fa-spinner fa-spin text-gray-400"></i>';

        // Show loading spinners
        totalInspectionsEl.innerHTML = spinnerHtml;
        activeViolationsEl.innerHTML = spinnerHtml;
        averageComplianceEl.innerHTML = spinnerHtml;

        try {
            const response = await fetch(`api/get_dashboard_data.php?range=${range}`);
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            const result = await response.json();
            if (!result.success) {
                throw new Error(result.message || 'API returned an error.');
            }
            const data = result.data;

            // Update stat cards
            totalInspectionsEl.textContent = data.totalInspections;
            activeViolationsEl.textContent = data.activeViolations;
            averageComplianceEl.textContent = `${data.averageCompliance}%`;

            // Update "Inspections by Status" chart
            if (inspectionsByStatusChart) {
                inspectionsByStatusChart.data.labels = Object.keys(data.inspectionStats).map(s => s.charAt(0).toUpperCase() + s.slice(1).replace('_', ' '));
                inspectionsByStatusChart.data.datasets[0].data = Object.values(data.inspectionStats);
                inspectionsByStatusChart.update();
            }

            // Update "Violations by Severity" chart
            if (violationsBySeverityChart) {
                violationsBySeverityChart.data.labels = Object.keys(data.violationStatsBySeverity).map(s => s.charAt(0).toUpperCase() + s.slice(1));
                violationsBySeverityChart.data.datasets[0].data = Object.values(data.violationStatsBySeverity);
                violationsBySeverityChart.update();
            }

            // Update "Inspections by Priority" chart
            if (inspectionsByPriorityChart) {
                inspectionsByPriorityChart.data.labels = Object.keys(data.inspectionStatsByPriority).map(s => s.charAt(0).toUpperCase() + s.slice(1));
                inspectionsByPriorityChart.data.datasets[0].data = Object.values(data.inspectionStatsByPriority);
                inspectionsByPriorityChart.update();
            }

        } catch (error) {
            console.error('Failed to update dashboard:', error);
            const errorHtml = '<i class="fas fa-exclamation-circle text-red-500"></i>';
            totalInspectionsEl.innerHTML = errorHtml;
            activeViolationsEl.innerHTML = errorHtml;
            averageComplianceEl.innerHTML = errorHtml;
            // Optionally show an error message to the user
        }
    }

    // Date Range Filter Handler
    const dateRangeFilter = document.getElementById('dateRangeFilter');
    dateRangeFilter.addEventListener('change', function() {
        // Update the URL for bookmarking/sharing, but don't reload
        const url = new URL(window.location);
        url.searchParams.set('range', this.value);
        window.history.pushState({}, '', url);
        
        // Fetch new data
        updateDashboard(this.value);
    });

    // Chart 1: Inspections by Status (Doughnut Chart)
    const statusCtx = document.getElementById('inspectionsByStatusChart').getContext('2d');
    inspectionsByStatusChart = new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: [], // Initially empty
            datasets: [{
                label: 'Inspections',
                data: [], // Initially empty
                backgroundColor: [
                    'rgba(54, 162, 235, 0.8)', // scheduled (blue)
                    'rgba(255, 206, 86, 0.8)', // in_progress (yellow)
                    'rgba(75, 192, 192, 0.8)', // completed (green)
                    'rgba(255, 99, 132, 0.8)',  // overdue (red)
                    'rgba(153, 102, 255, 0.8)',// cancelled (purple)
                    'rgba(255, 159, 64, 0.8)'  // requested (orange)
                ],
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: false,
                    text: 'Inspections by Status',
                }
            },
            onClick: (e, elements) => {
                if (elements.length > 0) {
                    const clickedElementIndex = elements[0].index;
                    const status = inspectionsByStatusChart.data.labels[clickedElementIndex];
                    // The label might be "In Progress", which needs to be URL-encoded.
                    // The inspections.php page will handle converting it back.
                    window.location.href = `inspections.php?status=${encodeURIComponent(status)}`;
                }
            }
        }
    });

    // Chart 2: Inspections by Priority (Pie Chart)
    const priorityCtx = document.getElementById('inspectionsByPriorityChart').getContext('2d');
    inspectionsByPriorityChart = new Chart(priorityCtx, {
        type: 'pie',
        data: {
            labels: [], // Initially empty
            datasets: [{
                label: 'Inspections',
                data: [], // Initially empty
                backgroundColor: [
                    'rgba(75, 192, 192, 0.8)',  // low (green)
                    'rgba(255, 206, 86, 0.8)', // medium (yellow)
                    'rgba(255, 99, 132, 0.8)'   // high (red)
                ],
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: false,
                    text: 'Inspections by Priority'
                }
            }
        }
    });

    // Chart 3: Businesses by Type (Bar Chart)
    const businessChartCanvas = document.getElementById('businessByTypeChart');
    const businessTypeData = JSON.parse(businessChartCanvas.dataset.businessTypes || '[]');
    const businessCtx = businessChartCanvas.getContext('2d');
    const businessLabels = businessTypeData.map(d => d.business_type);
    const businessCounts = businessTypeData.map(d => d.count);

    new Chart(businessCtx, {
        type: 'bar',
        data: {
            labels: businessLabels,
            datasets: [{
                label: 'Number of Businesses',
                data: businessCounts,
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });

    // Chart 4: Violations by Severity (Pie Chart)
    const violationCtx = document.getElementById('violationsBySeverityChart').getContext('2d');
    violationsBySeverityChart = new Chart(violationCtx, {
        type: 'pie',
        data: {
            labels: [], // Initially empty
            datasets: [{
                label: 'Violations',
                data: [], // Initially empty
                backgroundColor: [
                    'rgba(75, 192, 192, 0.8)',  // low (green)
                    'rgba(255, 206, 86, 0.8)', // medium (yellow)
                    'rgba(255, 159, 64, 0.8)',  // high (orange)
                    'rgba(255, 99, 132, 0.8)'   // critical (red)
                ],
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                }
            }
        }
    });

    // Initial data load
    updateDashboard(dateRangeFilter.value);
});