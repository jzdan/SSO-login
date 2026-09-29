# SSO Login

Server Single Sign-On (SSO) berbasis **Laravel 12** + **Laravel Passport (OAuth2)** dengan tampilan **Bootstrap 5**.
Pengguna cukup login sekali di server SSO untuk mengakses semua website yang terhubung.

## Fitur

- Login, registrasi (bisa dimatikan), dan pembatasan percobaan login
- OAuth2 *Authorization Code* + PKCE untuk website klien
- Aplikasi tepercaya langsung login tanpa halaman persetujuan (bisa diatur per klien)
- **Single logout**: logout di SSO atau di salah satu aplikasi mencabut semua token, sehingga semua aplikasi ikut logout
- Dashboard portal berisi daftar aplikasi yang terhubung
- Profil pengguna: ubah data, ubah password, lihat dan cabut sesi aplikasi
- Panel admin: kelola klien SSO (Client ID/Secret, redirect URI, aktif/nonaktif) dan kelola pengguna (aktif/nonaktif, admin)

## Instalasi (Laragon)

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan passport:keys
php artisan migrate --seed
```

Aktifkan ekstensi `sodium` di `php.ini` (dibutuhkan Passport).

Buka `http://sso-login.test` dan login dengan akun admin bawaan:

| Email | Password |
|---|---|
| `admin@sso.test` | `password` |

> Ganti password admin setelah login pertama. Nilai awal akun admin diatur lewat `SSO_ADMIN_EMAIL` / `SSO_ADMIN_PASSWORD` di `.env`.

### Memakai MySQL

Secara default proyek memakai SQLite. Untuk MySQL, ubah `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sso_login
DB_USERNAME=root
DB_PASSWORD=
```

Buat database `sso_login`, lalu jalankan `php artisan migrate --seed`.

### Konfigurasi `.env`

| Variabel | Default | Keterangan |
|---|---|---|
| `SSO_ALLOW_REGISTRATION` | `true` | Izinkan pendaftaran mandiri |
| `SSO_TOKEN_TTL` | `60` | Masa berlaku access token (menit) |
| `SSO_REFRESH_TOKEN_TTL_DAYS` | `30` | Masa berlaku refresh token (hari) |

## Menghubungkan website klien

1. Login sebagai admin, buka **Klien SSO → Tambah Klien**.
2. Isi nama aplikasi dan Redirect URI, misalnya `http://app1.test/auth/sso/callback`.
3. Salin **Client ID** dan **Client Secret**. Secret hanya ditampilkan sekali.

### Endpoint

| Keperluan | URL |
|---|---|
| Authorize | `GET  {SSO}/oauth/authorize` |
| Tukar kode / refresh token | `POST {SSO}/oauth/token` |
| Data pengguna | `GET  {SSO}/api/user` (header `Authorization: Bearer <token>`) |
| Logout global | `GET  {SSO}/logout?client_id=...&redirect_uri=...` |

Contoh respons `/api/user`:

```json
{ "id": 1, "name": "Administrator", "email": "admin@sso.test", "email_verified": true, "is_admin": true, "updated_at": "..." }
```

### Klien Laravel (siap salin)

Folder [`client-integration/laravel`](client-integration/laravel) berisi file yang tinggal disalin ke aplikasi klien:

| File | Tujuan di aplikasi klien |
|---|---|
| `app/Http/Controllers/Auth/SsoController.php` | sama |
| `app/Http/Middleware/EnsureSsoSession.php` | sama |
| `config/sso.php` | sama |
| `database/migrations/..._add_sso_id_to_users_table.php` | sama, lalu `php artisan migrate` |
| `routes/sso.php` | salin isinya ke `routes/web.php` |

Tambahkan `sso_id` ke `$fillable` pada model `User` klien, lalu isi `.env` klien:

```env
SSO_BASE_URL=http://sso-login.test
SSO_CLIENT_ID=<client id>
SSO_CLIENT_SECRET=<client secret>
SSO_REDIRECT_URI=http://app1.test/auth/sso/callback
```

Alurnya: pengguna membuka klien, diarahkan ke SSO, login (atau langsung lolos jika sudah login), lalu kembali ke klien dengan kode. Klien menukar kode dengan token, mengambil `/api/user`, dan membuat atau memperbarui user lokal.
Middleware `EnsureSsoSession` memeriksa token setiap `SSO_CHECK_INTERVAL` detik (default 60). Jika pengguna sudah logout di SSO, ia otomatis dikeluarkan dari klien.

Website non-Laravel (PHP native, CodeIgniter, dan lain-lain) dapat memakai alur yang sama dengan library OAuth2 apa pun, misalnya `league/oauth2-client`.

## Pengujian

```bash
php artisan test
```

## Catatan produksi

- Gunakan HTTPS untuk server SSO dan semua klien.
- Jalankan `php artisan passport:purge` secara berkala untuk membersihkan token kedaluwarsa.
- Jangan commit `storage/oauth-*.key` ke repository.
