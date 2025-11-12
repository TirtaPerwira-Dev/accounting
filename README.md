# StarterKit App

Sebuah aplikasi starter kit yang dibangun dengan Laravel dan Filament untuk mempercepat pengembangan aplikasi web modern dengan fitur admin panel yang lengkap.

## 🚀 Fitur Utama

-   **Admin Panel** - Menggunakan Filament v3 untuk interface admin yang modern dan responsif
-   **Authentication** - Sistem autentikasi lengkap dengan Filament Breezy
-   **Authorization** - Role dan permission management dengan Filament Shield
-   **Activity Logging** - Pencatatan aktivitas pengguna secara otomatis
-   **Health Check** - Monitoring kesehatan aplikasi
-   **GeoIP** - Deteksi lokasi berdasarkan IP address
-   **Modern Frontend** - Menggunakan Vite dan TailwindCSS v4

## 🛠️ Tech Stack

### Backend

-   **Laravel v12** - PHP Framework
-   **PHP ^8.2** - Programming Language
-   **PostgreSQL** - Database (default)
-   **Laravel Sanctum** - API Authentication

### Frontend

-   **Vite v7** - Build Tool
-   **TailwindCSS v4** - CSS Framework
-   **Axios** - HTTP Client

### Admin Panel & UI

-   **Filament v3.3** - Admin Panel Framework
-   **Filament Breezy v2.6** - Authentication UI
-   **Filament Shield v3.9** - Role & Permission Management
-   **Filament Activity Log v1.1** - Activity Logging Interface
-   **Filament Spatie Health v2.3** - Health Check Dashboard

### Additional Libraries

-   **Laravel Authentication Log v5.0** - Login activity tracking
-   **TorannGeoIP v3.0** - IP geolocation service

## 📋 Requirements

-   PHP >= 8.2
-   Composer
-   Node.js & NPM
-   PostgreSQL (atau database lain sesuai konfigurasi)

## ⚡ Quick Start

### 1. Clone Repository

```bash
git clone <repository-url>
cd starterkit-app
```

### 2. Setup Aplikasi

```bash
composer setup
```

Script `composer setup` akan otomatis:

-   Install dependencies PHP
-   Copy file `.env.example` ke `.env`
-   Generate application key
-   Menjalankan migrasi database
-   Install dependencies Node.js
-   Build assets

### 3. Jalankan Development Server

```bash
composer dev
```

Script `composer dev` akan menjalankan:

-   Laravel development server (http://localhost:8000)
-   Queue worker
-   Log viewer (Pail)
-   Vite development server

## 🔧 Manual Setup

Jika ingin setup secara manual:

### 1. Install Dependencies

```bash
composer install
npm install
```

### 2. Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Database Setup

```bash
php artisan migrate
```

### 4. Build Assets

```bash
npm run build
# atau untuk development
npm run dev
```

### 5. Run Application

```bash
php artisan serve
```

## 🧪 Testing

Menjalankan test suite:

```bash
composer test
```

## 📁 Struktur Project

```
├── app/
│   ├── Filament/           # Filament resources & pages
│   ├── Http/Controllers/   # HTTP controllers
│   ├── Models/            # Eloquent models
│   ├── Policies/          # Authorization policies
│   └── Providers/         # Service providers
├── config/                # Configuration files
├── database/
│   ├── migrations/        # Database migrations
│   ├── seeders/          # Database seeders
│   └── factories/        # Model factories
├── resources/
│   ├── css/              # Stylesheets
│   ├── js/               # JavaScript files
│   └── views/            # Blade templates
├── routes/               # Application routes
├── storage/              # Storage files
└── tests/                # Test files
```

## 🔐 Default Access

Setelah setup, Anda dapat mengakses:

-   **Website**: http://localhost:8000
-   **Admin Panel**: http://localhost:8000/admin

> **Note**: Pastikan untuk membuat user admin pertama melalui seeder atau command artisan.

## 📝 Development Commands

-   `composer setup` - Setup aplikasi lengkap
-   `composer dev` - Jalankan development server dengan semua services
-   `composer test` - Jalankan test suite
-   `php artisan migrate` - Jalankan database migration
-   `php artisan filament:make-user` - Buat user admin
-   `npm run dev` - Development mode untuk frontend assets
-   `npm run build` - Build production assets

## 📖 Documentation

-   [Laravel Documentation](https://laravel.com/docs)
-   [Filament Documentation](https://filamentphp.com/docs)
-   [TailwindCSS Documentation](https://tailwindcss.com/docs)

## 🤝 Contributing

1. Fork repository
2. Buat feature branch (`git checkout -b feature/amazing-feature`)
3. Commit perubahan (`git commit -m 'Add some amazing feature'`)
4. Push ke branch (`git push origin feature/amazing-feature`)
5. Buat Pull Request

## 📄 License

Project ini menggunakan [MIT License](LICENSE).

## ⚙️ Configuration

### Environment Variables

File `.env` berisi konfigurasi penting:

-   `APP_NAME` - Nama aplikasi
-   `APP_ENV` - Environment (local, production)
-   `APP_DEBUG` - Debug mode
-   `DB_CONNECTION` - Database connection
-   Dan lainnya sesuai kebutuhan

### Admin Panel

Filament admin panel dapat dikustomisasi melalui:

-   `config/filament.php` - Konfigurasi utama Filament
-   `config/filament-shield.php` - Konfigurasi role & permission
-   `app/Filament/` - Resources, pages, dan widgets custom

## 🆘 Troubleshooting

### Common Issues

1. **Permission Error**: Pastikan folder `storage` dan `bootstrap/cache` writable
2. **Database Error**: Periksa konfigurasi database di `.env`
3. **Asset Error**: Jalankan `npm install` dan `npm run build`

### Logs

-   Application logs: `storage/logs/laravel.log`
-   Real-time logs: `php artisan pail`

---

**Happy Coding! 🎉**
