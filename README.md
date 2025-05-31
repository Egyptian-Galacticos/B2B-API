# B2B API

A modern B2B (Business-to-Business) API application built with Laravel framework, featuring JWT authentication and professional development workflows.

## 🚀 Features

- **JWT Authentication**: Secure token-based authentication system
- **RESTful API**: Clean and consistent API endpoints
- **Code Quality**: Laravel Pint formatting and PHPStan static analysis
- **Pre-commit Hooks**: Automated code quality checks before commits
- **Professional Setup**: Modern development workflow with Husky and lint-staged

## 🛠️ Technologies

- **Laravel 12**: Latest Laravel framework
- **JWT Auth**: Token-based authentication
- **Laravel Pint**: Code formatting
- **PHPStan**: Static analysis
- **Husky**: Git hooks
- **SQLite**: Database (development)

## 📋 Requirements

- PHP >= 8.2
- Composer
- Node.js & npm
- SQLite (for development)

## 🔧 Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/Egyptian-Galacticos/B2B-API.git
   cd B2B-API
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**
   ```bash
   npm install
   ```

4. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   php artisan jwt:secret
   ```

5. **Database setup**
   ```bash
   touch database/database.sqlite
   php artisan migrate
   ```

6. **Install Git hooks**
   ```bash
   npm run prepare
   ```

## 🚀 Usage

### Start the development server
```bash
php artisan serve
```

### API Endpoints

#### Authentication
- `POST /api/register` - Register a new user
- `POST /api/login` - Login user
- `POST /api/logout` - Logout user (requires auth)
- `POST /api/refresh` - Refresh JWT token (requires auth)
- `GET /api/me` - Get authenticated user (requires auth)

## 🔒 Authentication

This API uses JWT (JSON Web Tokens) for authentication. Include the token in the Authorization header:

```bash
Authorization: Bearer {your-jwt-token}
```

## 🧪 Testing

```bash
# Run tests
php artisan test

# Run with coverage
php artisan test --coverage
```

## 📝 Code Quality

This project uses automated code quality tools:

- **Laravel Pint**: Automatic code formatting
- **PHPStan**: Static analysis for bug detection
- **Pre-commit hooks**: Automatic checks before commits

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Commit using conventional commits
5. Push to the branch
6. Open a Pull Request

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 👥 Team

Developed by [Egyptian Galacticos](https://github.com/Egyptian-Galacticos)

---

**Built with ❤️ using Laravel Framework**

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
