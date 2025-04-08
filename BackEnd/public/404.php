<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Trang không tìm thấy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #6a11cb, #2575fc); /* Gradient màu mượt */
            color: white;
            font-family: 'Arial', sans-serif;
        }
        .error-container {
            text-align: center;
            max-width: 600px;
            padding: 30px;
            background: rgba(0, 0, 0, 0.7); /* Bóng mờ giúp nổi bật nội dung */
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        h1 {
            font-size: 100px;
            font-weight: bold;
            color: #ff6b6b;
            margin-bottom: 20px;
        }
        p {
            font-size: 18px;
            margin-bottom: 20px;
        }
        .btn-primary {
            background-color: #ff6b6b;
            border-color: #ff6b6b;
            padding: 10px 20px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 5px;
            text-transform: uppercase;
            transition: background-color 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #ff3b3b;
            border-color: #ff3b3b;
        }
        .img-container {
            margin: 20px 0;
        }
        .img-container img {
            max-width: 250px; /* Điều chỉnh lại kích thước ảnh */
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body>
    <div class="error-container">
        <img src="http://localhost/PJ1/FrontEnd/Home/img/logo.png" alt="Logo Website" style="max-width: 150px;">
        
        <h1>404</h1>
        <p>Rất tiếc, trang bạn yêu cầu không tồn tại. Hãy thử tìm lại hoặc quay về trang chủ.</p>
        
        <div class="img-container">
            <img src="assets/img/iconerror.png" alt="404 Image">
        </div>

        <a href="../../FrontEnd/Home/home/home.html" class="btn btn-primary mt-3">Quay lại trang chủ</a>
    </div>
</body>
</html>