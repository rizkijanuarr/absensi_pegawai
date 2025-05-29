<x-filament-panels::page>

    <!-- {{-- Section Sambutan Umum --}}
    <div class="mb-6">
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900 p-6">
            <h3 class="text-xl font-semibold text-gray-950 dark:text-white mb-4">
                Halo, {{ Auth::user()->name }} Selamat datang! 👋
            </h3>
            {{-- Anda bisa tambahkan teks sapaan lain di sini jika perlu --}}
        </div>
    </div> -->

    {{-- Tampilkan pesan informasi jika karyawan belum memiliki schedule --}}
    @if ($this->isKaryawanWithoutSchedule())
        <div class="mb-6">
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-6 shadow-sm dark:border-amber-200/20 dark:bg-amber-900/20">
                <div class="flex items-center gap-x-3 mb-4">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/50">
                        <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-amber-900 dark:text-amber-100">
                        Informasi Penting
                    </h3>
                </div>
                
                <div class="space-y-4">
                    <p class="text-amber-800 dark:text-amber-200 font-medium">
                        Halo {{ $this->getUserName() }}! 👋
                    </p>
                    <p class="text-amber-700 dark:text-amber-300">
                        Akun Anda telah berhasil terdaftar sebagai <strong>Karyawan</strong>. 
                        Namun untuk memulai proses presensi, Anda perlu memiliki jadwal kerja yang aktif.
                    </p>
                    <div class="bg-amber-100/80 dark:bg-amber-900/30 rounded-lg p-4 border border-amber-200 dark:border-amber-700">
                        <p class="text-amber-800 dark:text-amber-200 text-sm">
                            <strong>Langkah selanjutnya:</strong><br>
                            Silakan hubungi Administrator sistem untuk pengaturan jadwal kerja Anda. 
                            Setelah jadwal diatur, Anda dapat melakukan presensi dan mengakses fitur lengkap sistem.
                        </p>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-amber-600 dark:text-amber-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                        </svg>
                        <span>Halaman ini akan diperbarui otomatis setelah jadwal Anda diatur.</span>
                    </div>
                </div>
            </div>
        </div>
    @endif

</x-filament-panels::page>