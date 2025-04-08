# Game Store API Documentation

## Base URL
```
http://localhost:8000/api
```

## Authentication
Tất cả các API endpoints (trừ login, register, forgot-password) đều yêu cầu JWT token trong header:
```
Authorization: Bearer <token>
```

## API Endpoints

### Authentication

#### Login
```http
POST /auth/login
```
Request body:
```json
{
    "email": "user@example.com",
    "password": "password123"
}
```
Response:
```json
{
    "status": "success",
    "data": {
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "user": {
            "id": 1,
            "username": "user1",
            "email": "user@example.com",
            "role": "user"
        }
    }
}
```

#### Register
```http
POST /auth/register
```
Request body:
```json
{
    "username": "newuser",
    "email": "newuser@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "full_name": "New User"
}
```
Response:
```json
{
    "status": "success",
    "message": "Registration successful"
}
```

#### Forgot Password
```http
POST /auth/forgot-password
```
Request body:
```json
{
    "email": "user@example.com"
}
```
Response:
```json
{
    "status": "success",
    "message": "Password reset link sent to your email"
}
```

### Games

#### Get All Games
```http
GET /games
```
Query parameters:
- `page`: Số trang (default: 1)
- `per_page`: Số items mỗi trang (default: 10)
- `category`: ID danh mục
- `tag`: ID tag
- `search`: Từ khóa tìm kiếm
- `sort`: Sắp xếp (price_asc, price_desc, name_asc, name_desc)

Response:
```json
{
    "status": "success",
    "data": {
        "games": [
            {
                "id": 1,
                "name": "The Witcher 3",
                "slug": "the-witcher-3",
                "description": "...",
                "thumbnail": "games/witcher3.jpg",
                "price": 29.99,
                "category": {
                    "id": 1,
                    "name": "RPG"
                },
                "tags": [
                    {
                        "id": 1,
                        "name": "Action"
                    }
                ]
            }
        ],
        "pagination": {
            "total": 100,
            "per_page": 10,
            "current_page": 1,
            "last_page": 10
        }
    }
}
```

#### Get Game Detail
```http
GET /games/{slug}
```
Response:
```json
{
    "status": "success",
    "data": {
        "game": {
            "id": 1,
            "name": "The Witcher 3",
            "slug": "the-witcher-3",
            "description": "...",
            "content": "...",
            "thumbnail": "games/witcher3.jpg",
            "price": 29.99,
            "category": {
                "id": 1,
                "name": "RPG"
            },
            "tags": [
                {
                    "id": 1,
                    "name": "Action"
                }
            ],
            "comments": [
                {
                    "id": 1,
                    "content": "Great game!",
                    "user": {
                        "id": 1,
                        "username": "user1"
                    },
                    "created_at": "2024-01-01 12:00:00"
                }
            ]
        }
    }
}
```

### News

#### Get All News
```http
GET /news
```
Query parameters:
- `page`: Số trang (default: 1)
- `per_page`: Số items mỗi trang (default: 10)
- `category`: ID danh mục
- `tag`: ID tag
- `search`: Từ khóa tìm kiếm
- `sort`: Sắp xếp (latest, oldest)

Response:
```json
{
    "status": "success",
    "data": {
        "news": [
            {
                "id": 1,
                "title": "Game News Title",
                "slug": "game-news-title",
                "content": "...",
                "thumbnail": "news/image.jpg",
                "category": {
                    "id": 1,
                    "name": "Game News"
                },
                "author": {
                    "id": 1,
                    "username": "admin"
                },
                "created_at": "2024-01-01 12:00:00"
            }
        ],
        "pagination": {
            "total": 100,
            "per_page": 10,
            "current_page": 1,
            "last_page": 10
        }
    }
}
```

#### Get News Detail
```http
GET /news/{slug}
```
Response:
```json
{
    "status": "success",
    "data": {
        "news": {
            "id": 1,
            "title": "Game News Title",
            "slug": "game-news-title",
            "content": "...",
            "thumbnail": "news/image.jpg",
            "category": {
                "id": 1,
                "name": "Game News"
            },
            "tags": [
                {
                    "id": 1,
                    "name": "Release"
                }
            ],
            "author": {
                "id": 1,
                "username": "admin"
            },
            "created_at": "2024-01-01 12:00:00"
        }
    }
}
```

### User Profile

#### Get Profile
```http
GET /profile
```
Response:
```json
{
    "status": "success",
    "data": {
        "user": {
            "id": 1,
            "username": "user1",
            "email": "user@example.com",
            "full_name": "User One",
            "wallet": {
                "balance": 100.00,
                "status": "active"
            },
            "orders": [
                {
                    "id": 1,
                    "total_amount": 29.99,
                    "status": "completed",
                    "created_at": "2024-01-01 12:00:00"
                }
            ]
        }
    }
}
```

#### Update Profile
```http
PUT /profile
```
Request body:
```json
{
    "full_name": "Updated Name",
    "email": "newemail@example.com",
    "current_password": "current123",
    "new_password": "new123",
    "new_password_confirmation": "new123"
}
```
Response:
```json
{
    "status": "success",
    "message": "Profile updated successfully"
}
```

### Wallet

#### Get Wallet
```http
GET /wallet
```
Response:
```json
{
    "status": "success",
    "data": {
        "wallet": {
            "balance": 100.00,
            "status": "active",
            "transactions": [
                {
                    "id": 1,
                    "type": "deposit",
                    "amount": 100.00,
                    "status": "completed",
                    "created_at": "2024-01-01 12:00:00"
                }
            ]
        }
    }
}
```

#### Deposit
```http
POST /wallet/deposit
```
Request body:
```json
{
    "amount": 100.00,
    "payment_method": "credit_card",
    "payment_details": {
        "card_number": "4111111111111111",
        "expiry": "12/25",
        "cvv": "123"
    }
}
```
Response:
```json
{
    "status": "success",
    "data": {
        "transaction": {
            "id": 1,
            "type": "deposit",
            "amount": 100.00,
            "status": "completed",
            "created_at": "2024-01-01 12:00:00"
        }
    }
}
```

### Orders

#### Create Order
```http
POST /orders
```
Request body:
```json
{
    "items": [
        {
            "game_id": 1,
            "quantity": 1
        }
    ],
    "payment_method": "wallet"
}
```
Response:
```json
{
    "status": "success",
    "data": {
        "order": {
            "id": 1,
            "total_amount": 29.99,
            "status": "completed",
            "items": [
                {
                    "game_id": 1,
                    "name": "The Witcher 3",
                    "price": 29.99
                }
            ],
            "created_at": "2024-01-01 12:00:00"
        }
    }
}
```

#### Get Orders
```http
GET /orders
```
Query parameters:
- `page`: Số trang (default: 1)
- `per_page`: Số items mỗi trang (default: 10)
- `status`: Trạng thái đơn hàng

Response:
```json
{
    "status": "success",
    "data": {
        "orders": [
            {
                "id": 1,
                "total_amount": 29.99,
                "status": "completed",
                "items": [
                    {
                        "game_id": 1,
                        "name": "The Witcher 3",
                        "price": 29.99
                    }
                ],
                "created_at": "2024-01-01 12:00:00"
            }
        ],
        "pagination": {
            "total": 10,
            "per_page": 10,
            "current_page": 1,
            "last_page": 1
        }
    }
}
```

### Comments

#### Create Comment
```http
POST /comments
```
Request body:
```json
{
    "game_id": 1,
    "content": "Great game!"
}
```
Response:
```json
{
    "status": "success",
    "data": {
        "comment": {
            "id": 1,
            "content": "Great game!",
            "user": {
                "id": 1,
                "username": "user1"
            },
            "created_at": "2024-01-01 12:00:00"
        }
    }
}
```

#### Get Comments
```http
GET /comments
```
Query parameters:
- `game_id`: ID game
- `page`: Số trang (default: 1)
- `per_page`: Số items mỗi trang (default: 10)

Response:
```json
{
    "status": "success",
    "data": {
        "comments": [
            {
                "id": 1,
                "content": "Great game!",
                "user": {
                    "id": 1,
                    "username": "user1"
                },
                "created_at": "2024-01-01 12:00:00"
            }
        ],
        "pagination": {
            "total": 100,
            "per_page": 10,
            "current_page": 1,
            "last_page": 10
        }
    }
}
```

### Reports

#### Create Report
```http
POST /reports
```
Request body:
```json
{
    "target_type": "comment",
    "target_id": 1,
    "reason": "spam",
    "description": "This comment is spam"
}
```
Response:
```json
{
    "status": "success",
    "message": "Report submitted successfully"
}
```

## Error Responses

### Validation Error
```json
{
    "status": "error",
    "message": "The given data was invalid",
    "errors": {
        "email": [
            "The email field is required"
        ],
        "password": [
            "The password must be at least 8 characters"
        ]
    }
}
```

### Authentication Error
```json
{
    "status": "error",
    "message": "Unauthorized"
}
```

### Not Found Error
```json
{
    "status": "error",
    "message": "Resource not found"
}
```

### Server Error
```json
{
    "status": "error",
    "message": "Internal server error"
}
``` 