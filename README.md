# Herobot App

Herobot is your 24/7 customer service assistant that helps you manage multi-channel customer conversations effortlessly. With support for WhatsApp, WhatsApp Business, Instagram, Facebook Messenger, and TikTok (coming soon), Herobot enables businesses of all sizes to provide instant responses and superior customer service at scale.

<a href="https://studio.firebase.google.com/import?url=https://github.com/dihak/app.herobot.id">
  <picture>
    <source
      media="(prefers-color-scheme: dark)"
      srcset="https://cdn.firebasestudio.dev/btn/try_dark_32.svg">
    <source
      media="(prefers-color-scheme: light)"
      srcset="https://cdn.firebasestudio.dev/btn/try_light_32.svg">
    <img
      height="32"
      alt="Try in Firebase Studio"
      src="https://cdn.firebasestudio.dev/btn/try_blue_32.svg">
  </picture>
</a>

## Key Features

- **Multi-Channel Support**: Manage all your customer conversations from a single platform across multiple messaging channels
- **Smart Business Tools**: Seamlessly integrate with your existing tools - from shipping cost checks to Google Forms, spreadsheets, and custom API integrations
- **Instant Responses**: Provide 24/7 customer support, qualify leads automatically, and handle routine inquiries while your team focuses on high-value conversations
- **Scalable Solution**: Perfect for both small businesses and large enterprises, with the ability to manage multiple channels and teams from one dashboard

**Special Offer**: Herobot is completely FREE until July 1st, 2025! 🚀

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
       laravelsail/php84-composer:latest \
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

