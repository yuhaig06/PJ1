// API Configuration
const API_CONFIG = {
    baseURL: 'http://localhost/PJ1/BackEnd/public/api',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
};

// API Client
const apiClient = {
    async request(endpoint, options = {}) {
        const token = localStorage.getItem('token');
        if (token) {
            options.headers = {
                ...options.headers,
                'Authorization': `Bearer ${token}`
            };
        }
        
        try {
            const response = await fetch(`${API_CONFIG.baseURL}${endpoint}`, {
                ...options,
                headers: {
                    ...API_CONFIG.headers,
                    ...options.headers
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error('API request failed:', error);
            throw error;
        }
    }
};

// Auth Service
const AuthService = {
    // Demo accounts
    accounts: [
        {
            email: "admin@gmail.com",
            password: "admin123",
            username: "Admin"
        },
        {
            email: "user@gmail.com", 
            password: "user123",
            username: "User"
        },
        {
            email: "test@gmail.com",
            password: "test123", 
            username: "Test"
        }
    ],

    async login(email, password) {
        const account = this.accounts.find(acc => 
            acc.email === email && acc.password === password
        );
        
        if (account) {
            localStorage.setItem("currentUser", JSON.stringify(account));
            return true;
        }

        try {
            const response = await apiClient.request('/auth/login', {
                method: 'POST',
                body: JSON.stringify({ email, password })
            });
            
            if (response.token) {
                localStorage.setItem('token', response.token);
                localStorage.setItem('user', JSON.stringify(response.user));
                return true;
            }
            return false;
        } catch (error) {
            console.error('Login failed:', error);
            throw error;
        }
    },
    
    async register(userData) {
        try {
            const response = await apiClient.request('/auth/register', {
                method: 'POST',
                body: JSON.stringify(userData)
            });
            
            if (response.success) {
                return await this.login(userData.email, userData.password);
            }
            return false;
        } catch (error) {
            console.error('Registration failed:', error);
            throw error;
        }
    },
    
    async forgotPassword(email) {
        try {
            const response = await apiClient.request('/auth/forgot-password', {
                method: 'POST',
                body: JSON.stringify({ email })
            });
            return response;
        } catch (error) {
            console.error('Forgot password request failed:', error);
            throw error;
        }
    },
    
    async resetPassword(token, newPassword) {
        try {
            const response = await apiClient.request('/auth/reset-password', {
                method: 'POST',
                body: JSON.stringify({ token, newPassword })
            });
            return response;
        } catch (error) {
            console.error('Password reset failed:', error);
            throw error;
        }
    },
    
    async verifyEmail(token) {
        try {
            const response = await apiClient.request('/auth/verify-email', {
                method: 'POST',
                body: JSON.stringify({ token })
            });
            return response;
        } catch (error) {
            console.error('Email verification failed:', error);
            throw error;
        }
    },
    
    logout() {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        window.location.href = '/FrontEnd/Login-Register/Login/login.html';
    },
    
    isAuthenticated() {
        return !!localStorage.getItem('token');
    },
    
    getUser() {
        const user = localStorage.getItem('user');
        return user ? JSON.parse(user) : null;
    }
};

// Store Service
const StoreService = {
    async getProducts(filters = {}) {
        try {
            const queryString = new URLSearchParams(filters).toString();
            const response = await apiClient.request(`/products?${queryString}`);
            return response.data;
        } catch (error) {
            console.error('Failed to fetch products:', error);
            throw error;
        }
    },
    
    async getProductById(id) {
        try {
            const response = await apiClient.request(`/products/${id}`);
            return response.data;
        } catch (error) {
            console.error('Failed to fetch product details:', error);
            throw error;
        }
    },
    
    async createOrder(orderData) {
        try {
            const response = await apiClient.request('/orders', {
                method: 'POST',
                body: JSON.stringify(orderData)
            });
            return response.data;
        } catch (error) {
            console.error('Failed to create order:', error);
            throw error;
        }
    },
    
    async getOrderHistory() {
        try {
            const response = await apiClient.request('/orders/history');
            return response.data;
        } catch (error) {
            console.error('Failed to fetch order history:', error);
            throw error;
        }
    },
    
    async addToCart(productId, quantity) {
        try {
            const response = await apiClient.request('/cart', {
                method: 'POST',
                body: JSON.stringify({ productId, quantity })
            });
            return response.data;
        } catch (error) {
            console.error('Failed to add to cart:', error);
            throw error;
        }
    },
    
    async getCart() {
        try {
            const response = await apiClient.request('/cart');
            return response.data;
        } catch (error) {
            console.error('Failed to fetch cart:', error);
            throw error;
        }
    },
    
    async updateCartItem(itemId, quantity) {
        try {
            const response = await apiClient.request(`/cart/${itemId}`, {
                method: 'PUT',
                body: JSON.stringify({ quantity })
            });
            return response.data;
        } catch (error) {
            console.error('Failed to update cart item:', error);
            throw error;
        }
    },
    
    async removeFromCart(itemId) {
        try {
            const response = await apiClient.request(`/cart/${itemId}`, {
                method: 'DELETE'
            });
            return response.data;
        } catch (error) {
            console.error('Failed to remove from cart:', error);
            throw error;
        }
    }
};

// News Service
const NewsService = {
    async getNews(page = 1, limit = 10, filters = {}) {
        try {
            const queryParams = {
                page,
                limit,
                ...filters
            };
            const queryString = new URLSearchParams(queryParams).toString();
            const response = await apiClient.request(`/news?${queryString}`);
            return response.data;
        } catch (error) {
            console.error('Failed to fetch news:', error);
            throw error;
        }
    },
    
    async getNewsById(id) {
        try {
            const response = await apiClient.request(`/news/${id}`);
            return response.data;
        } catch (error) {
            console.error('Failed to fetch news details:', error);
            throw error;
        }
    },
    
    async addComment(newsId, content) {
        try {
            const response = await apiClient.request(`/news/${newsId}/comments`, {
                method: 'POST',
                body: JSON.stringify({ content })
            });
            return response.data;
        } catch (error) {
            console.error('Failed to add comment:', error);
            throw error;
        }
    }
};

// Game Service
const GameService = {
    async getGames(filters = {}) {
        try {
            const queryString = new URLSearchParams(filters).toString();
            const response = await apiClient.request(`/games?${queryString}`);
            return response.data;
        } catch (error) {
            console.error('Failed to fetch games:', error);
            throw error;
        }
    },
    
    async getGameById(id) {
        try {
            const response = await apiClient.request(`/games/${id}`);
            return response.data;
        } catch (error) {
            console.error('Failed to fetch game details:', error);
            throw error;
        }
    },
    
    async addReview(gameId, rating, comment) {
        try {
            const response = await apiClient.request(`/games/${gameId}/reviews`, {
                method: 'POST',
                body: JSON.stringify({ rating, comment })
            });
            return response.data;
        } catch (error) {
            console.error('Failed to add review:', error);
            throw error;
        }
    }
};

// User Service
const UserService = {
    async updateProfile(userData) {
        try {
            const response = await apiClient.request('/users/profile', {
                method: 'PUT',
                body: JSON.stringify(userData)
            });
            return response.data;
        } catch (error) {
            console.error('Failed to update profile:', error);
            throw error;
        }
    },
    
    async getWallet() {
        try {
            const response = await apiClient.request('/users/wallet');
            return response.data;
        } catch (error) {
            console.error('Failed to fetch wallet:', error);
            throw error;
        }
    },
    
    async addFunds(amount) {
        try {
            const response = await apiClient.request('/users/wallet/add', {
                method: 'POST',
                body: JSON.stringify({ amount })
            });
            return response.data;
        } catch (error) {
            console.error('Failed to add funds:', error);
            throw error;
        }
    },
    
    async getNotifications() {
        try {
            const response = await apiClient.request('/users/notifications');
            return response.data;
        } catch (error) {
            console.error('Failed to fetch notifications:', error);
            throw error;
        }
    },
    
    async markNotificationAsRead(notificationId) {
        try {
            const response = await apiClient.request(`/users/notifications/${notificationId}/read`, {
                method: 'PUT'
            });
            return response.data;
        } catch (error) {
            console.error('Failed to mark notification as read:', error);
            throw error;
        }
    }
};

// Export all services
window.API = {
    config: API_CONFIG,
    client: apiClient,
    auth: AuthService,
    store: StoreService,
    news: NewsService,
    games: GameService,
    users: UserService
};

const API = {
    auth: {
        accounts: [
            {
                email: "admin@gmail.com",
                password: "admin123",
                username: "Admin"
            },
            {
                email: "user@gmail.com",
                password: "user123",
                username: "User"
            }
        ],

        login(email, password) {
            const account = this.accounts.find(acc => 
                acc.email === email && acc.password === password
            );
            
            if (account) {
                console.log('Login successful:', account); // Debugging log
                localStorage.setItem('currentUser', JSON.stringify(account));
                return true;
            }
            console.log('Login failed'); // Debugging log
            return false;
        },

        logout() {
            console.log('Clearing user data from localStorage'); // Debugging log
            localStorage.removeItem('currentUser');
        },

        isAuthenticated() {
            return localStorage.getItem('currentUser') !== null;
        }
    },

    store: {
        getProducts: async function() {
            return [
                {
                    id: 1,
                    name: "Gaming Mouse G502",
                    description: "Chuột gaming cao cấp với RGB và DPI tùy chỉnh",
                    price: 500000,
                    image: "gaming-mouse.webp",
                    stock: 10
                },
                {
                    id: 2, 
                    name: "Mechanical Keyboard K100",
                    description: "Bàn phím cơ chuyên game với switch Cherry MX",
                    price: 1200000,
                    image: "gaming-keyboard.avif",
                    stock: 5
                },
                {
                    id: 3,
                    name: "Gaming Headset Cloud II",  
                    description: "Tai nghe 7.1 với micro khử tiếng ồn",
                    price: 800000,
                    image: "gaming-headset.jpg",
                    stock: 8
                },
                {
                    id: 4,
                    name: "Gaming Chair DXRacer",
                    description: "Ghế gaming cao cấp với đệm êm ái",
                    price: 3500000,
                    image: "gaming-chair.jpg",
                    stock: 3
                },
                {
                    id: 5,
                    name: "Gaming Monitor 27\" 165Hz",
                    description: "Màn hình gaming 27 inch, 165Hz, 1ms, G-Sync",
                    price: 6500000,
                    image: "gaming-monitor.avif",
                    stock: 6
                },
                {
                    id: 6,
                    name: "Gaming Laptop RTX 4070",
                    description: "Laptop gaming với RTX 4070, i7, 16GB RAM, 1TB SSD",
                    price: 35000000,
                    image: "gaming-laptop.webp",
                    stock: 4
                },
                {
                    id: 7,
                    name: "Gaming Desk RGB",
                    description: "Bàn gaming cao cấp với LED RGB và quản lý dây cáp",
                    price: 2800000,
                    image: "gaming-desk.jpg",
                    stock: 7
                },
                {
                    id: 8,
                    name: "Ultimate Gaming Bundle",
                    description: "Bộ gaming full setup: Chuột + Bàn phím + Tai nghe + Mousepad",
                    price: 4500000,
                    image: "gaming-bundle.jpg",
                    stock: 3
                }
            ];
        },

        createOrder: async function(orderData) {
            // Mock API call để tạo order
            console.log("Order created:", orderData);
            return {
                success: true,
                message: "Order created successfully"
            };
        }
    }
};