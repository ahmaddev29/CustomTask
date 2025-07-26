<!-- 3D Configurator Modal -->
<div id="tpc-configurator-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-7xl h-5/6 flex flex-col">
            <!-- Modal Header -->
            <div class="flex items-center justify-between p-6 border-b">
                <h2 class="text-2xl font-bold text-gray-900">
                    <?php _e('Customize Your Product', '3d-product-configurator'); ?>
                </h2>
                <button id="tpc-close-modal" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Modal Content -->
            <div class="flex-1 flex overflow-hidden">
                <!-- 3D Viewer -->
                <div class="flex-1 relative bg-gray-100">
                    <div id="tpc-3d-viewer" class="w-full h-full"></div>
                    
                    <!-- Loading Overlay -->
                    <div id="tpc-loading" class="absolute inset-0 bg-white bg-opacity-90 flex items-center justify-center">
                        <div class="text-center">
                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
                            <p class="text-gray-600"><?php _e('Loading 3D model...', '3d-product-configurator'); ?></p>
                        </div>
                    </div>
                    
                    <!-- View Controls -->
                    <div class="absolute top-4 right-4 flex flex-col space-y-2">
                        <button id="tpc-reset-view" class="bg-white shadow-lg rounded-lg p-3 hover:bg-gray-50 transition-colors" title="<?php _e('Reset View', '3d-product-configurator'); ?>">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                        </button>
                        
                        <button id="tpc-fullscreen" class="bg-white shadow-lg rounded-lg p-3 hover:bg-gray-50 transition-colors" title="<?php _e('Fullscreen', '3d-product-configurator'); ?>">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Export Button -->
                    <div class="absolute bottom-4 right-4">
                        <button id="tpc-export-render" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg shadow-lg transition-colors">
                            <?php _e('Export Render', '3d-product-configurator'); ?>
                        </button>
                    </div>
                </div>
                
                <!-- Control Panel -->
                <div class="w-80 bg-gray-50 border-l overflow-y-auto">
                    <!-- Logo Upload Section -->
                    <div class="p-6 border-b">
                        <h3 class="text-lg font-semibold mb-4"><?php _e('Logo & Graphics', '3d-product-configurator'); ?></h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <?php _e('Upload Logo', '3d-product-configurator'); ?>
                                </label>
                                <div class="flex items-center justify-center w-full">
                                    <label for="tpc-logo-upload" class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100">
                                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                            <svg class="w-8 h-8 mb-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                            </svg>
                                            <p class="mb-2 text-sm text-gray-500">
                                                <span class="font-semibold"><?php _e('Click to upload', '3d-product-configurator'); ?></span>
                                            </p>
                                            <p class="text-xs text-gray-500"><?php _e('PNG, JPG or GIF', '3d-product-configurator'); ?></p>
                                        </div>
                                        <input id="tpc-logo-upload" type="file" class="hidden" accept="image/*">
                                    </label>
                                </div>
                            </div>
                            
                            <div id="tpc-logo-positions" class="hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <?php _e('Logo Position', '3d-product-configurator'); ?>
                                </label>
                                <select id="tpc-logo-position-select" class="w-full p-2 border border-gray-300 rounded-md">
                                    <!-- Options will be populated by JavaScript -->
                                </select>
                            </div>
                            
                            <div id="tpc-logo-controls" class="hidden space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        <?php _e('Logo Size', '3d-product-configurator'); ?>
                                    </label>
                                    <input type="range" id="tpc-logo-scale" min="0.1" max="2" step="0.1" value="1" class="w-full">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        <?php _e('Logo Rotation', '3d-product-configurator'); ?>
                                    </label>
                                    <input type="range" id="tpc-logo-rotation" min="0" max="360" step="1" value="0" class="w-full">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Materials Section -->
                    <div class="p-6 border-b">
                        <h3 class="text-lg font-semibold mb-4"><?php _e('Materials & Colors', '3d-product-configurator'); ?></h3>
                        <div id="tpc-materials-container">
                            <!-- Material options will be populated by JavaScript -->
                        </div>
                    </div>
                    
                    <!-- Lighting Section -->
                    <div class="p-6 border-b">
                        <h3 class="text-lg font-semibold mb-4"><?php _e('Lighting & Environment', '3d-product-configurator'); ?></h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <?php _e('Lighting Intensity', '3d-product-configurator'); ?>
                                </label>
                                <input type="range" id="tpc-lighting-intensity" min="0.1" max="3" step="0.1" value="1" class="w-full">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <?php _e('Environment', '3d-product-configurator'); ?>
                                </label>
                                <select id="tpc-environment" class="w-full p-2 border border-gray-300 rounded-md">
                                    <option value="studio"><?php _e('Studio', '3d-product-configurator'); ?></option>
                                    <option value="sunset"><?php _e('Sunset', '3d-product-configurator'); ?></option>
                                    <option value="forest"><?php _e('Forest', '3d-product-configurator'); ?></option>
                                    <option value="city"><?php _e('City', '3d-product-configurator'); ?></option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Actions Section -->
                    <div class="p-6">
                        <div class="space-y-3">
                            <button id="tpc-save-config" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition-colors">
                                <?php _e('Save Configuration', '3d-product-configurator'); ?>
                            </button>
                            
                            <button id="tpc-add-to-cart" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-lg transition-colors">
                                <?php _e('Add to Cart', '3d-product-configurator'); ?>
                            </button>
                            
                            <button id="tpc-share-config" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-4 rounded-lg transition-colors">
                                <?php _e('Share Configuration', '3d-product-configurator'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hidden data for JavaScript -->
<script type="application/json" id="tpc-product-data">
{
    "productId": <?php echo $product->get_id(); ?>,
    "modelUrl": <?php echo json_encode($model_url); ?>,
    "materials": <?php echo $materials ? $materials : '{}'; ?>,
    "logoPositions": <?php echo $logo_positions ? $logo_positions : '[]'; ?>,
    "nonce": "<?php echo wp_create_nonce('tpc_nonce'); ?>"
}
</script>