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

REM Check if php-static-cli is available
where static-php-cli >nul 2>nul
if %ERRORLEVEL% EQU 0 (
    echo PHP-Static-CLI found, building static binary...

    REM Build static binary
    static-php-cli init
    static-php-cli config:set source.path=build/joe.phar
    static-php-cli config:set output.path=build/joe
    static-php-cli config:set php.version=8.4
    static-php-cli config:set php.extensions=phar,json,mbstring,tokenizer,ctype,fileinfo,pcntl,posix,dom,xml,simplexml,xmlwriter,xmlreader
    static-php-cli build:binary

    echo Static binary is available at: build\joe
) else (
    echo PHP-Static-CLI not found. Only PHAR file was built.
    echo To build a static binary, install php-static-cli and run this script again.
)

echo.
echo Note: Build outputs are ignored by git and won't be committed to the repository.
