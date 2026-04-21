@echo off
cd /d "D:\Web Dev\tssdapp\tssdapp"
php artisan schedule:run >> "D:\Web Dev\tssdapp\tssdapp\storage\logs\schedule.log" 2>&1