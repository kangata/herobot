{pkgs, ...}: {
  channel = "stable-24.11";

  packages = [
    pkgs.git
    pkgs.nodejs
    pkgs.docker
  ];

  services = {
    # Docker for containerization (required for Sail)
    docker.enable = true;
  };

  idx.workspace = {
    onStart = {
      env-setup = ''
        if [ ! -f .env ]; then
          cp .env.example .env
        fi

        sed -i "s/^APP_URL=.*/APP_URL=https:\/\/$WEB_HOST/" .env

        echo "APP_PORT=8888" >> .env
        
        echo "SUPERVISOR_PHP_USER=root" >> .env
      '';

      docker-up = "docker compose up";

      # Default files to open when workspace starts
      default.openFiles = [
        "README.md"
      ];
    };
  };
}