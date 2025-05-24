// Custom Map Functionality for Filament

// Wait for both DOM and Leaflet to be ready
function initializeCustomMap() {
    if (!window.L) {
        console.log('Waiting for Leaflet to load...');
        setTimeout(initializeCustomMap, 100);
        return;
    }

    console.log('Custom map styling loaded');
    
    // Custom office icon
    const customOfficeIcon = L.divIcon({
        className: 'custom-office-marker',
        html: '<div class="office-icon"><i class="fas fa-building"></i></div>',
        iconSize: [40, 40],
        iconAnchor: [20, 20]
    });

    // Function to apply custom styling to maps
    function applyCustomMapStyling() {
        // Find all Leaflet maps
        const maps = document.querySelectorAll('.leaflet-container');
        
        maps.forEach(function(mapContainer) {
            try {
                // Get the Leaflet map instance
                let map = null;
                if (mapContainer._leaflet_id) {
                    // Try to get map from global L object
                    if (window.L && window.L.Map) {
                        // Find map instance
                        for (let key in window) {
                            if (window[key] instanceof L.Map && window[key].getContainer() === mapContainer) {
                                map = window[key];
                                break;
                            }
                        }
                    }
                }
                
                if (map) {
                    console.log('Found map instance, applying custom styling');
                    
                    // Replace existing markers with custom styled ones
                    let lastMarker = null;
                    let lastLatLng = null;
                    map.eachLayer(function(layer) {
                        if (layer instanceof L.Marker && customOfficeIcon) {
                            layer.setIcon(customOfficeIcon);
                            layer.bindPopup(`
                                <div class="office-popup">
                                    <div class="popup-header">
                                        <i class="fas fa-building"></i>
                                        <strong>temanweb.id</strong>
                                    </div>
                                    <div class="popup-content">
                                        <div class="info-item">
                                            <i class="fas fa-circle-dot"></i>
                                            <span>Radius Absensi: <strong>100m</strong></span>
                                        </div>
                                        <div class="info-item">
                                            <i class="fas fa-map-pin"></i>
                                            <span>Kantor Pusat</span>
                                        </div>
                                    </div>
                                </div>
                            `);
                            lastMarker = layer;
                            lastLatLng = layer.getLatLng();
                        }
                    });
                    // Tambahkan circle radius biru di sekitar marker office
                    if (lastLatLng) {
                        L.circle(lastLatLng, {
                            color: '#3B82F6',
                            fillColor: '#3B82F6',
                            fillOpacity: 0.15,
                            weight: 3,
                            radius: 100, // radius dalam meter, bisa diganti dinamis
                            dashArray: '10, 5'
                        }).addTo(map);
                    }
                    if (lastMarker) {
                        lastMarker.openPopup();
                        setTimeout(() => lastMarker.openPopup(), 500);
                        setTimeout(() => lastMarker.openPopup(), 1000);
                    }
                    
                    // Handle new markers added to the map
                    map.on('layeradd', function(e) {
                        if (e.layer instanceof L.Marker && customOfficeIcon) {
                            setTimeout(function() {
                                if (map.hasLayer(e.layer)) {
                                    e.layer.setIcon(customOfficeIcon);
                                }
                            }, 100);
                        }
                    });
                }
            } catch (error) {
                console.log('Error applying custom styling:', error);
            }
        });

        // Paksa ganti marker OSMMap dengan custom icon setiap saat
        document.querySelectorAll('.leaflet-marker-icon').forEach(function(markerEl) {
            if (!markerEl.classList.contains('custom-office-marker')) {
                markerEl.classList.add('custom-office-marker');
                markerEl.innerHTML = '<div class="office-icon"><i class="fas fa-building"></i></div>';
                markerEl.style.background = 'none';
                markerEl.style.border = 'none';
                markerEl.style.width = '40px';
                markerEl.style.height = '40px';
                markerEl.style.display = 'flex';
                markerEl.style.alignItems = 'center';
                markerEl.style.justifyContent = 'center';
                markerEl.style.padding = '0';
            }
        });
    }

    // Apply styling immediately if Leaflet is loaded
    if (window.L) {
        applyCustomMapStyling();
    }
    
    // Apply styling after delays for dynamic content
    setTimeout(applyCustomMapStyling, 1000);
    setTimeout(applyCustomMapStyling, 3000);
    
    // Watch for new maps being added to the DOM
    const observer = new MutationObserver(function(mutations) {
        let hasNewMaps = false;
        
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) { // Element node
                        if (node.classList && node.classList.contains('leaflet-container')) {
                            hasNewMaps = true;
                        } else if (node.querySelector && node.querySelector('.leaflet-container')) {
                            hasNewMaps = true;
                        }
                    }
                });
            }
        });
        
        if (hasNewMaps) {
            setTimeout(applyCustomMapStyling, 500);
        }
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });

    // Handle Livewire updates
    if (window.Livewire) {
        window.Livewire.hook('message.processed', function() {
            setTimeout(applyCustomMapStyling, 500);
        });
    }

    // Handle Alpine.js updates
    if (window.Alpine) {
        document.addEventListener('alpine:initialized', function() {
            setTimeout(applyCustomMapStyling, 500);
        });
    }
}

// Start initialization when DOM is ready
document.addEventListener('DOMContentLoaded', initializeCustomMap);

// Export function for manual calling if needed
window.applyCustomMapStyling = function() {
    const event = new CustomEvent('apply-custom-map-styling');
    document.dispatchEvent(event);
};

function isMapReady() {
    return document.querySelector('.leaflet-container') && window.L && typeof L.Map === 'function';
}

function tryApplyCustomMapStyling(retry = 10) {
    if (isMapReady()) {
        applyCustomMapStyling();
        // Cek apakah marker sudah ada dan popup sudah terbuka
        const marker = document.querySelector('.leaflet-marker-icon.custom-office-marker');
        const popup = document.querySelector('.leaflet-popup');
        if ((!marker || !popup) && retry > 0) {
            setTimeout(() => tryApplyCustomMapStyling(retry - 1), 500);
        }
    } else if (retry > 0) {
        setTimeout(() => tryApplyCustomMapStyling(retry - 1), 500);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => tryApplyCustomMapStyling(10), 500);
}); 