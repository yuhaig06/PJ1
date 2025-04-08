<?php require APPROOT . '/views/inc/header.php'; ?>

<div class="container">
    <div class="row">
        <div class="col-md-8">
            <div class="product-details">
                <h1><?php echo $data['product']->name; ?></h1>
                <div class="product-image">
                    <img src="<?php echo URLROOT; ?>/img/store/<?php echo $data['product']->image; ?>" alt="<?php echo $data['product']->name; ?>">
                </div>
                <div class="product-info">
                    <p class="price">Giá: <?php echo number_format($data['product']->price); ?> VNĐ</p>
                    <p class="stock">Còn lại: <?php echo $data['product']->stock; ?> sản phẩm</p>
                    <div class="description">
                        <?php echo $data['product']->description; ?>
                    </div>
                    <button class="btn btn-primary" onclick="addToCart(<?php echo $data['product']->id; ?>)">Thêm vào giỏ hàng</button>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="related-products">
                <h3>Sản phẩm liên quan</h3>
                <?php foreach($data['relatedProducts'] as $product): ?>
                    <div class="related-product">
                        <img src="<?php echo URLROOT; ?>/img/store/<?php echo $product->image; ?>" alt="<?php echo $product->name; ?>">
                        <h4><?php echo $product->name; ?></h4>
                        <p class="price"><?php echo number_format($product->price); ?> VNĐ</p>
                        <a href="<?php echo URLROOT; ?>/store/details/<?php echo $product->id; ?>" class="btn btn-sm btn-outline-primary">Xem chi tiết</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal xác nhận mua hàng -->
<div class="modal fade" id="purchaseModal" tabindex="-1" role="dialog" aria-labelledby="purchaseModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="purchaseModalLabel">Xác nhận mua hàng</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn mua sản phẩm này?</p>
                <p>Tên sản phẩm: <span id="productName"></span></p>
                <p>Giá: <span id="productPrice"></span> VNĐ</p>
                <div class="form-group">
                    <label for="quantity">Số lượng:</label>
                    <input type="number" class="form-control" id="quantity" min="1" value="1">
                </div>
                <p>Tổng tiền: <span id="totalPrice"></span> VNĐ</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" onclick="confirmPurchase()">Xác nhận</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentProduct = null;

function addToCart(productId) {
    currentProduct = productId;
    const product = <?php echo json_encode($data['product']); ?>;
    
    document.getElementById('productName').textContent = product.name;
    document.getElementById('productPrice').textContent = number_format(product.price);
    updateTotalPrice();
    
    $('#purchaseModal').modal('show');
}

function updateTotalPrice() {
    const quantity = document.getElementById('quantity').value;
    const price = <?php echo $data['product']->price; ?>;
    const total = quantity * price;
    document.getElementById('totalPrice').textContent = number_format(total);
}

function confirmPurchase() {
    const quantity = document.getElementById('quantity').value;
    
    fetch('<?php echo URLROOT; ?>/store/processOrder', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            productId: currentProduct,
            quantity: quantity
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Đặt hàng thành công!');
            location.reload();
        } else {
            alert(data.message || 'Có lỗi xảy ra khi đặt hàng');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi đặt hàng');
    });
}

function number_format(number) {
    return new Intl.NumberFormat('vi-VN').format(number);
}

document.getElementById('quantity').addEventListener('change', updateTotalPrice);
</script>

<?php require APPROOT . '/views/inc/footer.php'; ?> 