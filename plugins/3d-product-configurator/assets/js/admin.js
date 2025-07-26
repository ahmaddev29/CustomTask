/**
 * 3D Product Configurator Admin JavaScript
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        initAdminFeatures();
    });
    
    function initAdminFeatures() {
        // Initialize JSON validation
        initJSONValidation();
        
        // Initialize model upload
        initModelUpload();
        
        // Initialize 3D preview
        init3DPreview();
        
        // Initialize tabs if any
        initTabs();
        
        // Initialize tooltips
        initTooltips();
    }
    
    /**
     * JSON Validation for material and position configurations
     */
    function initJSONValidation() {
        const jsonFields = ['#tpc_materials', '#tpc_logo_positions'];
        
        jsonFields.forEach(function(fieldSelector) {
            const $field = $(fieldSelector);
            if ($field.length) {
                // Add validation container
                $field.closest('td').addClass('tpc-json-editor');
                
                // Validate on input
                $field.on('input', function() {
                    validateJSON($(this));
                });
                
                // Initial validation
                validateJSON($field);
            }
        });
    }
    
    function validateJSON($field) {
        const value = $field.val().trim();
        const $container = $field.closest('.tpc-json-editor');
        
        // Remove existing indicators
        $container.find('.json-error, .json-valid').remove();
        $field.removeClass('invalid valid');
        
        if (!value) {
            return; // Empty is okay
        }
        
        try {
            JSON.parse(value);
            $field.addClass('valid');
            $container.append('<div class="json-valid">Valid JSON</div>');
        } catch (error) {
            $field.addClass('invalid');
            $container.append('<div class="json-error">Invalid JSON: ' + error.message + '</div>');
        }
    }
    
    /**
     * Model Upload Functionality
     */
    function initModelUpload() {
        $('#tpc_upload_model').on('click', function(e) {
            e.preventDefault();
            openMediaUploader();
        });
        
        // Handle drag and drop if we add a drop area later
        initDragAndDrop();
    }
    
    function openMediaUploader() {
        // Create custom media uploader for 3D models
        const mediaUploader = wp.media({
            title: 'Upload 3D Model',
            button: {
                text: 'Use this model'
            },
            multiple: false,
            library: {
                type: ['model/gltf-binary', 'model/gltf+json', 'application/octet-stream']
            }
        });
        
        mediaUploader.on('select', function() {
            const attachment = mediaUploader.state().get('selection').first().toJSON();
            
            // Validate file extension
            const validExtensions = ['.glb', '.gltf'];
            const fileExtension = attachment.url.toLowerCase().split('.').pop();
            
            if (!validExtensions.includes('.' + fileExtension)) {
                showNotice('Please select a valid 3D model file (.glb or .gltf)', 'error');
                return;
            }
            
            // Set the URL
            $('#tpc_model_url').val(attachment.url);
            
            // Trigger preview update
            updateModelPreview(attachment.url);
            
            // Show success message
            showNotice('3D model uploaded successfully!', 'success');
        });
        
        mediaUploader.open();
    }
    
    function initDragAndDrop() {
        // This could be enhanced to support drag-and-drop upload
        const $uploadArea = $('.tpc-model-upload-area');
        
        if ($uploadArea.length) {
            $uploadArea.on('dragover', function(e) {
                e.preventDefault();
                $(this).addClass('dragover');
            });
            
            $uploadArea.on('dragleave', function(e) {
                e.preventDefault();
                $(this).removeClass('dragover');
            });
            
            $uploadArea.on('drop', function(e) {
                e.preventDefault();
                $(this).removeClass('dragover');
                
                const files = e.originalEvent.dataTransfer.files;
                if (files.length > 0) {
                    handleFileUpload(files[0]);
                }
            });
        }
    }
    
    function handleFileUpload(file) {
        // Validate file type
        const validTypes = ['model/gltf-binary', 'model/gltf+json'];
        const validExtensions = ['.glb', '.gltf'];
        const fileName = file.name.toLowerCase();
        const hasValidExtension = validExtensions.some(ext => fileName.endsWith(ext));
        
        if (!hasValidExtension && !validTypes.includes(file.type)) {
            showNotice('Please upload a valid 3D model file (.glb or .gltf)', 'error');
            return;
        }
        
        // Create FormData and upload
        const formData = new FormData();
        formData.append('file', file);
        formData.append('action', 'upload_3d_model');
        formData.append('nonce', wp.nonce || '');
        
        // Show progress
        showUploadProgress(0);
        
        // Upload using jQuery AJAX with progress
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(evt) {
                    if (evt.lengthComputable) {
                        const percentComplete = evt.loaded / evt.total * 100;
                        showUploadProgress(percentComplete);
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                hideUploadProgress();
                
                if (response.success) {
                    $('#tpc_model_url').val(response.data.url);
                    updateModelPreview(response.data.url);
                    showNotice('3D model uploaded successfully!', 'success');
                } else {
                    showNotice('Upload failed: ' + response.data, 'error');
                }
            },
            error: function() {
                hideUploadProgress();
                showNotice('Upload failed. Please try again.', 'error');
            }
        });
    }
    
    function showUploadProgress(percent) {
        let $progress = $('.tpc-progress-bar');
        
        if (!$progress.length) {
            $progress = $('<div class="tpc-progress-bar"><div class="tpc-progress-bar-fill"></div></div>');
            $('.tpc-model-upload-area').after($progress);
        }
        
        $progress.find('.tpc-progress-bar-fill').css('width', percent + '%');
    }
    
    function hideUploadProgress() {
        $('.tpc-progress-bar').fadeOut(function() {
            $(this).remove();
        });
    }
    
    /**
     * 3D Model Preview
     */
    function init3DPreview() {
        const $enableCheckbox = $('#tpc_enable_3d');
        const $previewContainer = $('.tpc-preview-container');
        
        // Show/hide preview based on checkbox
        $enableCheckbox.on('change', function() {
            if ($(this).is(':checked')) {
                $previewContainer.show();
                initializePreview();
            } else {
                $previewContainer.hide();
            }
        });
        
        // Update preview when model URL changes
        $('#tpc_model_url').on('change', function() {
            const modelUrl = $(this).val();
            if (modelUrl && $enableCheckbox.is(':checked')) {
                updateModelPreview(modelUrl);
            }
        });
        
        // Initialize preview if checkbox is already checked
        if ($enableCheckbox.is(':checked')) {
            $previewContainer.show();
            initializePreview();
        }
    }
    
    function initializePreview() {
        const $previewDiv = $('#tpc-admin-preview');
        
        if (!$previewDiv.length) return;
        
        // Add loading indicator
        $previewDiv.html('<div class="tpc-preview-loading"><div class="spinner"></div><p>Initializing 3D preview...</p></div>');
        
        // Check if model URL exists
        const modelUrl = $('#tpc_model_url').val();
        if (modelUrl) {
            updateModelPreview(modelUrl);
        }
    }
    
    function updateModelPreview(modelUrl) {
        const $previewDiv = $('#tpc-admin-preview');
        
        if (!$previewDiv.length) return;
        
        // Show loading
        $previewDiv.html('<div class="tpc-preview-loading"><div class="spinner"></div><p>Loading 3D model...</p></div>');
        
        // Initialize Three.js scene (simplified version for admin)
        try {
            initThreeJsPreview($previewDiv[0], modelUrl);
        } catch (error) {
            console.error('Failed to initialize 3D preview:', error);
            $previewDiv.html('<div class="tpc-admin-error"><p>Failed to load 3D preview. Please check the model URL.</p></div>');
        }
    }
    
    function initThreeJsPreview(container, modelUrl) {
        // This is a simplified version - in a real implementation,
        // you'd want to use the same Three.js setup as the frontend
        
        // Clear container
        container.innerHTML = '';
        
        // Check if Three.js is available
        if (typeof THREE === 'undefined') {
            container.innerHTML = '<div class="tpc-admin-error"><p>Three.js not loaded. 3D preview unavailable.</p></div>';
            return;
        }
        
        // Create scene, camera, renderer
        const scene = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(75, container.clientWidth / container.clientHeight, 0.1, 1000);
        const renderer = new THREE.WebGLRenderer({ antialias: true });
        
        renderer.setSize(container.clientWidth, container.clientHeight);
        renderer.setClearColor(0xf0f0f0);
        container.appendChild(renderer.domElement);
        
        // Add lighting
        const ambientLight = new THREE.AmbientLight(0x404040, 0.6);
        scene.add(ambientLight);
        
        const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
        directionalLight.position.set(1, 1, 1);
        scene.add(directionalLight);
        
        // Load model
        const loader = new THREE.GLTFLoader();
        loader.load(
            modelUrl,
            function(gltf) {
                scene.add(gltf.scene);
                
                // Auto-fit camera
                const box = new THREE.Box3().setFromObject(gltf.scene);
                const center = box.getCenter(new THREE.Vector3());
                const size = box.getSize(new THREE.Vector3());
                
                const maxDim = Math.max(size.x, size.y, size.z);
                camera.position.set(center.x, center.y, center.z + maxDim * 2);
                camera.lookAt(center);
                
                // Add controls if available
                if (typeof THREE.OrbitControls !== 'undefined') {
                    const controls = new THREE.OrbitControls(camera, renderer.domElement);
                    controls.target.copy(center);
                    controls.update();
                }
                
                // Render loop
                function animate() {
                    requestAnimationFrame(animate);
                    renderer.render(scene, camera);
                }
                animate();
                
                // Show success message
                showNotice('3D model loaded successfully in preview!', 'success');
            },
            function(progress) {
                // Loading progress - could update UI here
                console.log('Loading progress:', progress);
            },
            function(error) {
                console.error('Error loading model:', error);
                container.innerHTML = '<div class="tpc-admin-error"><p>Failed to load 3D model. Please check the file format and URL.</p></div>';
            }
        );
        
        // Handle resize
        window.addEventListener('resize', function() {
            camera.aspect = container.clientWidth / container.clientHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(container.clientWidth, container.clientHeight);
        });
    }
    
    /**
     * Tabs functionality
     */
    function initTabs() {
        $('.tpc-admin-tabs a').on('click', function(e) {
            e.preventDefault();
            
            const $tab = $(this);
            const target = $tab.attr('href');
            
            // Update active tab
            $tab.closest('.tpc-admin-tabs').find('li').removeClass('active');
            $tab.closest('li').addClass('active');
            
            // Show corresponding content
            $('.tpc-tab-content').removeClass('active');
            $(target).addClass('active');
        });
    }
    
    /**
     * Tooltips
     */
    function initTooltips() {
        // Tooltips are handled via CSS, but we could enhance them here
        $('.tpc-tooltip').each(function() {
            const $tooltip = $(this);
            const text = $tooltip.attr('data-tooltip');
            
            if (!text) {
                // If no data-tooltip, try to get from title
                const title = $tooltip.attr('title');
                if (title) {
                    $tooltip.attr('data-tooltip', title).removeAttr('title');
                }
            }
        });
    }
    
    /**
     * Utility Functions
     */
    function showNotice(message, type = 'info') {
        const $notice = $('<div class="tpc-admin-notice notice-' + type + '"><p>' + message + '</p></div>');
        
        // Find a good place to insert the notice
        let $target = $('.tpc-admin-settings').first();
        if (!$target.length) {
            $target = $('h1').first();
        }
        
        $notice.insertAfter($target).hide().fadeIn();
        
        // Auto-remove after 5 seconds
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    /**
     * Form validation before save
     */
    $(document).on('submit', '#post', function() {
        const $enabledCheckbox = $('#tpc_enable_3d');
        
        if ($enabledCheckbox.is(':checked')) {
            const modelUrl = $('#tpc_model_url').val();
            const materials = $('#tpc_materials').val();
            
            if (!modelUrl) {
                showNotice('Please provide a 3D model URL when enabling the configurator.', 'error');
                return false;
            }
            
            // Validate JSON fields
            let hasJsonError = false;
            $('.tpc-json-editor .json-error').each(function() {
                hasJsonError = true;
            });
            
            if (hasJsonError) {
                showNotice('Please fix JSON errors before saving.', 'error');
                return false;
            }
        }
        
        return true;
    });
    
    /**
     * Auto-save draft functionality
     */
    function setupAutoSave() {
        let autoSaveTimer;
        
        $('.tpc-admin-settings input, .tpc-admin-settings textarea').on('input', function() {
            clearTimeout(autoSaveTimer);
            autoSaveTimer = setTimeout(function() {
                // Trigger WordPress auto-save
                if (typeof wp !== 'undefined' && wp.autosave) {
                    wp.autosave.server.triggerSave();
                }
            }, 3000);
        });
    }
    
    // Initialize auto-save
    setupAutoSave();
    
})(jQuery);