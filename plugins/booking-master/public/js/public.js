/**
 * Booking Master Public JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        initPublicFunctions();
    });

    function initPublicFunctions() {
        // Handle booking form submission
        $(document).on('submit', '#bm-booking-form', handleBookingSubmission);
        
        // Handle service booking button clicks
        $(document).on('click', '.book-service', openBookingModal);
        
        // Handle date change for time slot loading
        $(document).on('change', '#booking-date', loadAvailableTimeSlots);
        
        // Handle modal close
        $(document).on('click', '.bm-modal-close, .bm-modal', function(e) {
            if (e.target === this) {
                $('.bm-modal').hide();
            }
        });
        
        // Handle booking cancellation
        $(document).on('click', '.cancel-booking', handleBookingCancellation);
        
        // Handle Zoom connection for mentors
        $(document).on('click', '.connect-zoom', handleZoomConnection);
        $(document).on('click', '.disconnect-zoom', handleZoomDisconnection);
        
        // Handle mentor application form
        $(document).on('submit', '#bm-mentor-application-form', handleMentorApplication);
        
        // Handle service form submission (in modal)
        $(document).on('submit', '#bm-service-form', handleServiceFormSubmission);
    }

    function handleBookingSubmission(e) {
        e.preventDefault();
        
        var form = $(this);
        var formData = new FormData(form[0]);
        
        // Combine date and time
        var date = form.find('#booking-date').val();
        var time = form.find('#booking-time').val();
        
        if (!date || !time) {
            showMessage('Please select both date and time.', 'error');
            return;
        }
        
        formData.append('booking_date', date + ' ' + time);
        formData.append('action', 'bm_create_booking');
        
        // Show loading state
        form.addClass('bm-loading');
        var submitButton = form.find('button[type="submit"]');
        var originalText = submitButton.text();
        submitButton.text('Processing...').prop('disabled', true);
        
        $.ajax({
            url: bm_public_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                form.removeClass('bm-loading');
                submitButton.text(originalText).prop('disabled', false);
                
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    
                    // Close modal and refresh page or redirect
                    $('.bm-modal').hide();
                    
                    // Optionally redirect to user dashboard
                    setTimeout(function() {
                        if (window.location.href.indexOf('dashboard') === -1) {
                            window.location.reload();
                        }
                    }, 2000);
                } else {
                    showMessage(response.data.message, 'error');
                }
            },
            error: function() {
                form.removeClass('bm-loading');
                submitButton.text(originalText).prop('disabled', false);
                showMessage('An error occurred. Please try again.', 'error');
            }
        });
    }

    function openBookingModal(e) {
        e.preventDefault();
        
        var button = $(this);
        var serviceId = button.data('service-id') || button.closest('.service-card').data('service-id');
        
        if (!serviceId) {
            showMessage('Service ID not found.', 'error');
            return;
        }
        
        var modal = $('#bm-booking-modal');
        if (modal.length === 0) {
            showMessage('Booking modal not found.', 'error');
            return;
        }
        
        var content = modal.find('#bm-booking-form-content');
        content.html('<div class="loading-content"><p>Loading booking form...</p></div>');
        
        // Load booking form via AJAX
        $.ajax({
            url: bm_public_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'bm_get_booking_form',
                service_id: serviceId,
                nonce: bm_public_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    content.html(response.data.html);
                } else {
                    content.html('<div class="error-content"><p>' + (response.data.message || 'Error loading booking form.') + '</p></div>');
                }
            },
            error: function() {
                content.html('<div class="error-content"><p>Error loading booking form. Please try again.</p></div>');
            }
        });
        
        modal.show();
    }

    function loadAvailableTimeSlots() {
        var dateInput = $(this);
        var date = dateInput.val();
        var serviceId = dateInput.closest('form').find('input[name="service_id"]').val();
        var timeSelect = $('#booking-time');
        
        if (!date || !serviceId) {
            return;
        }
        
        timeSelect.html('<option value="">Loading...</option>').prop('disabled', true);
        
        $.ajax({
            url: bm_public_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'bm_get_available_slots',
                service_id: serviceId,
                date: date,
                nonce: bm_public_ajax.nonce
            },
            success: function(response) {
                timeSelect.prop('disabled', false);
                
                if (response.success && response.data.slots.length > 0) {
                    var options = '<option value="">Select a time</option>';
                    
                    response.data.slots.forEach(function(slot) {
                        options += '<option value="' + slot.time + '">' + slot.display + '</option>';
                    });
                    
                    timeSelect.html(options);
                } else {
                    timeSelect.html('<option value="">No available times</option>');
                    showMessage('No available time slots for this date.', 'warning');
                }
            },
            error: function() {
                timeSelect.prop('disabled', false);
                timeSelect.html('<option value="">Error loading times</option>');
                showMessage('Error loading available times.', 'error');
            }
        });
    }

    function handleBookingCancellation(e) {
        e.preventDefault();
        
        var button = $(this);
        var bookingId = button.data('booking-id');
        
        if (!confirm('Are you sure you want to cancel this booking?')) {
            return;
        }
        
        button.prop('disabled', true);
        var originalText = button.text();
        button.text('Cancelling...');
        
        $.ajax({
            url: bm_public_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'bm_cancel_booking',
                booking_id: bookingId,
                nonce: bm_public_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    
                    // Update booking status in UI
                    var bookingItem = button.closest('.booking-item');
                    bookingItem.find('.status').removeClass().addClass('status status-cancelled').text('Cancelled');
                    button.remove();
                } else {
                    button.prop('disabled', false);
                    button.text(originalText);
                    showMessage(response.data.message, 'error');
                }
            },
            error: function() {
                button.prop('disabled', false);
                button.text(originalText);
                showMessage('An error occurred. Please try again.', 'error');
            }
        });
    }

    function handleZoomConnection(e) {
        e.preventDefault();
        
        var button = $(this);
        button.prop('disabled', true);
        var originalText = button.text();
        button.text('Connecting...');
        
        $.ajax({
            url: bm_public_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'bm_connect_zoom',
                nonce: bm_public_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    if (response.data.redirect_url) {
                        window.location.href = response.data.redirect_url;
                    } else {
                        showMessage(response.data.message, 'success');
                        button.prop('disabled', false);
                        button.text(originalText);
                    }
                } else {
                    button.prop('disabled', false);
                    button.text(originalText);
                    showMessage(response.data.message, 'error');
                }
            },
            error: function() {
                button.prop('disabled', false);
                button.text(originalText);
                showMessage('An error occurred. Please try again.', 'error');
            }
        });
    }

    function handleZoomDisconnection(e) {
        e.preventDefault();
        
        var button = $(this);
        
        if (!confirm('Are you sure you want to disconnect your Zoom account?')) {
            return;
        }
        
        button.prop('disabled', true);
        var originalText = button.text();
        button.text('Disconnecting...');
        
        $.ajax({
            url: bm_public_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'bm_disconnect_zoom',
                nonce: bm_public_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    
                    // Update Zoom status UI
                    $('.zoom-status').removeClass('connected').addClass('disconnected');
                    $('.zoom-status p').text('Not connected to Zoom');
                    button.replaceWith('<button class="btn btn-primary connect-zoom">Connect Zoom</button>');
                } else {
                    button.prop('disabled', false);
                    button.text(originalText);
                    showMessage(response.data.message, 'error');
                }
            },
            error: function() {
                button.prop('disabled', false);
                button.text(originalText);
                showMessage('An error occurred. Please try again.', 'error');
            }
        });
    }

    function handleMentorApplication(e) {
        e.preventDefault();
        
        var form = $(this);
        var formData = new FormData(form[0]);
        formData.append('action', 'bm_submit_mentor_application');
        
        var submitButton = form.find('button[type="submit"]');
        var originalText = submitButton.text();
        submitButton.text('Submitting...').prop('disabled', true);
        
        $.ajax({
            url: bm_public_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                submitButton.text(originalText).prop('disabled', false);
                
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    form[0].reset();
                } else {
                    showMessage(response.data.message, 'error');
                }
            },
            error: function() {
                submitButton.text(originalText).prop('disabled', false);
                showMessage('An error occurred. Please try again.', 'error');
            }
        });
    }

    function handleServiceFormSubmission(e) {
        e.preventDefault();
        
        var form = $(this);
        var formData = new FormData(form[0]);
        
        // Get the action from the hidden input
        var action = form.find('input[name="action"]').val();
        if (!action) {
            action = 'bm_create_service';
        }
        
        formData.append('action', action);
        formData.append('nonce', bm_public_ajax.nonce);
        
        var submitButton = form.find('button[type="submit"]');
        var originalText = submitButton.text();
        submitButton.text('Saving...').prop('disabled', true);
        form.addClass('bm-loading');
        
        $.ajax({
            url: bm_public_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                form.removeClass('bm-loading');
                submitButton.text(originalText).prop('disabled', false);
                
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    
                    // Close modal
                    $('.bm-modal').hide();
                    
                    // Refresh page to show updated services
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showMessage(response.data.message, 'error');
                }
            },
            error: function() {
                form.removeClass('bm-loading');
                submitButton.text(originalText).prop('disabled', false);
                showMessage('An error occurred. Please try again.', 'error');
            }
        });
    }

    function showMessage(message, type) {
        // Remove existing messages
        $('.bm-message').remove();
        
        var messageClass = 'bm-message';
        if (type === 'success') {
            messageClass += ' bm-message-success';
        } else if (type === 'error') {
            messageClass += ' bm-message-error';
        } else if (type === 'warning') {
            messageClass += ' bm-message-warning';
        } else {
            messageClass += ' bm-message-info';
        }
        
        var messageHtml = '<div class="' + messageClass + '" style="position: fixed; top: 20px; right: 20px; z-index: 999999; background: #fff; padding: 15px 20px; border-radius: 4px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-left: 4px solid #007cba; max-width: 400px;">';
        
        if (type === 'success') {
            messageHtml = messageHtml.replace('#007cba', '#28a745');
        } else if (type === 'error') {
            messageHtml = messageHtml.replace('#007cba', '#dc3545');
        } else if (type === 'warning') {
            messageHtml = messageHtml.replace('#007cba', '#ffc107');
        }
        
        messageHtml += '<span style="cursor: pointer; float: right; font-weight: bold; margin-left: 10px;" onclick="$(this).closest(\'.bm-message\').remove()">&times;</span>';
        messageHtml += '<div>' + message + '</div>';
        messageHtml += '</div>';
        
        $('body').append(messageHtml);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $('.bm-message').fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }

    // Global functions for inline onclick handlers
    window.bookService = function(serviceId) {
        $('.book-service[data-service-id="' + serviceId + '"]').first().click();
    };

    window.cancelBooking = function(bookingId) {
        $('.cancel-booking[data-booking-id="' + bookingId + '"]').click();
    };

    window.connectZoom = function() {
        $('.connect-zoom').click();
    };

    window.disconnectZoom = function() {
        $('.disconnect-zoom').click();
    };

    window.openBookingModal = function(serviceId) {
        var fakeButton = $('<button class="book-service" data-service-id="' + serviceId + '"></button>');
        openBookingModal.call(fakeButton[0], { preventDefault: function() {} });
    };

    window.showServiceForm = function(serviceId) {
        showServiceModal(serviceId);
    };

    window.closeServiceForm = function() {
        $('.bm-modal').hide();
    };

    window.editService = function(serviceId) {
        showServiceModal(serviceId);
    };

    window.deleteService = function(serviceId) {
        if (confirm('Are you sure you want to delete this service? This action cannot be undone.')) {
            // Trigger delete service action
            $.ajax({
                url: bm_public_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'bm_delete_service',
                    service_id: serviceId,
                    nonce: bm_public_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showMessage(response.data.message, 'success');
                        // Refresh the services list
                        location.reload();
                    } else {
                        showMessage(response.data.message, 'error');
                    }
                },
                error: function() {
                    showMessage('An error occurred. Please try again.', 'error');
                }
            });
        }
    };

    function showServiceModal(serviceId) {
        var modal = $('#service-modal');
        if (modal.length === 0) {
            // Create modal if it doesn't exist
            modal = $('<div id="service-modal" class="bm-modal"><div class="bm-modal-content"><span class="bm-modal-close">&times;</span><div id="service-form-content"></div></div></div>');
            $('body').append(modal);
        }
        
        // Load service form via AJAX
        var content = modal.find('#service-form-content');
        content.html('<div style="padding: 40px; text-align: center;"><p>Loading...</p></div>');
        
        $.ajax({
            url: bm_public_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'bm_get_service_form',
                service_id: serviceId || '',
                nonce: bm_public_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    content.html(response.data.html);
                } else {
                    content.html('<div style="padding: 40px; text-align: center;"><p>Error loading form.</p></div>');
                }
            },
            error: function() {
                content.html('<div style="padding: 40px; text-align: center;"><p>Error loading form. Please try again.</p></div>');
            }
        });
        
        modal.show();
    }

})(jQuery);