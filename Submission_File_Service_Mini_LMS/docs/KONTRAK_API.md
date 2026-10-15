# KONTRAK API — Submission/File Service Mini-LMS

**Base URL:** `http://127.0.0.1:8105` | **Format:** `application/json` | **Versi:** Prototipe 1.0

## 1. GET /health
- Fungsi: cek service dapat merespons.
- Request Body: tidak ada.
- HTTP `200 OK`:
```json
{"service":"submission-file-service","status":"ok","storage_mode":"metadata-only"}
```

## 2. POST /api/submissions
- Fungsi: merekam metadata sebuah pengumpulan tugas.
- Header: `Content-Type: application/json`.
- Input:
```json
{
  "assignment_id": 501,
  "student_id": 101,
  "filename": "uts-mini-lms.zip",
  "file_size": 248000,
  "content_type": "application/zip"
}
```
- Semua field wajib. `assignment_id`, `student_id`, `file_size` adalah integer positif; dua field lainnya string. Daftar assignment **mock** valid di file konfigurasi ialah 501 dan 502. Nilai `file_size` (ukuran file yang dinyatakan dalam metadata) maksimal 5 MiB = 5.242.880 byte; tipe yang didukung `application/zip`/`.zip` dan `application/pdf`/`.pdf`.
- HTTP `201 Created` (ID/waktu nyata berbeda di setiap request):
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
- Header response `Location: /api/submissions/<submission_id>`.

## 3. GET /api/submissions/{id}
- Fungsi: mengambil kembali metadata submission berdasarkan `submission_id`.
- Contoh: `GET /api/submissions/s-0123456789abcdef`.
- Sukses: `200 OK` dengan objek `data` yang sama seperti record POST.
- Data tidak ada: `404 Not Found`.

## 4. GET /api/files/{file_id}
- Fungsi: mengambil **metadata** file berdasarkan `file_id`, **bukan mengunduh byte file**.
- Contoh: `GET /api/files/f-fedcba9876543210`.
- Sukses `200 OK`:
```json
{
  "data": {
    "file_id": "f-fedcba9876543210",
    "submission_id": "s-0123456789abcdef",
    "filename": "uts-mini-lms.zip",
    "file_size": 248000,
    "content_type": "application/zip",
    "storage_mode": "metadata-only",
    "file_content_stored": false,
    "message": "Prototipe ini belum menyimpan byte file fisik."
  }
}
```
- Tidak ada: `404 Not Found`.

## Error response
Semua error memiliki struktur `{"error":{"code":"...","message":"..."}}`.

| HTTP status | error.code | Keterangan |
|---|---|---|
| 400 | missing_field | Salah satu field wajib tidak dikirim |
| 400 | invalid_json | Body bukan objek JSON valid |
| 400 | invalid_field | Tipe atau nilai `assignment_id`, `student_id`, `file_size`, `filename`, atau `content_type` tidak sesuai |
| 400 | invalid_filename | Nama file tidak valid (misalnya mengandung path, terlalu panjang, atau karakter kontrol) |
| 404 | assignment_not_found | Assignment ID tidak tercantum pada mock |
| 404 | submission_not_found | Submission ID tidak ditemukan |
| 404 | file_not_found | File ID tidak ditemukan |
| 404 | endpoint_not_found | URL endpoint tidak terdaftar atau format path tidak sesuai |
| 413 | file_too_large | Metadata file_size > 5 MiB |
| 415 | file_type_not_allowed | Metadata tipe/ekstensi file tidak diperbolehkan |
| 415 | unsupported_media_type | Header Content-Type bukan application/json |
| 405 | method_not_allowed | Method tidak didukung pada endpoint tersebut |
| 500 | storage_error | Gagal membuat folder atau menulis metadata JSON |
| 500 | server_config_error | Konfigurasi `config/assignments.json` tidak tersedia atau tidak valid |

### Contoh 404 — assignment tidak ditemukan
```json
{"error":{"code":"assignment_not_found","message":"Assignment dengan ID tersebut tidak ditemukan."}}
```

### Contoh 400 — field wajib hilang
```json
{"error":{"code":"missing_field","message":"Field wajib tidak lengkap: assignment_id"}}
```

## Keterangan arsitektur
- Pemilik data: Submission/File Service.
- Metadata disimpan lokal di `data/submissions/<submission_id>.json`.
- Nama file asli hanya dicatat sebagai metadata; file fisik belum diunggah atau disimpan. Nilai ukuran dan tipe file berasal dari request client dan belum diverifikasi dari byte file.
- Client hanya mengetahui `submission_id` dan `file_id`, **tidak internal path**.
- Assignment Service **belum berjalan sebagai service jaringan**. Prototipe menggunakan `config/assignments.json` sebagai mock; ini perlu disebut sebagai keterbatasan, bukan diklaim sebagai integrasi antarlayanan sungguhan.
