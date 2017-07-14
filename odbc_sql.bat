@echo off

REM Download 7z for Windows
IF EXIST "%~dp07z\7z\Files\7-Zip\7z.exe" GOTO DOWNLOAD_PHP
IF EXIST "%~dp07z.msi" GOTO DOWNLOAD_PHP
echo Download 7z for Windows
cscript /nologo  "%~dp0wget.vbs" "http://www.7-zip.org/a/7z1604-x64.msi"  "%~dp07z.msi"

:DOWNLOAD_PHP
REM Download php for Windows
IF EXIST "%~dp0php\php.exe" GOTO EXTRACT_7Z
IF EXIST "%~dp0php.zip" GOTO EXTRACT_7Z
echo Download php for Windows
cscript /nologo  "%~dp0wget.vbs" "http://windows.php.net/downloads/releases/php-7.1.7-nts-Win32-VC14-x64.zip"  "%~dp0php.zip"

:EXTRACT_7Z
REM Extract 7z for Windows
IF EXIST "%~dp07z\Files\7-Zip\7z.exe" GOTO EXTRACT_PHP
echo Extract 7z for Windows
msiexec /a  "%~dp07z.msi" /qb TARGETDIR="%~dp07z"

:EXTRACT_PHP
REM Extract php for Windows
IF EXIST "%~dp0php\php.exe" GOTO ODBC_CMD
echo Extract php for Windows
"%~dp07z\Files\7-Zip\7z.exe" e "%~dp0php.zip" -o"%~dp0php"

:ODBC_CMD
 "%~dp0php\php.exe" -f  "%~dp0odbc_sql.php" -- %* 