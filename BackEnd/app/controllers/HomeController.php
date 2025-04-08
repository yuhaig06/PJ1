<?php
require_once '../app/config/config.php'; // Đảm bảo có dòng này để lấy $db
require_once '../app/models/NewsModel.php';

class HomeController extends Controller {
    private $newsModel;
    private $categoryModel;
    private $tagModel;

    public function __construct() {
        // Kiểm tra nếu user chưa đăng nhập
        if(!isLoggedIn()) {
            redirect('users/login');
        }

        // Load các model cần thiết
        $this->newsModel = $this->model('News');
        $this->categoryModel = $this->model('Category');
        $this->tagModel = $this->model('Tag');
    }

    // Trang chủ
    public function index() {
        // Lấy danh sách tin tức mới nhất
        $latestNews = $this->newsModel->getLatestNews(5);
        
        // Lấy tin tức nổi bật
        $featuredNews = $this->newsModel->getFeaturedNews(3);
        
        // Lấy danh mục và số lượng tin
        $categories = $this->categoryModel->getCategoriesWithCount();
        
        // Lấy tags phổ biến
        $popularTags = $this->tagModel->getPopularTags(10);

        // Dữ liệu truyền vào view
        $data = [
            'latestNews' => $latestNews,
            'featuredNews' => $featuredNews,
            'categories' => $categories,
            'popularTags' => $popularTags
        ];

        // Load view
        $this->view('home/index', $data);
    }

    // Trang tin tức theo danh mục
    public function category($slug) {
        // Lấy thông tin danh mục
        $category = $this->categoryModel->getCategoryBySlug($slug);
        
        if(!$category) {
            redirect('home/error/404');
        }

        // Lấy tin tức theo danh mục
        $news = $this->newsModel->getNewsByCategory($category->id);
        
        // Lấy danh mục con
        $subCategories = $this->categoryModel->getSubCategories($category->id);

        $data = [
            'category' => $category,
            'news' => $news,
            'subCategories' => $subCategories
        ];

        $this->view('home/category', $data);
    }

    // Trang tin tức theo tag
    public function tag($slug) {
        // Lấy thông tin tag
        $tag = $this->tagModel->getTagBySlug($slug);
        
        if(!$tag) {
            redirect('home/error/404');
        }

        // Lấy tin tức theo tag
        $news = $this->newsModel->getNewsByTag($tag->id);

        $data = [
            'tag' => $tag,
            'news' => $news
        ];

        $this->view('home/tag', $data);
    }

    // Trang chi tiết tin tức
    public function news($slug) {
        // Lấy thông tin tin tức
        $news = $this->newsModel->getNewsBySlug($slug);
        
        if(!$news) {
            redirect('home/error/404');
        }

        // Tăng lượt xem
        $this->newsModel->incrementViewCount($news->id);

        // Lấy tin liên quan
        $relatedNews = $this->newsModel->getRelatedNews($news->id, $news->category_id, 3);

        // Lấy comments
        $comments = $this->newsModel->getComments($news->id);

        $data = [
            'news' => $news,
            'relatedNews' => $relatedNews,
            'comments' => $comments
        ];

        $this->view('home/news', $data);
    }

    // Trang tìm kiếm
    public function search() {
        $keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
        
        if(empty($keyword)) {
            redirect('home');
        }

        // Tìm kiếm tin tức
        $news = $this->newsModel->searchNews($keyword);

        $data = [
            'keyword' => $keyword,
            'news' => $news
        ];

        $this->view('home/search', $data);
    }

    // Trang lỗi
    public function error($code = '404') {
        $data = [
            'code' => $code
        ];

        $this->view('errors/' . $code, $data);
    }
}
?>