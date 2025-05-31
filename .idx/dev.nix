{pkgs, ...}: {
  # Use stable channel
  channel = "stable-24.11";

  # Required packages based on README setup
  packages = [
    pkgs.git
    pkgs.nodejs
    pkgs.docker
  ];

  # Enable required services
  services = {
    # Docker for containerization (required for Sail)
    docker.enable = true;
  };

  # Workspace startup commands from README using Sail
  idx.workspace = {
    onStart = {
      # Setup environment file if not exists
      env-setup = ''
        if [ ! -f .env ]; then
          cp .env.example .env
          # Generate application key
          ./vendor/bin/sail artisan key:generate --ansi
        fi
      '';

      # Initial Sail setup with Composer
      sail-setup = ''
        docker run --rm \
          -u "$(id -u):$(id -g)" \
          -v "$(pwd):/var/www/html" \
          -w /var/www/html \
          laravelsail/php84-composer:latest \
          composer install --ignore-platform-reqs
      '';
      
      # Start Sail services in detached mode
      sail-up = "./vendor/bin/sail up -d";

      # Install NPM dependencies through Sail
      sail-npm-install = "./vendor/bin/sail npm install";
      
      # Start development servers through Sail
      sail-npm-dev = "./vendor/bin/sail npm run dev";
      sail-reverb = "./vendor/bin/sail artisan reverb:start";
      sail-whatsapp = "./vendor/bin/sail artisan whatsapp:start";
    };

    # Default files to open
    default.openFiles = [
      "README.md"
      ".env"
    ];
  };
} 