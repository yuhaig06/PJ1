<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Cửa hàng game trực tuyến với các sản phẩm chất lượng cho game thủ">
    <title>Store - Cửa hàng game</title>
    <link rel="stylesheet" href="<?php echo URLROOT; ?>/css/store.css">
    <link rel="icon" type="image/png" sizes="96x96" href="<?php echo URLROOT; ?>/img/favicon/favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="192x192" href="<?php echo URLROOT; ?>/img/favicon/web-app-manifest-192x192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="<?php echo URLROOT; ?>/img/favicon/web-app-manifest-512x512.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo URLROOT; ?>/img/favicon/apple-touch-icon.png">
    <link rel="manifest" href="<?php echo URLROOT; ?>/img/favicon/site.webmanifest">
</head>
<body>
    <header>
        <div class="header-container">
            <a href="<?php echo URLROOT; ?>">
                <img src="<?php echo URLROOT; ?>/img/logo.png" alt="Logo" class="logo">
            </a>
            <h1>STORE</h1>
            <span class="menu-icon" onclick="toggleMenu()">☰</span>
        </div>
        <ul id="mobile-menu" class="mobile-menu">
            <li><a href="<?php echo URLROOT; ?>">Trang chủ</a></li>
            <li><a href="<?php echo URLROOT; ?>/news">Tin tức</a></li>
            <li><a href="<?php echo URLROOT; ?>/esports">ESPORTS</a></li>
            <li><a href="<?php echo URLROOT; ?>/store">Store</a></li>
            <li><a href="<?php echo URLROOT; ?>/contact">Liên hệ</a></li>
        </ul>
    </header>
    
    <main>
        <section class="products" id="product-list">
            <?php if(isset($products) && !empty($products)): ?>
                <?php foreach($products as $product): ?>
                    <div class="product">
                        <img src="<?php echo URLROOT; ?>/img/store/<?php echo $product->image; ?>" alt="<?php echo $product->name; ?>">
                        <h2><?php echo $product->name; ?></h2>
                        <p><?php echo $product->description; ?></p>
                        <p class="price">Giá: <?php echo number_format($product->price, 0, ',', '.'); ?> VNĐ</p>
                        <input type="number" min="1" max="<?php echo $product->stock; ?>" value="1" class="quantity" 
                               data-id="<?php echo $product->id; ?>" 
                               data-name="<?php echo $product->name; ?>" 
                               data-price="<?php echo $product->price; ?>">
                        <button class="buy-button" onclick="addToCart(this)">Mua ngay</button>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-products">Không có sản phẩm nào trong cửa hàng.</p>
            <?php endif; ?>
        </section>
        
        <div id="cart-modal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal()">&times;</span>
                <h2>Xác nhận mua hàng</h2>
                <p id="cart-details"></p>
                <button onclick="confirmPurchase()">Thanh toán</button>
            </div>
        </div>
    </main>
    
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>Về WarStorm</h3>
                <p>WarStorm là nền tảng game hàng đầu Việt Nam, cung cấp trải nghiệm game đa dạng và chất lượng.</p>
            </div>
            <div class="footer-section">
                <h3>Liên kết</h3>
                <ul>
                    <li><a href="<?php echo URLROOT; ?>/about">Giới thiệu</a></li>
                    <li><a href="<?php echo URLROOT; ?>/contact">Liên hệ</a></li>
                    <li><a href="<?php echo URLROOT; ?>/privacy">Chính sách bảo mật</a></li>
                    <li><a href="<?php echo URLROOT; ?>/terms">Điều khoản sử dụng</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Theo dõi chúng tôi</h3>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> WarStorm. Tất cả quyền được bảo lưu.</p>
        </div>
    </footer>
    
    <script>
        function toggleMenu() {
            document.getElementById("mobile-menu").classList.toggle("show");
        }

        function addToCart(button) {
            const productDiv = button.parentElement;
            const quantityInput = productDiv.querySelector(".quantity");
            const quantity = parseInt(quantityInput.value);
            const productId = quantityInput.dataset.id;
            const name = quantityInput.dataset.name;
            const price = parseInt(quantityInput.dataset.price);
            
            document.getElementById("cart-details").innerHTML = `Bạn muốn mua <strong>${quantity}</strong> x <strong>${name}</strong> với giá <strong>${(quantity * price).toLocaleString("vi-VN")} VNĐ</strong>?`;
            document.getElementById("cart-modal").style.display = "flex";

            button.dataset.quantity = quantity;
            button.dataset.productId = productId;
        }

        function closeModal() {
            document.getElementById("cart-modal").style.display = "none";
        }

        function confirmPurchase() {
            const modal = document.getElementById("cart-modal");
            const productName = modal.querySelector("strong:nth-child(2)").innerText;
            const button = document.querySelector(".buy-button[data-quantity]");
            const productId = button.dataset.productId;
            const quantity = parseInt(button.dataset.quantity);
            
            // Gửi yêu cầu AJAX để xử lý đơn hàng
            fetch('<?php echo URLROOT; ?>/store/processOrder', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    productId: productId,
                    quantity: quantity
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert("Thanh toán thành công! Cảm ơn bạn đã mua hàng.");
                    
                    // Cập nhật số lượng tồn kho
                    const quantityInput = document.querySelector(`.quantity[data-id='${productId}']`);
                    const newStock = data.newStock;
                    quantityInput.max = newStock;
                    quantityInput.value = newStock > 0 ? 1 : 0;
                    
                    const buyButton = quantityInput.nextElementSibling;
                    if (newStock <= 0) {
                        buyButton.disabled = true;
                        buyButton.innerText = "Hết hàng";
                    }
                } else {
                    alert("Có lỗi xảy ra: " + data.message);
                }
                closeModal();
            })
            .catch(error => {
                console.error('Error:', error);
                alert("Có lỗi xảy ra khi xử lý đơn hàng.");
                closeModal();
            });
        }
    </script>
    
    <!-- Font Awesome cho biểu tượng mạng xã hội -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</body>
</html>
