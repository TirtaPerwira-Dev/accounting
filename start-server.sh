#!/bin/bash

# Start Laravel development server with optimized memory settings
echo "🚀 Starting Laravel development server with optimized memory settings..."

# Set environment variables
export MEMORY_LIMIT=512M
export MAX_EXECUTION_TIME=300
export MAX_INPUT_VARS=3000

# Clear caches first
echo "🧹 Clearing caches..."
php -d memory_limit=512M artisan cache:clear
php -d memory_limit=512M artisan config:clear
php -d memory_limit=512M artisan view:clear
php -d memory_limit=512M artisan route:clear

# Cache configurations
echo "⚡ Caching configurations..."
php -d memory_limit=512M artisan config:cache
php -d memory_limit=512M artisan route:cache

# Start server
echo "🌐 Starting server on http://localhost:8000"
php -d memory_limit=512M -d max_execution_time=300 artisan serve --host=0.0.0.0 --port=8000
