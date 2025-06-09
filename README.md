# Herobot App

Herobot is your 24/7 customer service assistant that helps you manage multi-channel customer conversations effortlessly. With support for WhatsApp, WhatsApp Business, Instagram, Facebook Messenger, and TikTok (coming soon), Herobot enables businesses of all sizes to provide instant responses and superior customer service at scale.

❤️ **Support this project**: If you find Herobot helpful, consider [sponsoring the project on GitHub](https://github.com/sponsors/dihak)

![Image](https://github.com/user-attachments/assets/0e5d0d9a-8aea-4501-90e3-d5396f173104)

## Deployment Options

Herobot is an open-source project that offers flexible deployment options to suit your needs:

1. **Herobot Cloud (herobot.id)**: Coming soon! Our managed cloud solution at [herobot.id](https://herobot.id) will provide a hassle-free setup with automatic updates and maintenance. Perfect for businesses that want to get started quickly without infrastructure management.

2. **Self-Hosting**: Deploy Herobot on your own infrastructure for complete control and customization. Follow our setup guide below to host it on your servers.

Both options provide the same powerful features, letting you choose the deployment that best fits your requirements and privacy needs.

## Key Features

- **Multi-Channel Support**: Manage all your customer conversations from a single platform across multiple messaging channels
- **Smart Business Tools**: Seamlessly integrate with your existing tools - from shipping cost checks to Google Forms, spreadsheets, and custom API integrations
- **Instant Responses**: Provide 24/7 customer support, qualify leads automatically, and handle routine inquiries while your team focuses on high-value conversations
- **Scalable Solution**: Perfect for both small businesses and large enterprises, with the ability to manage multiple channels and teams from one dashboard

## Setup Guide

### Quick Start Options

For a quick and easy setup without local installation, try these cloud-based options:

<a href="https://studio.firebase.google.com/import?url=https://github.com/herobot-id/herobot">
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
<a href="https://gitpod.io/#https://github.com/herobot-id/herobot">
  <img src="https://gitpod.io/button/open-in-gitpod.svg" alt="Open in Gitpod" height="32">
</a>

### Local Development Setup

To set up the Herobot App locally, follow these steps:

1. **Clone the Repository**:
    ```sh
    git clone git@github.com:herobot-id/herobot.git
    ```

2. **Set Up Environment Variables**:
    ```sh
    cp .env.example .env
    ```

3. **Start Services**
   ```sh
   # Start all services
   docker compose up
   ```
   
   This single command will automatically:
   - Install Composer dependencies
   - Install NPM dependencies
   - Start the Laravel application
   - Start the Vite development server
   - Start the Reverb WebSocket server
   - Start the WhatsApp server

4. **Access the Application**:
   - The application will be accessible on port 80. Open your browser and navigate to `http://localhost`.

5. **Stopping Services**:
   ```sh
   # Stop all Docker containers and services
   docker compose down
   ```
   
   This will automatically stop all services including the Laravel application, Vite development server, Reverb WebSocket server, and WhatsApp server.

By following these steps, you will have the Herobot App up and running on your local machine, ready for development and testing.

