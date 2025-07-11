/**
 * Booking Master Admin JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        initAdminFunctions();
    });

    function initAdminFunctions() {
        // Initialize tooltips if available
        if ($.fn.tooltip) {
            $('[data-toggle="tooltip"]').tooltip();
        }

        // Handle service form submission
        $(document).on('submit', '#bm-service-form', handleServiceForm);
        
        // Handle booking status updates
        $(document).on('click', '.update-booking-status', handleBookingStatusUpdate);
        
        // Handle user role assignments
        $(document).on('click', '.assign-role', handleRoleAssignment);
        
        // Handle Zoom connection
        $(document).on('click', '.connect-zoom', handleZoomConnection);
        $(document).on('click', '.disconnect-zoom', handleZoomDisconnection);
        
        // Handle service deletion
        $(document).on('click', '.delete-service', handleServiceDeletion);
        
        // Handle bulk actions
        $(document).on('click', '.bulk-action-submit', handleBulkActions);
        
        // Handle modal close
        $(document).on('click', '.bm-modal-close, .bm-modal', function(e) {
            if (e.target === this) {
                $('.bm-modal').hide();
            }
        });
    }

    function handleServiceForm(e) {
        e.preventDefault();
        
        var form = $(this);
        var formData = new FormData(form[0]);
        
        // Add action and nonce
        formData.append('action', form.find('input[name="action"]').val() || 'bm_create_service');
        formData.append('nonce', bm_admin_ajax.nonce);
        
        // Show loading state
        form.addClass('bm-loading');
        
        $.ajax({
            url: bm_admin_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                form.removeClass('bm-loading');
                
                if (response.success) {
                    showNotice(response.data.message, 'success');
                    
                    // Reset form if creating new service
                    if (formData.get('action') === 'bm_create_service') {
                        form[0].reset();
                    }
                    
                    // Refresh services list if available
                    refreshServicesList();
                    
                    // Close modal if open
                    $('.bm-modal').hide();
                } else {
                    showNotice(response.data.message, 'error');
                }
            },
            error: function() {
                form.removeClass('bm-loading');
                showNotice('An error occurred. Please try again.', 'error');
            }
        });
    }

    function handleBookingStatusUpdate(e) {
        e.preventDefault();
        
        var button = $(this);
        var bookingId = button.data('booking-id');
        var status = button.data('status');
        
        if (!confirm('Are you sure you want to update this booking status?')) {
            return;
        }
        
        button.prop('disabled', true);
        
        $.ajax({
            url: bm_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'bm_update_booking_status',
                booking_id: bookingId,
                status: status,
                nonce: bm_admin_ajax.nonce
            },
            success: function(response) {
                button.prop('disabled', false);
                
                if (response.success) {
                    showNotice(response.data.message, 'success');
                    
                    // Update status in the UI
                    var statusCell = button.closest('tr').find('.booking-status');
                    statusCell.html('<span class="bm-status ' + status + '">' + status + '</span>');
                    
                    // Hide action buttons if status is final
                    if (status === 'confirmed' || status === 'cancelled' || status === 'completed') {
                        button.closest('.booking-actions').hide();
                    }
                } else {
                    showNotice(response.data.message, 'error');
                }
            },
            error: function() {
                button.prop('disabled', false);
                showNotice('An error occurred. Please try again.', 'error');
            }
        });
    }

    function handleRoleAssignment(e) {
        e.preventDefault();
        
        var button = $(this);
        var userId = button.data('user-id');
        var role = button.data('role');
        
        if (!confirm('Are you sure you want to assign this role to the user?')) {
            return;
        }
        
        button.prop('disabled', true);
        
        $.ajax({
            url: bm_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'bm_assign_' + role + '_role',
                user_id: userId,
                nonce: bm_admin_ajax.nonce
            },
            success: function(response) {
                button.prop('disabled', false);
                
                if (response.success) {
                    showNotice(response.data.message, 'success');
                    
                    // Update role badge in the UI
                    var roleCell = button.closest('tr').find('.user-role');
                    roleCell.html('<span class="bm-user-role-badge ' + role + '">' + role + '</span>');
                } else {
                    showNotice(response.data.message, 'error');
                }
            },
            error: function() {
                button.prop('disabled', false);
                showNotice('An error occurred. Please try again.', 'error');
            }
        });
    }

    function handleZoomConnection(e) {
        e.preventDefault();
        
        var button = $(this);
        button.prop('disabled', true);
        
        $.ajax({
            url: bm_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'bm_connect_zoom',
                nonce: bm_admin_ajax.nonce
            },
            success: function(response) {
                button.prop('disabled', false);
                
                if (response.success) {
                    if (response.data.redirect_url) {
                        window.location.href = response.data.redirect_url;
                    } else {
                        showNotice(response.data.message, 'success');
                    }
                } else {
                    showNotice(response.data.message, 'error');
                }
            },
            error: function() {
                button.prop('disabled', false);
                showNotice('An error occurred. Please try again.', 'error');
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
        
        $.ajax({
            url: bm_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'bm_disconnect_zoom',
                nonce: bm_admin_ajax.nonce
            },
            success: function(response) {
                button.prop('disabled', false);
                
                if (response.success) {
                    showNotice(response.data.message, 'success');
                    
                    // Update Zoom status UI
                    $('.bm-zoom-status').removeClass('connected').addClass('disconnected');
                    $('.bm-zoom-status p').text('Not connected to Zoom');
                    button.replaceWith('<button class="bm-btn primary connect-zoom">Connect Zoom</button>');
                } else {
                    showNotice(response.data.message, 'error');
                }
            },
            error: function() {
                button.prop('disabled', false);
                showNotice('An error occurred. Please try again.', 'error');
            }
        });
    }

    function handleServiceDeletion(e) {
        e.preventDefault();
        
        var button = $(this);
        var serviceId = button.data('service-id');
        
        if (!confirm('Are you sure you want to delete this service? This action cannot be undone.')) {
            return;
        }
        
        button.prop('disabled', true);
        
        $.ajax({
            url: bm_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'bm_delete_service',
                service_id: serviceId,
                nonce: bm_admin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotice(response.data.message, 'success');
                    
                    // Remove service row from table
                    button.closest('tr').fadeOut(function() {
                        $(this).remove();
                    });
                } else {
                    button.prop('disabled', false);
                    showNotice(response.data.message, 'error');
                }
            },
            error: function() {
                button.prop('disabled', false);
                showNotice('An error occurred. Please try again.', 'error');
            }
        });
    }

    function handleBulkActions(e) {
        e.preventDefault();
        
        var form = $(this).closest('form');
        var action = form.find('select[name="action"]').val();
        var selectedItems = form.find('input[name="selected[]"]:checked');
        
        if (!action || action === '-1') {
            alert('Please select an action.');
            return;
        }
        
        if (selectedItems.length === 0) {
            alert('Please select at least one item.');
            return;
        }
        
        if (!confirm('Are you sure you want to perform this action on ' + selectedItems.length + ' item(s)?')) {
            return;
        }
        
        var button = $(this);
        button.prop('disabled', true);
        
        var itemIds = [];
        selectedItems.each(function() {
            itemIds.push($(this).val());
        });
        
        $.ajax({
            url: bm_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'bm_bulk_action',
                bulk_action: action,
                item_ids: itemIds,
                nonce: bm_admin_ajax.nonce
            },
            success: function(response) {
                button.prop('disabled', false);
                
                if (response.success) {
                    showNotice(response.data.message, 'success');
                    location.reload(); // Refresh page to show changes
                } else {
                    showNotice(response.data.message, 'error');
                }
            },
            error: function() {
                button.prop('disabled', false);
                showNotice('An error occurred. Please try again.', 'error');
            }
        });
    }

    function refreshServicesList() {
        var container = $('#services-container');
        if (container.length) {
            container.addClass('bm-loading');
            
            $.ajax({
                url: bm_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'bm_get_services',
                    nonce: bm_admin_ajax.nonce
                },
                success: function(response) {
                    container.removeClass('bm-loading');
                    
                    if (response.success) {
                        // Update services list (would need server-side HTML generation)
                        // For now, just reload the page section
                        location.reload();
                    }
                },
                error: function() {
                    container.removeClass('bm-loading');
                }
            });
        }
    }

    function showNotice(message, type) {
        // Remove existing notices
        $('.bm-admin-notice').remove();
        
        var noticeClass = 'notice';
        if (type === 'success') {
            noticeClass += ' notice-success';
        } else if (type === 'error') {
            noticeClass += ' notice-error';
        } else if (type === 'warning') {
            noticeClass += ' notice-warning';
        } else {
            noticeClass += ' notice-info';
        }
        
        var notice = $('<div class="' + noticeClass + ' is-dismissible bm-admin-notice"><p>' + message + '</p></div>');
        
        // Insert after the first h1 or at the top of the content
        var target = $('.wrap h1').first();
        if (target.length) {
            target.after(notice);
        } else {
            $('.wrap').prepend(notice);
        }
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            notice.fadeOut(function() {
                notice.remove();
            });
        }, 5000);
    }

    // Global functions for inline onclick handlers
    window.editService = function(serviceId) {
        // Open edit service modal
        showServiceModal(serviceId);
    };

    window.deleteService = function(serviceId) {
        if (confirm('Are you sure you want to delete this service?')) {
            $('.delete-service[data-service-id="' + serviceId + '"]').click();
        }
    };

    window.updateBookingStatus = function(bookingId, status) {
        $('.update-booking-status[data-booking-id="' + bookingId + '"][data-status="' + status + '"]').click();
    };

    window.showServiceForm = function() {
        showServiceModal();
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
        content.html('<p>Loading...</p>');
        
        $.ajax({
            url: bm_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'bm_get_service_form',
                service_id: serviceId || '',
                nonce: bm_admin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    content.html(response.data.html);
                } else {
                    content.html('<p>Error loading form.</p>');
                }
            },
            error: function() {
                content.html('<p>Error loading form.</p>');
            }
        });
        
        modal.show();
    }

})(jQuery);