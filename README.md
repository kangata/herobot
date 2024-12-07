# Herobot App

Herobot is a platform to build chatbot AI with custom knowledge. It allows users to create, train, and deploy intelligent chatbots tailored to their specific needs, leveraging advanced natural language processing and machine learning techniques. With Herobot, you can easily integrate your chatbot into various applications and services, providing a seamless user experience and enhancing customer engagement.

## Setup Guide

To set up the Herobot App locally, follow these steps:

1. **Clone the Repository**:
   - Clone the repository to your local machine using:
     ```sh
     git clone git@github.com:dihak/app.herobot.id.git
     ```

2. **Set Up Environment Variables**:
   - Copy the `.env.example` file to `.env`:
     ```sh
     cp .env.example .env
     ```
   - Update the `DB_HOST` in the `.env` file to `mariadb` or your database host.

3. **Install Dependencies**:
   - Install the necessary Composer dependencies:
     ```sh
     composer install
     ```
   - Alternatively, you can use Docker to install Composer dependencies:
     ```sh
     docker run --rm \
       -u "$(id -u):$(id -g)" \
       -v "$(pwd):/var/www/html" \
       -w /var/www/html \
       laravelsail/php83-composer:latest \
       composer install --ignore-platform-reqs
     ```

4. **Start Services**
   ```sh
   # Start Docker containers in detached mode
   ./vendor/bin/sail up -d

   # Install NPM dependencies
   ./vendor/bin/sail npm install

   # Start Vite development server
   ./vendor/bin/sail npm run dev

   # Start Reverb WebSocket server
   ./vendor/bin/sail artisan reverb:start

   # Start WhatsApp server
   ./vendor/bin/sail artisan whatsapp:start
   ```

5. **Access the Application**:
   - The application will be accessible on port 80. Open your browser and navigate to `http://localhost`.


6. **Stopping Services**:
   ```sh
   # Stop Docker containers
   ./vendor/bin/sail down

   # If you need to stop individual services:
   # Stop Vite development server: Ctrl+C in the terminal running npm run dev
   # Stop Reverb WebSocket server: Ctrl+C in the terminal running reverb:start
   # Stop WhatsApp server: Ctrl+C in the terminal running whatsapp:start
   ```

By following these steps, you will have the Herobot App up and running on your local machine, ready for development and testing.

