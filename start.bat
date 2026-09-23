@echo off
chcp 65001 > nul
title برنامج مطبعة محمد

cd /d "%~dp0"

set PORT=8123
set URL=http://localhost:%PORT%

echo.
echo   جاري فحص الجهاز...  /  Checking this computer...
echo.

rem ---------------------------------------------------------------- PHP ---

where php > nul 2>&1
if errorlevel 1 (
    echo   [X] PHP غير مثبّت على هذا الجهاز.
    echo       PHP is not installed, or is not in PATH.
    echo.
    echo   الحل / Fix:
    echo     1. نزّل PHP 8 من  https://windows.php.net/download/
    echo        Download PHP 8 ^(Thread Safe zip^).
    echo     2. فك الضغط إلى  C:\php
    echo        Extract it to C:\php
    echo     3. أضف C:\php إلى متغيّر PATH ثم أعد تشغيل هذا الملف.
    echo        Add C:\php to PATH, then run this file again.
    echo.
    pause
    exit /b 1
)

rem ------------------------------------------------------- extensions ---

rem Windows ships php.ini with these two commented out, and the app cannot
rem run without them: the database needs pdo_sqlite, Arabic text needs mbstring.

php -r "exit(extension_loaded('pdo_sqlite') ? 0 : 1);"
if errorlevel 1 goto missing_sqlite

php -r "exit(extension_loaded('mbstring') ? 0 : 1);"
if errorlevel 1 goto missing_mbstring

rem ------------------------------------------------------------- port ---

php -r "$s=@stream_socket_server('tcp://127.0.0.1:%PORT%'); if($s===false){exit(1);} fclose($s); exit(0);"
if errorlevel 1 (
    echo   [X] المنفذ %PORT% مشغول ببرنامج آخر.
    echo       Port %PORT% is already in use.
    echo.
    echo   الحل / Fix:
    echo     افتح هذا الملف بالمفكرة، وغيّر الرقم %PORT% إلى 8124 ثم احفظ وأعد التشغيل.
    echo     Open this file in Notepad, change %PORT% to 8124, save, run again.
    echo.
    pause
    exit /b 1
)

rem ------------------------------------------------------------ files ---

if not exist "public\index.php" (
    echo   [X] ملفات البرنامج ناقصة.
    echo       Program files are missing ^(public\index.php not found^).
    echo.
    echo   تأكد من فك ضغط المجلد بالكامل، وأن هذا الملف بداخله.
    echo   Make sure the folder was fully extracted and this file sits inside it.
    echo.
    pause
    exit /b 1
)

if not exist data mkdir data

rem ------------------------------------------------------------- run ---

echo   [OK] PHP موجود / found
echo   [OK] قاعدة البيانات جاهزة / database driver ready
echo   [OK] اللغة العربية جاهزة / mbstring ready
echo.
echo   ==============================================
echo    برنامج مطبعة محمد يعمل الآن
echo.
echo    افتح المتصفح على العنوان:
echo    %URL%
echo.
echo    لإيقاف البرنامج: أغلق هذه النافذة
echo   ==============================================
echo.

rem The browser is opened a few seconds late, otherwise it reaches the port
rem before PHP has finished binding it and shows "cannot connect".
start "" /min powershell -NoProfile -WindowStyle Hidden -Command "Start-Sleep -Seconds 3; Start-Process '%URL%'"

php -S 127.0.0.1:%PORT% -t public router.php

echo.
echo   توقّف البرنامج. / The program stopped.
pause
exit /b 0

rem -------------------------------------------------------- messages ---

:missing_sqlite
echo   [X] إضافة pdo_sqlite غير مفعّلة في PHP.
echo       The pdo_sqlite extension is turned off in PHP.
echo.
echo   الحل / Fix:
echo     1. افتح ملف php.ini الظاهر بالأسفل بالمفكرة.
echo        Open the php.ini shown below in Notepad.
echo     2. ابحث عن السطر:  ;extension=pdo_sqlite
echo        Find the line:  ;extension=pdo_sqlite
echo     3. احذف الفاصلة المنقوطة من أوله ليصبح:  extension=pdo_sqlite
echo        Remove the leading semicolon so it reads: extension=pdo_sqlite
echo     4. احفظ الملف وأعد تشغيل هذا الملف.
echo        Save the file and run this file again.
echo.
php --ini
echo.
pause
exit /b 1

:missing_mbstring
echo   [X] إضافة mbstring غير مفعّلة في PHP.
echo       The mbstring extension is turned off in PHP.
echo.
echo   الحل / Fix:
echo     في ملف php.ini الظاهر بالأسفل، احذف الفاصلة المنقوطة من السطر:
echo     In the php.ini shown below, remove the leading semicolon from:
echo        ;extension=mbstring    ==^>    extension=mbstring
echo     ثم احفظ وأعد تشغيل هذا الملف.
echo     Then save and run this file again.
echo.
php --ini
echo.
pause
exit /b 1
