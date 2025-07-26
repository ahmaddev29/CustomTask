/**
 * 3D Product Configurator React Component
 * Built with React, Three.js, React-Three-Fiber, and Drei
 */

const { useState, useEffect, useRef, Suspense } = React;
const { Canvas, useFrame, useLoader, useThree, extend } = window.ReactThreeFiber;
const { 
    OrbitControls, 
    useGLTF, 
    Environment, 
    ContactShadows, 
    Html, 
    useProgress,
    Text3D
} = window.ReactThreeDrei;

// Three.js imports
const { 
    TextureLoader, 
    sRGBEncoding, 
    LinearEncoding,
    MeshStandardMaterial,
    PlaneGeometry,
    Mesh,
    CanvasTexture,
    RepeatWrapping
} = window.THREE;

// Loading component
function Loader() {
    const { progress } = useProgress();
    return (
        <Html center>
            <div className="text-center">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-2"></div>
                <div className="text-sm text-gray-600">{progress.toFixed(0)}% loaded</div>
            </div>
        </Html>
    );
}

// Product Model Component
function ProductModel({ 
    modelUrl, 
    materials, 
    logoTexture, 
    logoPosition, 
    logoScale, 
    logoRotation, 
    selectedMaterials,
    lightingIntensity 
}) {
    const { scene } = useGLTF(modelUrl);
    const modelRef = useRef();
    const logoRef = useRef();
    
    // Apply materials to the model
    useEffect(() => {
        if (!scene || !materials) return;
        
        scene.traverse((child) => {
            if (child.isMesh) {
                const materialName = child.material.name;
                const selectedMaterial = selectedMaterials[materialName];
                
                if (selectedMaterial) {
                    const newMaterial = new MeshStandardMaterial({
                        color: selectedMaterial.color,
                        metalness: selectedMaterial.metalness || 0,
                        roughness: selectedMaterial.roughness || 0.5
                    });
                    
                    if (selectedMaterial.texture) {
                        const textureLoader = new TextureLoader();
                        textureLoader.load(selectedMaterial.texture, (texture) => {
                            texture.wrapS = RepeatWrapping;
                            texture.wrapT = RepeatWrapping;
                            texture.encoding = sRGBEncoding;
                            newMaterial.map = texture;
                            newMaterial.needsUpdate = true;
                        });
                    }
                    
                    child.material = newMaterial;
                }
            }
        });
    }, [scene, selectedMaterials]);
    
    // Handle logo positioning
    useEffect(() => {
        if (logoRef.current && logoPosition) {
            logoRef.current.position.set(...logoPosition.position);
            logoRef.current.rotation.set(...logoPosition.rotation);
            logoRef.current.scale.set(
                logoPosition.scale[0] * logoScale,
                logoPosition.scale[1] * logoScale,
                logoPosition.scale[2] * logoScale
            );
            logoRef.current.rotateZ((logoRotation * Math.PI) / 180);
        }
    }, [logoPosition, logoScale, logoRotation]);
    
    return (
        <group>
            <primitive ref={modelRef} object={scene} />
            
            {logoTexture && logoPosition && (
                <mesh ref={logoRef}>
                    <planeGeometry args={[1, 1]} />
                    <meshStandardMaterial 
                        map={logoTexture} 
                        transparent 
                        alphaTest={0.1}
                    />
                </mesh>
            )}
        </group>
    );
}

// Scene Lighting Component
function SceneLighting({ intensity, environment }) {
    return (
        <>
            <ambientLight intensity={0.4 * intensity} />
            <directionalLight 
                position={[10, 10, 5]} 
                intensity={1.2 * intensity}
                castShadow
                shadow-mapSize-width={2048}
                shadow-mapSize-height={2048}
            />
            <pointLight position={[-10, -10, -10]} intensity={0.5 * intensity} />
            <Environment preset={environment} />
        </>
    );
}

// Camera Controls Component
function CameraControls({ resetTrigger }) {
    const { camera, gl } = useThree();
    const controlsRef = useRef();
    
    useEffect(() => {
        if (resetTrigger && controlsRef.current) {
            controlsRef.current.reset();
        }
    }, [resetTrigger]);
    
    return (
        <OrbitControls
            ref={controlsRef}
            args={[camera, gl.domElement]}
            enablePan={true}
            enableZoom={true}
            enableRotate={true}
            minDistance={3}
            maxDistance={20}
            maxPolarAngle={Math.PI / 1.75}
        />
    );
}

// Main 3D Scene Component
function Scene3D({ 
    modelUrl,
    materials,
    logoTexture,
    logoPosition,
    logoScale,
    logoRotation,
    selectedMaterials,
    lightingIntensity,
    environment,
    resetTrigger,
    onRender
}) {
    const canvasRef = useRef();
    
    // Expose render function
    useEffect(() => {
        if (onRender && canvasRef.current) {
            onRender(() => {
                const canvas = canvasRef.current;
                const dataURL = canvas.toDataURL('image/png');
                return dataURL;
            });
        }
    }, [onRender]);
    
    return (
        <Canvas
            ref={canvasRef}
            camera={{ position: [0, 0, 5], fov: 50 }}
            shadows
            gl={{ 
                preserveDrawingBuffer: true,
                antialias: true,
                alpha: true
            }}
            style={{ background: 'transparent' }}
        >
            <Suspense fallback={<Loader />}>
                <SceneLighting 
                    intensity={lightingIntensity} 
                    environment={environment} 
                />
                
                <ProductModel
                    modelUrl={modelUrl}
                    materials={materials}
                    logoTexture={logoTexture}
                    logoPosition={logoPosition}
                    logoScale={logoScale}
                    logoRotation={logoRotation}
                    selectedMaterials={selectedMaterials}
                />
                
                <ContactShadows 
                    position={[0, -1.5, 0]} 
                    opacity={0.3} 
                    scale={10} 
                    blur={2} 
                />
                
                <CameraControls resetTrigger={resetTrigger} />
            </Suspense>
        </Canvas>
    );
}

// Main Configurator Component
function ProductConfigurator({ productData }) {
    const [selectedMaterials, setSelectedMaterials] = useState({});
    const [logoTexture, setLogoTexture] = useState(null);
    const [logoPosition, setLogoPosition] = useState(null);
    const [logoScale, setLogoScale] = useState(1);
    const [logoRotation, setLogoRotation] = useState(0);
    const [lightingIntensity, setLightingIntensity] = useState(1);
    const [environment, setEnvironment] = useState('studio');
    const [resetTrigger, setResetTrigger] = useState(0);
    const [renderFunction, setRenderFunction] = useState(null);
    const [isLoading, setIsLoading] = useState(true);
    
    const materials = productData.materials ? JSON.parse(productData.materials) : {};
    const logoPositions = productData.logoPositions ? JSON.parse(productData.logoPositions) : [];
    
    // Initialize default materials
    useEffect(() => {
        const defaultMaterials = {};
        Object.keys(materials).forEach(materialType => {
            if (materials[materialType].options.length > 0) {
                defaultMaterials[materialType] = materials[materialType].options[0];
            }
        });
        setSelectedMaterials(defaultMaterials);
        
        // Set default logo position
        if (logoPositions.length > 0) {
            setLogoPosition(logoPositions[0]);
        }
        
        setTimeout(() => setIsLoading(false), 1000);
    }, []);
    
    // Handle logo upload
    const handleLogoUpload = async (event) => {
        const file = event.target.files[0];
        if (!file) return;
        
        const formData = new FormData();
        formData.append('logo', file);
        formData.append('action', 'upload_logo');
        formData.append('nonce', productData.nonce);
        
        try {
            const response = await fetch(window.tpc_ajax.ajax_url, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                const textureLoader = new TextureLoader();
                textureLoader.load(result.data.url, (texture) => {
                    texture.encoding = sRGBEncoding;
                    setLogoTexture(texture);
                    
                    // Show logo controls
                    document.getElementById('tpc-logo-positions').classList.remove('hidden');
                    document.getElementById('tpc-logo-controls').classList.remove('hidden');
                });
            } else {
                alert('Failed to upload logo: ' + result.data);
            }
        } catch (error) {
            console.error('Upload error:', error);
            alert('Failed to upload logo');
        }
    };
    
    // Handle material change
    const handleMaterialChange = (materialType, option) => {
        setSelectedMaterials(prev => ({
            ...prev,
            [materialType]: option
        }));
    };
    
    // Handle logo position change
    const handleLogoPositionChange = (positionId) => {
        const position = logoPositions.find(p => p.id === positionId);
        if (position) {
            setLogoPosition(position);
        }
    };
    
    // Export render
    const handleExportRender = () => {
        if (renderFunction) {
            const dataURL = renderFunction();
            
            // Create download link
            const link = document.createElement('a');
            link.download = `product-render-${Date.now()}.png`;
            link.href = dataURL;
            link.click();
        }
    };
    
    // Save configuration
    const handleSaveConfiguration = async () => {
        const configuration = {
            selectedMaterials,
            logoTexture: logoTexture ? logoTexture.image.src : null,
            logoPosition: logoPosition ? logoPosition.id : null,
            logoScale,
            logoRotation,
            lightingIntensity,
            environment
        };
        
        try {
            const response = await fetch(window.tpc_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'save_configuration',
                    nonce: productData.nonce,
                    product_id: productData.productId,
                    configuration: JSON.stringify(configuration)
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert('Configuration saved successfully!');
            } else {
                alert('Failed to save configuration');
            }
        } catch (error) {
            console.error('Save error:', error);
            alert('Failed to save configuration');
        }
    };
    
    return (
        <div className="w-full h-full">
            {/* Loading Overlay */}
            {isLoading && (
                <div id="tpc-loading" className="absolute inset-0 bg-white bg-opacity-90 flex items-center justify-center z-10">
                    <div className="text-center">
                        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
                        <p className="text-gray-600">Loading 3D model...</p>
                    </div>
                </div>
            )}
            
            {/* 3D Scene */}
            <Scene3D
                modelUrl={productData.modelUrl}
                materials={materials}
                logoTexture={logoTexture}
                logoPosition={logoPosition}
                logoScale={logoScale}
                logoRotation={logoRotation}
                selectedMaterials={selectedMaterials}
                lightingIntensity={lightingIntensity}
                environment={environment}
                resetTrigger={resetTrigger}
                onRender={setRenderFunction}
            />
            
            {/* Event Handlers */}
            <script dangerouslySetInnerHTML={{
                __html: `
                    // Logo upload handler
                    document.getElementById('tpc-logo-upload').addEventListener('change', ${handleLogoUpload});
                    
                    // Logo position change
                    document.getElementById('tpc-logo-position-select').addEventListener('change', (e) => {
                        ${handleLogoPositionChange}(e.target.value);
                    });
                    
                    // Logo scale change
                    document.getElementById('tpc-logo-scale').addEventListener('input', (e) => {
                        ${setLogoScale}(parseFloat(e.target.value));
                    });
                    
                    // Logo rotation change
                    document.getElementById('tpc-logo-rotation').addEventListener('input', (e) => {
                        ${setLogoRotation}(parseFloat(e.target.value));
                    });
                    
                    // Lighting intensity change
                    document.getElementById('tpc-lighting-intensity').addEventListener('input', (e) => {
                        ${setLightingIntensity}(parseFloat(e.target.value));
                    });
                    
                    // Environment change
                    document.getElementById('tpc-environment').addEventListener('change', (e) => {
                        ${setEnvironment}(e.target.value);
                    });
                    
                    // Reset view
                    document.getElementById('tpc-reset-view').addEventListener('click', () => {
                        ${setResetTrigger}(prev => prev + 1);
                    });
                    
                    // Export render
                    document.getElementById('tpc-export-render').addEventListener('click', ${handleExportRender});
                    
                    // Save configuration
                    document.getElementById('tpc-save-config').addEventListener('click', ${handleSaveConfiguration});
                `
            }} />
        </div>
    );
}

// Populate logo positions dropdown
function populateLogoPositions(logoPositions) {
    const select = document.getElementById('tpc-logo-position-select');
    select.innerHTML = '';
    
    logoPositions.forEach(position => {
        const option = document.createElement('option');
        option.value = position.id;
        option.textContent = position.name;
        select.appendChild(option);
    });
}

// Populate materials controls
function populateMaterialsControls(materials) {
    const container = document.getElementById('tpc-materials-container');
    container.innerHTML = '';
    
    Object.keys(materials).forEach(materialType => {
        const materialSection = document.createElement('div');
        materialSection.className = 'mb-4';
        
        const label = document.createElement('label');
        label.className = 'block text-sm font-medium text-gray-700 mb-2';
        label.textContent = materials[materialType].name;
        
        const optionsContainer = document.createElement('div');
        optionsContainer.className = 'grid grid-cols-2 gap-2';
        
        materials[materialType].options.forEach(option => {
            const optionButton = document.createElement('button');
            optionButton.className = 'p-3 border rounded-lg text-sm hover:bg-gray-50 transition-colors';
            optionButton.innerHTML = `
                <div class="w-full h-8 rounded mb-1" style="background-color: ${option.color}"></div>
                <div>${option.name}</div>
            `;
            
            optionButton.addEventListener('click', () => {
                // Handle material selection
                window.tpcConfiguratorRef?.handleMaterialChange(materialType, option);
                
                // Update UI
                optionsContainer.querySelectorAll('button').forEach(btn => {
                    btn.classList.remove('ring-2', 'ring-blue-500');
                });
                optionButton.classList.add('ring-2', 'ring-blue-500');
            });
            
            optionsContainer.appendChild(optionButton);
        });
        
        materialSection.appendChild(label);
        materialSection.appendChild(optionsContainer);
        container.appendChild(materialSection);
    });
}

// Initialize configurator when called
window.initProductConfigurator = function(containerId, productData) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    // Populate controls
    if (productData.logoPositions) {
        populateLogoPositions(JSON.parse(productData.logoPositions));
    }
    
    if (productData.materials) {
        populateMaterialsControls(JSON.parse(productData.materials));
    }
    
    // Render React component
    const root = ReactDOM.createRoot(container);
    root.render(React.createElement(ProductConfigurator, { productData }));
    
    return root;
};