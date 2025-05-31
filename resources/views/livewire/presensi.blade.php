@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .office-tooltip {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 8px 16px;
            font-weight: 600;
            font-size: 14px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            text-align: center;
            letter-spacing: 0.5px;
            animation: tooltipFloat 2s ease-in-out infinite alternate;
        }
        .office-tooltip::before {
            display: none;
        }
        @keyframes tooltipFloat {
            0% { transform: translateY(0px); }
            100% { transform: translateY(-5px); }
        }
        .custom-office-marker {
            background: none;
            border: none;
        }
        .office-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            border: 3px solid white;
            animation: officePulse 2s ease-in-out infinite;
        }
        @keyframes officePulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        .custom-user-marker {
            background: none;
            border: none;
        }
        .user-icon {
            width: 30px;
            height: 30px;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            border-radius: 50% 50% 50% 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.4);
            border: 2px solid white;
            transform: rotate(-45deg);
            animation: userBounce 1s ease-in-out infinite alternate;
        }
        .user-icon i {
            transform: rotate(45deg);
        }
        @keyframes userBounce {
            0% { transform: rotate(-45deg) translateY(0px); }
            100% { transform: rotate(-45deg) translateY(-3px); }
        }
        .leaflet-popup-content-wrapper {
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            border: none;
            overflow: hidden;
        }
        .leaflet-popup-content {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .office-popup, .user-popup {
            min-width: 200px;
        }
        .popup-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 16px;
            font-size: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .user-header {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
        }
        .popup-content {
            padding: 12px 16px;
            background: white;
        }
        .info-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            font-size: 13px;
            color: #666;
        }
        .info-item:last-child {
            margin-bottom: 0;
        }
        .info-item i {
            color: #3B82F6;
            width: 14px;
            text-align: center;
        }
        .user-popup .info-item i {
            color: #ff6b6b;
        }
        .leaflet-popup-tip {
            background: white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        #map {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        .leaflet-control-zoom a {
            border-radius: 8px;
            border: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .leaflet-control-zoom a:first-child {
            border-radius: 8px 8px 0 0;
        }
        .leaflet-control-zoom a:last-child {
            border-radius: 0 0 8px 8px;
        }
        
        /* Camera Styles */
        .camera-container {
            position: relative;
            width: 100%;
            max-width: 640px;
            margin: 0 auto;
            background: #000;
            border-radius: 12px;
            overflow: hidden;
        }
        
        #video {
            width: 100%;
            border-radius: 12px;
            margin-bottom: 1rem;
        }
        
        #canvas {
            display: none;
        }
        
        .camera-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin: 1rem 0;
        }
        
        .captured-photo {
            width: 100%;
            max-width: 640px;
            border-radius: 12px;
            margin: 1rem auto;
            display: none;
        }

        .face-detection-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }

        .face-detection-box {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 200px;
            height: 200px;
            border: 3px solid rgba(255, 255, 255, 0.5);
            border-radius: 12px;
            box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.5);
        }

        .face-detection-label {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -20%);
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 6px 12px;
            border-radius: 16px;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
            text-align: center;
        }

        .progress-circle .position-absolute {
             position: absolute;
             top: 50%;
             left: 50%;
             transform: translate(-50%, -50%);
             display: flex;
             align-items: center;
             justify-content: center;
             width: 100%;
             height: 100%;
             font-weight: bold;
             font-size: 14px;
        }

        .btn-camera {
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-camera:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .btn-camera:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .btn-camera i {
            font-size: 18px;
        }

        .btn-capture {
            background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);
            color: white;
        }

        .btn-capture:hover {
            background: linear-gradient(135deg, #2563EB 0%, #1D4ED8 100%);
        }

        .btn-retake {
            background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
            color: white;
        }

        .btn-retake:hover {
            background: linear-gradient(135deg, #D97706 0%, #B45309 100%);
        }

        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
            margin-right: 8px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
@endpush

<div class="container py-4">
    <!-- Stepper Bootstrap -->
    <div class="mb-4">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <div class="progress-circle position-relative" style="width:48px;height:48px;">
                        <svg width="48" height="48">
                            <circle cx="24" cy="24" r="20" fill="none" stroke="#e5e7eb" stroke-width="6"/>
                            <circle cx="24" cy="24" r="20" fill="none" stroke="#3B82F6" stroke-width="6" stroke-dasharray="125.6" stroke-dashoffset="{{ $step == 1 ? 62.8 : 0 }}" style="transition:stroke-dashoffset 0.3s;"/>
                        </svg>
                        <div class="position-absolute top-50 start-50 translate-middle fw-bold">{{ $step }} / 2</div>
                    </div>
                </div>
                <div>
                    <div class="fw-bold fs-5">Presensi Pegawai</div>
                    <div class="text-muted small">
                        @if($step == 1 && $isCameraEnabled) Ambil Photo
                        @elseif($step == 2)
                            @if($currentAction == 'arrival') Tag Lokasi Datang @else Tag Lokasi Pulang @endif
                        @else
                            Tag Lokasi
                        @endif
                    </div>
                </div>
            </div>
            <button type="button" 
                    wire:click="markAsNotPresent" 
                    class="btn {{ $canMarkNotPresent ? 'btn-danger' : 'btn-secondary' }}"
                    {{ !$canMarkNotPresent ? 'disabled' : '' }}>
                <i class="fas fa-times-circle me-1"></i> Tidak Hadir
            </button>
        </div>
        <div class="progress" style="height:6px;">
            <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $step == 1 ? '50%' : '100%' }};"></div>
        </div>
    </div>

    <div class="bg-white p-4 rounded shadow-sm">
        @if($step == 1 && $isCameraEnabled)
            <!-- Step 1: Ambil Photo -->
            <div class="mb-3 text-center">
                <div class="camera-container mb-3 mx-auto" style="max-width:340px;">
                    <video id="video" autoplay playsinline style="width:100%;border-radius:12px;"></video>
                    <canvas id="canvas" style="display:none;"></canvas>
                    <img id="captured-photo" class="captured-photo" alt="Captured photo" style="display:none;width:100%;border-radius:12px;"/>
                    <div id="face-detection-overlay" class="face-detection-overlay">
                        <div class="face-detection-box"></div>
                        <div class="face-detection-label">Tidak Dikenali</div>
                    </div>
                </div>
                <button type="button" id="capture-photo" class="btn btn-primary px-4">
                    <i class="fas fa-camera"></i> Ambil Foto
                </button>
            </div>
            <input type="file" id="photo-input" wire:model="photo" accept="image/*" style="display:none;">
        @elseif($step == 2)
            <!-- Step 2: Informasi Pegawai & Map Presensi -->
            <div class="mb-3">
                <div class="bg-light p-3 rounded mb-3">
                    <h5 class="fw-bold mb-2">Informasi Pegawai</h5>
                    <div class="mb-2"><strong>Nama Pegawai :</strong> {{ Auth::user()->name ?? 'N/A' }}</div>
                    <div class="mb-2"><strong>Kantor :</strong> {{ $schedule?->office?->name ?? 'N/A' }}</div>
                    <div class="mb-2"><strong>Shift :</strong> {{ $schedule?->shift?->name ?? 'N/A' }} ({{ $schedule?->shift?->start_time ?? '-' }} - {{ $schedule?->shift?->end_time ?? '-' }}) WIB</div>
                    <div class="mb-2"><strong>Status :</strong>
                        @if($schedule && $schedule->is_wfa)
                            <span class="text-success fw-bold">WFA</span>
                        @else
                            <span class="text-danger fw-bold">WFO</span>
                        @endif
                    </div>
                    <div class="row mt-3 g-3">
                        <div class="col-12 col-md-6">
                            <div class="bg-gray-100 p-3 rounded">
                                <h6 class="fw-bold mb-1">Waktu Datang</h6>
                                <p class="mb-0">{{ $attendance?->start_time ?? '-' }}</p>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="bg-gray-100 p-3 rounded">
                                <h6 class="fw-bold mb-1">Waktu Pulang</h6>
                                <p class="mb-0">{{ $attendance?->end_time ?? '-' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="map" class="mb-3 rounded-lg border border-gray-300" style="height:260px;" wire:ignore></div>
                <div class="d-flex gap-2">
                    <button type="button" onclick="tagLocation()" class="btn btn-outline-primary flex-fill">Tag Location</button>
                    @if ($hasTaggedLocation)
                        <form wire:submit.prevent="submitPresensi" class="flex-fill">
                            <button type="submit" class="btn btn-success w-100">Submit Presensi</button>
                        </form>
                    @endif
                </div>
                @if (!$hasTaggedLocation)
                    <div class="text-danger small mt-2">Silakan tag lokasi terlebih dahulu.</div>
                @endif
            </div>
        @endif
    </div>
</div>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Global variables
        let map = null;
        let marker = null;
        let officeMarker = null;
        let mapInitialized = false;
        let component = null;
        
        // Office data
        const office = [{{ $schedule?->office?->latitude ?? 0 }}, {{ $schedule?->office?->longitude ?? 0 }}];
        const radius = {{ $schedule?->office?->radius ?? 0 }};
        const officeName = "{{ $schedule?->office?->name ?? 'N/A' }}";

        // Icon definitions
        const officeIcon = L.divIcon({
            className: 'custom-office-marker',
            html: '<div class="office-icon"><i class="fas fa-building"></i></div>',
            iconSize: [40, 40],
            iconAnchor: [20, 20]
        });
        const userIcon = L.divIcon({
            className: 'custom-user-marker',
            html: '<div class="user-icon"><i class="fas fa-map-marker-alt"></i></div>',
            iconSize: [30, 30],
            iconAnchor: [15, 30]
        });

        // Force map always show on step 2
        function initializeMapIfNeeded() {
            const mapElement = document.getElementById('map');
            const currentStep = document.querySelector('.fw-bold')?.textContent?.includes('2 / 2');
            if (!currentStep) {
                mapInitialized = false;
                if (map) {
                    map.remove();
                    map = null;
                }
                return;
            }
            if (mapElement && !mapInitialized) {
                mapInitialized = true;
                if (map) {
                    map.remove();
                    map = null;
                }
                
                // Hanya inisialisasi map jika office data valid
                if (office[0] !== 0 || office[1] !== 0) {
                    map = L.map('map').setView(office, 15);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap contributors'
                    }).addTo(map);
                    const circle = L.circle(office, {
                        color: '#3B82F6', fillColor: '#3B82F6', fillOpacity: 0.15, weight: 3, radius: radius, dashArray: '10, 5'
                    }).addTo(map);
                    officeMarker = L.marker(office, { icon: officeIcon }).addTo(map)
                        .bindPopup(`
                            <div class="office-popup">
                                <div class="popup-header">
                                    <i class="fas fa-building"></i>
                                    <strong>${officeName}</strong>
                                </div>
                                <div class="popup-content">
                                    <div class="info-item">
                                        <i class="fas fa-circle-dot"></i>
                                        <span>Radius Absensi: <strong>${radius}m</strong></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-map-pin"></i>
                                        <span>Kantor Pusat</span>
                                    </div>
                                </div>
                            </div>
                        `);
                    map.invalidateSize();
                } else {
                    // Tampilkan pesan jika data office tidak lengkap
                    console.error('Office data not available for map initialization.');
                    mapElement.innerHTML = '<div class="d-flex align-items-center justify-content-center h-100 text-muted">Data lokasi kantor tidak lengkap.</div>';
                }
            }
        }
        setInterval(initializeMapIfNeeded, 300);

        // Tag location function
        function tagLocation() {
            // Jangan tag lokasi jika office data tidak valid
             if (office[0] === 0 && office[1] === 0) {
                 Swal.fire({
                     icon: 'error',
                     title: 'Data Kantor Tidak Lengkap!',
                     text: 'Tidak dapat menandai lokasi karena data kantor belum lengkap. Mohon hubungi administrator.',
                     confirmButtonText: 'OK'
                 });
                 return;
             }

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function (position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;

                    if (marker) map.removeLayer(marker);
                    marker = L.marker([lat, lng], { icon: userIcon }).addTo(map);
                    map.setView([lat, lng], 16);

                    marker.bindPopup(`
                        <div class="user-popup">
                            <div class="popup-header user-header">
                                <i class="fas fa-user-circle"></i>
                                <strong>{{ Auth::user()->name ?? 'N/A' }}</strong>
                            </div>
                            <div class="popup-content">
                                <div class="info-item">
                                    <i class="fas fa-crosshairs"></i>
                                    <span>Lat: ${lat.toFixed(6)}</span>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-crosshairs"></i>
                                    <span>Lng: ${lng.toFixed(6)}</span>
                                </div>
                            </div>
                        </div>
                    `).openPopup();

                    // Handle potential null for schedule before accessing is_wfa
                    const isWfa = "{{ $schedule?->is_wfa ?? 0 }}" === "1"; // Gunakan null coalesce
                    const withinRadius = map.distance([lat, lng], office) <= radius;

                    if (isWfa || withinRadius) {
                        component.set('insideRadius', true);
                        component.set('latitude', lat);
                        component.set('longitude', lng);
                        component.set('hasTaggedLocation', true);
                        window.dispatchEvent(new CustomEvent('location-tagged-success'));
                    } else {
                        component.set('insideRadius', false);
                        Swal.fire({
                            icon: 'error',
                            title: 'Lokasi Tidak Valid!',
                            text: 'Status Anda WFO, maka harus melakukan absensi didalam radius Office',
                            confirmButtonText: 'OK'
                        });
                    }
                }, function(error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error Geolocation!',
                        text: 'Tidak dapat mengakses lokasi Anda. Pastikan GPS aktif dan izin lokasi diberikan.',
                        confirmButtonText: 'OK'
                    });
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Geolocation Tidak Didukung!',
                    text: 'Geolocation tidak didukung di browser Anda.',
                    confirmButtonText: 'OK'
                });
            }
        }

        // Livewire events
        document.addEventListener('livewire:initialized', function () {
            component = @this;
        });

        // Success/error handlers
        window.addEventListener('presensi-success', function () {
            Swal.fire({
                icon: 'success',
                title: 'Presensi Berhasil!',
                text: 'Presensi kamu sudah tercatat!',
                confirmButtonText: 'OK',
                allowOutsideClick: false,
                didClose: () => {
                    // Add a small delay to ensure data is saved
                    setTimeout(() => {
                        window.location.href = '/backoffice/attendances';
                    }, 500);
                }
            });
        });

        window.addEventListener('presensi-error', function (event) {
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: event.detail.message || 'Terjadi kesalahan saat menyimpan presensi.',
                confirmButtonText: 'OK'
            });
        });

        window.addEventListener('location-tagged-success', function () {
            Swal.fire({
                icon: 'success',
                title: 'Lokasi Berhasil Ditemukan!',
                text: 'Silakan submit presensi!',
                confirmButtonText: 'OK',
                timer: 3000,
                timerProgressBar: true
            });
        });

        // Listen for presensi completed event to show Swal and redirect
        window.addEventListener('presensi-completed', function (event) {
            Swal.fire({
                icon: 'info',
                title: 'Presensi Selesai',
                text: event.detail.message || 'Anda sudah melakukan presensi hari ini.',
                confirmButtonText: 'OK',
                allowOutsideClick: false,
                didClose: () => {
                    window.location.href = '/backoffice/attendances';
                }
            });
        });

        // Listen for tidak-hadir event
        window.addEventListener('tidak-hadir', function (event) {
            Swal.fire({
                icon: 'warning',
                title: 'Konfirmasi Tidak Hadir',
                text: 'Apakah Anda yakin ingin menandai presensi sebagai tidak hadir?',
                showCancelButton: true,
                confirmButtonText: 'Ya, Tandai Tidak Hadir',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                allowOutsideClick: false,
            }).then((result) => {
                if (result.isConfirmed) {
                    // Jika dikonfirmasi, panggil method confirmNotPresent di Livewire
                    @this.confirmNotPresent();
                }
            });
        });

        // Listen for confirm-not-present event
        window.addEventListener('confirm-not-present', function () {
            // Proses tidak hadir dan tampilkan SWAL sukses
            Swal.fire({
                icon: 'success',
                title: 'Presensi Ditandai Tidak Hadir',
                text: 'Presensi Anda telah ditandai sebagai tidak hadir.',
                confirmButtonText: 'OK',
                allowOutsideClick: false,
                didClose: () => {
                    window.location.href = '/backoffice/attendances';
                }
            });
        });

         // Listen for no-schedule event to show Swal and redirect
        window.addEventListener('no-schedule', function (event) {
            Swal.fire({
                icon: 'warning', // Atau icon lain yang sesuai
                title: 'Akses Dibatasi',
                text: event.detail.message || 'Anda belum memiliki jadwal kerja.',
                confirmButtonText: 'OK',
                allowOutsideClick: false, // Jangan izinkan klik di luar SweetAlert
                didClose: () => { // Ketika SweetAlert ditutup
                    window.location.href = '/backoffice/attendances'; // Redirect
                }
            });
        });

        // Camera functionality
        let video = document.getElementById('video');
        let canvas = document.getElementById('canvas');
        let capturedPhoto = document.getElementById('captured-photo');
        let capturePhotoBtn = document.getElementById('capture-photo');
        let faceDetectionLabel = document.querySelector('.face-detection-label');
        let input = document.getElementById('photo-input');
        let stream = null;

        if (video && capturePhotoBtn) {
            async function startCamera() {
                try {
                    // Use ideal constraints instead of exact for better compatibility
                    stream = await navigator.mediaDevices.getUserMedia({ 
                        video: { ideal: { width: 340, height: 340 }, facingMode: 'user' }
                    });
                    video.srcObject = stream;
                    video.play(); // Ensure video plays

                    // Optional: Wait for video to load metadata before drawing
                    await new Promise(resolve => video.onloadedmetadata = resolve);

                } catch (err) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Tidak dapat mengakses kamera. Pastikan izin kamera diberikan.' + (err.message ? ': ' + err.message : ''),
                        confirmButtonText: 'OK'
                    });
                     capturePhotoBtn.disabled = true; // Disable button if camera access fails
                     capturePhotoBtn.innerHTML = '<i class="fas fa-camera"></i> Gagal Akses Kamera';
                }
            }
            startCamera();

            capturePhotoBtn.addEventListener('click', async () => {
                if (!video.srcObject) { // Check if stream is available
                    Swal.fire({
                         icon: 'warning',
                         title: 'Kamera Tidak Aktif',
                         text: 'Tidak dapat mengambil foto karena kamera tidak aktif.',
                         confirmButtonText: 'OK'
                    });
                    return;
                }

                capturePhotoBtn.disabled = true;
                capturePhotoBtn.innerHTML = '<span class="loading-spinner"></span> Mengambil Foto...';
                faceDetectionLabel.textContent = 'Mengambil Foto...';

                try {
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);

                    // Add a small delay to ensure drawing is complete (optional, but can help)
                    await new Promise(resolve => setTimeout(resolve, 100));

                    canvas.toBlob((blob) => {
                        if (blob) {
                            let file = new File([blob], 'photo.jpg', { type: 'image/jpeg' });
                            let dataTransfer = new DataTransfer();
                            dataTransfer.items.add(file);
                            input.files = dataTransfer.files;

                            // Dispatch the change event using Livewire.dispatch for robustness
                            // Use a separate event to signal photo taken, if 'change' is unreliable
                            // window.dispatchEvent(new CustomEvent('photo-captured', { detail: { file: file } }));
                            
                            // Using wire:model requires dispatching the 'change' event on the input element
                            input.dispatchEvent(new Event('change', { bubbles: true }));
                            
                            capturedPhoto.src = URL.createObjectURL(blob);
                            capturedPhoto.style.display = 'block';
                            video.style.display = 'none';
                            capturePhotoBtn.style.display = 'none'; // Hide capture button
                            
                             // Optionally show a retake button
                             // let retakeBtn = document.getElementById('retake-photo');
                             // if (retakeBtn) retakeBtn.style.display = 'inline-flex';

                            if (stream) { stream.getTracks().forEach(track => track.stop()); stream = null; }
                            faceDetectionLabel.textContent = 'Foto Terambil';

                            // Now wait for Livewire to process and move to step 2
                            capturePhotoBtn.innerHTML = '<span class="loading-spinner"></span> Memproses...';

                        } else {
                             Swal.fire({
                                icon: 'error',
                                title: 'Gagal Membuat Foto!',
                                text: 'Terjadi kesalahan saat memproses gambar.',
                                confirmButtonText: 'OK'
                             });
                            capturePhotoBtn.disabled = false; // Re-enable button
                            capturePhotoBtn.innerHTML = '<i class="fas fa-camera"></i> Ambil Foto'; // Reset button text
                            faceDetectionLabel.textContent = 'Tidak Dikenali'; // Reset label
                        }
                    }, 'image/jpeg', 0.8);

                } catch (err) {
                     Swal.fire({
                         icon: 'error',
                         title: 'Gagal Mengambil Foto!',
                         text: 'Terjadi kesalahan saat mengambil gambar: ' + (err.message ? err.message : ''),
                         confirmButtonText: 'OK'
                     });
                     capturePhotoBtn.disabled = false; // Re-enable button
                     capturePhotoBtn.innerHTML = '<i class="fas fa-camera"></i> Ambil Foto'; // Reset button text
                     faceDetectionLabel.textContent = 'Tidak Dikenali'; // Reset label
                }
            });
        }
    </script>
@endpush