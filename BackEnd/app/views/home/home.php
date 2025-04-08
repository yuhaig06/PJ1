<?php
// Đây là nơi lấy dữ liệu từ controller, chắc chắn rằng $posts được truyền vào view
// Đảm bảo $posts là một mảng các bài viết từ controller (đã được truyền trong controller)
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang Chủ</title>
    <link rel="stylesheet" href="<?php echo URLROOT; ?>/Frontend/Home/css/home.css">
    <link rel="stylesheet" href="<?php echo URLROOT; ?>/Frontend/Home/themify-icons/themify-icons.css">
    <link rel="stylesheet" href="<?php echo URLROOT; ?>/Frontend/Home/fontawesome-free-6.7.2-web/css/all.min.css">
    <link rel="icon" type="image/png" sizes="96x96" href="<?php echo URLROOT; ?>/Frontend/Home/favicon/favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="192x192" href="<?php echo URLROOT; ?>/Frontend/Home/favicon/web-app-manifest-192x192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="<?php echo URLROOT; ?>/Frontend/Home/favicon/web-app-manifest-512x512.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo URLROOT; ?>/Frontend/Home/favicon/apple-touch-icon.png">
    <link rel="manifest" href="<?php echo URLROOT; ?>/Frontend/Home/favicon/site.webmanifest">
</head>
<body>
    <header id="header">
        <div class="col-auto">
            <img src="<?php echo URLROOT; ?>/Frontend/Home/img/logo.png" alt="Logo" class="logo">
        </div>
    
        <ul class="nav">
            <li><a href="<?php echo URLROOT; ?>/home">Trang chủ</a></li>
            <li><a href="<?php echo URLROOT; ?>/news">Tin tức</a></li>
            <li><a href="<?php echo URLROOT; ?>/esports">ESPORTS</a></li>
            <li><a href="<?php echo URLROOT; ?>/store">Cửa hàng</a></li>
            <li><a href="<?php echo URLROOT; ?>/contact">Liên hệ</a></li>
        </ul>
    
        <div class="col-auto">
            <button class="menu-toggle" onclick="toggleMenu()">☰</button> 

            <button id="search-toggle" onclick="toggleSearchBox()">
                🔍 TÌM KIẾM
            </button>

            <div id="search-box" class="search-box">
                <input type="text" id="search-input" placeholder="Nhập từ khóa..." />
                <button onclick="searchGames()">🔎 Tìm kiếm</button>
            </div>

            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="<?php echo URLROOT; ?>/users/profile" class="login-register-btn-header">Tài khoản</a>
            <?php else: ?>
                <a href="<?php echo URLROOT; ?>/users/login" class="login-register-btn-header">Đăng nhập / Đăng ký</a>
            <?php endif; ?>
        </div>
    </header>    

    <section id="hero-section">
        <img src="<?php echo URLROOT; ?>/Frontend/Home/img/a1.webp" alt="Banner" class="img-big-banner">

        <h1 class="big-heading">Welcome to WarStorm - Fastest game news updates</h1>

        <button class="call-to-action"><a href="<?php echo URLROOT; ?>/news">Khám Phá Tin Tức</a></button>
    </section>

    <main id="main-content" class="container">
        <div class="row">
            <!-- Featured News -->
            <section class="col-md-8 featured-news">
                <ul class="list-of-latest-posts">
                    <?php if (!empty($posts)): ?>
                        <?php foreach ($posts as $post): ?>
                            <li>
                                <a href="<?php echo URLROOT; ?>/news/details/<?php echo $post->slug; ?>">
                                    <img src="<?php echo URLROOT; ?>/uploads/news/<?php echo $post->image; ?>" alt="<?php echo $post->title; ?>">
                                    <span><?php echo $post->title; ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li>Không có bài viết nào.</li>
                    <?php endif; ?>
                </ul>
            </section>

            <!-- Show Title -->
            <div class="col-md-4">
                <div class="show-title">
                    <img src="<?php echo URLROOT; ?>/Frontend/Home/img/h1.jpg" alt="Ảnh thumbnail bài viết">
                    <p class="short-description">Tựa game hot nhất tuần này với nhiều cập nhật mới.</p>
                    <button class="see-more-btn"><a href="<?php echo URLROOT; ?>/hotgames">Xem Thêm</a></button>
                </div>

                <!-- Categories -->
                <div class="categories">
                    <h2>Danh mục Games - Tin tức</h2>
                    <div class="content">
                        <div class="game-list">
                            <?php if(isset($categories) && !empty($categories)): ?>
                                <?php foreach($categories as $category): ?>
                                    <a href="<?php echo URLROOT; ?>/news/category/<?php echo $category->slug; ?>" class="game-card"><?php echo $category->name; ?></a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="news-list">
                            <a href="<?php echo URLROOT; ?>/news" class="news-card">
                                <p>Cập nhật tin nóng về thế giới game!</p>
                            </a>
                        </div>
                    </div>
                </div>              
            </div>

            <!-- Video Section -->
            <div class="col-md-12">
                <div class="video-stream text-center">
                    <video id="myVideo" controls loop muted playsinline>
                        <source src="<?php echo URLROOT; ?>/Frontend/Home/video/n1_fixed.mp4" type="video/mp4">
                    </video>
                    <audio id="myAudio" loop>
                        <source src="<?php echo URLROOT; ?>/Frontend/Home/audio/t1.mp3" type="audio/mp3">
                    </audio>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="row mt-4">
            <div class="col-md-4 text-center">
                <a href="<?php echo URLROOT; ?>/aboutus">
                    <h2 class="about-us">Về chúng tôi</h2>
                    <img src="<?php echo URLROOT; ?>/Frontend/Home/img/m1.jpg" alt="Về Chúng tôi" class="sidebar-img">
                </a>
            </div>
        
            <div class="col-md-4 text-center">
                <a href="<?php echo URLROOT; ?>/gamerankings">
                    <h2 class="game-rankings">Bảng xếp hạng game</h2>
                    <img src="<?php echo URLROOT; ?>/Frontend/Home/img/u1.png" alt="Bảng xếp hạng game" class="sidebar-img">
                </a>
            </div>
        
            <div class="col-md-4 text-center">
                <a href="<?php echo URLROOT; ?>/events">
                    <h2 class="events-ad">Sự kiện quảng cáo</h2>
                    <img src="<?php echo URLROOT; ?>/Frontend/Home/img/u2.png" alt="Sự kiện quảng cáo" class="sidebar-img">
                </a>
            </div>
        </div>        
    </main>                    

    <div id="footer" class="container">
        <div class="row">
            <div class="col-md-4">
                <h4 class="information">Thông tin về WarStorm</h4>
                <p><strong>Giới thiệu:</strong> WarStorm là một nền tảng tin tức và cộng đồng dành cho game thủ, cung cấp thông tin mới nhất về trò chơi, bảng xếp hạng, và sự kiện game.</p>
                <p><strong>Mục tiêu:</strong>Chúng tôi hướng tới việc trở thành trang web hàng đầu về tin tức game, giúp game thủ cập nhật thông tin nhanh chóng và chính xác.</p>
                <p class="contact"><a href="<?php echo URLROOT; ?>/contact">Liên hệ</a></p>
            </div>

            <div class="col-md-4">
                <h4 class="social">Liên kết mạng xã hội:</h4>
                <a href=""><i class="ti-facebook"></i></a>
                <a href=""><i class="ti-youtube"></i></a>
                <a href=""><i class="fa-brands fa-discord"></i></a>
            </div>

            <div class="col-md-4">
                <h4 class="policies-terms">
                    <a href="<?php echo URLROOT; ?>/privacy-policy">Chính sách bảo mật</a>
                    <a href="<?php echo URLROOT; ?>/terms">Điều khoản sử dụng</a>
                </h4>
            </div>
        </div>
    </div>
</body>
<script>
    function searchGames() {
        let keyword = document.getElementById("search-input").value.toLowerCase();
        let games = document.querySelectorAll(".game-item");

        games.forEach(game => {
            let title = game.querySelector(".game-title").innerText.toLowerCase();
            if (title.includes(keyword)) {
                game.style.display = "block";
            } else {
                game.style.display = "none";
            }
        });
    }

    function toggleSearchBox() {
        let searchBox = document.getElementById("search-box");
        if (searchBox.classList.contains("active")) {
            searchBox.classList.remove("active");
        } else {
            searchBox.classList.add("active");
        }
    }

    function toggleMenu() {
        var nav = document.querySelector(".nav");
        nav.classList.toggle("active");
    }

    let video = document.getElementById("myVideo");
    let audio = document.getElementById("myAudio");

    document.querySelector('a[href="#myVideo"]').addEventListener('click', function (event) {
        event.preventDefault(); 
        document.getElementById('myVideo').scrollIntoView({ behavior: 'smooth' });
    });
    
    let observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                video.play();
            } else {
                video.pause();
            }
        });
    }, { threshold: 0.5 });

    video.onplay = function () {
        audio.play();
    };

    video.onpause = function () {
        audio.pause();
    };

    video.ontimeupdate = function () {
        audio.currentTime = video.currentTime;
    };

    video.onvolumechange = function () {
        audio.muted = video.muted;
        audio.volume = video.volume;
    };

    observer.observe(video);
</script>
</html>