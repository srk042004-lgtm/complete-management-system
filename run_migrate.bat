@echo off
php -v
php artisan migrate --force > migrate_output.txt 2>&1
if %ERRORLEVEL% neq 0 (
  echo MIGRATION FAILED
) else (
  echo MIGRATION SUCCEEDED
)
type migrate_output.txt
