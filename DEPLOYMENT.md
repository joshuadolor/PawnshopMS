# Docker Deployment Guide

## Quick Start

### On Your VPS:

1. **Install Docker** (if not already installed):
```bash
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo apt-get install docker-compose-plugin -y
```

2. **Upload your project** to the VPS

3. **Run the deployment script**:
```bash
./deploy.sh
```

That's it! The script will:
- Check for Docker installation
- Create .env file if needed
- Build the Docker image
- Start the container
- Run migrations
- Optimize Laravel
- Show you the status

## Access Your Application

Once deployed, access it at: **http://your-vps-ip:8800**

## Manual Commands

If you prefer to run commands manually:

```bash
# Build and start
docker compose up -d --build

# Run migrations
docker compose exec app php artisan migrate --force

# Run seeders
docker compose exec app php artisan db:seed --force

# View logs
docker compose logs -f

# Stop
docker compose down

# Restart
docker compose restart
```

## Environment Configuration

Make sure your `.env` file has:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=http://your-vps-ip:8800

# Database (SQLite for testing)
DB_CONNECTION=sqlite
```

## Firewall

Don't forget to open port 8800:
```bash
sudo ufw allow 8800/tcp
sudo ufw reload
```

## Troubleshooting

- **View logs**: `docker compose logs -f`
- **Container shell**: `docker compose exec app bash`
- **Check status**: `docker compose ps`
- **Rebuild**: `docker compose up -d --build --force-recreate`

### Fix Vite Permission Issues

If you get "Permission denied" when running `npm run build`:

```bash
# Fix node_modules permissions inside container
docker compose exec app chmod -R +x node_modules/.bin

# Or reinstall node_modules
docker compose exec app rm -rf node_modules
docker compose exec app npm install
docker compose exec app chmod -R +x node_modules/.bin
docker compose exec app npm run build
```