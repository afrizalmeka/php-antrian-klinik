# KliniKu — Sistem Antrian Klinik

Aplikasi sistem antrian klinik berbasis PHP dan SQLite untuk keperluan pembelajaran pengujian perangkat lunak.

## Fitur
- Tampilan antrian hari ini (publik)
- Pendaftaran pasien baru oleh petugas
- Update status antrian (menunggu → dipanggil → selesai)
- Kelola daftar dokter (admin)

## Cara Menjalankan

1. **Clone atau unduh** folder `php-antrian-klinik`.
2. **Jalankan server PHP:**
   ```bash
   cd php-antrian-klinik
   php -S localhost:8007
   ```
3. **Buka browser** dan akses `http://localhost:8007`

Database dibuat otomatis saat pertama kali diakses.

## Akun Demo

| Role    | Email                   | Password    |
|---------|-------------------------|-------------|
| Admin   | admin@kliniku.com       | admin123    |
| Petugas | petugas@kliniku.com     | petugas123  |

## Catatan untuk Mahasiswa

Aplikasi ini adalah **versi latihan** yang mengandung bug untuk praktikum pengujian perangkat lunak.
