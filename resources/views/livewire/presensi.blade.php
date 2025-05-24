@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
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
    </style>
@endpush

<div>
    <div class="container mx-auto max-w-sm">
        <div class="bg-white p-6 rounded-lg mt-3 shadow-lg">
            <div class="grid grid-cols-1 gap-6 mb-6">
                {{-- Informasi Pegawai --}}
                <div>
                    <h2 class="text-2xl font-bold mb-2">Informasi Pegawai</h2>
                    <div class="bg-gray-100 p-4 rounded-lg">
                        <p><strong>Nama Pegawai :</strong> {{ Auth::user()->name }}</p>
                        <p><strong>Kantor :</strong> {{ $schedule->office->name }}</p>
                        <p><strong>Shift :</strong> {{ $schedule->shift->name }} ({{ $schedule->shift->start_time }} - {{ $schedule->shift->end_time }}) WIB</p>
                        <p><strong>Status :</strong>
                            @if($schedule->is_wfa)
                                <span class="text-green-500 font-bold">WFA</span>
                            @else
                                <span class="text-red-500 font-bold">WFO</span>
                            @endif
                        </p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                        <div class="bg-gray-100 p-4 rounded-lg">
                            <h4 class="text-lg font-bold mb-2">Waktu Datang</h4>
                            <p>{{ $attendance?->start_time ?? '-' }}</p>
                        </div>
                        <div class="bg-gray-100 p-4 rounded-lg">
                            <h4 class="text-lg font-bold mb-2">Waktu Pulang</h4>
                            <p>{{ $attendance?->end_time ?? '-' }}</p>
                        </div>
                    </div>
                </div>
                {{-- Presensi --}}
                <div>
                    <h2 class="text-2xl font-bold mb-2">Presensi</h2>
                    <div id="map" class="mb-4 rounded-lg border border-gray-300 h-64" wire:ignore></div>
                    @if (session()->has('error'))
                        <div class="bg-red-100 text-red-800 p-2 rounded mb-2 border border-red-400">
                            {{ session('error') }}
                        </div>
                    @endif
                    <form wire:submit.prevent="store" class="mt-4 space-y-3">
                        <button type="button" onclick="tagLocation()" class="px-4 py-2 bg-blue-500 text-white rounded">Tag Location</button>
                        @if ($hasTaggedLocation)
                            <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded">Submit Presensi</button>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        let map, marker, officeMarker;
        const office = [{{ $schedule->office->latitude }}, {{ $schedule->office->longitude }}];
        const radius = {{ $schedule->office->radius }};
        const officeName = "{{ $schedule->office->name }}";
        let component;

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

        document.addEventListener('livewire:initialized', function () {
            component = @this;
            map = L.map('map').setView(office, 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);
            const circle = L.circle(office, {
                color: '#3B82F6',
                fillColor: '#3B82F6',
                fillOpacity: 0.15,
                weight: 3,
                radius: radius,
                dashArray: '10, 5'
            }).addTo(map);
            // circle.bindTooltip(officeName, { permanent: true, direction: 'center', className: 'office-tooltip', offset: [0, -10] });
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
        });

        function tagLocation() {
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
                                <strong>{{ Auth::user()->name }}</strong>
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

                    const isWfa = "{{ $schedule->is_wfa }}" === "1";
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

        window.addEventListener('presensi-success', function () {
            Swal.fire({
                icon: 'success',
                title: 'Presensi Berhasil!',
                text: 'Presensi kamu sudah tercatat!',
                confirmButtonText: 'OK'
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
    </script>
@endpush
