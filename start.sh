#!/bin/bash

echo "=========================================="
echo "Photo Selection App - Setup Script"
echo "=========================================="

# Create photos directory if it doesn't exist
if [ ! -d "photos" ]; then
    echo "Creating photos directory..."
    mkdir -p photos/uploads
    mkdir -p photos/final_choices
fi

# Create docker nginx config directory
if [ ! -d "docker/nginx" ]; then
    echo "Creating nginx config directory..."
    mkdir -p docker/nginx
fi

# Create nginx config if it doesn't exist
if [ ! -f "docker/nginx/default.conf" ]; then
    echo "Creating nginx configuration..."
    cat > docker/nginx/default.conf << 'EOF'
server {
    listen 80;
    index index.php index.html;
    error_log  /var/log/nginx/error.log;
    access_log /var/log/nginx/access.log;
    root /var/www/public;
    
    client_max_body_size 100M;

    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
        gzip_static on;
    }
}
EOF
fi

# Check if Laravel is already installed
if [ ! -f "composer.json" ]; then
    echo "Installing Laravel..."
    docker run --rm -v $(pwd):/app composer create-project laravel/laravel .
    
    # Set proper permissions
    chmod -R 777 storage bootstrap/cache
fi

# Build and start Docker containers
echo "Building Docker containers..."
docker-compose build

echo "Starting Docker containers..."
docker-compose up -d

# Wait for database to be ready
echo "Waiting for database to be ready..."
sleep 10

# Install dependencies if vendor doesn't exist
if [ ! -d "vendor" ]; then
    echo "Installing Composer dependencies..."
    docker-compose exec -T app composer install
fi

# Copy .env file if it doesn't exist
if [ ! -f ".env" ]; then
    echo "Creating .env file..."
    cp .env.example .env
    
    # Update database configuration
    docker-compose exec -T app php artisan key:generate
fi

# Update .env with correct database settings
echo "Configuring database settings..."
docker-compose exec -T app sed -i 's/DB_CONNECTION=.*/DB_CONNECTION=mysql/' .env
docker-compose exec -T app sed -i 's/DB_HOST=.*/DB_HOST=db/' .env
docker-compose exec -T app sed -i 's/DB_PORT=.*/DB_PORT=3306/' .env
docker-compose exec -T app sed -i 's/DB_DATABASE=.*/DB_DATABASE=photo_selection/' .env
docker-compose exec -T app sed -i 's/DB_USERNAME=.*/DB_USERNAME=photo_user/' .env
docker-compose exec -T app sed -i 's/DB_PASSWORD=.*/DB_PASSWORD=password/' .env

# Run migrations
echo "Running database migrations..."
docker-compose exec -T app php artisan migrate --force

echo "=========================================="
echo "Setup complete!"
echo "Application is running at: http://localhost:8080"
echo "=========================================="
echo ""
echo "Useful commands:"
echo "  - Stop containers: docker-compose down"
echo "  - View logs: docker-compose logs -f"
echo "  - Access app container: docker-compose exec app bash"
echo ""

