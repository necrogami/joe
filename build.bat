@echo off
echo Building application locally for testing...

REM Install dependencies
echo Installing dependencies...
composer install --no-dev --optimize-autoloader

REM Create build directory if it doesn't exist
if not exist build mkdir build

REM Build PHAR with Box
echo Building PHAR file...
vendor\bin\box compile

echo Build completed successfully!
echo PHAR file is available at: build\joe.phar

REM Check if spc is available
where spc >nul 2>nul
if %ERRORLEVEL% EQU 0 (
    echo spc (static-php-cli) found, building static binary...

    REM Check system dependencies
    echo Checking system dependencies...
    spc doctor --auto-fix

    REM Download PHP and extensions
    echo Downloading PHP and extensions...
    spc download --for-extensions="phar,json,mbstring,tokenizer,ctype,fileinfo,pcntl,posix,dom,xml,simplexml,xmlwriter,xmlreader" --with-php=8.4 --prefer-pre-built

    REM Build static binary with micro SAPI
    echo Building static binary with micro SAPI...
    spc build "phar,json,mbstring,tokenizer,ctype,fileinfo,pcntl,posix,dom,xml,simplexml,xmlwriter,xmlreader" --build-micro --with-upx-pack

    REM Combine micro.sfx with the PHAR file
    echo Combining micro.sfx with PHAR file...
    spc micro:combine build\joe.phar

    REM Move the combined binary to the correct location
    move .\micro.sfx build\joe.exe

    echo Static binary is available at: build\joe.exe
) else (
    echo spc (static-php-cli) not found. Only PHAR file was built.
    echo To build a static binary, download spc from https://dl.static-php.dev/static-php-cli/spc-bin/nightly/ and run this script again.
)

echo.
echo Note: Build outputs are ignored by git and won't be committed to the repository.
