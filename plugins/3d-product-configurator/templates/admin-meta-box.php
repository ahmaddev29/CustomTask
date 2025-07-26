<div class="tpc-admin-settings">
    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row">
                    <label for="tpc_enable_3d"><?php _e('Enable 3D Configurator', '3d-product-configurator'); ?></label>
                </th>
                <td>
                    <input type="checkbox" id="tpc_enable_3d" name="tpc_enable_3d" value="1" <?php checked($enable_3d, 1); ?> />
                    <p class="description"><?php _e('Enable the 3D configurator for this product.', '3d-product-configurator'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="tpc_model_url"><?php _e('3D Model URL', '3d-product-configurator'); ?></label>
                </th>
                <td>
                    <input type="url" id="tpc_model_url" name="tpc_model_url" value="<?php echo esc_attr($model_url); ?>" class="regular-text" />
                    <button type="button" id="tpc_upload_model" class="button"><?php _e('Upload Model', '3d-product-configurator'); ?></button>
                    <p class="description"><?php _e('URL to the 3D model file (.glb or .gltf format).', '3d-product-configurator'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="tpc_materials"><?php _e('Available Materials', '3d-product-configurator'); ?></label>
                </th>
                <td>
                    <textarea id="tpc_materials" name="tpc_materials" rows="6" cols="50" class="large-text"><?php echo esc_textarea($materials); ?></textarea>
                    <p class="description">
                        <?php _e('JSON configuration for available materials. Example:', '3d-product-configurator'); ?><br>
                        <code>
{
  "fabric": {
    "name": "Fabric",
    "options": [
      {"id": "cotton", "name": "Cotton", "color": "#ffffff", "texture": "/path/to/cotton.jpg"},
      {"id": "denim", "name": "Denim", "color": "#4169e1", "texture": "/path/to/denim.jpg"}
    ]
  },
  "metal": {
    "name": "Metal Parts",
    "options": [
      {"id": "silver", "name": "Silver", "color": "#c0c0c0", "metalness": 0.8, "roughness": 0.2},
      {"id": "gold", "name": "Gold", "color": "#ffd700", "metalness": 0.9, "roughness": 0.1}
    ]
  }
}
                        </code>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="tpc_logo_positions"><?php _e('Logo Positions', '3d-product-configurator'); ?></label>
                </th>
                <td>
                    <textarea id="tpc_logo_positions" name="tpc_logo_positions" rows="4" cols="50" class="large-text"><?php echo esc_textarea($logo_positions); ?></textarea>
                    <p class="description">
                        <?php _e('JSON configuration for logo placement positions. Example:', '3d-product-configurator'); ?><br>
                        <code>
[
  {"id": "front", "name": "Front", "position": [0, 0, 0.51], "rotation": [0, 0, 0], "scale": [0.3, 0.3, 0.3]},
  {"id": "back", "name": "Back", "position": [0, 0, -0.51], "rotation": [0, Math.PI, 0], "scale": [0.3, 0.3, 0.3]},
  {"id": "sleeve", "name": "Left Sleeve", "position": [-0.3, 0.2, 0.4], "rotation": [0, 0.5, 0], "scale": [0.15, 0.15, 0.15]}
]
                        </code>
                    </p>
                </td>
            </tr>
        </tbody>
    </table>
    
    <div class="tpc-preview-container" style="margin-top: 20px; display: none;">
        <h3><?php _e('3D Model Preview', '3d-product-configurator'); ?></h3>
        <div id="tpc-admin-preview" style="width: 100%; height: 400px; border: 1px solid #ddd; border-radius: 4px;"></div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#tpc_enable_3d').change(function() {
        if ($(this).is(':checked')) {
            $('.tpc-preview-container').show();
        } else {
            $('.tpc-preview-container').hide();
        }
    });
    
    $('#tpc_upload_model').click(function(e) {
        e.preventDefault();
        
        var mediaUploader = wp.media({
            title: '<?php _e('Upload 3D Model', '3d-product-configurator'); ?>',
            button: {
                text: '<?php _e('Use this model', '3d-product-configurator'); ?>'
            },
            multiple: false,
            library: {
                type: ['model/gltf-binary', 'model/gltf+json']
            }
        });
        
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#tpc_model_url').val(attachment.url);
        });
        
        mediaUploader.open();
    });
});
</script>