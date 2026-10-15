<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$root = dirname(__DIR__);
$dataDir = $root . '/data/submissions';
$assignmentFile = $root . '/config/assignments.json';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

function respond(int $status, array $body): void {
    http_response_code($status);
    echo json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function apiError(int $status, string $code, string $message): void {
    respond($status, ['error' => ['code' => $code, 'message' => $message]]);
}

function jsonFromFile(string $file): ?array {
    if (!is_file($file)) return null;
    $raw = file_get_contents($file);
    if ($raw === false) return null;
    $result = json_decode($raw, true);
    return is_array($result) ? $result : null;
}

function publicRecord(array $record): array {
    return [
        'submission_id' => $record['submission_id'],
        'file_id' => $record['file_id'],
        'assignment_id' => $record['assignment_id'],
        'student_id' => $record['student_id'],
        'filename' => $record['filename'],
        'file_size' => $record['file_size'],
        'content_type' => $record['content_type'],
        'status' => $record['status'],
        'storage_mode' => 'metadata-only',
        'file_content_stored' => false,
        'submitted_at' => $record['submitted_at'],
    ];
}

if ($path === '/health') {
    if ($method !== 'GET') {
        header('Allow: GET');
        apiError(405, 'method_not_allowed', 'Gunakan GET untuk /health.');
    }
    respond(200, ['service' => 'submission-file-service', 'status' => 'ok', 'storage_mode' => 'metadata-only']);
}

if ($path === '/api/submissions') {
    if ($method !== 'POST') {
        header('Allow: POST');
        apiError(405, 'method_not_allowed', 'Gunakan POST untuk membuat submission.');
    }

    $contentType = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]));
    if ($contentType !== 'application/json') {
        apiError(415, 'unsupported_media_type', 'Gunakan header Content-Type: application/json.');
    }

    $raw = file_get_contents('php://input');
    $body = json_decode($raw === false ? '' : $raw, true);
    if (!is_array($body) || array_values($body) === $body) {
        apiError(400, 'invalid_json', 'Body harus berupa objek JSON yang valid.');
    }

    $required = ['assignment_id', 'student_id', 'filename', 'file_size', 'content_type'];
    foreach ($required as $key) {
        if (!array_key_exists($key, $body) || $body[$key] === null || $body[$key] === '') {
            apiError(400, 'missing_field', 'Field wajib tidak lengkap: ' . $key);
        }
    }

    if (!is_int($body['assignment_id']) || $body['assignment_id'] < 1 ||
        !is_int($body['student_id']) || $body['student_id'] < 1 ||
        !is_int($body['file_size']) || $body['file_size'] < 1 ||
        !is_string($body['filename']) || !is_string($body['content_type'])) {
        apiError(400, 'invalid_field', 'ID dan file_size harus bilangan bulat positif; filename dan content_type harus teks.');
    }

    $assignments = jsonFromFile($assignmentFile);
    if ($assignments === null || !isset($assignments['assignment_ids']) || !is_array($assignments['assignment_ids'])) {
        apiError(500, 'server_config_error', 'Konfigurasi assignment belum tersedia.');
    }
    // Pengganti/mock Assignment Service, hanya untuk demonstrasi tugas ini.
    if (!in_array($body['assignment_id'], $assignments['assignment_ids'], true)) {
        apiError(404, 'assignment_not_found', 'Assignment dengan ID tersebut tidak ditemukan.');
    }

    $filename = trim($body['filename']);
    if ($filename === '' || strlen($filename) > 150 ||
        basename(str_replace('\\', '/', $filename)) !== $filename ||
        preg_match('/[\x00-\x1F\x7F]/', $filename)) {
        apiError(400, 'invalid_filename', 'Nama file harus berupa nama sederhana tanpa path.');
    }

    $maxBytes = 5 * 1024 * 1024; // Batas contoh: 5 MiB.
    if ($body['file_size'] > $maxBytes) {
        apiError(413, 'file_too_large', 'Metadata ukuran file melebihi batas 5 MiB.');
    }
    $allowedTypes = ['application/pdf' => 'pdf', 'application/zip' => 'zip'];
    $mime = strtolower(trim($body['content_type']));
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if (!isset($allowedTypes[$mime]) || $extension !== $allowedTypes[$mime]) {
        apiError(415, 'file_type_not_allowed', 'Hanya metadata file PDF (.pdf) dan ZIP (.zip) yang diterima.');
    }

    $submissionId = 's-' . bin2hex(random_bytes(8));
    $fileId = 'f-' . bin2hex(random_bytes(8));
    $record = [
        'submission_id' => $submissionId,
        'file_id' => $fileId,
        'assignment_id' => $body['assignment_id'],
        'student_id' => $body['student_id'],
        'filename' => $filename,
        'file_size' => $body['file_size'],
        'content_type' => $mime,
        'status' => 'metadata_saved',
        'submitted_at' => gmdate('Y-m-d\TH:i:s\Z')
    ];

    if (!is_dir($dataDir) && !mkdir($dataDir, 0775, true) && !is_dir($dataDir)) {
        apiError(500, 'storage_error', 'Folder data tidak dapat dibuat.');
    }
    $serialized = json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $saveTo = $dataDir . '/' . $submissionId . '.json';
    $saved = file_put_contents($saveTo, $serialized, LOCK_EX);
    if ($saved === false) apiError(500, 'storage_error', 'Metadata gagal disimpan.');
    header('Location: /api/submissions/' . $submissionId);
    respond(201, ['data' => publicRecord($record)]);
}

if (preg_match('#^/api/submissions/(s-[a-f0-9]{16})$#', $path, $matches)) {
    if ($method !== 'GET') {
        header('Allow: GET');
        apiError(405, 'method_not_allowed', 'Gunakan GET untuk membaca submission.');
    }
    $record = jsonFromFile($dataDir . '/' . $matches[1] . '.json');
    if ($record === null) apiError(404, 'submission_not_found', 'Submission tidak ditemukan.');
    respond(200, ['data' => publicRecord($record)]);
}

if (preg_match('#^/api/files/(f-[a-f0-9]{16})$#', $path, $matches)) {
    if ($method !== 'GET') {
        header('Allow: GET');
        apiError(405, 'method_not_allowed', 'Gunakan GET untuk membaca metadata file.');
    }
    // Karena mode metadata-only, tidak ada byte file yang dapat diunduh.
    foreach (glob($dataDir . '/s-*.json') ?: [] as $candidate) {
        $record = jsonFromFile($candidate);
        if (is_array($record) && ($record['file_id'] ?? null) === $matches[1]) {
            respond(200, ['data' => [
                'file_id' => $record['file_id'],
                'submission_id' => $record['submission_id'],
                'filename' => $record['filename'],
                'file_size' => $record['file_size'],
                'content_type' => $record['content_type'],
                'storage_mode' => 'metadata-only',
                'file_content_stored' => false,
                'message' => 'Prototipe ini belum menyimpan byte file fisik.'
            ]]);
        }
    }
    apiError(404, 'file_not_found', 'Metadata file tidak ditemukan.');
}

apiError(404, 'endpoint_not_found', 'Endpoint tidak ditemukan. Periksa method dan URL.');
