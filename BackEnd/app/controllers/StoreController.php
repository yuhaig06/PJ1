<?php

namespace App\Controllers;

use App\Models\ProductModel;
use App\Models\OrderModel;
use App\Models\CartModel;
use App\Config\Database;

class StoreController {
    private $db;
    private $productModel;
    private $orderModel;
    private $cartModel;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->productModel = new ProductModel($this->db);
        $this->orderModel = new OrderModel($this->db);
        $this->cartModel = new CartModel($this->db);
    }

    public function getProducts() {
        try {
            $filters = $_GET;
            $products = $this->productModel->getAll($filters);
            return $this->jsonResponse(['success' => true, 'data' => $products]);
        } catch (\Exception $e) {
            return $this->jsonResponse(['success' => false, 'message' => 'Failed to fetch products'], 500);
        }
    }

    public function getProductById($id) {
        try {
            $product = $this->productModel->getById($id);
            
            if (!$product) {
                return $this->jsonResponse(['success' => false, 'message' => 'Product not found'], 404);
            }
            
            return $this->jsonResponse(['success' => true, 'data' => $product]);
        } catch (\Exception $e) {
            return $this->jsonResponse(['success' => false, 'message' => 'Failed to fetch product'], 500);
        }
    }

    public function addToCart() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $userId = $_SESSION['user_id'] ?? null;
            
            if (!$userId) {
                return $this->jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            
            if (!isset($data['productId']) || !isset($data['quantity'])) {
                return $this->jsonResponse(['success' => false, 'message' => 'Product ID and quantity are required'], 400);
            }
            
            $product = $this->productModel->getById($data['productId']);
            
            if (!$product) {
                return $this->jsonResponse(['success' => false, 'message' => 'Product not found'], 404);
            }
            
            if ($product['stock'] < $data['quantity']) {
                return $this->jsonResponse(['success' => false, 'message' => 'Not enough stock'], 400);
            }
            
            $cartItem = $this->cartModel->addItem($userId, $data['productId'], $data['quantity']);
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Product added to cart',
                'data' => $cartItem
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse(['success' => false, 'message' => 'Failed to add to cart'], 500);
        }
    }

    public function getCart() {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            
            if (!$userId) {
                return $this->jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            
            $cart = $this->cartModel->getCart($userId);
            
            return $this->jsonResponse([
                'success' => true,
                'data' => $cart
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse(['success' => false, 'message' => 'Failed to fetch cart'], 500);
        }
    }

    public function updateCartItem() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $userId = $_SESSION['user_id'] ?? null;
            
            if (!$userId) {
                return $this->jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            
            if (!isset($data['itemId']) || !isset($data['quantity'])) {
                return $this->jsonResponse(['success' => false, 'message' => 'Item ID and quantity are required'], 400);
            }
            
            $cartItem = $this->cartModel->getItem($data['itemId']);
            
            if (!$cartItem || $cartItem['user_id'] !== $userId) {
                return $this->jsonResponse(['success' => false, 'message' => 'Cart item not found'], 404);
            }
            
            $product = $this->productModel->getById($cartItem['product_id']);
            
            if ($product['stock'] < $data['quantity']) {
                return $this->jsonResponse(['success' => false, 'message' => 'Not enough stock'], 400);
            }
            
            $updatedItem = $this->cartModel->updateItem($data['itemId'], $data['quantity']);
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Cart item updated',
                'data' => $updatedItem
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse(['success' => false, 'message' => 'Failed to update cart item'], 500);
        }
    }

    public function removeFromCart($itemId) {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            
            if (!$userId) {
                return $this->jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            
            $cartItem = $this->cartModel->getItem($itemId);
            
            if (!$cartItem || $cartItem['user_id'] !== $userId) {
                return $this->jsonResponse(['success' => false, 'message' => 'Cart item not found'], 404);
            }
            
            $this->cartModel->removeItem($itemId);
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Item removed from cart'
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse(['success' => false, 'message' => 'Failed to remove from cart'], 500);
        }
    }

    public function createOrder() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $userId = $_SESSION['user_id'] ?? null;
            
            if (!$userId) {
                return $this->jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            
            $cart = $this->cartModel->getCart($userId);
            
            if (empty($cart['items'])) {
                return $this->jsonResponse(['success' => false, 'message' => 'Cart is empty'], 400);
            }
            
            // Calculate total
            $total = 0;
            foreach ($cart['items'] as $item) {
                $total += $item['price'] * $item['quantity'];
            }
            
            // Create order
            $orderData = [
                'user_id' => $userId,
                'total' => $total,
                'status' => 'pending',
                'items' => $cart['items']
            ];
            
            $orderId = $this->orderModel->create($orderData);
            
            if (!$orderId) {
                return $this->jsonResponse(['success' => false, 'message' => 'Failed to create order'], 500);
            }
            
            // Clear cart
            $this->cartModel->clearCart($userId);
            
            // Update product stock
            foreach ($cart['items'] as $item) {
                $this->productModel->updateStock($item['product_id'], -$item['quantity']);
            }
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => ['order_id' => $orderId]
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse(['success' => false, 'message' => 'Failed to create order'], 500);
        }
    }

    public function getOrderHistory() {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            
            if (!$userId) {
                return $this->jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            
            $orders = $this->orderModel->getUserOrders($userId);
            
            return $this->jsonResponse([
                'success' => true,
                'data' => $orders
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse(['success' => false, 'message' => 'Failed to fetch order history'], 500);
        }
    }

    private function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}