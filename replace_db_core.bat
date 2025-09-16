@echo off
setlocal enabledelayedexpansion

REM The search string needs to have regex special characters like ( ) and $ escaped for PowerShell's -replace operator.
set "search=new User\(\$db_core\)"
set "replace=new User($database)"

for /r "c:\xampp\htdocs\lgu4" %%f in (*.php) do (
    if exist "%%f" (
        powershell -Command "(Get-Content '%%f' -Raw) -replace '%search%', '%replace%' | Set-Content '%%f'"
    )
)

echo Replacement complete.
pause
