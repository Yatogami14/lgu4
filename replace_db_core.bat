@echo off
setlocal enabledelayedexpansion

set "search=new User($db_core)"
set "replace=new User($database)"

for /r "c:\xampp\htdocs\lgu4" %%f in (*.php) do (
    if exist "%%f" (
        powershell -Command "(Get-Content '%%f') -replace '%search%', '%replace%' | Set-Content '%%f'"
    )
)

echo Replacement complete.
pause
