# P06.2 — Prototipe Submission/File Service (Mini-LMS)

## Identitas Kelompok

**Kelompok 2**

| No. | Nama Anggota | NIM |
|---|---|---|
| 1 | Mursalina | 230209500074 |
| 2 | Putri Salsabilah | 240209501045 |
| 3 | Putri Ayu Auliah | 240209501064 |

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
│   └── KONTRAK_API.md
├── postman/
│   └── Submission_File_Service.postman_collection.json
├── screenshots/
├── Jalankan_Service.bat
├── Pembagian_Kontribusi_Anggota.docx
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
|---|---|---|---|
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

Apabila valid, service mengembalikan **`201 Created`** beserta objek `data`. Identifier dibuat secara unik untuk setiap record baru sehingga nilainya dapat berbeda pada setiap pengujian.

### Bentuk Respons API

**1. Berhasil membuat submission — `201 Created`**

```json
{
  "data": {
    "submission_id": "s-0123456789abcdef",
    "file_id": "f-fedcba9876543210",
    "assignment_id": 501,
    "student_id": 101,
    "filename": "uts-mini-lms.zip",
    "file_size": 248000,
    "content_type": "application/zip",
    "status": "metadata_saved",
    "storage_mode": "metadata-only",
    "file_content_stored": false,
    "submitted_at": "2026-10-15T12:00:00Z"
  }
}
```

Nilai identifier dan waktu di atas hanya **contoh**; hasil sebenarnya dibuat oleh server. Respons POST juga menyertakan header `Location` yang menunjuk ke endpoint submission yang baru dibuat.

**2. Berhasil mengambil data — `200 OK`**

- `GET /api/submissions/{id}` mengembalikan objek `data` berisi seluruh metadata submission, seperti pada contoh POST di atas.
- `GET /api/files/{file_id}` mengembalikan objek `data` yang berisi `file_id`, `submission_id`, `filename`, `file_size`, `content_type`, `storage_mode`, `file_content_stored`, dan `message`. Endpoint ini **tidak mengunduh file fisik**.

**3. Permintaan gagal — `400`, `404`, `413`, atau `415` sesuai kondisi**

Contoh respons apabila assignment tidak ditemukan (`404 Not Found`):

```json
{
  "error": {
    "code": "assignment_not_found",
    "message": "Assignment dengan ID tersebut tidak ditemukan."
  }
}
```

Secara umum, respons sukses berisi properti `data`, sedangkan respons kesalahan berisi `error` dengan `code` dan `message`. Rincian setiap status dan kesalahan tersedia pada `docs/KONTRAK_API.md`.

## Pengujian dengan Postman

Impor file `postman/Submission_File_Service.postman_collection.json` melalui menu **Import** di Postman. Gunakan Body **raw → JSON** pada operasi POST.

| Skenario | Input/kondisi | Status yang diharapkan |
|---|---|---|
| Permintaan valid | `assignment_id: 501` dan seluruh field wajib lengkap | `201 Created` |
| Assignment tidak ditemukan | `assignment_id: 999` dengan field lainnya lengkap | `404 Not Found` |
| Input tidak lengkap | Hanya mengirim `student_id` | `400 Bad Request` |
| Baca submission | GET menggunakan `submission_id` dari POST berhasil | `200 OK` |
| Baca metadata file | GET menggunakan `file_id` dari POST berhasil | `200 OK` |

Bukti tiga skenario utama (201, 404, dan 400) disimpan sebagai tangkapan layar Postman pada folder `screenshots/`. Screenshot harus memperlihatkan method, URL, data request (jika relevan), HTTP status, dan response.

## Identifier File dan Lokasi Penyimpanan Internal

Layanan menggunakan dua identifier untuk membedakan identitas pengumpulan dengan identitas metadata file:

| Identifier | Penjelasan | Contoh |
|---|---|---|
| `submission_id` | ID unik setiap record pengumpulan tugas. Digunakan pada `GET /api/submissions/{id}`. | `s-0123456789abcdef` |
| `file_id` | ID unik metadata file yang terhubung dengan submission. Digunakan pada `GET /api/files/{file_id}`. | `f-fedcba9876543210` |
| `filename` | Nama file asli yang dinyatakan client sebagai metadata, **bukan** ID atau lokasi penyimpanan. | `uts-mini-lms.zip` |

Setelah permintaan POST dinyatakan valid, service membuat kedua identifier tersebut dan menyimpan **satu record metadata JSON** di folder internal berikut:

```text
data/submissions/<submission_id>.json
```

Contohnya, record dengan ID `s-0123456789abcdef` disimpan sebagai `data/submissions/s-0123456789abcdef.json` di dalam direktori proyek pada server. Path ini adalah **lokasi penyimpanan internal** yang digunakan aplikasi, bukan alamat untuk diakses client.

Client memperoleh `submission_id` dan `file_id` melalui response API, lalu membaca data melalui endpoint HTTP yang tersedia. **Path internal tidak disertakan dalam response API**, sehingga client tidak perlu mengetahui struktur folder server. Karena implementasi menggunakan pendekatan *metadata-only*, tidak ada file ZIP/PDF fisik yang tersimpan atau tersedia untuk diunduh.

Validasi assignment menggunakan data simulasi pada `config/assignments.json`, dengan ID yang tersedia `501` dan `502`. Batas ukuran yang diterapkan adalah **5 MiB (5.242.880 byte)** berdasarkan nilai `file_size` yang dikirim client. Tipe metadata yang diterima adalah `application/pdf` (ekstensi `.pdf`) dan `application/zip` (ekstensi `.zip`). Pemeriksaan ukuran dan tipe tersebut **tidak memverifikasi isi file fisik**, karena tidak ada konten file yang diunggah.

## Batasan Implementasi

Prototipe ini dibatasi pada penyimpanan metadata JSON lokal. Layanan belum mencakup upload/download byte file fisik, penyimpanan database produksi, autentikasi pengguna, atau pemanggilan HTTP ke Assignment Service yang sesungguhnya. Data assignment saat ini disimulasikan dengan file konfigurasi lokal.

Untuk pengembangan berikutnya, service dapat diintegrasikan dengan Assignment Service, menambahkan upload file fisik dan validasi konten, serta menerapkan kontrol akses dan penyimpanan yang lebih andal.

## Kontribusi Kelompok

Proyek dikerjakan oleh tiga anggota Kelompok 2. Tabel di bawah merupakan **contoh rancangan pembagian tugas** yang perlu disesuaikan dengan kontribusi sebenarnya sebelum README dikumpulkan.

| No. | Nama Anggota | NIM | Kontribusi (perlu dikonfirmasi) |
|---|---|---|---|
| 1 | Mursalina | 230209500074 | Implementasi endpoint POST/GET dan validasi data pada PHP. |
| 2 | Putri Salsabilah | 240209501045 | Penyusunan kontrak API dan dokumentasi struktur penyimpanan metadata. |
| 3 | Putri Ayu Auliah | 240209501064 | Pengujian menggunakan Postman, pengambilan screenshot, dan pengelolaan repository GitHub. |

**Catatan:** Ubah pembagian tugas pada tabel jika tidak sesuai dengan pekerjaan yang benar-benar dilakukan oleh masing-masing anggota.
