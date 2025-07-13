# Absensi Pegawai

## 📋 Requirements

- PHP >= 8.2
- Laravel Framework >= 11.31
- Laravel Filament >= 3.3
- MySQL

## 🚀 Installation

### 1. Clone Repository
```bash
git clone git@github.com:rizkijanuarr/absensi_pegawai.git
cd absensi_pegawai
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Database Setup
Login ke MySQL dan buat database baru:
```sql
mysql -u root -p
CREATE DATABASE absensi_pegawai DEFAULT CHARACTER SET = 'utf8mb4';
```

### 4. Environment Configuration
Copy file environment dan sesuaikan konfigurasi:
```bash
cp .env.example .env
```

Edit file `.env` dan atur koneksi database:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=absensi_pegawai
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 5. Generate Application Key
```bash
php artisan key:generate
```

### 6. Run Migrations
```bash
php artisan migrate
```

### 7. Create Super Admin User
Buat user super admin menggunakan Tinker:
```bash
php artisan tinker
```

Di dalam Tinker console, jalankan:
```php
$user = App\Models\User::create([
    'name' => 'Super Admin',
    'email' => 'superadmin@gmail.com',
    'password' => bcrypt('password'),
    'email_verified_at' => now(),
    'is_camera_enabled' => false,
    'image' => null
]);
```

### 8. Start Development Server
```bash
php artisan serve
```

Aplikasi akan berjalan di `http://127.0.0.1:8000/backoffice/login`
- **Email**: superadmin@gmail.com
- **Password**: password

### 9. Generate Spatie Resources 
```bash
php artisan shield:generate --all
```
```bash
php artisan shield:super-admin
```

### 10. Konfigurasi Role
- Masuk ke `http://127.0.0.1:8000/backoffice/shield/roles/create` 
- Buat role dengan nama `Karyawan`, untuk guard `web` 
- Checklist semua hanya pada fitur `Attendance` dan `Schedule` !
- And simpan!

### 11. Generate semua seeder!
```bash
php artisan migrate --seed
```

### 12. Symlink public folder
```bash
php artisan storage:link
```

## 📝 License

This project is open-sourced software licensed under the MIT license.

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📧 Contact

For any questions or support, please contact the repository owner.