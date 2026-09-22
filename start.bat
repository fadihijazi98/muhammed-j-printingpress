@echo off
chcp 65001 > nul
title برنامج مطبعة محمد

cd /d "%~dp0"

set PORT=8123
set URL=http://localhost:%PORT%

where php > nul 2>&1
if errorlevel 1 (
    echo.
    echo   لم يتم العثور على PHP على هذا الجهاز.
    echo   ثبّت PHP ثم أضفه إلى PATH وأعد تشغيل هذا الملف.
    echo.
    pause
    exit /b 1
)

if not exist data mkdir data

echo.
echo   ==============================================
echo    برنامج مطبعة محمد يعمل الآن
echo.
echo    افتح المتصفح على العنوان:
echo    %URL%
echo.
echo    لإيقاف البرنامج: اضغط Control + C
echo   ==============================================
echo.

start "" "%URL%"

php -S 127.0.0.1:%PORT% -t public router.php

pause
