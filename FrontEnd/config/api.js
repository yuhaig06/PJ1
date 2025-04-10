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
    // Store registered users in localStorage
    getRegisteredUsers() {
        const users = localStorage.getItem('registeredUsers');
        return users ? JSON.parse(users) : [];
    },

    saveRegisteredUsers(users) {
        localStorage.setItem('registeredUsers', JSON.stringify(users));
    },

    // Xóa người dùng theo email
    deleteUser(email) {
        try {
            const users = this.getRegisteredUsers();
            const userIndex = users.findIndex(user => user.email === email);
            
            if (userIndex === -1) {
                throw new Error('Không tìm thấy người dùng với email này.');
            }

            // Xóa người dùng khỏi danh sách
            users.splice(userIndex, 1);
            this.saveRegisteredUsers(users);

            // Nếu người dùng đang đăng nhập là người bị xóa, đăng xuất họ
            const currentUser = JSON.parse(localStorage.getItem('currentUser'));
            if (currentUser && currentUser.email === email) {
                localStorage.removeItem('currentUser');
            }

            return true;
        } catch (error) {
            console.error('Delete user failed:', error);
            throw error;
        }
    },

    // Lấy danh sách email của tất cả người dùng đã đăng ký
    getAllUserEmails() {
        const users = this.getRegisteredUsers();
        return users.map(user => user.email);
    },

    async register(userData) {
        try {
            // First check if email already exists
            const users = this.getRegisteredUsers();
            if (users.some(user => user.email === userData.email)) {
                throw new Error('Email đã được sử dụng. Vui lòng chọn email khác.');
            }

            // Add new user
            const newUser = {
                ...userData,
                id: Date.now(), // Simple way to generate unique ID
                createdAt: new Date().toISOString()
            };

            // Save to localStorage
            users.push(newUser);
            this.saveRegisteredUsers(users);

            // Auto login after registration
            localStorage.setItem('currentUser', JSON.stringify(newUser));
            
            console.log('Registration successful:', newUser);
            return true;
        } catch (error) {
            console.error('Registration failed:', error);
            throw error;
        }
    },

    async login(email, password) {
        // First check registered users
        const users = this.getRegisteredUsers();
        const registeredUser = users.find(user => 
            user.email === email && user.password === password
        );

        if (registeredUser) {
            console.log('Registered user login successful:', registeredUser);
            localStorage.setItem('currentUser', JSON.stringify(registeredUser));
            return true;
        }

        // If no registered user matches, try demo accounts
        const demoAccount = this.demoAccounts.find(acc => 
            acc.email === email && acc.password === password
        );
        
        if (demoAccount) {
            console.log('Demo account login successful:', demoAccount);
            localStorage.setItem('currentUser', JSON.stringify(demoAccount));
            return true;
        }

        // If no account matches
        console.log('Login failed: Invalid credentials');
        return false;
    },

    // Demo accounts for testing
    demoAccounts: [
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

    logout() {
        localStorage.removeItem('token');
        localStorage.removeItem('currentUser');
    },

    isAuthenticated() {
        return localStorage.getItem('currentUser') !== null;
    },

    getUser() {
        const user = localStorage.getItem('currentUser');
        return user ? JSON.parse(user) : null;
    }
};

// Store Service
const StoreService = {
    async getProducts() {
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
                price: 25000000,
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

    async createOrder(orderData) {
        // Mock API call để tạo order
        console.log("Order created:", orderData);
        return {
            success: true,
            message: "Order created successfully"
        };
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

// Export API
const API = {
    config: API_CONFIG,
    client: apiClient,
    auth: AuthService,
    store: StoreService,
    news: NewsService,
    games: GameService,
    users: UserService
};

// Export to global scope
window.API = API;