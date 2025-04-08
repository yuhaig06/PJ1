<?php require APPROOT . '/views/inc/header.php'; ?>

<div class="container">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h2>Thêm sản phẩm mới</h2>
                </div>
                <div class="card-body">
                    <form action="<?php echo URLROOT; ?>/store/add" method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="name">Tên sản phẩm <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control <?php echo (!empty($data['name_err'])) ? 'is-invalid' : ''; ?>" value="<?php echo $data['name']; ?>">
                            <span class="invalid-feedback"><?php echo $data['name_err']; ?></span>
                        </div>

                        <div class="form-group">
                            <label for="category_id">Danh mục <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-control <?php echo (!empty($data['category_id_err'])) ? 'is-invalid' : ''; ?>">
                                <option value="">Chọn danh mục</option>
                                <?php foreach($data['categories'] as $category): ?>
                                    <option value="<?php echo $category->id; ?>" <?php echo ($data['category_id'] == $category->id) ? 'selected' : ''; ?>>
                                        <?php echo $category->name; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="invalid-feedback"><?php echo $data['category_id_err']; ?></span>
                        </div>

                        <div class="form-group">
                            <label for="price">Giá <span class="text-danger">*</span></label>
                            <input type="number" name="price" class="form-control <?php echo (!empty($data['price_err'])) ? 'is-invalid' : ''; ?>" value="<?php echo $data['price']; ?>" min="0">
                            <span class="invalid-feedback"><?php echo $data['price_err']; ?></span>
                        </div>

                        <div class="form-group">
                            <label for="stock">Số lượng tồn kho <span class="text-danger">*</span></label>
                            <input type="number" name="stock" class="form-control <?php echo (!empty($data['stock_err'])) ? 'is-invalid' : ''; ?>" value="<?php echo $data['stock']; ?>" min="0">
                            <span class="invalid-feedback"><?php echo $data['stock_err']; ?></span>
                        </div>

                        <div class="form-group">
                            <label for="description">Mô tả <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control <?php echo (!empty($data['description_err'])) ? 'is-invalid' : ''; ?>" rows="5"><?php echo $data['description']; ?></textarea>
                            <span class="invalid-feedback"><?php echo $data['description_err']; ?></span>
                        </div>

                        <div class="form-group">
                            <label for="image">Hình ảnh <span class="text-danger">*</span></label>
                            <input type="file" name="image" class="form-control-file <?php echo (!empty($data['image_err'])) ? 'is-invalid' : ''; ?>" accept="image/*">
                            <span class="invalid-feedback"><?php echo $data['image_err']; ?></span>
                            <small class="form-text text-muted">Chấp nhận: JPG, PNG, GIF. Kích thước tối đa: 2MB</small>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Thêm sản phẩm</button>
                            <a href="<?php echo URLROOT; ?>/store/manage" class="btn btn-secondary">Hủy</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?> 