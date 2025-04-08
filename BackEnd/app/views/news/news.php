<?php
require_once '../app/init.php';
$app = new App();

// Đây là nơi lấy dữ liệu từ controller, chắc chắn rằng $news được truyền vào view
// Đảm bảo $news là một mảng các bài viết từ controller (đã được truyền trong controller)
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Trang tin tức Gaming">
    <meta name="keywords" content="game, tin tức game, eSports">
    <meta name="author" content="WarStorm">
    <title>Tin Tức - WarStorm</title>
    <link rel="stylesheet" href="<?php echo URLROOT; ?>/Frontend/News/css/news.css">
    <link rel="icon" type="image/png" sizes="96x96" href="<?php echo URLROOT; ?>/Frontend/Home/favicon/favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="192x192" href="<?php echo URLROOT; ?>/Frontend/Home/favicon/web-app-manifest-192x192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="<?php echo URLROOT; ?>/Frontend/Home/favicon/web-app-manifest-512x512.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo URLROOT; ?>/Frontend/Home/favicon/apple-touch-icon.png">
    <link rel="manifest" href="<?php echo URLROOT; ?>/Frontend/Home/favicon/site.webmanifest">
</head>
<body>
    <header id="header">
        <a href="<?php echo URLROOT; ?>/home" class="logo-link">
            <img src="<?php echo URLROOT; ?>/Frontend/Home/img/logo.png" alt="Logo WarStorm" class="logo">
        </a>
        <h1>TIN TỨC</h1>
        <button id="menu-toggle">☰</button>
        <nav id="mobile-menu">
            <ul>
                <li><a href="<?php echo URLROOT; ?>/home">Trang chủ</a></li>
                <li><a href="<?php echo URLROOT; ?>/news">Tin tức</a></li>
                <li><a href="<?php echo URLROOT; ?>/esports">ESPORTS</a></li>
                <li><a href="<?php echo URLROOT; ?>/store">Cửa hàng</a></li>
                <li><a href="<?php echo URLROOT; ?>/contact">Liên hệ</a></li>
            </ul>
        </nav>
    </header>           

    <section id="news-container">
        <?php if (!empty($news)): ?>
            <?php 
            // Lấy tin tức đầu tiên làm tin chính
            $mainNews = array_shift($news); 
            ?>
            <div id="news-item">
                <a href="<?php echo URLROOT; ?>/news/details/<?php echo $mainNews->slug; ?>">
                    <img src="<?php echo URLROOT; ?>/uploads/news/<?php echo $mainNews->image; ?>" alt="<?php echo $mainNews->title; ?>">
                    <h2><?php echo $mainNews->title; ?></h2>
                </a>
            </div>
        <?php endif; ?>
    </section>    

    <div id="news-container-1">
        <?php 
        // Hiển thị 4 tin tức đầu tiên
        $count = 0;
        foreach ($news as $item): 
            if ($count >= 4) break;
        ?>
            <div id="news-item-<?php echo $count + 1; ?>">
                <a href="<?php echo URLROOT; ?>/news/details/<?php echo $item->slug; ?>">
                    <img src="<?php echo URLROOT; ?>/uploads/news/<?php echo $item->image; ?>" alt="<?php echo $item->title; ?>">
                    <h2><?php echo $item->title; ?></h2>
                </a>
            </div>
        <?php 
            $count++;
        endforeach; 
        ?>
    </div>            

    <div id="news-container-2">
        <?php 
        // Hiển thị các tin tức còn lại
        $count = 0;
        foreach ($news as $item): 
            if ($count < 4) {
                $count++;
                continue;
            }
        ?>
            <div class="news-item-sub hidden">
                <a href="<?php echo URLROOT; ?>/news/details/<?php echo $item->slug; ?>">
                    <img src="<?php echo URLROOT; ?>/uploads/news/<?php echo $item->image; ?>" alt="<?php echo $item->title; ?>">
                    <h2><?php echo $item->title; ?></h2>
                </a>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="load-more-wrapper">
        <button class="btn-load-more" id="loadMoreBtn" aria-label="Tải thêm tin tức vào trang">Tải thêm bài viết</button>
    </div>        

    <footer class="container bg-black text-center py-4">
        <p>&copy; 2025 WarStorm. All rights reserved.</p>
        <a href="<?php echo URLROOT; ?>/privacy-policy" class="text-danger">Chính sách bảo mật</a>
        <a href="<?php echo URLROOT; ?>/terms" class="text-danger">Điều khoản sử dụng</a>
        <a href="<?php echo URLROOT; ?>/contact" class="text-danger">Liên hệ</a>
    </footer>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const menuToggle = document.getElementById("menu-toggle");
        const mobileMenu = document.getElementById("mobile-menu");

        menuToggle.addEventListener("click", function () {
            mobileMenu.classList.toggle("active");
        });

        document.addEventListener("click", function (event) {
            if (!menuToggle.contains(event.target) && !mobileMenu.contains(event.target)) {
                mobileMenu.classList.remove("active");
            }
        });
    });

    document.addEventListener("DOMContentLoaded", function() {
        const loadMoreBtn = document.getElementById("loadMoreBtn");
        const articlesPerClick = 4;

        loadMoreBtn.addEventListener("click", function() {
            const hiddenNews = document.querySelectorAll(".news-item-sub.hidden");
            
            for (let i = 0; i < articlesPerClick; i++) {
                if (hiddenNews[i]) {
                    hiddenNews[i].classList.remove("hidden");
                }
            }

            if (document.querySelectorAll(".news-item-sub.hidden").length === 0) {
                loadMoreBtn.style.display = "none";
            }
        });
    });
</script>  
</body>
</html>