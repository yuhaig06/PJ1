<?php

namespace App\Tests\News;

use App\Tests\TestCase;
use App\Controllers\News\NewsController;
use App\Controllers\News\CategoryController;
use App\Controllers\News\TagController;

class NewsTest extends TestCase
{
    private $newsController;
    private $categoryController;
    private $tagController;

    protected function setUp(): void
    {
        parent::setUp();
        $this->newsController = new NewsController();
        $this->categoryController = new CategoryController();
        $this->tagController = new TagController();
    }

    public function testCreateNews()
    {
        // Create test category
        $categoryId = $this->createTestNewsCategory();
        
        $newsData = [
            'title' => 'Test News ' . uniqid(),
            'slug' => 'test-news-' . uniqid(),
            'summary' => 'Test news summary',
            'content' => 'Test news content',
            'thumbnail' => 'news/test.jpg',
            'category_id' => $categoryId,
            'status' => 'published'
        ];

        $result = $this->newsController->create($newsData);
        
        $this->assertTrue($result['status'] === 'success');
        $this->assertTrue(isset($result['data']['id']));
        
        // Verify news was created in database
        $news = $this->db->query(
            "SELECT * FROM news WHERE id = :id",
            ['id' => $result['data']['id']]
        )->fetch();

        $this->assertNotNull($news);
        $this->assertEquals($newsData['title'], $news['title']);
        $this->assertEquals($newsData['summary'], $news['summary']);
    }

    public function testUpdateNews()
    {
        // Create test news
        $newsId = $this->createTestNews();
        
        $updateData = [
            'title' => 'Updated News ' . uniqid(),
            'summary' => 'Updated news summary',
            'status' => 'draft'
        ];

        $result = $this->newsController->update($newsId, $updateData);
        
        $this->assertTrue($result['status'] === 'success');
        
        // Verify news was updated in database
        $news = $this->db->query(
            "SELECT * FROM news WHERE id = :id",
            ['id' => $newsId]
        )->fetch();

        $this->assertEquals($updateData['title'], $news['title']);
        $this->assertEquals($updateData['summary'], $news['summary']);
        $this->assertEquals($updateData['status'], $news['status']);
    }

    public function testDeleteNews()
    {
        // Create test news
        $newsId = $this->createTestNews();

        $result = $this->newsController->delete($newsId);
        
        $this->assertTrue($result['status'] === 'success');
        
        // Verify news was deleted from database
        $news = $this->db->query(
            "SELECT * FROM news WHERE id = :id",
            ['id' => $newsId]
        )->fetch();

        $this->assertNull($news);
    }

    public function testGetNewsDetails()
    {
        // Create test news
        $newsId = $this->createTestNews();

        $result = $this->newsController->show($newsId);
        
        $this->assertTrue($result['status'] === 'success');
        $this->assertTrue(isset($result['data']));
        $this->assertEquals($newsId, $result['data']['id']);
    }

    public function testListNews()
    {
        // Create multiple test news
        for ($i = 0; $i < 3; $i++) {
            $this->createTestNews();
        }

        $result = $this->newsController->index();
        
        $this->assertTrue($result['status'] === 'success');
        $this->assertTrue(isset($result['data']));
        $this->assertGreaterThanOrEqual(3, count($result['data']));
    }

    public function testCreateNewsCategory()
    {
        $categoryData = [
            'name' => 'Test News Category ' . uniqid(),
            'slug' => 'test-news-category-' . uniqid(),
            'description' => 'Test news category description'
        ];

        $result = $this->categoryController->create($categoryData);
        
        $this->assertTrue($result['status'] === 'success');
        $this->assertTrue(isset($result['data']['id']));
        
        // Verify category was created in database
        $category = $this->db->query(
            "SELECT * FROM news_categories WHERE id = :id",
            ['id' => $result['data']['id']]
        )->fetch();

        $this->assertNotNull($category);
        $this->assertEquals($categoryData['name'], $category['name']);
    }

    public function testCreateNewsTag()
    {
        $tagData = [
            'name' => 'Test News Tag ' . uniqid(),
            'slug' => 'test-news-tag-' . uniqid()
        ];

        $result = $this->tagController->create($tagData);
        
        $this->assertTrue($result['status'] === 'success');
        $this->assertTrue(isset($result['data']['id']));
        
        // Verify tag was created in database
        $tag = $this->db->query(
            "SELECT * FROM news_tags WHERE id = :id",
            ['id' => $result['data']['id']]
        )->fetch();

        $this->assertNotNull($tag);
        $this->assertEquals($tagData['name'], $tag['name']);
    }

    public function testAddTagToNews()
    {
        // Create test news and tag
        $newsId = $this->createTestNews();
        $tagId = $this->createTestNewsTag();

        $result = $this->newsController->addTag($newsId, $tagId);
        
        $this->assertTrue($result['status'] === 'success');
        
        // Verify tag was added to news
        $newsTag = $this->db->query(
            "SELECT * FROM news_tags WHERE news_id = :news_id AND tag_id = :tag_id",
            ['news_id' => $newsId, 'tag_id' => $tagId]
        )->fetch();

        $this->assertNotNull($newsTag);
    }
} 