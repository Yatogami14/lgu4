@echo off
setlocal enabledelayedexpansion

echo Starting replacement of User instances...

REM Replace all instances of "new User($db_core)" with "new User($database)"
for /r %%f in (*.php) do (
    if exist "%%f" (
        echo Processing %%f
        powershell -Command "(Get-Content '%%f') -replace '\$user = new User\(\$db_core\);', '$user = new User($database);' | Set-Content '%%f'"
        powershell -Command "(Get-Content '%%f') -replace '\$newUser = new User\(\$db_core\);', '$newUser = new User($database);' | Set-Content '%%f'"
        powershell -Command "(Get-Content '%%f') -replace '\$updateUser = new User\(\$db_core\);', '$updateUser = new User($database);' | Set-Content '%%f'"
        powershell -Command "(Get-Content '%%f') -replace '\$deleteUser = new User\(\$db_core\);', '$deleteUser = new User($database);' | Set-Content '%%f'"
        powershell -Command "(Get-Content '%%f') -replace '\$resetUser = new User\(\$db_core\);', '$resetUser = new User($database);' | Set-Content '%%f'"
        powershell -Command "(Get-Content '%%f') -replace '\$newInspector = new User\(\$db_core\);', '$newInspector = new User($database);' | Set-Content '%%f'"
        powershell -Command "(Get-Content '%%f') -replace '\$updateInspector = new User\(\$db_core\);', '$updateInspector = new User($database);' | Set-Content '%%f'"
        powershell -Command "(Get-Content '%%f') -replace '\$deleteInspector = new User\(\$db_core\);', '$deleteInspector = new User($database);' | Set-Content '%%f'"
        powershell -Command "(Get-Content '%%f') -replace '\$inspector = new User\(\$db_core\);', '$inspector = new User($database);' | Set-Content '%%f'"
        powershell -Command "(Get-Content '%%f') -replace '\$inspectorUser = new User\(\$db_core\);', '$inspectorUser = new User($database);' | Set-Content '%%f'"
        powershell -Command "(Get-Content '%%f') -replace '\$adminUserModel = new User\(\$db_core\);', '$adminUserModel = new User($database);' | Set-Content '%%f'"
    )
)

echo Replacement completed!
pause
