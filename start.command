#!/bin/bash

# تشغيل برنامج مطبعة محمد على هذا الجهاز (macOS / Linux)

cd "$(dirname "$0")" || exit 1

PORT="${PORT:-8123}"
URL="http://localhost:$PORT"

if ! command -v php > /dev/null 2>&1; then
    echo ""
    echo "  لم يتم العثور على PHP على هذا الجهاز."
    echo "  ثبّت PHP أولاً ثم شغّل هذا الملف من جديد."
    echo "  على الماك:  brew install php"
    echo ""
    read -r -p "اضغط Enter للإغلاق..."
    exit 1
fi

if ! php -m | grep -qi "^pdo_sqlite$"; then
    echo ""
    echo "  إضافة pdo_sqlite غير مفعّلة في PHP."
    echo "  فعّلها من ملف php.ini ثم أعد التشغيل."
    echo ""
    read -r -p "اضغط Enter للإغلاق..."
    exit 1
fi

mkdir -p data

echo ""
echo "  =============================================="
echo "   برنامج مطبعة محمد يعمل الآن"
echo ""
echo "   افتح المتصفح على العنوان:"
echo "   $URL"
echo ""
echo "   لإيقاف البرنامج: اضغط Control + C"
echo "  =============================================="
echo ""

# Give the server a moment to bind the port before the browser knocks.
(
    sleep 1

    if command -v open > /dev/null 2>&1; then
        open "$URL"
    elif command -v xdg-open > /dev/null 2>&1; then
        xdg-open "$URL"
    fi
) &

php -S "127.0.0.1:$PORT" -t public router.php
