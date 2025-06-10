# Contributing to Herobot Enterprise Application

Thank you for your interest in contributing to the Herobot Enterprise Application! This guide will help you get started with development and ensure consistency across the codebase.

## 🚀 Quick Start

### Prerequisites
- PHP 8.4+
- Node.js 18+
- MySQL 8.0+
- Composer
- npm/yarn
- Docker (optional)

### Development Setup

1. **Clone the Repository**
   ```bash
   git clone git@github.com:herobot-id/herobot.git
   cd herobot
   ```

2. **Set Up Environment Variables**
   ```bash
   cp .env.example .env
   ```

3. **Start All Services**
   ```bash
   docker compose up
   ```
   
   This single command will automatically:
   - Install Composer dependencies
   - Install NPM dependencies
   - Start the Laravel application
   - Start the Vite development server
   - Start the Reverb WebSocket server
   - Start the WhatsApp server

4. **Access the Application**
   - The application will be accessible on port 80
   - Open your browser and navigate to `http://localhost`

5. **Stopping Services**
   ```bash
   docker compose down
   ```

### Manual Setup (Alternative)
If you prefer to run services manually without Docker:

```bash
# Backend setup
composer install
php artisan key:generate
php artisan migrate --seed

# Frontend setup
npm install

# Start services in separate terminals
php artisan serve          # Terminal 1: Laravel server
npm run dev                # Terminal 2: Vite dev server
php artisan reverb:start   # Terminal 3: WebSocket server
cd whatsapp-server && npm start  # Terminal 4: WhatsApp server
```

## 🏗️ Architecture Overview

### Tech Stack
- **Backend**: Laravel 12, PHP 8.4+
- **Frontend**: Vue 3, Inertia.js, Tailwind CSS
- **Database**: MySQL with Baileys for WhatsApp sessions
- **Real-time**: Laravel Reverb (WebSockets)
- **WhatsApp**: Baileys library with Express.js server
- **Build Tools**: Vite
- **Authentication**: Laravel Jetstream + Sanctum

### Project Structure
```
herobot/
├── app/                    # Laravel application code
├── resources/js/           # Vue.js frontend
│   ├── Pages/             # Inertia.js page components
│   ├── Components/        # Reusable Vue components
│   └── Layouts/           # Page layouts
├── whatsapp-server/       # Node.js WhatsApp bot server
├── database/              # Migrations, seeders, factories
├── routes/                # Laravel routes
└── tests/                 # PHPUnit tests
```

## 📋 Development Guidelines

### Code Standards

#### PHP/Laravel
- Follow PSR-12 coding standards
- Use Laravel Pint for code formatting: `./vendor/bin/pint`
- Type hints are required for all method parameters and return types
- Use proper Eloquent relationships and avoid N+1 queries
- Follow Laravel naming conventions

#### JavaScript/Vue.js
- Use ES6+ syntax
- Follow Vue 3 Composition API patterns
- Use TypeScript-style JSDoc comments for better IDE support
- Prefer `<script setup>` syntax for new components
- Use Tailwind CSS utility classes

#### Database
- Always create migrations for schema changes
- Use descriptive migration and seeder names
- Add proper foreign key constraints
- Include rollback methods in migrations

### File Naming Conventions
- **Laravel Controllers**: `PascalCase` (e.g., `UserController.php`)
- **Vue Components**: `PascalCase` (e.g., `UserProfile.vue`)
- **Vue Pages**: `PascalCase` (e.g., `Dashboard.vue`)
- **Database migrations**: `snake_case` with timestamps
- **Routes**: `kebab-case` for URLs

## 🧪 Testing

### Running Tests
```bash
# PHP tests
php artisan test
# or
./vendor/bin/phpunit

# Frontend tests (if configured)
npm run test
```

### Test Requirements
- All new features must include tests
- Controllers should have feature tests
- Models should have unit tests
- Critical business logic requires comprehensive test coverage
- WhatsApp integration should include mock tests

### Test Structure
```php
// Feature test example
class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_create_whatsapp_bot()
    {
        // Test implementation
    }
}
```

## 🔄 Git Workflow

### Branch Naming
- `feature/description` - New features
- `bugfix/description` - Bug fixes
- `hotfix/description` - Critical production fixes
- `chore/description` - Maintenance tasks

### Commit Messages
Use conventional commits format:
```
type(scope): description

feat(auth): add WhatsApp QR code authentication
fix(bot): resolve message sending timeout issue
docs(api): update WhatsApp integration documentation
chore(deps): update Laravel to version 12.x
```

### Pull Request Process

1. **Before Creating PR**
   ```bash
   # Ensure code quality
   ./vendor/bin/pint
   php artisan test
   npm run build
   ```

2. **PR Requirements**
   - Clear description of changes
   - Link to related issues
   - Screenshots for UI changes
   - Test coverage for new features
   - Documentation updates if needed

3. **PR Template**
   ```markdown
   ## Description
   Brief description of changes

   ## Type of Change
   - [ ] Bug fix
   - [ ] New feature
   - [ ] Breaking change
   - [ ] Documentation update

   ## Testing
   - [ ] Tests pass
   - [ ] Manual testing completed
   - [ ] WhatsApp integration tested (if applicable)

   ## Screenshots
   (if applicable)
   ```

## 🤖 WhatsApp Integration

### Development Guidelines
- WhatsApp server runs independently from Laravel
- Use proper session management via `mysql-baileys`
- Respect WhatsApp rate limits and terms of service
- Test with WhatsApp Business API sandbox when possible
- Handle disconnections gracefully

### Testing WhatsApp Features
```bash
# Start WhatsApp server
cd whatsapp-server
npm run dev

# Monitor logs for connection status
tail -f storage/logs/whatsapp.log
```

## 🎨 UI/UX Guidelines

### Design Principles
- Mobile-first responsive design
- Consistent use of Tailwind CSS utilities
- Accessible components (follow ARIA guidelines)
- Use Headless UI components for interactive elements
- Heroicons for consistent iconography

### Component Guidelines
```vue
<template>
  <!-- Use semantic HTML -->
  <article class="bg-white shadow rounded-lg p-6">
    <h2 class="text-xl font-semibold text-gray-900">
      {{ title }}
    </h2>
    <!-- Component content -->
  </article>
</template>

<script setup>
// Use Composition API
import { ref, computed } from 'vue'

const props = defineProps({
  title: String
})
</script>
```

## 🚀 Deployment

### Build Process
```bash
# Production build
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Environment Setup
- Copy `.env.example` to `.env`
- Configure database connections
- Set up Laravel Reverb for WebSockets
- Configure WhatsApp server environment

## 📚 Resources

### Documentation
- [Laravel Documentation](https://laravel.com/docs)
- [Vue.js Documentation](https://vuejs.org/)
- [Inertia.js Documentation](https://inertiajs.com/)
- [Tailwind CSS Documentation](https://tailwindcss.com/docs)
- [Baileys Documentation](https://github.com/WhiskeySockets/Baileys)

### Development Tools
- **Laravel Telescope**: Application debugging
- **Laravel Tinker**: Interactive PHP REPL
- **Vue DevTools**: Browser extension for Vue debugging
- **Vite**: Fast development server and build tool

## 🤝 Getting Help

### Communication Channels
- Open an issue for bugs or feature requests
- Check existing issues before creating new ones
- Use descriptive titles and provide reproduction steps

### Code Review Process
- All changes require review from at least one maintainer
- Address feedback promptly
- Keep PRs focused and reasonably sized
- Update documentation when necessary

## 📝 License

This project is licensed under the MIT License. By contributing, you agree that your contributions will be licensed under the same license.

---

Thank you for contributing to the Herobot Enterprise Application! 🎉 