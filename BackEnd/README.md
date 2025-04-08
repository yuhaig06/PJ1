# GameStore Backend

A robust backend API for the GameStore application, providing services for user authentication, product management, order processing, and more.

## Features

- **User Authentication**: JWT-based authentication with refresh tokens
- **Product Management**: CRUD operations for game products
- **Order Processing**: Handle orders and payments
- **Wallet System**: Manage user wallets and transactions
- **News & Articles**: Manage gaming news and articles
- **Esports Coverage**: Track esports tournaments and teams
- **Admin Dashboard**: Comprehensive admin interface
- **Security Features**: CSRF protection, rate limiting, and more
- **Caching**: Redis-based caching for improved performance
- **Monitoring**: System monitoring and error tracking

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Redis (optional, for caching)
- Composer
- Apache/Nginx web server

## Installation

1. Clone the repository:
   ```
   git clone https://github.com/yourusername/game-store.git
   cd game-store/BackEnd
   ```

2. Install dependencies:
   ```
   composer install
   ```

3. Copy the environment file:
   ```
   cp .env.example .env
   ```

4. Configure your environment variables in the `.env` file.

5. Create the database:
   ```
   mysql -u root -p
   CREATE DATABASE game_store;
   exit;
   ```

6. Run migrations:
   ```
   php app/database/migrate.php
   ```

7. Start the development server:
   ```
   php -S localhost:8000 -t public
   ```

## API Endpoints

### Authentication

- `POST /api/auth/login`: User login
- `POST /api/auth/register`: User registration
- `POST /api/auth/refresh`: Refresh token
- `POST /api/auth/forgot-password`: Request password reset
- `POST /api/auth/reset-password`: Reset password

### Users

- `GET /api/users/profile`: Get user profile
- `PUT /api/users/profile`: Update user profile
- `GET /api/users/wallet`: Get user wallet
- `POST /api/users/wallet/add-funds`: Add funds to wallet
- `GET /api/users/notifications`: Get user notifications
- `PUT /api/users/notifications/{id}`: Mark notification as read

### Products

- `GET /api/products`: Get all products
- `GET /api/products/{id}`: Get product details
- `POST /api/products`: Create product (admin only)
- `PUT /api/products/{id}`: Update product (admin only)
- `DELETE /api/products/{id}`: Delete product (admin only)

### Orders

- `GET /api/orders`: Get user orders
- `GET /api/orders/{id}`: Get order details
- `POST /api/orders`: Create order
- `PUT /api/orders/{id}/cancel`: Cancel order

### News

- `GET /api/news`: Get all news
- `GET /api/news/{id}`: Get news details
- `POST /api/news`: Create news (admin only)
- `PUT /api/news/{id}`: Update news (admin only)
- `DELETE /api/news/{id}`: Delete news (admin only)

### Games

- `GET /api/games`: Get all games
- `GET /api/games/{id}`: Get game details
- `POST /api/games`: Create game (admin only)
- `PUT /api/games/{id}`: Update game (admin only)
- `DELETE /api/games/{id}`: Delete game (admin only)

### Esports

- `GET /api/esports`: Get all esports tournaments
- `GET /api/esports/{id}`: Get tournament details
- `POST /api/esports`: Create tournament (admin only)
- `PUT /api/esports/{id}`: Update tournament (admin only)
- `DELETE /api/esports/{id}`: Delete tournament (admin only)

## Testing

Run the test suite:

```
./vendor/bin/phpunit
```

## Security

- All API endpoints are protected with JWT authentication
- CSRF protection is enabled for web forms
- Rate limiting is implemented to prevent abuse
- Input validation is performed on all requests
- Passwords are hashed using bcrypt
- Sensitive data is encrypted

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.
