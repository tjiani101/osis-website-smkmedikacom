#!/bin/bash
# Script untuk menjalankan Web OSIS dan phpMyAdmin secara bersamaan

echo "Loading..."

# Jalankan phpMyAdmin di port 8080
php -S localhost:8080 -t /home/tjiani/phpmyadmin > /dev/null 2>&1 &
PMA_PID=$!

# Trap untuk mematikan phpMyAdmin jika terminal di-close atau ditekan Ctrl+C
trap "echo ''; echo 'Menghentikan server...'; kill $PMA_PID 2>/dev/null; exit" INT TERM EXIT

echo "phpMyAdmin : http://localhost:8080"
echo "Web        : http://localhost:8000"

# Jalankan Web OSIS di port 8000
php -S localhost:8000
