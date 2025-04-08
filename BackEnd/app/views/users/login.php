<?php require APPROOT . '/views/inc/header.php'; ?>

<div class="login-container">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card login-card">
                    <div class="card-body">
                        <!-- Form đăng nhập -->
                        <div class="form-box" id="login-box">
                            <h2 class="text-center mb-4">Đăng nhập</h2>
                            <form action="<?php echo URLROOT; ?>/users/login" method="POST">
                                <div class="form-group">
                                    <input type="email" name="email" class="form-control <?php echo (!empty($data['email_err'])) ? 'is-invalid' : ''; ?>" placeholder="Email" value="<?php echo $data['email']; ?>" required>
                                    <span class="invalid-feedback"><?php echo $data['email_err']; ?></span>
                                </div>
                                <div class="form-group">
                                    <input type="password" name="password" class="form-control <?php echo (!empty($data['password_err'])) ? 'is-invalid' : ''; ?>" placeholder="Mật khẩu" required>
                                    <span class="invalid-feedback"><?php echo $data['password_err']; ?></span>
                                </div>
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary btn-block">Đăng nhập</button>
                                </div>
                                <div class="text-center mt-3">
                                    <p>Chưa có tài khoản? <a href="#" onclick="showRegister()">Đăng ký</a></p>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Form đăng ký -->
                        <div class="form-box hidden" id="register-box">
                            <h2 class="text-center mb-4">Đăng ký</h2>
                            <form action="<?php echo URLROOT; ?>/users/register" method="POST">
                                <div class="form-group">
                                    <input type="text" name="name" class="form-control <?php echo (!empty($data['name_err'])) ? 'is-invalid' : ''; ?>" placeholder="Tên của bạn" value="<?php echo $data['name']; ?>" required>
                                    <span class="invalid-feedback"><?php echo $data['name_err']; ?></span>
                                </div>
                                <div class="form-group">
                                    <input type="email" name="email" class="form-control <?php echo (!empty($data['email_err'])) ? 'is-invalid' : ''; ?>" placeholder="Email" value="<?php echo $data['email']; ?>" required>
                                    <span class="invalid-feedback"><?php echo $data['email_err']; ?></span>
                                </div>
                                <div class="form-group">
                                    <input type="password" name="password" class="form-control <?php echo (!empty($data['password_err'])) ? 'is-invalid' : ''; ?>" placeholder="Mật khẩu" required>
                                    <span class="invalid-feedback"><?php echo $data['password_err']; ?></span>
                                </div>
                                <div class="form-group">
                                    <input type="password" name="confirm_password" class="form-control <?php echo (!empty($data['confirm_password_err'])) ? 'is-invalid' : ''; ?>" placeholder="Xác nhận mật khẩu" required>
                                    <span class="invalid-feedback"><?php echo $data['confirm_password_err']; ?></span>
                                </div>
                                <div class="form-group">
                                    <button type="submit" class="btn btn-success btn-block">Đăng ký</button>
                                </div>
                                <div class="text-center mt-3">
                                    <p>Đã có tài khoản? <a href="#" onclick="showLogin()">Đăng nhập</a></p>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    body {
        background: linear-gradient(120deg, #2980b9, #8e44ad);
        min-height: 100vh;
        display: flex;
        align-items: center;
        position: relative;
        overflow: hidden;
    }
    
    body::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: url('<?php echo URLROOT; ?>/img/login-bg.jpg') no-repeat center center/cover;
        opacity: 0.3;
        z-index: -1;
    }
    
    .login-container {
        width: 100%;
        padding: 20px;
    }
    
    .login-card {
        background: rgba(255, 255, 255, 0.9);
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        backdrop-filter: blur(10px);
        border: none;
    }
    
    .form-box {
        padding: 20px;
    }
    
    .form-box h2 {
        color: #333;
        font-weight: 600;
    }
    
    .form-control {
        height: 50px;
        border-radius: 25px;
        padding: 0 20px;
        border: 1px solid #ddd;
        margin-bottom: 15px;
        transition: all 0.3s;
    }
    
    .form-control:focus {
        border-color: #8e44ad;
        box-shadow: 0 0 0 0.2rem rgba(142, 68, 173, 0.25);
    }
    
    .btn {
        height: 50px;
        border-radius: 25px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        transition: all 0.3s;
    }
    
    .btn-primary {
        background: #2980b9;
        border: none;
    }
    
    .btn-primary:hover {
        background: #1c6ea4;
        transform: translateY(-2px);
    }
    
    .btn-success {
        background: #27ae60;
        border: none;
    }
    
    .btn-success:hover {
        background: #219150;
        transform: translateY(-2px);
    }
    
    a {
        color: #8e44ad;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    a:hover {
        color: #2980b9;
        text-decoration: none;
    }
    
    .hidden {
        display: none;
    }
    
    @media (max-width: 768px) {
        .login-card {
            margin: 0 15px;
        }
    }
</style>

<script>
    function showRegister() {
        document.getElementById('login-box').classList.add('hidden');
        document.getElementById('register-box').classList.remove('hidden');
    }
    
    function showLogin() {
        document.getElementById('register-box').classList.add('hidden');
        document.getElementById('login-box').classList.remove('hidden');
    }
</script>

<?php require APPROOT . '/views/inc/footer.php'; ?> 