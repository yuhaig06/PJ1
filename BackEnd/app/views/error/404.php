<?php require APPROOT . '/views/inc/header.php'; ?>

<div class="error-container">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 text-center">
                <div class="error-box">
                    <h1 class="error-code"><?php echo $data['code']; ?></h1>
                    <h2 class="error-title"><?php echo $data['title']; ?></h2>
                    <p class="error-message"><?php echo $data['message']; ?></p>
                    <div class="error-actions">
                        <a href="<?php echo URLROOT; ?>" class="btn btn-primary">Về trang chủ</a>
                        <a href="javascript:history.back()" class="btn btn-secondary">Quay lại</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .error-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    }
    
    .error-box {
        background: white;
        padding: 40px;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }
    
    .error-code {
        font-size: 120px;
        font-weight: 700;
        color: #e74c3c;
        margin: 0;
        line-height: 1;
    }
    
    .error-title {
        font-size: 32px;
        color: #2c3e50;
        margin: 20px 0;
    }
    
    .error-message {
        font-size: 18px;
        color: #7f8c8d;
        margin-bottom: 30px;
    }
    
    .error-actions {
        margin-top: 30px;
    }
    
    .error-actions .btn {
        margin: 0 10px;
        padding: 12px 30px;
        font-size: 16px;
        border-radius: 25px;
    }
    
    .btn-primary {
        background: #3498db;
        border: none;
    }
    
    .btn-primary:hover {
        background: #2980b9;
        transform: translateY(-2px);
    }
    
    .btn-secondary {
        background: #95a5a6;
        border: none;
    }
    
    .btn-secondary:hover {
        background: #7f8c8d;
        transform: translateY(-2px);
    }
    
    @media (max-width: 768px) {
        .error-code {
            font-size: 80px;
        }
        
        .error-title {
            font-size: 24px;
        }
        
        .error-message {
            font-size: 16px;
        }
        
        .error-actions .btn {
            display: block;
            margin: 10px auto;
            width: 200px;
        }
    }
</style>

<?php require APPROOT . '/views/inc/footer.php'; ?> 