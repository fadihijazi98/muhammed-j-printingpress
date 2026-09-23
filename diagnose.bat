@echo off
chcp 65001 > nul
title فحص برنامج مطبعة محمد  /  Diagnostics

cd /d "%~dp0"

set REPORT=%~dp0diagnose-report.txt

rem The report is written in English on purpose: it is read by whoever is
rem fixing the machine, and English survives any console code page.

echo Muhammed Printing Press - setup report > "%REPORT%"
echo Generated: %DATE% %TIME% >> "%REPORT%"
echo ======================================================== >> "%REPORT%"
echo. >> "%REPORT%"

echo [1] Folder >> "%REPORT%"
echo Running from: %~dp0 >> "%REPORT%"
if exist "public\index.php" (echo public\index.php: FOUND >> "%REPORT%") else (echo public\index.php: MISSING - folder not fully extracted >> "%REPORT%")
if exist "router.php" (echo router.php: FOUND >> "%REPORT%") else (echo router.php: MISSING >> "%REPORT%")
if exist "src\bootstrap.php" (echo src\bootstrap.php: FOUND >> "%REPORT%") else (echo src\bootstrap.php: MISSING >> "%REPORT%")
if exist "migrations\001_init.sql" (echo migrations: FOUND >> "%REPORT%") else (echo migrations: MISSING >> "%REPORT%")
echo. >> "%REPORT%"

echo [2] PHP >> "%REPORT%"
where php >> "%REPORT%" 2>&1
if errorlevel 1 (
    echo RESULT: PHP NOT FOUND IN PATH - this alone stops the program >> "%REPORT%"
    goto finish
)
php -v >> "%REPORT%" 2>&1
echo. >> "%REPORT%"

echo [3] php.ini in use >> "%REPORT%"
php --ini >> "%REPORT%" 2>&1
echo. >> "%REPORT%"

echo [4] Required extensions >> "%REPORT%"
php -r "echo 'pdo_sqlite : ' . (extension_loaded('pdo_sqlite') ? 'OK' : 'MISSING - uncomment extension=pdo_sqlite in php.ini');" >> "%REPORT%" 2>&1
echo. >> "%REPORT%"
php -r "echo 'mbstring   : ' . (extension_loaded('mbstring') ? 'OK' : 'MISSING - uncomment extension=mbstring in php.ini');" >> "%REPORT%" 2>&1
echo. >> "%REPORT%"
php -r "echo 'pdo        : ' . (extension_loaded('pdo') ? 'OK' : 'MISSING');" >> "%REPORT%" 2>&1
echo. >> "%REPORT%"
php -r "echo 'json       : ' . (extension_loaded('json') ? 'OK' : 'MISSING');" >> "%REPORT%" 2>&1
echo. >> "%REPORT%"
echo. >> "%REPORT%"

echo [5] Port 8123 >> "%REPORT%"
php -r "$s=@stream_socket_server('tcp://127.0.0.1:8123'); if($s===false){echo 'IN USE by another program - change PORT in start.bat';} else {fclose($s); echo 'free';}" >> "%REPORT%" 2>&1
echo. >> "%REPORT%"
echo. >> "%REPORT%"

echo [6] Can the app write its database folder >> "%REPORT%"
if not exist data mkdir data
echo test > "data\writetest.tmp" 2>nul
if exist "data\writetest.tmp" (
    echo data folder: WRITABLE >> "%REPORT%"
    del "data\writetest.tmp" >nul 2>&1
) else (
    echo data folder: NOT WRITABLE - move the folder out of Program Files >> "%REPORT%"
)
if exist "data\app.sqlite" (echo database file: exists >> "%REPORT%") else (echo database file: not created yet >> "%REPORT%")
echo. >> "%REPORT%"

echo [7] Can PHP actually open a database >> "%REPORT%"
php -r "try { $d = new PDO('sqlite:' . __DIR__ . '/data/probe.sqlite'); $d->exec('CREATE TABLE IF NOT EXISTS t (a int)'); echo 'SQLite works'; unset($d); @unlink(__DIR__ . '/data/probe.sqlite'); } catch (Throwable $e) { echo 'SQLite FAILED: ' . $e->getMessage(); }" >> "%REPORT%" 2>&1
echo. >> "%REPORT%"
echo. >> "%REPORT%"

echo [8] Last errors recorded by the app >> "%REPORT%"
if exist "data\error.log" (
    powershell -NoProfile -Command "Get-Content 'data\error.log' -Tail 25" >> "%REPORT%" 2>&1
) else (
    echo no error.log - the app has not recorded a crash >> "%REPORT%"
)

:finish
echo. >> "%REPORT%"
echo ======================================================== >> "%REPORT%"
echo End of report >> "%REPORT%"

cls
type "%REPORT%"

echo.
echo ========================================================
echo  تم حفظ التقرير في:  /  Report saved to:
echo  %REPORT%
echo.
echo  أرسل هذا الملف للمبرمج.  /  Send this file to the developer.
echo ========================================================
echo.
pause
