#!/bin/sh
# Nginx entrypoint script to handle environment variable substitution

# Set default value for ACTIVE_FRONTEND if not provided
export ACTIVE_FRONTEND=${ACTIVE_FRONTEND:-blue}

echo "Starting Nginx with active frontend: $ACTIVE_FRONTEND"

# Create the actual config from template
cat > /etc/nginx/conf.d/default.conf << 'EOF'
# Blue-Green Deployment Configuration for Frontend
# Active frontend is controlled by ACTIVE_FRONTEND environment variable

upstream backend {
    server backend:8080;
}

server {
    listen 80;
    server_name _;

    # Dynamic root based on active frontend (set via environment)
    set $frontend_root /usr/share/nginx/html/ACTIVE_FRONTEND_PLACEHOLDER;

    # API v1 requests proxy to backend
    location /api/v1/ {
        proxy_pass http://backend/api/v1/;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
    }

    # Static files (frontend)
    location / {
        root $frontend_root;
        try_files $uri $uri/ /index.html;
    }

    # Health check endpoint
    location /health {
        access_log off;
        return 200 "healthy\n";
        add_header Content-Type text/plain;
    }

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied any;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/json application/xml;
}
EOF

# Replace placeholder with actual environment variable value
sed -i "s/ACTIVE_FRONTEND_PLACEHOLDER/$ACTIVE_FRONTEND/g" /etc/nginx/conf.d/default.conf

# Start nginx
exec nginx -g 'daemon off;'
