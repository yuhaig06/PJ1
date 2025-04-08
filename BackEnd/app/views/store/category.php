<?php require APPROOT . '/views/inc/header.php'; ?>

<div class="container">
    <div class="row">
        <div class="col-md-3">
            <div class="categories-sidebar">
                <h3>Danh mục sản phẩm</h3>
                <ul class="list-group">
                    <?php foreach($data['categories'] as $category): ?>
                        <li class="list-group-item <?php echo ($category->id == $data['category']->id) ? 'active' : ''; ?>">
                            <a href="<?php echo URLROOT; ?>/store/category/<?php echo $category->id; ?>">
                                <?php echo $category->name; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <div class="col-md-9">
            <div class="category-header">
                <h1><?php echo $data['category']->name; ?></h1>
                <p class="description"><?php echo $data['category']->description; ?></p>
            </div>
            
            <div class="row">
                <?php if(empty($data['products'])): ?>
                    <div class="col-12">
                        <div class="alert alert-info">
                            Không có sản phẩm nào trong danh mục này.
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach($data['products'] as $product): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card product-card">
                                <img src="<?php echo URLROOT; ?>/img/store/<?php echo $product->image; ?>" class="card-img-top" alt="<?php echo $product->name; ?>">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $product->name; ?></h5>
                                    <p class="card-text price"><?php echo number_format($product->price); ?> VNĐ</p>
                                    <p class="card-text stock">Còn lại: <?php echo $product->stock; ?> sản phẩm</p>
                                    <div class="card-actions">
                                        <a href="<?php echo URLROOT; ?>/store/details/<?php echo $product->id; ?>" class="btn btn-primary">Xem chi tiết</a>
                                        <button class="btn btn-success" onclick="addToCart(<?php echo $product->id; ?>)">Thêm vào giỏ</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
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
    
    // Lấy thông tin sản phẩm từ data attribute
    const productCard = document.querySelector(`[data-product-id="${productId}"]`);
    const productName = productCard.querySelector('.card-title').textContent;
    const productPrice = parseFloat(productCard.querySelector('.price').textContent.replace(/[^\d]/g, ''));
    
    document.getElementById('productName').textContent = productName;
    document.getElementById('productPrice').textContent = number_format(productPrice);
    updateTotalPrice(productPrice);
    
    $('#purchaseModal').modal('show');
}

function updateTotalPrice(price) {
    const quantity = document.getElementById('quantity').value;
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

document.getElementById('quantity').addEventListener('change', function() {
    const price = parseFloat(document.getElementById('productPrice').textContent.replace(/[^\d]/g, ''));
    updateTotalPrice(price);
});
</script>

<?php require APPROOT . '/views/inc/footer.php'; ?> 