/**
 * Main JavaScript for 3D Product Configurator
 */

(function($) {
    'use strict';
    
    let configuratorInstance = null;
    let currentConfiguration = null;
    
    $(document).ready(function() {
        initConfigurator();
    });
    
    function initConfigurator() {
        // Open configurator modal
        $('#tpc-open-configurator').on('click', function(e) {
            e.preventDefault();
            openConfiguratorModal();
        });
        
        // Close modal handlers
        $('#tpc-close-modal, #tpc-configurator-modal').on('click', function(e) {
            if (e.target === this) {
                closeConfiguratorModal();
            }
        });
        
        // Prevent modal content clicks from closing modal
        $('.bg-white').on('click', function(e) {
            e.stopPropagation();
        });
        
        // Escape key handler
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('#tpc-configurator-modal').is(':visible')) {
                closeConfiguratorModal();
            }
        });
        
        // Initialize event handlers for controls
        initControlHandlers();
    }
    
    function openConfiguratorModal() {
        const modal = $('#tpc-configurator-modal');
        const productData = JSON.parse($('#tpc-product-data').text());
        
        // Validate product data
        if (!productData.modelUrl) {
            alert('No 3D model configured for this product. Please contact the administrator.');
            return;
        }
        
        // Show modal
        modal.removeClass('hidden');
        $('body').addClass('overflow-hidden');
        
        // Initialize 3D configurator
        if (!configuratorInstance) {
            setTimeout(() => {
                configuratorInstance = window.initProductConfigurator('tpc-3d-viewer', productData);
                
                // Store reference for material changes
                window.tpcConfiguratorRef = {
                    handleMaterialChange: handleMaterialChange
                };
            }, 100);
        }
    }
    
    function closeConfiguratorModal() {
        const modal = $('#tpc-configurator-modal');
        modal.addClass('hidden');
        $('body').removeClass('overflow-hidden');
    }
    
    function initControlHandlers() {
        // Logo upload handler
        $(document).on('change', '#tpc-logo-upload', handleLogoUpload);
        
        // Logo position change
        $(document).on('change', '#tpc-logo-position-select', function() {
            const positionId = $(this).val();
            if (window.tpcConfiguratorRef) {
                window.tpcConfiguratorRef.handleLogoPositionChange(positionId);
            }
        });
        
        // Logo controls
        $(document).on('input', '#tpc-logo-scale', function() {
            const scale = parseFloat($(this).val());
            updateLogoScale(scale);
        });
        
        $(document).on('input', '#tpc-logo-rotation', function() {
            const rotation = parseFloat($(this).val());
            updateLogoRotation(rotation);
        });
        
        // Lighting controls
        $(document).on('input', '#tpc-lighting-intensity', function() {
            const intensity = parseFloat($(this).val());
            updateLightingIntensity(intensity);
        });
        
        $(document).on('change', '#tpc-environment', function() {
            const environment = $(this).val();
            updateEnvironment(environment);
        });
        
        // View controls
        $(document).on('click', '#tpc-reset-view', resetView);
        $(document).on('click', '#tpc-fullscreen', toggleFullscreen);
        
        // Action buttons
        $(document).on('click', '#tpc-export-render', exportRender);
        $(document).on('click', '#tpc-save-config', saveConfiguration);
        $(document).on('click', '#tpc-add-to-cart', addToCart);
        $(document).on('click', '#tpc-share-config', shareConfiguration);
    }
    
    async function handleLogoUpload(event) {
        const file = event.target.files[0];
        if (!file) return;
        
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
            alert('Please select a valid image file (JPG, PNG, or GIF).');
            return;
        }
        
        // Validate file size (5MB max)
        const maxSize = 5 * 1024 * 1024;
        if (file.size > maxSize) {
            alert('File size must be less than 5MB.');
            return;
        }
        
        // Show loading state
        const uploadArea = $('#tpc-logo-upload').closest('.flex');
        const originalContent = uploadArea.html();
        uploadArea.html(`
            <div class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg bg-gray-50">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mb-2"></div>
                <p class="text-sm text-gray-500">Uploading...</p>
            </div>
        `);
        
        try {
            const formData = new FormData();
            formData.append('logo', file);
            formData.append('action', 'upload_logo');
            formData.append('nonce', tpc_ajax.nonce);
            
            const response = await fetch(tpc_ajax.ajax_url, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Show success state
                uploadArea.html(`
                    <div class="flex flex-col items-center justify-center w-full h-32 border-2 border-green-500 border-dashed rounded-lg bg-green-50">
                        <svg class="w-8 h-8 text-green-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <p class="text-sm text-green-600">Logo uploaded successfully!</p>
                    </div>
                `);
                
                // Show logo position and control options
                $('#tpc-logo-positions').removeClass('hidden');
                $('#tpc-logo-controls').removeClass('hidden');
                
                // Auto-select first position if available
                const firstOption = $('#tpc-logo-position-select option:first');
                if (firstOption.length) {
                    firstOption.prop('selected', true);
                    firstOption.trigger('change');
                }
                
            } else {
                throw new Error(result.data || 'Upload failed');
            }
        } catch (error) {
            console.error('Logo upload error:', error);
            alert('Failed to upload logo: ' + error.message);
            
            // Restore original content
            uploadArea.html(originalContent);
        }
    }
    
    function handleMaterialChange(materialType, option) {
        // Update the configuration
        if (!currentConfiguration) {
            currentConfiguration = {};
        }
        
        if (!currentConfiguration.selectedMaterials) {
            currentConfiguration.selectedMaterials = {};
        }
        
        currentConfiguration.selectedMaterials[materialType] = option;
        
        // Update the 3D model
        if (window.tpcConfiguratorRef && window.tpcConfiguratorRef.updateMaterial) {
            window.tpcConfiguratorRef.updateMaterial(materialType, option);
        }
        
        // Visual feedback
        const container = $(`.tpc-material-${materialType}`);
        container.find('button').removeClass('ring-2 ring-blue-500');
        container.find(`[data-option-id="${option.id}"]`).addClass('ring-2 ring-blue-500');
    }
    
    function updateLogoScale(scale) {
        if (window.tpcConfiguratorRef && window.tpcConfiguratorRef.updateLogoScale) {
            window.tpcConfiguratorRef.updateLogoScale(scale);
        }
        
        // Update configuration
        if (!currentConfiguration) currentConfiguration = {};
        currentConfiguration.logoScale = scale;
    }
    
    function updateLogoRotation(rotation) {
        if (window.tpcConfiguratorRef && window.tpcConfiguratorRef.updateLogoRotation) {
            window.tpcConfiguratorRef.updateLogoRotation(rotation);
        }
        
        // Update configuration
        if (!currentConfiguration) currentConfiguration = {};
        currentConfiguration.logoRotation = rotation;
    }
    
    function updateLightingIntensity(intensity) {
        if (window.tpcConfiguratorRef && window.tpcConfiguratorRef.updateLightingIntensity) {
            window.tpcConfiguratorRef.updateLightingIntensity(intensity);
        }
        
        // Update configuration
        if (!currentConfiguration) currentConfiguration = {};
        currentConfiguration.lightingIntensity = intensity;
    }
    
    function updateEnvironment(environment) {
        if (window.tpcConfiguratorRef && window.tpcConfiguratorRef.updateEnvironment) {
            window.tpcConfiguratorRef.updateEnvironment(environment);
        }
        
        // Update configuration
        if (!currentConfiguration) currentConfiguration = {};
        currentConfiguration.environment = environment;
    }
    
    function resetView() {
        if (window.tpcConfiguratorRef && window.tpcConfiguratorRef.resetView) {
            window.tpcConfiguratorRef.resetView();
        }
    }
    
    function toggleFullscreen() {
        const viewer = document.getElementById('tpc-3d-viewer');
        
        if (!document.fullscreenElement) {
            viewer.requestFullscreen().catch(err => {
                console.error('Error attempting to enable fullscreen:', err);
            });
        } else {
            document.exitFullscreen();
        }
    }
    
    function exportRender() {
        if (window.tpcConfiguratorRef && window.tpcConfiguratorRef.exportRender) {
            const button = $('#tpc-export-render');
            const originalText = button.text();
            
            button.text('Exporting...').prop('disabled', true);
            
            try {
                window.tpcConfiguratorRef.exportRender();
                
                // Show success message
                button.text('Exported!').removeClass('bg-green-600 hover:bg-green-700')
                      .addClass('bg-green-500');
                
                setTimeout(() => {
                    button.text(originalText).prop('disabled', false)
                          .removeClass('bg-green-500')
                          .addClass('bg-green-600 hover:bg-green-700');
                }, 2000);
                
            } catch (error) {
                console.error('Export error:', error);
                alert('Failed to export render');
                
                button.text(originalText).prop('disabled', false);
            }
        } else {
            alert('3D viewer not ready. Please wait for the model to load.');
        }
    }
    
    async function saveConfiguration() {
        if (!currentConfiguration) {
            alert('No configuration to save.');
            return;
        }
        
        const button = $('#tpc-save-config');
        const originalText = button.text();
        
        button.text('Saving...').prop('disabled', true);
        
        try {
            const response = await fetch(tpc_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'save_configuration',
                    nonce: tpc_ajax.nonce,
                    product_id: tpc_ajax.product_id,
                    configuration: JSON.stringify(currentConfiguration)
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                button.text('Saved!').removeClass('bg-blue-600 hover:bg-blue-700')
                      .addClass('bg-green-500');
                
                setTimeout(() => {
                    button.text(originalText).prop('disabled', false)
                          .removeClass('bg-green-500')
                          .addClass('bg-blue-600 hover:bg-blue-700');
                }, 2000);
            } else {
                throw new Error(result.data || 'Save failed');
            }
        } catch (error) {
            console.error('Save error:', error);
            alert('Failed to save configuration: ' + error.message);
            
            button.text(originalText).prop('disabled', false);
        }
    }
    
    function addToCart() {
        if (!currentConfiguration) {
            // Add without customization
            $('.single_add_to_cart_button').click();
            return;
        }
        
        // Save configuration first, then add to cart with custom data
        saveConfiguration().then(() => {
            // You can extend this to pass custom configuration data to WooCommerce
            const form = $('form.cart');
            
            // Add configuration ID as hidden field
            if (currentConfiguration.configId) {
                $('<input>').attr({
                    type: 'hidden',
                    name: 'tpc_config_id',
                    value: currentConfiguration.configId
                }).appendTo(form);
            }
            
            form.submit();
        }).catch(error => {
            console.error('Error adding to cart:', error);
            alert('Failed to add customized product to cart. Adding standard product instead.');
            $('.single_add_to_cart_button').click();
        });
    }
    
    function shareConfiguration() {
        if (!currentConfiguration) {
            alert('No configuration to share.');
            return;
        }
        
        // Create shareable URL (you can implement this based on your needs)
        const shareData = {
            title: 'Check out my custom product design!',
            text: 'I created this awesome custom product design.',
            url: window.location.href + '?config=' + btoa(JSON.stringify(currentConfiguration))
        };
        
        if (navigator.share) {
            navigator.share(shareData).catch(err => {
                console.error('Error sharing:', err);
                fallbackShare(shareData.url);
            });
        } else {
            fallbackShare(shareData.url);
        }
    }
    
    function fallbackShare(url) {
        // Copy to clipboard
        navigator.clipboard.writeText(url).then(() => {
            alert('Configuration link copied to clipboard!');
        }).catch(err => {
            console.error('Failed to copy to clipboard:', err);
            
            // Show share modal with URL
            const modal = $(`
                <div class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
                    <div class="bg-white rounded-lg p-6 max-w-md w-full">
                        <h3 class="text-lg font-bold mb-4">Share Configuration</h3>
                        <p class="text-sm text-gray-600 mb-3">Copy this link to share your configuration:</p>
                        <input type="text" value="${url}" class="w-full p-2 border rounded text-sm" readonly>
                        <div class="mt-4 flex space-x-2">
                            <button class="flex-1 bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700" onclick="this.previousElementSibling.select(); document.execCommand('copy'); alert('Copied!');">
                                Copy Link
                            </button>
                            <button class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded hover:bg-gray-400" onclick="this.closest('.fixed').remove();">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            `);
            
            $('body').append(modal);
        });
    }
    
    // Check for shared configuration in URL
    function checkForSharedConfig() {
        const urlParams = new URLSearchParams(window.location.search);
        const configParam = urlParams.get('config');
        
        if (configParam) {
            try {
                const config = JSON.parse(atob(configParam));
                currentConfiguration = config;
                
                // Show notification
                const notification = $(`
                    <div class="fixed top-4 right-4 bg-blue-600 text-white p-4 rounded-lg shadow-lg z-50">
                        <p class="text-sm">A shared configuration was loaded!</p>
                        <button class="mt-2 bg-blue-700 px-3 py-1 rounded text-xs hover:bg-blue-800" onclick="$('#tpc-open-configurator').click(); $(this).closest('.fixed').remove();">
                            View Configuration
                        </button>
                        <button class="mt-2 ml-2 bg-gray-600 px-3 py-1 rounded text-xs hover:bg-gray-700" onclick="$(this).closest('.fixed').remove();">
                            Dismiss
                        </button>
                    </div>
                `);
                
                $('body').append(notification);
                
                // Auto-remove after 10 seconds
                setTimeout(() => {
                    notification.fadeOut();
                }, 10000);
                
            } catch (error) {
                console.error('Invalid shared configuration:', error);
            }
        }
    }
    
    // Initialize shared config check
    $(document).ready(function() {
        checkForSharedConfig();
    });
    
})(jQuery);