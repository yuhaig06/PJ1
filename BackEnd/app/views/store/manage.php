<?php require APPROOT . '/views/inc/header.php'; ?>

<div class="container">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1>Quản lý sản phẩm</h1>
                <a href="<?php echo URLROOT; ?>/store/add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Thêm sản phẩm mới
                </a>
            </div>
        </div>
    </div>

    <?php flash('store_message'); ?>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Hình ảnh</th>
                                    <th>Tên sản phẩm</th>
                                    <th>Danh mục</th>
                                    <th>Giá</th>
                                    <th>Tồn kho</th>
                                    <th>Ngày tạo</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($data['products'] as $product): ?>
                                    <tr>
                                        <td><?php echo $product->id; ?></td>
                                        <td>
                                            <img src="<?php echo URLROOT; ?>/img/store/<?php echo $product->image; ?>" 
                                                 alt="<?php echo $product->name; ?>" 
                                                 class="img-thumbnail" 
                                                 style="max-width: 50px;">
                                        </td>
                                        <td><?php echo $product->name; ?></td>
                                        <td>
                                            <?php 
                                            foreach($data['categories'] as $category) {
                                                if($category->id == $product->category_id) {
                                                    echo $category->name;
                                                    break;
                                                }
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo number_format($product->price); ?> VNĐ</td>
                                        <td><?php echo $product->stock; ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($product->created_at)); ?></td>
                                        <td>
                                            <a href="<?php echo URLROOT; ?>/store/edit/<?php echo $product->id; ?>" 
                                               class="btn btn-sm btn-info">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="btn btn-sm btn-danger" 
                                                    onclick="deleteProduct(<?php echo $product->id; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal xác nhận xóa sản phẩm -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Xác nhận xóa sản phẩm</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn xóa sản phẩm này?</p>
                <p>Hành động này không thể hoàn tác.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-danger" onclick="confirmDelete()">Xóa</button>
            </div>
        </div>
    </div>
</div>

<script>
let productToDelete = null;

function deleteProduct(productId) {
    productToDelete = productId;
    $('#deleteModal').modal('show');
}

function confirmDelete() {
    if (productToDelete) {
        window.location.href = `<?php echo URLROOT; ?>/store/delete/${productToDelete}`;
    }
}

function number_format(number) {
    return new Intl.NumberFormat('vi-VN').format(number);
}
</script>

<?php require APPROOT . '/views/inc/footer.php'; ?> 