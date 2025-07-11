<?php
/**
 * Reports & Analytics Page
 *
 * @since      1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Get reports instance
$reports = new Booking_Master_Reports();
?>

<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    
    <div class="bm-reports-container">
        <!-- Report Filters -->
        <div class="bm-report-filters">
            <form method="get" action="" id="bm-report-filter-form">
                <input type="hidden" name="page" value="booking-master-reports">
                
                <div class="filter-row">
                    <label for="report-type">Report Type:</label>
                    <select name="report_type" id="report-type">
                        <option value="bookings">Bookings Report</option>
                        <option value="revenue">Revenue Report</option>
                        <option value="mentors">Mentors Report</option>
                        <option value="services">Services Report</option>
                        <option value="cancellations">Cancellations Report</option>
                        <option value="payments">Payments Report</option>
                    </select>
                    
                    <label for="date-range">Date Range:</label>
                    <select name="date_range" id="date-range">
                        <option value="today">Today</option>
                        <option value="week">This Week</option>
                        <option value="month" selected>This Month</option>
                        <option value="quarter">This Quarter</option>
                        <option value="year">This Year</option>
                        <option value="custom">Custom Range</option>
                    </select>
                    
                    <input type="date" name="start_date" id="start-date" style="display: none;">
                    <input type="date" name="end_date" id="end-date" style="display: none;">
                    
                    <label for="mentor-filter">Mentor:</label>
                    <select name="mentor_id" id="mentor-filter">
                        <option value="">All Mentors</option>
                        <?php
                        $mentors = get_users( array( 'role' => 'mentor' ) );
                        foreach ( $mentors as $mentor ) {
                            echo '<option value="' . $mentor->ID . '">' . esc_html( $mentor->display_name ) . '</option>';
                        }
                        ?>
                    </select>
                    
                    <button type="button" id="generate-report" class="button button-primary">Generate Report</button>
                    <button type="button" id="export-report" class="button">Export</button>
                </div>
            </form>
        </div>
        
        <!-- Dashboard Statistics -->
        <div class="bm-dashboard-stats">
            <div class="stat-boxes">
                <div class="stat-box">
                    <h3>Total Bookings</h3>
                    <span class="stat-number" id="total-bookings">-</span>
                </div>
                <div class="stat-box">
                    <h3>Total Revenue</h3>
                    <span class="stat-number" id="total-revenue">-</span>
                </div>
                <div class="stat-box">
                    <h3>Active Mentors</h3>
                    <span class="stat-number" id="active-mentors">-</span>
                </div>
                <div class="stat-box">
                    <h3>Completion Rate</h3>
                    <span class="stat-number" id="completion-rate">-</span>
                </div>
            </div>
        </div>
        
        <!-- Charts Section -->
        <div class="bm-charts-section">
            <div class="chart-container">
                <h3>Revenue Chart</h3>
                <canvas id="revenue-chart" width="400" height="200"></canvas>
            </div>
            
            <div class="chart-container">
                <h3>Booking Trends</h3>
                <canvas id="booking-trends-chart" width="400" height="200"></canvas>
            </div>
        </div>
        
        <!-- Report Results -->
        <div class="bm-report-results" id="report-results" style="display: none;">
            <h3>Report Results</h3>
            <div id="report-content"></div>
        </div>
    </div>
</div>

<style>
.bm-reports-container {
    max-width: 1200px;
}

.bm-report-filters {
    background: #fff;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 5px;
    margin-bottom: 20px;
}

.filter-row {
    display: flex;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}

.filter-row label {
    font-weight: 600;
    margin-right: 5px;
}

.filter-row select, .filter-row input {
    padding: 5px 10px;
    border: 1px solid #ddd;
    border-radius: 3px;
}

.bm-dashboard-stats {
    margin-bottom: 30px;
}

.stat-boxes {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.stat-box {
    background: #fff;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 5px;
    text-align: center;
}

.stat-box h3 {
    margin: 0 0 10px 0;
    font-size: 14px;
    color: #666;
}

.stat-number {
    font-size: 24px;
    font-weight: bold;
    color: #0073aa;
}

.bm-charts-section {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 30px;
}

.chart-container {
    background: #fff;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 5px;
}

.chart-container h3 {
    margin-top: 0;
    margin-bottom: 15px;
}

.bm-report-results {
    background: #fff;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 5px;
}

.report-table {
    width: 100%;
    border-collapse: collapse;
}

.report-table th,
.report-table td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.report-table th {
    background: #f9f9f9;
    font-weight: 600;
}

@media (max-width: 768px) {
    .bm-charts-section {
        grid-template-columns: 1fr;
    }
    
    .filter-row {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-row label {
        margin-bottom: 5px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Initialize charts
    loadDashboardStats();
    
    // Date range change handler
    $('#date-range').on('change', function() {
        if ($(this).val() === 'custom') {
            $('#start-date, #end-date').show();
        } else {
            $('#start-date, #end-date').hide();
        }
    });
    
    // Generate report
    $('#generate-report').on('click', function() {
        generateReport();
    });
    
    // Export report
    $('#export-report').on('click', function() {
        exportReport();
    });
    
    function loadDashboardStats() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bm_get_dashboard_stats',
                period: 'month',
                nonce: '<?php echo wp_create_nonce( 'bm_reports_nonce' ); ?>'
            },
            success: function(response) {
                if (response.success) {
                    const stats = response.data.stats;
                    $('#total-bookings').text(stats.total_bookings || 0);
                    $('#total-revenue').text('$' + (stats.total_revenue || 0));
                    $('#active-mentors').text(stats.active_mentors || 0);
                    $('#completion-rate').text((stats.completion_rate || 0) + '%');
                    
                    loadCharts();
                }
            }
        });
    }
    
    function loadCharts() {
        // Load revenue chart
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bm_get_revenue_chart',
                period: 'month',
                nonce: '<?php echo wp_create_nonce( 'bm_reports_nonce' ); ?>'
            },
            success: function(response) {
                if (response.success) {
                    drawRevenueChart(response.data.chart);
                }
            }
        });
        
        // Load booking trends chart
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bm_get_booking_trends',
                period: 'month',
                nonce: '<?php echo wp_create_nonce( 'bm_reports_nonce' ); ?>'
            },
            success: function(response) {
                if (response.success) {
                    drawBookingTrendsChart(response.data.trends);
                }
            }
        });
    }
    
    function generateReport() {
        const formData = {
            action: 'bm_generate_report',
            report_type: $('#report-type').val(),
            date_range: $('#date-range').val(),
            start_date: $('#start-date').val(),
            end_date: $('#end-date').val(),
            mentor_id: $('#mentor-filter').val(),
            nonce: '<?php echo wp_create_nonce( 'bm_reports_nonce' ); ?>'
        };
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    displayReportResults(response.data.report);
                    $('#report-results').show();
                } else {
                    alert('Error generating report: ' + response.data.message);
                }
            }
        });
    }
    
    function exportReport() {
        const formData = {
            action: 'bm_export_report',
            report_type: $('#report-type').val(),
            date_range: $('#date-range').val(),
            start_date: $('#start-date').val(),
            end_date: $('#end-date').val(),
            mentor_id: $('#mentor-filter').val(),
            format: 'csv',
            nonce: '<?php echo wp_create_nonce( 'bm_reports_nonce' ); ?>'
        };
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    // Download file
                    window.open(response.data.download_url, '_blank');
                } else {
                    alert('Error exporting report: ' + response.data.message);
                }
            }
        });
    }
    
    function displayReportResults(report) {
        let html = '<div class="report-summary">';
        
        if (report.summary) {
            html += '<h4>Summary</h4>';
            html += '<ul>';
            for (const [key, value] of Object.entries(report.summary)) {
                html += `<li><strong>${key.replace(/_/g, ' ').toUpperCase()}:</strong> ${value}</li>`;
            }
            html += '</ul>';
        }
        
        if (report.bookings || report.mentors || report.services) {
            html += '<h4>Detailed Results</h4>';
            html += '<table class="report-table">';
            
            const data = report.bookings || report.mentors || report.services;
            if (data.length > 0) {
                // Table headers
                html += '<thead><tr>';
                for (const key of Object.keys(data[0])) {
                    html += `<th>${key.replace(/_/g, ' ').toUpperCase()}</th>`;
                }
                html += '</tr></thead>';
                
                // Table body
                html += '<tbody>';
                data.forEach(row => {
                    html += '<tr>';
                    for (const value of Object.values(row)) {
                        html += `<td>${value}</td>`;
                    }
                    html += '</tr>';
                });
                html += '</tbody>';
            }
            
            html += '</table>';
        }
        
        html += '</div>';
        $('#report-content').html(html);
    }
    
    function drawRevenueChart(data) {
        // Simple chart implementation - you can replace with Chart.js or similar
        const canvas = document.getElementById('revenue-chart');
        const ctx = canvas.getContext('2d');
        
        // Clear canvas
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        // Draw simple bar chart
        ctx.fillStyle = '#0073aa';
        ctx.fillRect(50, 50, 100, 100);
        ctx.fillText('Revenue Chart', 10, 20);
        ctx.fillText('(Chart.js recommended)', 10, 40);
    }
    
    function drawBookingTrendsChart(data) {
        // Simple chart implementation - you can replace with Chart.js or similar
        const canvas = document.getElementById('booking-trends-chart');
        const ctx = canvas.getContext('2d');
        
        // Clear canvas
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        // Draw simple line chart
        ctx.strokeStyle = '#0073aa';
        ctx.beginPath();
        ctx.moveTo(50, 150);
        ctx.lineTo(150, 100);
        ctx.lineTo(250, 120);
        ctx.lineTo(350, 80);
        ctx.stroke();
        ctx.fillText('Booking Trends', 10, 20);
        ctx.fillText('(Chart.js recommended)', 10, 40);
    }
});
</script>