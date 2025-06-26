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

# Check if spc is available
if command -v spc &> /dev/null; then
    echo "spc (static-php-cli) found, building static binary..."

    # Check system dependencies
    echo "Checking system dependencies..."
    spc doctor --auto-fix

    # Download PHP and extensions
    echo "Downloading PHP and extensions..."
    spc download --for-extensions="phar,json,mbstring,tokenizer,ctype,fileinfo,pcntl,posix,dom,xml,simplexml,xmlwriter,xmlreader" --with-php=8.4 --prefer-pre-built

    # Build static binary with micro SAPI
    echo "Building static binary with micro SAPI..."
    spc build "phar,json,mbstring,tokenizer,ctype,fileinfo,pcntl,posix,dom,xml,simplexml,xmlwriter,xmlreader" --build-micro --with-upx-pack

    # Combine micro.sfx with the PHAR file
    echo "Combining micro.sfx with PHAR file..."
    spc micro:combine build/joe.phar

    # Move the combined binary to the correct location
    mv ./micro.sfx build/joe

    echo "Static binary is available at: build/joe"
else
    echo "PHP-Static-CLI not found. Only PHAR file was built."
    echo "To build a static binary, download spc from https://dl.static-php.dev/static-php-cli/spc-bin/nightly/ and run this script again."
fi

echo ""
echo "Note: Build outputs are ignored by git and won't be committed to the repository."
