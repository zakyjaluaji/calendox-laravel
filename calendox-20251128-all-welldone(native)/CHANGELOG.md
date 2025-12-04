CalendoX – Catatan Perubahan (Changelog)

2025-11-07
- Lampiran: Jika unggah file lampiran gagal, penyebabnya dapat berupa konflik proses server Apache vs server PHP built-in. Solusi operasional: hentikan proses Apache yang aktif pada port lokal (mis. 8000) atau jalankan aplikasi CalendoX pada server PHP built-in terlebih dahulu (`/Applications/XAMPP/xamppfiles/bin/php -S 127.0.0.1:8080 -t .`) dan akses melalui `http://127.0.0.1:8080/`. Status: selesai (dikonfirmasi berjalan dengan PHP 7.4).
- Jalankan find /Applications/XAMPP/xamppfiles/htdocs/calendox/sessions -name 'sess_*' -delete
- Jalankan /Applications/XAMPP/xamppfiles/bin/php -S 127.0.0.1:8080 -t /Applications/XAMPP/xamppfiles/htdocs/calendox
- Lampiran: Perubahan link lampiran di aplikasi agar menggunakan jalur relatif dan domain/port aktif aplikasi. Tautan kini mengarah ke direktori `uploads/appointments/<appointment_id>/` dan ditampilkan di halaman terkait (detail event/daftar peserta) tanpa ketergantungan ke `localhost:8000`. Status: selesai.
- Basis Data: Ringkasan kolom tabel yang dipakai di proyek ini:
  - `appointments`: `id`, `title`, `pic_name`, `start_date`, `end_date`, `start_time`, `end_time`, `attachment_filename`, `google_event_id`, `created_at`. Index: `id_title_pic (BTREE)`.
  - `participant`: `id`, `appointment_id`, `name`, `email`, `created_at`. Constraint: `fk_participant_appointment` (FK ke `appointments.id`), unik `idx_unique_event_email (appointment_id,email)`, index `appointment_id`. Status: terdokumentasi.

2025-11-06
- UI: Modal tambah/edit event dapat discroll (`#eventModal .modal-content` disetel `max-height: 80vh`, `overflow-y: auto`, `overscroll-behavior: contain`). Status: selesai.
- Sinkronisasi: Koreksi impor all-day event dari Google agar tidak tampil dua hari (di `sync_two_way.php`). Status: selesai dan dikonfirmasi.
- Peserta: Investigasi error pemuatan data; solusi frontend parsing `res.text()` → `JSON.parse()` dan backend fallback `get_result()` + menonaktifkan output warning agar JSON bersih. Status: siap diterapkan.
- Konfigurasi & Operasional: Perbaikan sesi Apache; set `session.save_path` di `php.ini` ke `/Applications/XAMPP/xamppfiles/htdocs/calendox/sessions`; folder dibuat dan diberi izin tulis. Status: selesai.
- Tema & Tampilan: Konsolidasi variabel warna di `style.css`, dukungan `prefers-color-scheme`, pengurangan `!important`; tetap mendukung toggle manual `body.dark`. Status: diusulkan.
- Integrasi: WhatsApp via Fonnte; input nomor WhatsApp, normalisasi/validasi, indeks unik per event, kirim via API tanpa menggagalkan insert. Status: diusulkan.
- Catatan Lingkungan: Basis data MariaDB 10.4.27, koneksi `mysqli`, kompatibel Navicat 15.
- Catatan Pengujian: Preview via `http://localhost/calendox/`; verifikasi modal scroll, all-day tampil satu hari, data peserta muncul saat respons JSON bersih.
- Langkah Lanjut: Normalisasi push all-day ke Google; uji impor all-day dan validasi peserta; alternatif integrasi WhatsApp.

Petunjuk Penambahan Entri
- Tambahkan entri baru per tanggal dengan poin ringkas dan status.