# 3D Product Configurator for WordPress/WooCommerce

A powerful WordPress plugin that allows customers to personalize and brand products in real-time using a 3D configurator. Built with React, Three.js, and seamlessly integrated with WooCommerce.

## Features

- **Real-time 3D Product Visualization** - Interactive 3D models with zoom, pan, and rotate
- **Logo Upload & Placement** - Customers can upload logos and position them on products
- **Material & Color Customization** - Dynamic material switching with textures and colors
- **Advanced Lighting Controls** - Adjustable lighting intensity and environments
- **High-Quality Render Export** - Export customized products as high-resolution images
- **WooCommerce Integration** - Seamless cart integration with custom configurations
- **Mobile Responsive** - Works perfectly on all device sizes
- **Share Configurations** - Generate shareable links for custom designs

## Tech Stack

- **Frontend**: React 18, Tailwind CSS
- **3D Engine**: Three.js with React-Three-Fiber and Drei
- **Backend**: WordPress/PHP with WooCommerce
- **Image Processing**: Canvas API

## Installation

1. **Upload the Plugin**
   - Download the plugin files
   - Upload to `/wp-content/plugins/3d-product-configurator/`
   - Or install via WordPress Admin → Plugins → Add New → Upload

2. **Activate the Plugin**
   - Go to WordPress Admin → Plugins
   - Find "3D Product Configurator" and click "Activate"

3. **Requirements Check**
   - Ensure WooCommerce is installed and active
   - The plugin will show a notice if WooCommerce is missing

## Configuration

### Setting Up a 3D Product

1. **Edit a WooCommerce Product**
   - Go to Products → Edit an existing product or create new
   - Scroll down to the "3D Configurator Settings" meta box

2. **Enable 3D Configurator**
   - Check "Enable 3D Configurator" checkbox
   - This will show additional configuration options

3. **Upload 3D Model**
   - Click "Upload Model" button or enter a direct URL
   - Supported formats: `.glb` (recommended) or `.gltf`
   - The model should be optimized for web (< 10MB recommended)

4. **Configure Materials**
   - Add materials configuration in JSON format
   - Example:
   ```json
   {
     "fabric": {
       "name": "Fabric",
       "options": [
         {
           "id": "cotton-white", 
           "name": "Cotton White", 
           "color": "#ffffff",
           "texture": "/path/to/cotton-texture.jpg"
         },
         {
           "id": "denim-blue", 
           "name": "Denim Blue", 
           "color": "#4169e1",
           "texture": "/path/to/denim-texture.jpg"
         }
       ]
     },
     "metal": {
       "name": "Metal Parts",
       "options": [
         {
           "id": "silver", 
           "name": "Silver", 
           "color": "#c0c0c0", 
           "metalness": 0.8, 
           "roughness": 0.2
         },
         {
           "id": "gold", 
           "name": "Gold", 
           "color": "#ffd700", 
           "metalness": 0.9, 
           "roughness": 0.1
         }
       ]
     }
   }
   ```

5. **Configure Logo Positions**
   - Define where logos can be placed on the model
   - Example:
   ```json
   [
     {
       "id": "front", 
       "name": "Front", 
       "position": [0, 0, 0.51], 
       "rotation": [0, 0, 0], 
       "scale": [0.3, 0.3, 0.3]
     },
     {
       "id": "back", 
       "name": "Back", 
       "position": [0, 0, -0.51], 
       "rotation": [0, 3.14159, 0], 
       "scale": [0.3, 0.3, 0.3]
     },
     {
       "id": "sleeve", 
       "name": "Left Sleeve", 
       "position": [-0.3, 0.2, 0.4], 
       "rotation": [0, 0.5, 0], 
       "scale": [0.15, 0.15, 0.15]
     }
   ]
   ```

### 3D Model Requirements

**Format**: 
- GLB (recommended) or GLTF
- Embedded textures preferred for GLB
- External textures should be accessible via URL for GLTF

**Optimization**:
- Keep file size under 10MB for best performance
- Use compressed textures (1024x1024 or 512x512)
- Optimize polygon count (< 50k triangles recommended)
- Use LOD (Level of Detail) when possible

**Material Setup**:
- Name materials descriptively (matching your JSON config)
- Use PBR materials (Metallic/Roughness workflow)
- Avoid complex node setups that might not export properly

## Usage

### Customer Experience

1. **Product Page**
   - Customers see a "Customize in 3D" button on enabled products
   - Click opens the full-screen 3D configurator modal

2. **3D Configurator Interface**
   - **3D Viewer**: Interactive model with mouse/touch controls
   - **Materials Panel**: Choose different materials and colors
   - **Logo Upload**: Upload and position custom logos
   - **Lighting Controls**: Adjust lighting and environment
   - **Export**: Download high-quality renders

3. **Customization Process**
   - Select materials from available options
   - Upload logo (PNG, JPG, GIF supported)
   - Choose logo position and adjust size/rotation
   - Fine-tune lighting and environment
   - Save configuration or add to cart

4. **Sharing & Saving**
   - Save configurations to session
   - Generate shareable links
   - Add customized product to cart

### Admin Features

- **Product Configuration**: Easy setup via WordPress admin
- **3D Model Preview**: See models directly in admin
- **JSON Validation**: Real-time validation of configuration data
- **File Upload**: Integrated with WordPress Media Library

## Customization

### Styling

The plugin uses Tailwind CSS classes but also includes custom CSS in:
- `/assets/css/style.css` - Frontend styles
- `/assets/css/admin.css` - Admin interface styles

### Adding Custom Features

The plugin is built with a modular architecture:

1. **Main Plugin Class**: `ThreeDProductConfigurator` in main plugin file
2. **React Components**: Located in `/assets/js/configurator.js`
3. **WordPress Hooks**: Available for extending functionality

### Hooks & Filters

```php
// Filter 3D model URL before loading
add_filter('tpc_model_url', function($url, $product_id) {
    // Modify URL if needed
    return $url;
}, 10, 2);

// Action after configuration save
add_action('tpc_configuration_saved', function($config_id, $product_id, $config_data) {
    // Custom logic after save
}, 10, 3);

// Filter available materials
add_filter('tpc_materials', function($materials, $product_id) {
    // Modify materials array
    return $materials;
}, 10, 2);
```

## Troubleshooting

### Common Issues

**3D Model Not Loading**
- Check file format (GLB/GLTF only)
- Verify file URL is accessible
- Check browser console for errors
- Ensure file size is reasonable (< 10MB)

**Materials Not Applying**
- Verify material names in JSON match model material names
- Check JSON syntax validity
- Ensure texture URLs are accessible

**Performance Issues**
- Optimize 3D model file size
- Reduce texture resolution
- Check device/browser capabilities

**Upload Failures**
- Check WordPress file upload limits
- Verify file permissions in uploads directory
- Check server memory limits

### Browser Support

- **Recommended**: Chrome 80+, Firefox 75+, Safari 14+, Edge 80+
- **WebGL Required**: All modern browsers support WebGL
- **Mobile**: iOS Safari 14+, Chrome Mobile 80+

## Development

### Local Development Setup

1. **WordPress Installation**
   ```bash
   # Clone into WordPress plugins directory
   cd /path/to/wordpress/wp-content/plugins/
   git clone [repository] 3d-product-configurator
   ```

2. **Dependencies**
   - No build process required
   - All dependencies loaded via CDN
   - For development, consider local Three.js files

3. **File Structure**
   ```
   3d-product-configurator/
   ├── 3d-product-configurator.php    # Main plugin file
   ├── assets/
   │   ├── css/
   │   │   ├── style.css               # Frontend styles
   │   │   └── admin.css               # Admin styles
   │   └── js/
   │       ├── configurator.js         # React 3D component
   │       ├── main.js                 # Frontend logic
   │       └── admin.js                # Admin functionality
   ├── templates/
   │   ├── configurator-modal.php      # Main modal template
   │   └── admin-meta-box.php          # Admin interface
   ├── uploads/                        # Logo upload directory
   └── README.md
   ```

### Extending the Plugin

**Adding New Material Types**
1. Update materials JSON structure
2. Modify React component to handle new types
3. Update material application logic in Three.js

**Custom Environments**
1. Add HDR environment maps
2. Update environment selector in template
3. Modify lighting component in React

**Additional Export Formats**
1. Extend render export function
2. Add new export options to UI
3. Implement server-side processing if needed

## Support

### Documentation
- WordPress Plugin Documentation: [WordPress.org](https://wordpress.org/plugins/)
- Three.js Documentation: [threejs.org](https://threejs.org/docs/)
- React Three Fiber: [docs.pmnd.rs](https://docs.pmnd.rs/react-three-fiber)

### Community
- Submit issues and feature requests via GitHub
- Join discussions in WordPress forums
- Contribute to development

## License

This plugin is licensed under GPL v2 or later, the same license as WordPress.

## Changelog

### 1.0.0 - Initial Release
- Complete 3D configurator functionality
- WooCommerce integration
- Logo upload and positioning
- Material customization
- High-quality render export
- Mobile responsive design
- Share configuration feature

---

**Ready to revolutionize your product customization experience!** 🚀