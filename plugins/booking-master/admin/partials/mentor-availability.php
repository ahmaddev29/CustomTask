<?php
/**
 * Mentor Availability Management Page
 *
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$mentor_id = get_current_user_id();
$current_month = isset( $_GET['month'] ) ? intval( $_GET['month'] ) : date( 'm' );
$current_year = isset( $_GET['year'] ) ? intval( $_GET['year'] ) : date( 'Y' );
?>

<div class="wrap bm-availability-management">
    <h1 class="wp-heading-inline"><?php esc_html_e( 'Manage Availability', 'booking-master' ); ?></h1>
    
    <div class="bm-availability-header">
        <div class="bm-month-navigation">
            <a href="<?php echo esc_url( add_query_arg( array( 'month' => $current_month == 1 ? 12 : $current_month - 1, 'year' => $current_month == 1 ? $current_year - 1 : $current_year ) ) ); ?>" class="button">
                &#8249; <?php esc_html_e( 'Previous', 'booking-master' ); ?>
            </a>
            
            <h2 class="bm-current-month"><?php echo esc_html( date( 'F Y', mktime( 0, 0, 0, $current_month, 1, $current_year ) ) ); ?></h2>
            
            <a href="<?php echo esc_url( add_query_arg( array( 'month' => $current_month == 12 ? 1 : $current_month + 1, 'year' => $current_month == 12 ? $current_year + 1 : $current_year ) ) ); ?>" class="button">
                <?php esc_html_e( 'Next', 'booking-master' ); ?> &#8250;
            </a>
        </div>
        
        <div class="bm-availability-actions">
            <button type="button" class="button button-secondary" id="bm-set-working-hours">
                <?php esc_html_e( 'Set Working Hours', 'booking-master' ); ?>
            </button>
            <button type="button" class="button button-secondary" id="bm-set-time-off">
                <?php esc_html_e( 'Schedule Time Off', 'booking-master' ); ?>
            </button>
            <button type="button" class="button button-secondary" id="bm-copy-availability">
                <?php esc_html_e( 'Copy Availability', 'booking-master' ); ?>
            </button>
        </div>
    </div>

    <div class="bm-availability-legend">
        <div class="bm-legend-item">
            <span class="bm-legend-color bm-available"></span>
            <?php esc_html_e( 'Available', 'booking-master' ); ?>
        </div>
        <div class="bm-legend-item">
            <span class="bm-legend-color bm-blocked"></span>
            <?php esc_html_e( 'Blocked', 'booking-master' ); ?>
        </div>
        <div class="bm-legend-item">
            <span class="bm-legend-color bm-booked"></span>
            <?php esc_html_e( 'Booked', 'booking-master' ); ?>
        </div>
        <div class="bm-legend-item">
            <span class="bm-legend-color bm-past"></span>
            <?php esc_html_e( 'Past', 'booking-master' ); ?>
        </div>
    </div>

    <div class="bm-availability-container">
        <div class="bm-calendar-container">
            <div id="bm-availability-calendar" class="bm-calendar-grid">
                <!-- Calendar will be loaded via AJAX -->
            </div>
        </div>
        
        <div class="bm-time-slots-container">
            <div class="bm-selected-date-header">
                <h3 id="bm-selected-date-title"><?php esc_html_e( 'Select a date to manage time slots', 'booking-master' ); ?></h3>
                <div class="bm-date-actions" style="display: none;">
                    <button type="button" class="button button-small" id="bm-select-all-slots">
                        <?php esc_html_e( 'Select All', 'booking-master' ); ?>
                    </button>
                    <button type="button" class="button button-small" id="bm-clear-selection">
                        <?php esc_html_e( 'Clear Selection', 'booking-master' ); ?>
                    </button>
                    <button type="button" class="button button-primary button-small" id="bm-make-available">
                        <?php esc_html_e( 'Make Available', 'booking-master' ); ?>
                    </button>
                    <button type="button" class="button button-small" id="bm-block-slots">
                        <?php esc_html_e( 'Block', 'booking-master' ); ?>
                    </button>
                </div>
            </div>
            
            <div id="bm-time-slots-grid" class="bm-time-slots-grid">
                <!-- Time slots will be loaded when a date is selected -->
            </div>
        </div>
    </div>

    <!-- Working Hours Modal -->
    <div id="bm-working-hours-modal" class="bm-modal" style="display: none;">
        <div class="bm-modal-content">
            <div class="bm-modal-header">
                <h3><?php esc_html_e( 'Set Working Hours', 'booking-master' ); ?></h3>
                <span class="bm-modal-close">&times;</span>
            </div>
            <div class="bm-modal-body">
                <form id="bm-working-hours-form">
                    <table class="bm-working-hours-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Day', 'booking-master' ); ?></th>
                                <th><?php esc_html_e( 'Enabled', 'booking-master' ); ?></th>
                                <th><?php esc_html_e( 'Start Time', 'booking-master' ); ?></th>
                                <th><?php esc_html_e( 'End Time', 'booking-master' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $days = array(
                                'monday' => 'Monday',
                                'tuesday' => 'Tuesday', 
                                'wednesday' => 'Wednesday',
                                'thursday' => 'Thursday',
                                'friday' => 'Friday',
                                'saturday' => 'Saturday',
                                'sunday' => 'Sunday'
                            );
                            
                            foreach ( $days as $day_key => $day_name ) :
                            ?>
                            <tr>
                                <td><?php echo esc_html( $day_name ); ?></td>
                                <td>
                                    <input type="checkbox" name="working_hours[<?php echo esc_attr( $day_key ); ?>][enabled]" value="1" class="bm-day-enabled">
                                </td>
                                <td>
                                    <input type="time" name="working_hours[<?php echo esc_attr( $day_key ); ?>][start]" value="09:00" class="bm-start-time">
                                </td>
                                <td>
                                    <input type="time" name="working_hours[<?php echo esc_attr( $day_key ); ?>][end]" value="17:00" class="bm-end-time">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="bm-modal-footer">
                        <button type="button" class="button button-secondary bm-modal-close">
                            <?php esc_html_e( 'Cancel', 'booking-master' ); ?>
                        </button>
                        <button type="submit" class="button button-primary">
                            <?php esc_html_e( 'Save Working Hours', 'booking-master' ); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Time Off Modal -->
    <div id="bm-time-off-modal" class="bm-modal" style="display: none;">
        <div class="bm-modal-content">
            <div class="bm-modal-header">
                <h3><?php esc_html_e( 'Schedule Time Off', 'booking-master' ); ?></h3>
                <span class="bm-modal-close">&times;</span>
            </div>
            <div class="bm-modal-body">
                <form id="bm-time-off-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="bm-time-off-start"><?php esc_html_e( 'Start Date', 'booking-master' ); ?></label>
                            </th>
                            <td>
                                <input type="date" id="bm-time-off-start" name="start_date" required>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="bm-time-off-end"><?php esc_html_e( 'End Date', 'booking-master' ); ?></label>
                            </th>
                            <td>
                                <input type="date" id="bm-time-off-end" name="end_date" required>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="bm-time-off-reason"><?php esc_html_e( 'Reason (Optional)', 'booking-master' ); ?></label>
                            </th>
                            <td>
                                <input type="text" id="bm-time-off-reason" name="reason" class="regular-text" placeholder="<?php esc_attr_e( 'e.g., Vacation, Conference', 'booking-master' ); ?>">
                            </td>
                        </tr>
                    </table>
                    
                    <div class="bm-modal-footer">
                        <button type="button" class="button button-secondary bm-modal-close">
                            <?php esc_html_e( 'Cancel', 'booking-master' ); ?>
                        </button>
                        <button type="submit" class="button button-primary">
                            <?php esc_html_e( 'Schedule Time Off', 'booking-master' ); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Copy Availability Modal -->
    <div id="bm-copy-availability-modal" class="bm-modal" style="display: none;">
        <div class="bm-modal-content">
            <div class="bm-modal-header">
                <h3><?php esc_html_e( 'Copy Availability', 'booking-master' ); ?></h3>
                <span class="bm-modal-close">&times;</span>
            </div>
            <div class="bm-modal-body">
                <form id="bm-copy-availability-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="bm-copy-source-date"><?php esc_html_e( 'Copy From', 'booking-master' ); ?></label>
                            </th>
                            <td>
                                <input type="date" id="bm-copy-source-date" name="source_date" required>
                                <p class="description"><?php esc_html_e( 'Select the date to copy availability from', 'booking-master' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="bm-copy-type"><?php esc_html_e( 'Copy Type', 'booking-master' ); ?></label>
                            </th>
                            <td>
                                <select id="bm-copy-type" name="copy_type">
                                    <option value="day"><?php esc_html_e( 'Single Day', 'booking-master' ); ?></option>
                                    <option value="week"><?php esc_html_e( 'Entire Week', 'booking-master' ); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="bm-copy-target-dates"><?php esc_html_e( 'Copy To', 'booking-master' ); ?></label>
                            </th>
                            <td>
                                <textarea id="bm-copy-target-dates" name="target_dates" rows="3" class="large-text" placeholder="<?php esc_attr_e( 'Select dates from the calendar below', 'booking-master' ); ?>" readonly></textarea>
                                <p class="description"><?php esc_html_e( 'Click on dates in the mini calendar below to select target dates', 'booking-master' ); ?></p>
                                
                                <div id="bm-copy-date-picker" class="bm-mini-calendar">
                                    <!-- Mini calendar for date selection -->
                                </div>
                            </td>
                        </tr>
                    </table>
                    
                    <div class="bm-modal-footer">
                        <button type="button" class="button button-secondary bm-modal-close">
                            <?php esc_html_e( 'Cancel', 'booking-master' ); ?>
                        </button>
                        <button type="submit" class="button button-primary">
                            <?php esc_html_e( 'Copy Availability', 'booking-master' ); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.bm-availability-management {
    max-width: 1200px;
}

.bm-availability-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 20px 0;
    padding: 15px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.bm-month-navigation {
    display: flex;
    align-items: center;
    gap: 15px;
}

.bm-current-month {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
}

.bm-availability-actions {
    display: flex;
    gap: 10px;
}

.bm-availability-legend {
    display: flex;
    gap: 20px;
    margin: 15px 0;
    padding: 10px 15px;
    background: #f9f9f9;
    border-radius: 4px;
}

.bm-legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
}

.bm-legend-color {
    width: 16px;
    height: 16px;
    border-radius: 2px;
    border: 1px solid #ddd;
}

.bm-legend-color.bm-available {
    background-color: #d4edda;
    border-color: #28a745;
}

.bm-legend-color.bm-blocked {
    background-color: #f8d7da;
    border-color: #dc3545;
}

.bm-legend-color.bm-booked {
    background-color: #fff3cd;
    border-color: #ffc107;
}

.bm-legend-color.bm-past {
    background-color: #e2e3e5;
    border-color: #6c757d;
}

.bm-availability-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-top: 20px;
}

.bm-calendar-container,
.bm-time-slots-container {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
}

.bm-calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 1px;
    background: #ddd;
}

.bm-calendar-day {
    background: #fff;
    padding: 10px;
    text-align: center;
    cursor: pointer;
    min-height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    transition: all 0.2s;
}

.bm-calendar-day.bm-day-header {
    background: #f8f9fa;
    font-weight: 600;
    cursor: default;
}

.bm-calendar-day.bm-other-month {
    color: #999;
    background: #f8f9fa;
}

.bm-calendar-day.bm-today {
    background: #007cba;
    color: #fff;
}

.bm-calendar-day.bm-selected {
    background: #0073aa;
    color: #fff;
}

.bm-calendar-day.bm-has-availability::after {
    content: '';
    position: absolute;
    bottom: 2px;
    left: 50%;
    transform: translateX(-50%);
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #28a745;
}

.bm-calendar-day.bm-has-bookings::after {
    background: #ffc107;
}

.bm-calendar-day.bm-blocked::after {
    background: #dc3545;
}

.bm-selected-date-header {
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.bm-selected-date-header h3 {
    margin: 0 0 10px 0;
}

.bm-date-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.bm-time-slots-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
    gap: 8px;
    max-height: 400px;
    overflow-y: auto;
}

.bm-time-slot {
    padding: 8px;
    text-align: center;
    border: 1px solid #ddd;
    border-radius: 3px;
    cursor: pointer;
    font-size: 12px;
    transition: all 0.2s;
    user-select: none;
}

.bm-time-slot.bm-available {
    background: #d4edda;
    border-color: #28a745;
    color: #155724;
}

.bm-time-slot.bm-blocked {
    background: #f8d7da;
    border-color: #dc3545;
    color: #721c24;
    cursor: not-allowed;
}

.bm-time-slot.bm-booked {
    background: #fff3cd;
    border-color: #ffc107;
    color: #856404;
    cursor: not-allowed;
}

.bm-time-slot.bm-past {
    background: #e2e3e5;
    border-color: #6c757d;
    color: #495057;
    cursor: not-allowed;
}

.bm-time-slot.bm-selected {
    box-shadow: 0 0 0 2px #0073aa;
}

.bm-time-slot.bm-available:hover {
    background: #c3e6cb;
}

.bm-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.bm-modal-content {
    background: #fff;
    border-radius: 4px;
    max-width: 600px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
}

.bm-modal-header {
    padding: 15px 20px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.bm-modal-header h3 {
    margin: 0;
}

.bm-modal-close {
    cursor: pointer;
    font-size: 24px;
    line-height: 1;
    color: #999;
}

.bm-modal-close:hover {
    color: #333;
}

.bm-modal-body {
    padding: 20px;
}

.bm-modal-footer {
    padding: 15px 20px;
    border-top: 1px solid #ddd;
    text-align: right;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.bm-working-hours-table {
    width: 100%;
    border-collapse: collapse;
}

.bm-working-hours-table th,
.bm-working-hours-table td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.bm-working-hours-table th {
    background: #f8f9fa;
    font-weight: 600;
}

.bm-working-hours-table input[type="time"] {
    width: 100%;
}

.bm-mini-calendar {
    margin-top: 10px;
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 1px;
    background: #ddd;
    max-width: 300px;
}

.bm-mini-calendar .bm-calendar-day {
    min-height: 30px;
    font-size: 12px;
}

@media (max-width: 768px) {
    .bm-availability-container {
        grid-template-columns: 1fr;
    }
    
    .bm-availability-header {
        flex-direction: column;
        gap: 15px;
        align-items: stretch;
    }
    
    .bm-month-navigation {
        justify-content: center;
    }
    
    .bm-availability-actions {
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .bm-date-actions {
        justify-content: center;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    const BMAvailability = {
        mentorId: <?php echo esc_js( $mentor_id ); ?>,
        currentMonth: <?php echo esc_js( $current_month ); ?>,
        currentYear: <?php echo esc_js( $current_year ); ?>,
        selectedDate: null,
        selectedSlots: [],

        init: function() {
            this.loadCalendar();
            this.bindEvents();
        },

        bindEvents: function() {
            // Modal events
            $('.bm-modal-close').on('click', this.closeModal);
            $('#bm-set-working-hours').on('click', this.showWorkingHoursModal);
            $('#bm-set-time-off').on('click', this.showTimeOffModal);
            $('#bm-copy-availability').on('click', this.showCopyAvailabilityModal);

            // Form submissions
            $('#bm-working-hours-form').on('submit', this.saveWorkingHours);
            $('#bm-time-off-form').on('submit', this.saveTimeOff);
            $('#bm-copy-availability-form').on('submit', this.copyAvailability);

            // Time slot actions
            $('#bm-select-all-slots').on('click', this.selectAllSlots);
            $('#bm-clear-selection').on('click', this.clearSelection);
            $('#bm-make-available').on('click', this.makeAvailable);
            $('#bm-block-slots').on('click', this.blockSlots);

            // Enable/disable working hours inputs
            $(document).on('change', '.bm-day-enabled', function() {
                const row = $(this).closest('tr');
                const enabled = $(this).is(':checked');
                row.find('.bm-start-time, .bm-end-time').prop('disabled', !enabled);
            });
        },

        loadCalendar: function() {
            $.ajax({
                url: bmAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'bm_get_availability_calendar',
                    nonce: bmAdmin.nonce,
                    mentor_id: this.mentorId,
                    month: this.currentMonth,
                    year: this.currentYear
                },
                success: function(response) {
                    if (response.success) {
                        BMAvailability.renderCalendar(response.data.calendar);
                    }
                }
            });
        },

        renderCalendar: function(calendarData) {
            const calendar = $('#bm-availability-calendar');
            calendar.empty();

            // Add day headers
            const dayHeaders = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            dayHeaders.forEach(day => {
                calendar.append(`<div class="bm-calendar-day bm-day-header">${day}</div>`);
            });

            // Add calendar days
            const firstDay = new Date(this.currentYear, this.currentMonth - 1, 1);
            const lastDay = new Date(this.currentYear, this.currentMonth, 0);
            const startDate = new Date(firstDay);
            startDate.setDate(startDate.getDate() - firstDay.getDay());

            for (let i = 0; i < 42; i++) {
                const currentDate = new Date(startDate);
                currentDate.setDate(startDate.getDate() + i);
                
                const dateStr = currentDate.toISOString().split('T')[0];
                const dayData = calendarData[dateStr] || {};
                
                const isCurrentMonth = currentDate.getMonth() === this.currentMonth - 1;
                const isToday = this.isToday(currentDate);
                const isPast = currentDate < new Date().setHours(0, 0, 0, 0);
                
                let classes = 'bm-calendar-day';
                if (!isCurrentMonth) classes += ' bm-other-month';
                if (isToday) classes += ' bm-today';
                if (isPast) classes += ' bm-past';
                if (dayData.status === 'available') classes += ' bm-has-availability';
                if (dayData.status === 'partially-booked') classes += ' bm-has-bookings';
                if (dayData.status === 'blocked') classes += ' bm-blocked';
                
                const dayElement = $(`<div class="${classes}" data-date="${dateStr}">${currentDate.getDate()}</div>`);
                
                if (isCurrentMonth && !isPast) {
                    dayElement.on('click', () => this.selectDate(dateStr));
                }
                
                calendar.append(dayElement);
            }
        },

        selectDate: function(dateStr) {
            this.selectedDate = dateStr;
            $('.bm-calendar-day').removeClass('bm-selected');
            $(`.bm-calendar-day[data-date="${dateStr}"]`).addClass('bm-selected');
            
            $('#bm-selected-date-title').text(`Time Slots for ${new Date(dateStr).toLocaleDateString()}`);
            $('.bm-date-actions').show();
            
            this.loadTimeSlots(dateStr);
        },

        loadTimeSlots: function(dateStr) {
            $.ajax({
                url: bmAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'bm_get_mentor_availability',
                    nonce: bmAdmin.nonce,
                    mentor_id: this.mentorId,
                    start_date: dateStr,
                    end_date: dateStr
                },
                success: function(response) {
                    if (response.success) {
                        BMAvailability.renderTimeSlots(response.data.availability[dateStr] || {});
                    }
                }
            });
        },

        renderTimeSlots: function(slotsData) {
            const container = $('#bm-time-slots-grid');
            container.empty();
            this.selectedSlots = [];

            const isPastDate = new Date(this.selectedDate) < new Date().setHours(0, 0, 0, 0);
            
            // Generate time slots from 9 AM to 5 PM
            for (let hour = 9; hour < 17; hour++) {
                for (let minute = 0; minute < 60; minute += 30) {
                    const timeStr = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`;
                    const status = slotsData[timeStr] || 'available';
                    
                    let classes = 'bm-time-slot';
                    if (isPastDate) {
                        classes += ' bm-past';
                    } else {
                        classes += ` bm-${status}`;
                    }
                    
                    const slot = $(`<div class="${classes}" data-time="${timeStr}">${this.formatTime(timeStr)}</div>`);
                    
                    if (!isPastDate && (status === 'available' || status === 'blocked')) {
                        slot.on('click', () => this.toggleSlotSelection(timeStr, slot));
                    }
                    
                    container.append(slot);
                }
            }
        },

        toggleSlotSelection: function(timeStr, element) {
            const index = this.selectedSlots.indexOf(timeStr);
            if (index > -1) {
                this.selectedSlots.splice(index, 1);
                element.removeClass('bm-selected');
            } else {
                this.selectedSlots.push(timeStr);
                element.addClass('bm-selected');
            }
        },

        selectAllSlots: function() {
            BMAvailability.selectedSlots = [];
            $('#bm-time-slots-grid .bm-time-slot:not(.bm-booked):not(.bm-past)').each(function() {
                const timeStr = $(this).data('time');
                BMAvailability.selectedSlots.push(timeStr);
                $(this).addClass('bm-selected');
            });
        },

        clearSelection: function() {
            BMAvailability.selectedSlots = [];
            $('#bm-time-slots-grid .bm-time-slot').removeClass('bm-selected');
        },

        makeAvailable: function() {
            BMAvailability.updateTimeSlots('available');
        },

        blockSlots: function() {
            BMAvailability.updateTimeSlots('blocked');
        },

        updateTimeSlots: function(status) {
            if (this.selectedSlots.length === 0) {
                alert('Please select time slots first.');
                return;
            }

            const updates = this.selectedSlots.map(timeSlot => ({
                date: this.selectedDate,
                time_slot: timeSlot,
                status: status
            }));

            $.ajax({
                url: bmAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'bm_bulk_update_availability',
                    nonce: bmAdmin.nonce,
                    mentor_id: this.mentorId,
                    updates: JSON.stringify(updates)
                },
                success: function(response) {
                    if (response.success) {
                        BMAvailability.loadTimeSlots(BMAvailability.selectedDate);
                        BMAvailability.loadCalendar();
                        BMAvailability.clearSelection();
                    } else {
                        alert(response.data.message || 'Failed to update time slots.');
                    }
                }
            });
        },

        showWorkingHoursModal: function() {
            BMAvailability.loadWorkingHours();
            $('#bm-working-hours-modal').show();
        },

        loadWorkingHours: function() {
            // Load existing working hours via AJAX if needed
            // For now, use default values
        },

        saveWorkingHours: function(e) {
            e.preventDefault();
            
            const formData = $(this).serializeArray();
            const workingHours = {};
            
            formData.forEach(item => {
                const matches = item.name.match(/working_hours\[(\w+)\]\[(\w+)\]/);
                if (matches) {
                    const day = matches[1];
                    const field = matches[2];
                    if (!workingHours[day]) workingHours[day] = {};
                    workingHours[day][field] = item.value === '1' ? true : item.value;
                }
            });

            $.ajax({
                url: bmAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'bm_set_working_hours',
                    nonce: bmAdmin.nonce,
                    mentor_id: BMAvailability.mentorId,
                    working_hours: JSON.stringify(workingHours)
                },
                success: function(response) {
                    if (response.success) {
                        BMAvailability.closeModal();
                        BMAvailability.loadCalendar();
                    } else {
                        alert(response.data.message || 'Failed to save working hours.');
                    }
                }
            });
        },

        showTimeOffModal: function() {
            $('#bm-time-off-modal').show();
        },

        saveTimeOff: function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            $.ajax({
                url: bmAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'bm_set_time_off',
                    nonce: bmAdmin.nonce,
                    mentor_id: BMAvailability.mentorId,
                    start_date: formData.get('start_date'),
                    end_date: formData.get('end_date'),
                    reason: formData.get('reason')
                },
                success: function(response) {
                    if (response.success) {
                        BMAvailability.closeModal();
                        BMAvailability.loadCalendar();
                        $('#bm-time-off-form')[0].reset();
                    } else {
                        alert(response.data.message || 'Failed to schedule time off.');
                    }
                }
            });
        },

        showCopyAvailabilityModal: function() {
            $('#bm-copy-availability-modal').show();
            BMAvailability.renderCopyDatePicker();
        },

        renderCopyDatePicker: function() {
            // Render mini calendar for date selection
            // Implementation would be similar to main calendar but smaller
        },

        copyAvailability: function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const targetDates = formData.get('target_dates').split(',').filter(d => d.trim());
            
            if (targetDates.length === 0) {
                alert('Please select target dates.');
                return;
            }

            $.ajax({
                url: bmAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'bm_copy_availability',
                    nonce: bmAdmin.nonce,
                    mentor_id: BMAvailability.mentorId,
                    source_date: formData.get('source_date'),
                    target_dates: JSON.stringify(targetDates),
                    copy_type: formData.get('copy_type')
                },
                success: function(response) {
                    if (response.success) {
                        BMAvailability.closeModal();
                        BMAvailability.loadCalendar();
                        $('#bm-copy-availability-form')[0].reset();
                    } else {
                        alert(response.data.message || 'Failed to copy availability.');
                    }
                }
            });
        },

        closeModal: function() {
            $('.bm-modal').hide();
        },

        formatTime: function(timeStr) {
            const [hour, minute] = timeStr.split(':');
            const date = new Date();
            date.setHours(parseInt(hour), parseInt(minute));
            return date.toLocaleTimeString([], {hour: 'numeric', minute: '2-digit'});
        },

        isToday: function(date) {
            const today = new Date();
            return date.toDateString() === today.toDateString();
        }
    };

    BMAvailability.init();
});
</script>