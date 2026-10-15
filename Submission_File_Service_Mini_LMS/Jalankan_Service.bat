@echo off
setlocal
cd /d "%~dp0"
if not exist "C:\xampp\php\php.exe" (
    echo [ERROR] PHP XAMPP tidak ditemukan di C:\xampp\php\php.exe
    echo Periksa lokasi instalasi XAMPP milik Anda.
    pause
    exit /b 1
)
echo ===============================================
echo  Submission/File Service - Mini-LMS - Cara 1
echo ===============================================
echo Server: http://127.0.0.1:8105
 echo Buka Postman di komputer ini; jangan tutup jendela ini.
"C:\xampp\php\php.exe" -S 127.0.0.1:8105 -t public public/index.php
if errorlevel 1 (
    echo Server gagal dijalankan. Cek port 8105 dan versi PHP.
    pause
)
