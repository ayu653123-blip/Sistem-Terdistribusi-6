# Submission/File Service Mini-LMS

## Deskripsi

Submission/File Service adalah prototipe layanan pengumpulan tugas pada Mini-LMS yang dikembangkan menggunakan PHP. Layanan ini menerima permintaan HTTP, melakukan validasi data pengumpulan, menyimpan **metadata** ke berkas JSON, dan menyediakan endpoint untuk membaca kembali metadata yang tersimpan.

Implementasi menggunakan pendekatan **metadata-only**: nama, ukuran, dan tipe file dicatat, tetapi **konten fisik PDF/ZIP tidak diunggah atau disimpan**. Pendekatan ini sesuai dengan opsi penyimpanan metadata JSON pada tugas Pertemuan 6.

## Fitur

- Membuat record pengumpulan tugas melalui `POST /api/submissions`.
- Mengambil kembali metadata pengumpulan melalui `GET /api/submissions/{id}`.
- Mengambil metadata file menggunakan `GET /api/files/{file_id}`.
- Memeriksa respons dasar layanan melalui `GET /health`.
- Memvalidasi kelengkapan input, ID assignment, ukuran metadata, dan tipe file yang dinyatakan.
- Menyimpan record dalam berkas JSON dengan identifier unik.
- Mengembalikan HTTP status code dan response JSON sesuai hasil pemrosesan.

## Teknologi

| Komponen | Teknologi |
|---|---|
| Bahasa pemrograman | PHP |
| Server pengembangan | PHP built-in web server (PHP dari XAMPP) |
| Pertukaran data | HTTP dan JSON |
| Penyimpanan | Berkas JSON lokal |
| Pengujian API | Postman |

Proyek tidak memerlukan Apache, MySQL, ataupun database eksternal. PHP dari XAMPP dijalankan melalui terminal sebagai server pengembangan.

## Struktur Proyek

```text
Submission_File_Service_Mini_LMS/
├── public/
│   └── index.php
├── config/
│   └── assignments.json
├── data/
│   └── submissions/
├── docs/
│   ├── KONTRAK_API.md
│   └── LAPORAN_KELOMPOK.md
├── postman/
│   └── Submission_File_Service.postman_collection.json
├── bukti pengujian/
├── Jalankan_Service.bat
└── README.md
```

Folder `data/submissions/` berisi metadata yang dibuat selama pengujian. File `config/assignments.json` menyediakan data assignment simulasi. Folder `screenshots/` memuat bukti pengujian Postman yang diambil oleh kelompok.

## Cara Menjalankan

**Prasyarat:** XAMPP terinstal di Windows dan PHP tersedia, misalnya pada `C:\xampp\php\php.exe`. Versi PHP yang terpasang dapat diperiksa dengan `C:\xampp\php\php.exe -v`.

1. Tempatkan folder proyek pada `C:\xampp\htdocs\Submission_File_Service_Mini_LMS`.
2. Jalankan `Jalankan_Service.bat` dari folder utama proyek.
3. Biarkan jendela terminal tetap terbuka selama pengujian.
4. Buka `http://127.0.0.1:8105/health` melalui browser atau Postman untuk memeriksa respons dasar layanan.

Server juga dapat dijalankan melalui Command Prompt dari folder proyek:

```bat
cd /d C:\xampp\htdocs\Submission_File_Service_Mini_LMS
C:\xampp\php\php.exe -S 127.0.0.1:8105 -t public public/index.php
```

**Base URL:** `http://127.0.0.1:8105`

Port `8105` digunakan dalam konfigurasi proyek ini. Jika port diganti, sesuaikan pula `base_url` pada collection Postman. Server bawaan PHP digunakan untuk demonstrasi/pengembangan lokal, bukan sebagai web server produksi.

## Endpoint API

| Method | Endpoint | Keterangan | Status sukses |
| GET | `/health` | Pemeriksaan respons dasar service | `200 OK` |
| POST | `/api/submissions` | Membuat dan menyimpan metadata pengumpulan | `201 Created` |
| GET | `/api/submissions/{id}` | Membaca metadata berdasarkan `submission_id` | `200 OK` |
| GET | `/api/files/{file_id}` | Membaca metadata berdasarkan `file_id` | `200 OK` |

Spesifikasi field, response, dan error lebih lengkap tersedia pada `docs/KONTRAK_API.md`.

### Contoh pembuatan submission

**Request:** `POST http://127.0.0.1:8105/api/submissions`  
**Header:** `Content-Type: application/json`

```json
{
  "assignment_id": 501,
  "student_id": 101,
  "filename": "uts-mini-lms.zip",
  "file_size": 248000,
  "content_type": "application/zip"
}
```

Apabila valid, service mengembalikan **`201 Created`** dan objek `data` yang memuat `submission_id`, `file_id`, informasi file, status `metadata_saved`, dan waktu pengumpulan. Identifier dibuat secara unik untuk setiap record baru sehingga nilainya dapat berbeda pada setiap pengujian.

## Pengujian dengan Postman

Impor file `postman/Submission_File_Service.postman_collection.json` melalui menu **Import** di Postman. Gunakan Body **raw → JSON** pada operasi POST.

| Skenario | Input/kondisi | Status yang diharapkan |
| Permintaan valid | `assignment_id: 501` dan seluruh field wajib lengkap | `201 Created` |
| Assignment tidak ditemukan | `assignment_id: 999` dengan field lainnya lengkap | `404 Not Found` |
| Input tidak lengkap | Hanya mengirim `student_id` | `400 Bad Request` |
| Baca submission | GET menggunakan `submission_id` dari POST berhasil | `200 OK` |
| Baca metadata file | GET menggunakan `file_id` dari POST berhasil | `200 OK` |

Bukti tiga skenario utama (201, 404, dan 400) disimpan sebagai tangkapan layar Postman pada folder `bukti pengujian/`. Screenshot harus memperlihatkan method, URL, data request (jika relevan), HTTP status, dan response.

## Model Penyimpanan dan Identifier

- **`submission_id`**: identifier unik untuk setiap record pengumpulan.
- **`file_id`**: identifier unik metadata file yang berkaitan dengan submission.
- **Lokasi internal**: metadata disimpan pada `data/submissions/<submission_id>.json`.
- **Akses client**: menggunakan endpoint serta identifier, bukan path lokal penyimpanan.
- **Validasi assignment**: menggunakan daftar assignment simulasi pada `config/assignments.json`, dengan ID yang tersedia `501` dan `502`.

Batas validasi contoh adalah **5 MiB** berdasarkan nilai `file_size` yang dinyatakan client. Tipe yang diterima adalah `application/pdf` (ekstensi `.pdf`) dan `application/zip` (ekstensi `.zip`). Karena konten file tidak diunggah, pemeriksaan ukuran dan tipe tersebut **hanya berlaku pada metadata yang dikirim**, bukan pemeriksaan file fisik.

## Batasan Implementasi

Prototipe ini dibatasi pada penyimpanan metadata JSON lokal. Layanan belum mencakup upload/download byte file fisik, penyimpanan database produksi, autentikasi pengguna, atau pemanggilan HTTP ke Assignment Service yang sesungguhnya. Data assignment saat ini disimulasikan dengan file konfigurasi lokal.
Untuk pengembangan berikutnya, service dapat diintegrasikan dengan Assignment Service, menambahkan upload file fisik dan validasi konten, serta menerapkan kontrol akses dan penyimpanan yang lebih andal.