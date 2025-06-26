#!/bin/bash
echo "Building application locally for testing..."

# Install dependencies
echo "Installing dependencies..."
composer install --no-dev --optimize-autoloader

# Create build directory if it doesn't exist
if [ ! -d "build" ]; then
    mkdir build
fi

# Build PHAR with Box
echo "Building PHAR file..."
vendor/bin/box compile

echo "Build completed successfully!"
echo "PHAR file is available at: build/joe.phar"

# Check if php-static-cli is available
if command -v static-php-cli &> /dev/null; then
    echo "PHP-Static-CLI found, building static binary..."

    # Build static binary
    static-php-cli init
    static-php-cli config:set source.path=build/joe.phar
    static-php-cli config:set output.path=build/joe
    static-php-cli config:set php.version=8.1
    static-php-cli config:set php.extensions=phar,json,mbstring,tokenizer,ctype,fileinfo,pcntl,posix,dom,xml,simplexml,xmlwriter,xmlreader
    static-php-cli build:binary

    echo "Static binary is available at: build/joe"
else
    echo "PHP-Static-CLI not found. Only PHAR file was built."
    echo "To build a static binary, install php-static-cli and run this script again."
fi

echo ""
echo "Note: Build outputs are ignored by git and won't be committed to the repository."
