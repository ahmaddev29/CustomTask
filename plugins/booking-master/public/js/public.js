/**
 * Booking Master Public JavaScript
 *
 * Modern JavaScript for the frontend booking system
 *
 * @since      1.0.0
 */

(function($) {
    'use strict';

    // Global variables
    let currentStep = 1;
    let selectedService = null;
    let selectedDate = null;
    let selectedTime = null;
    let bookingData = {};
    let stripe = null;
    let cardElement = null;

    // Initialize when document is ready
    $(document).ready(function() {
        initializeBookingSystem();
    });

    /**
     * Initialize the booking system
     */
    function initializeBookingSystem() {
        // Initialize Stripe if available
        if (typeof Stripe !== 'undefined' && bm_public_ajax.stripe_publishable_key) {
            stripe = Stripe(bm_public_ajax.stripe_publishable_key);
        }

        // Bind event handlers
        bindEventHandlers();

        // Load services if container exists
        if ($('.bm-services-container').length) {
            loadServices();
        }
    }

    /**
     * Bind event handlers
     */
    function bindEventHandlers() {
        // Service booking
        $(document).on('click', '.bm-book-button', function() {
            const serviceId = $(this).closest('.bm-service-card').data('service-id');
            bmOpenBookingModal(serviceId);
        });

        // Modal close handlers
        $(document).on('click', '.bm-modal-close', function() {
            closeModal($(this).closest('.bm-modal'));
        });

        $(document).on('click', '.bm-modal', function(e) {
            if (e.target === this) {
                closeModal($(this));
            }
        });

        // Calendar date selection
        $(document).on('click', '.bm-calendar-day:not(.disabled):not(.header)', function() {
            selectDate($(this));
        });

        // Time slot selection
        $(document).on('click', '.bm-time-slot:not(.disabled):not(.booked)', function() {
            selectTimeSlot($(this));
        });

        // Form submission
        $(document).on('submit', '#bm-booking-details-form', function(e) {
            e.preventDefault();
            validatePersonalDetails();
        });

        // Payment method selection
        $(document).on('change', 'input[name="payment_method"]', function() {
            handlePaymentMethodChange($(this).val());
        });

        // Navigation buttons
        $(document).on('click', '#bm-booking-next', function() {
            bmBookingNextStep();
        });

        $(document).on('click', '#bm-booking-back', function() {
            bmBookingPrevStep();
        });

        // ESC key to close modal
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal($('.bm-modal:visible'));
            }
        });
    }

    /**
     * Load services
     */
    function loadServices() {
        const container = $('.bm-services-container');
        const mentorId = container.data('mentor-id');

        $.ajax({
            url: bm_public_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'bm_get_services',
                mentor_id: mentorId || '',
                nonce: bm_public_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    displayServices(response.data.services);
                }
            },
            error: function() {
                console.error('Failed to load services');
            }
        });
    }

    /**
     * Display services
     */
    function displayServices(services) {
        const container = $('.bm-services-grid');
        
        if (services.length === 0) {
            container.html('<div class="bm-no-services"><h3>No services available</h3><p>Please check back later for available services.</p></div>');
            return;
        }

        let html = '';
        services.forEach(function(service) {
            html += renderServiceCard(service);
        });

        container.html(html);
    }

    /**
     * Render service card
     */
    function renderServiceCard(service) {
        const zoomBadge = service.zoom_enabled ? '<div class="bm-service-badge zoom-enabled"><i class="bm-icon-video"></i> Online Session</div>' : '';
        const description = service.description ? `<p class="bm-service-description">${service.description}</p>` : '';
        
        return `
            <div class="bm-service-card" data-service-id="${service.id}">
                <div class="bm-service-header">
                    ${zoomBadge}
                    <h3 class="bm-service-title">${service.service_name}</h3>
                    <div class="bm-service-mentor">
                        <span class="bm-mentor-label">with</span>
                        <span class="bm-mentor-name">${service.mentor_name}</span>
                    </div>
                </div>
                
                <div class="bm-service-content">
                    ${description}
                    
                    <div class="bm-service-meta">
                        <div class="bm-service-price">
                            <span class="bm-price-amount">${bm_public_ajax.currency_symbol}${parseFloat(service.price).toFixed(2)}</span>
                            <span class="bm-price-label">per session</span>
                        </div>
                        <div class="bm-service-duration">
                            <i class="bm-icon-clock"></i>
                            <span>${service.duration} minutes</span>
                        </div>
                    </div>
                </div>
                
                <div class="bm-service-footer">
                    <button class="bm-book-button" onclick="bmOpenBookingModal(${service.id})">
                        <i class="bm-icon-calendar"></i>
                        <span>Book Now</span>
                    </button>
                    <button class="bm-view-details-button" onclick="bmViewServiceDetails(${service.id})">
                        <span>View Details</span>
                    </button>
                </div>
            </div>
        `;
    }

    /**
     * Open booking modal
     */
    window.bmOpenBookingModal = function(serviceId) {
        // Get service data
        $.ajax({
            url: bm_public_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'bm_get_service_details',
                service_id: serviceId,
                nonce: bm_public_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    selectedService = response.data.service;
                    showBookingModal();
                    generateCalendar();
                    setActiveStep(1);
                } else {
                    alert('Error loading service details');
                }
            },
            error: function() {
                alert('Failed to load service details');
            }
        });
    };

    /**
     * Show booking modal
     */
    function showBookingModal() {
        const modal = $('#bm-booking-modal');
        $('#bm-booking-modal-title').text(`Book: ${selectedService.service_name}`);
        
        // Reset form
        resetBookingForm();
        
        // Show modal with animation
        modal.css('display', 'flex');
        setTimeout(() => {
            modal.addClass('show');
        }, 10);
    }

    /**
     * Close booking modal
     */
    window.bmCloseBookingModal = function() {
        closeModal($('#bm-booking-modal'));
        resetBookingForm();
    };

    /**
     * Close modal
     */
    function closeModal(modal) {
        modal.removeClass('show');
        setTimeout(() => {
            modal.hide();
        }, 300);
    }

    /**
     * Reset booking form
     */
    function resetBookingForm() {
        currentStep = 1;
        selectedDate = null;
        selectedTime = null;
        bookingData = {};
        
        // Reset form fields
        $('#bm-booking-details-form')[0].reset();
        
        // Reset steps
        $('.bm-step').removeClass('active completed');
        $('.bm-booking-step').hide();
        
        // Reset navigation buttons
        $('#bm-booking-back').hide();
        $('#bm-booking-next').show().find('span').text('Continue');
        $('#bm-booking-finish').hide();
    }

    /**
     * Set active step
     */
    function setActiveStep(step) {
        currentStep = step;
        
        // Update step indicators
        $('.bm-step').removeClass('active completed');
        $('.bm-step').each(function() {
            const stepNum = parseInt($(this).data('step'));
            if (stepNum < step) {
                $(this).addClass('completed');
            } else if (stepNum === step) {
                $(this).addClass('active');
            }
        });
        
        // Show correct step content
        $('.bm-booking-step').hide();
        $(`#bm-step-${step}`).show();
        
        // Update navigation buttons
        updateNavigationButtons();
    }

    /**
     * Update navigation buttons
     */
    function updateNavigationButtons() {
        const backBtn = $('#bm-booking-back');
        const nextBtn = $('#bm-booking-next');
        const finishBtn = $('#bm-booking-finish');
        
        // Show/hide back button
        if (currentStep > 1) {
            backBtn.show();
        } else {
            backBtn.hide();
        }
        
        // Update next button text and show/hide finish button
        if (currentStep === 5) {
            nextBtn.hide();
            finishBtn.show();
        } else {
            nextBtn.show();
            finishBtn.hide();
            
            const nextTexts = {
                1: 'Select Date',
                2: 'Choose Time',
                3: 'Continue',
                4: 'Pay Now'
            };
            
            nextBtn.find('span').text(nextTexts[currentStep] || 'Continue');
        }
    }

    /**
     * Next step
     */
    window.bmBookingNextStep = function() {
        if (validateCurrentStep()) {
            if (currentStep < 5) {
                setActiveStep(currentStep + 1);
                
                // Load step-specific content
                if (currentStep === 2) {
                    loadTimeSlots();
                } else if (currentStep === 3) {
                    populatePersonalDetails();
                } else if (currentStep === 4) {
                    setupPaymentStep();
                }
            }
        }
    };

    /**
     * Previous step
     */
    window.bmBookingPrevStep = function() {
        if (currentStep > 1) {
            setActiveStep(currentStep - 1);
        }
    };

    /**
     * Validate current step
     */
    function validateCurrentStep() {
        switch (currentStep) {
            case 1:
                if (!selectedDate) {
                    alert('Please select a date');
                    return false;
                }
                break;
            case 2:
                if (!selectedTime) {
                    alert('Please select a time slot');
                    return false;
                }
                break;
            case 3:
                return validatePersonalDetails();
            case 4:
                return processPayment();
        }
        return true;
    }

    /**
     * Generate calendar
     */
    function generateCalendar() {
        const today = new Date();
        const currentDate = new Date(today.getFullYear(), today.getMonth(), 1);
        const calendarContainer = $('#bm-booking-calendar');
        
        let html = '';
        
        // Add day headers
        const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        dayNames.forEach(day => {
            html += `<div class="bm-calendar-day header">${day}</div>`;
        });
        
        // Add calendar days
        const firstDay = currentDate.getDay();
        const lastDate = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0).getDate();
        
        // Add empty cells for days before the first day of the month
        for (let i = 0; i < firstDay; i++) {
            html += '<div class="bm-calendar-day other-month"></div>';
        }
        
        // Add days of the month
        for (let day = 1; day <= lastDate; day++) {
            const date = new Date(currentDate.getFullYear(), currentDate.getMonth(), day);
            const dateStr = formatDate(date);
            
            let classes = 'bm-calendar-day';
            
            if (date < today) {
                classes += ' disabled';
            } else if (date.toDateString() === today.toDateString()) {
                classes += ' today';
            }
            
            html += `<div class="${classes}" data-date="${dateStr}">${day}</div>`;
        }
        
        calendarContainer.html(html);
    }

    /**
     * Select date
     */
    function selectDate(element) {
        // Remove previous selection
        $('.bm-calendar-day').removeClass('selected');
        
        // Add selection to clicked date
        element.addClass('selected');
        selectedDate = element.data('date');
        
        // Update selected date display
        const dateObj = new Date(selectedDate);
        const formattedDate = dateObj.toLocaleDateString('en-US', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        
        $('#bm-selected-date').text(formattedDate);
    }

    /**
     * Load time slots
     */
    function loadTimeSlots() {
        if (!selectedDate) return;
        
        const container = $('#bm-time-slots');
        container.html('<div class="loading">Loading available times...</div>');
        
        $.ajax({
            url: bm_public_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'bm_get_service_availability',
                service_id: selectedService.id,
                date: selectedDate,
                nonce: bm_public_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    displayTimeSlots(response.data.slots);
                } else {
                    container.html('<div class="error">No available times for this date</div>');
                }
            },
            error: function() {
                container.html('<div class="error">Failed to load available times</div>');
            }
        });
    }

    /**
     * Display time slots
     */
    function displayTimeSlots(slots) {
        const container = $('#bm-time-slots');
        
        if (slots.length === 0) {
            container.html('<div class="no-slots">No available times for this date</div>');
            return;
        }
        
        let html = '';
        slots.forEach(function(slot) {
            html += `<div class="bm-time-slot" data-time="${slot}">${formatTime(slot)}</div>`;
        });
        
        container.html(html);
    }

    /**
     * Select time slot
     */
    function selectTimeSlot(element) {
        // Remove previous selection
        $('.bm-time-slot').removeClass('selected');
        
        // Add selection to clicked time
        element.addClass('selected');
        selectedTime = element.data('time');
    }

    /**
     * Populate personal details
     */
    function populatePersonalDetails() {
        // Pre-fill with current user data if available
        if (typeof bm_user_data !== 'undefined') {
            $('#bm-first-name').val(bm_user_data.first_name || '');
            $('#bm-last-name').val(bm_user_data.last_name || '');
            $('#bm-email').val(bm_user_data.email || '');
        }
    }

    /**
     * Validate personal details
     */
    function validatePersonalDetails() {
        const form = $('#bm-booking-details-form');
        let isValid = true;
        
        // Check required fields
        form.find('input[required]').each(function() {
            if (!$(this).val().trim()) {
                $(this).addClass('error');
                isValid = false;
            } else {
                $(this).removeClass('error');
            }
        });
        
        // Validate email format
        const email = $('#bm-email').val();
        if (email && !isValidEmail(email)) {
            $('#bm-email').addClass('error');
            isValid = false;
        }
        
        if (!isValid) {
            alert('Please fill in all required fields correctly');
            return false;
        }
        
        // Store form data
        bookingData.personal = {
            first_name: $('#bm-first-name').val(),
            last_name: $('#bm-last-name').val(),
            email: $('#bm-email').val(),
            phone: $('#bm-phone').val(),
            notes: $('#bm-notes').val()
        };
        
        return true;
    }

    /**
     * Setup payment step
     */
    function setupPaymentStep() {
        // Update booking summary
        updateBookingSummary();
        
        // Calculate and display pricing
        calculateBookingTotal();
        
        // Initialize payment methods
        initializePaymentMethods();
    }

    /**
     * Update booking summary
     */
    function updateBookingSummary() {
        $('#bm-summary-service').text(selectedService.service_name);
        
        const dateObj = new Date(selectedDate);
        const formattedDate = dateObj.toLocaleDateString();
        const formattedTime = formatTime(selectedTime);
        $('#bm-summary-datetime').text(`${formattedDate} at ${formattedTime}`);
        
        $('#bm-summary-duration').text(`${selectedService.duration} minutes`);
    }

    /**
     * Calculate booking total
     */
    function calculateBookingTotal() {
        $.ajax({
            url: bm_public_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'bm_calculate_booking_total',
                service_id: selectedService.id,
                nonce: bm_public_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    displayPricingBreakdown(response.data);
                    bookingData.pricing = response.data;
                }
            },
            error: function() {
                console.error('Failed to calculate booking total');
            }
        });
    }

    /**
     * Display pricing breakdown
     */
    function displayPricingBreakdown(pricing) {
        $('#bm-breakdown-subtotal').text(`${bm_public_ajax.currency_symbol}${pricing.subtotal.toFixed(2)}`);
        $('#bm-breakdown-management').text(`${bm_public_ajax.currency_symbol}${pricing.management_fee.toFixed(2)}`);
        $('#bm-breakdown-tax').text(`${bm_public_ajax.currency_symbol}${pricing.tax_amount.toFixed(2)}`);
        $('#bm-breakdown-total').text(`${bm_public_ajax.currency_symbol}${pricing.total.toFixed(2)}`);
        
        $('#bm-management-rate').text(pricing.management_fee_rate.toFixed(1));
        $('#bm-tax-rate').text(pricing.tax_rate.toFixed(1));
    }

    /**
     * Initialize payment methods
     */
    function initializePaymentMethods() {
        // Initialize Stripe Elements
        if (stripe) {
            const elements = stripe.elements();
            cardElement = elements.create('card', {
                style: {
                    base: {
                        fontSize: '16px',
                        color: '#1a1a1a',
                        '::placeholder': {
                            color: '#6c757d',
                        },
                    },
                },
            });
            
            cardElement.mount('#bm-card-element');
            
            cardElement.on('change', function(event) {
                const displayError = document.getElementById('bm-card-errors');
                if (event.error) {
                    displayError.textContent = event.error.message;
                } else {
                    displayError.textContent = '';
                }
            });
        }
        
        // Set default payment method
        const defaultPaymentMethod = $('input[name="payment_method"]:first');
        if (defaultPaymentMethod.length) {
            defaultPaymentMethod.prop('checked', true);
            handlePaymentMethodChange(defaultPaymentMethod.val());
        }
    }

    /**
     * Handle payment method change
     */
    function handlePaymentMethodChange(method) {
        // Hide all payment forms
        $('.bm-payment-form').hide();
        
        // Show selected payment form
        if (method === 'stripe') {
            $('#bm-stripe-payment').show();
        } else if (method === 'paypal') {
            $('#bm-paypal-payment').show();
            initializePayPal();
        }
    }

    /**
     * Initialize PayPal
     */
    function initializePayPal() {
        if (typeof paypal !== 'undefined' && bookingData.pricing) {
            paypal.Buttons({
                createOrder: function(data, actions) {
                    return actions.order.create({
                        purchase_units: [{
                            amount: {
                                value: bookingData.pricing.total.toFixed(2)
                            }
                        }]
                    });
                },
                onApprove: function(data, actions) {
                    return actions.order.capture().then(function(details) {
                        processBookingPayment('paypal', {
                            order_id: data.orderID,
                            payer_id: data.payerID
                        });
                    });
                }
            }).render('#bm-paypal-button-container');
        }
    }

    /**
     * Process payment
     */
    function processPayment() {
        const paymentMethod = $('input[name="payment_method"]:checked').val();
        
        if (paymentMethod === 'stripe') {
            return processStripePayment();
        } else if (paymentMethod === 'paypal') {
            // PayPal payment is handled by the PayPal button
            return true;
        }
        
        return false;
    }

    /**
     * Process Stripe payment
     */
    function processStripePayment() {
        if (!stripe || !cardElement) {
            alert('Payment system not available');
            return false;
        }
        
        // Disable the submit button to prevent multiple submissions
        $('#bm-booking-next').prop('disabled', true);
        
        stripe.createPaymentMethod({
            type: 'card',
            card: cardElement,
            billing_details: {
                name: `${bookingData.personal.first_name} ${bookingData.personal.last_name}`,
                email: bookingData.personal.email,
            },
        }).then(function(result) {
            if (result.error) {
                // Show error to customer
                document.getElementById('bm-card-errors').textContent = result.error.message;
                $('#bm-booking-next').prop('disabled', false);
            } else {
                // Process the payment
                processBookingPayment('stripe', {
                    payment_method_id: result.paymentMethod.id
                });
            }
        });
        
        return false; // Prevent immediate step progression
    }

    /**
     * Process booking payment
     */
    function processBookingPayment(paymentMethod, paymentData) {
        const bookingRequestData = {
            action: 'bm_create_booking',
            service_id: selectedService.id,
            booking_date: selectedDate,
            booking_time: selectedTime,
            payment_method: paymentMethod,
            nonce: bm_public_ajax.nonce,
            ...bookingData.personal,
            ...paymentData
        };
        
        $.ajax({
            url: bm_public_ajax.ajax_url,
            type: 'POST',
            data: bookingRequestData,
            success: function(response) {
                if (response.success) {
                    // Move to success step
                    displayBookingConfirmation(response.data);
                    setActiveStep(5);
                } else {
                    alert('Booking failed: ' + response.data.message);
                }
            },
            error: function() {
                alert('Booking failed. Please try again.');
            },
            complete: function() {
                $('#bm-booking-next').prop('disabled', false);
            }
        });
    }

    /**
     * Display booking confirmation
     */
    function displayBookingConfirmation(bookingData) {
        const details = `
            <div class="bm-confirmation-item">
                <strong>Booking ID:</strong> ${bookingData.booking_id}
            </div>
            <div class="bm-confirmation-item">
                <strong>Service:</strong> ${selectedService.service_name}
            </div>
            <div class="bm-confirmation-item">
                <strong>Date & Time:</strong> ${new Date(selectedDate).toLocaleDateString()} at ${formatTime(selectedTime)}
            </div>
            <div class="bm-confirmation-item">
                <strong>Total Paid:</strong> ${bm_public_ajax.currency_symbol}${bookingData.total.total.toFixed(2)}
            </div>
        `;
        
        $('#bm-confirmation-details').html(details);
    }

    /**
     * Show login required modal
     */
    window.bmShowLoginRequired = function() {
        $('#bm-login-modal').css('display', 'flex').addClass('show');
    };

    /**
     * Close login modal
     */
    window.bmCloseLoginModal = function() {
        closeModal($('#bm-login-modal'));
    };

    /**
     * View service details
     */
    window.bmViewServiceDetails = function(serviceId) {
        // Implementation for viewing service details
        console.log('View service details for:', serviceId);
    };

    /**
     * Utility functions
     */
    function formatDate(date) {
        return date.toISOString().split('T')[0];
    }

    function formatTime(time) {
        if (typeof time === 'string' && time.includes(':')) {
            const [hours, minutes] = time.split(':');
            const hour = parseInt(hours);
            const ampm = hour >= 12 ? 'PM' : 'AM';
            const displayHour = hour % 12 || 12;
            return `${displayHour}:${minutes} ${ampm}`;
        }
        return time;
    }

    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    // Add smooth scrolling to anchors
    $(document).on('click', 'a[href^="#"]', function(e) {
        e.preventDefault();
        const target = $(this.getAttribute('href'));
        if (target.length) {
            $('html, body').animate({
                scrollTop: target.offset().top - 80
            }, 500);
        }
    });

    // Add loading states
    function showLoading(element) {
        element.addClass('bm-loading');
    }

    function hideLoading(element) {
        element.removeClass('bm-loading');
    }

    // Add error handling
    $(document).ajaxError(function(event, xhr, settings, error) {
        console.error('AJAX Error:', error);
        hideLoading($('.bm-loading'));
    });

    // Add CSS for loading states
    if (!document.getElementById('bm-loading-styles')) {
        const style = document.createElement('style');
        style.id = 'bm-loading-styles';
        style.textContent = `
            .bm-loading {
                position: relative;
                opacity: 0.6;
                pointer-events: none;
            }
            .bm-loading::after {
                content: '';
                position: absolute;
                top: 50%;
                left: 50%;
                width: 20px;
                height: 20px;
                margin: -10px 0 0 -10px;
                border: 2px solid #f3f3f3;
                border-top: 2px solid #007cba;
                border-radius: 50%;
                animation: bm-spin 1s linear infinite;
                z-index: 9999;
            }
            @keyframes bm-spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            .bm-form-group input.error,
            .bm-form-group select.error,
            .bm-form-group textarea.error {
                border-color: #dc3545;
                box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
            }
        `;
        document.head.appendChild(style);
    }

    // Expose public functions
    window.bmBookingNextStep = bmBookingNextStep;
    window.bmBookingPrevStep = bmBookingPrevStep;

})(jQuery);