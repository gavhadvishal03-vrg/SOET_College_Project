<?php
/**
 * CampusAI — Dynamic Blog & News Knowledge Retriever
 *
 * Scans published student blog articles and administrative press releases
 * to answer questions regarding campus activities, student tech projects, and announcements.
 *
 * @package SOET_College_Project
 * @subpackage Chatbot_Engine
 */

require_once __DIR__ . '/../../core/bootstrap.php';

class BlogNewsRetriever
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Identifies if user is inquiring about campus blogs, articles, or news releases.
     *
     * @param string $query Query string.
     * @return bool True if query is blog or news related.
     */
    public function isBlogNewsQuery(string $query): bool
    {
        return (bool)preg_match('/\b(blog|blogs|news|press|article|articles|student post|latest news|campus news|updates)\b/i', $query);
    }

    public function search(string $query): ?array
    {
        $clean = mb_strtolower(trim($query));
        $keywords = array_filter(explode(' ', preg_replace('/[^\w\s]/', '', $clean)));

        // 1. Search Blogs
        try {
            $blogs = $this->db->fetchAll("SELECT id, title, slug, content, author_name, published_at FROM blogs WHERE status = 'published' ORDER BY published_at DESC LIMIT 5");
            if (!empty($blogs)) {
                $matchedBlog = null;
                foreach ($blogs as $b) {
                    $text = mb_strtolower($b['title'] . ' ' . $b['content']);
                    foreach ($keywords as $kw) {
                        if (strlen($kw) >= 4 && mb_strpos($text, $kw) !== false) {
                            $matchedBlog = $b;
                            break 2;
                        }
                    }
                }

                if ($matchedBlog) {
                    $dateStr = !empty($matchedBlog['published_at']) ? date('d M Y', strtotime($matchedBlog['published_at'])) : 'Recent';
                    $excerpt = mb_substr(strip_tags($matchedBlog['content']), 0, 260) . '...';
                    $url = '/blog-detail.php?id=' . $matchedBlog['id'];

                    return [
                        'found'       => true,
                        'source_type' => 'BLOG',
                        'title'       => $matchedBlog['title'],
                        'content'     => "📰 **Campus Blog: {$matchedBlog['title']}** (By {$matchedBlog['author_name']}, {$dateStr})\n\n{$excerpt}\n\n🔗 [Read Full Article]({$url})",
                        'source_url'  => $url
                    ];
                }

                // If general "blogs" inquiry
                if (preg_match('/\b(blogs?|articles?)\b/i', $clean)) {
                    $list = [];
                    foreach ($blogs as $b) {
                        $list[] = "• **{$b['title']}** (By {$b['author_name']})";
                    }
                    return [
                        'found'       => true,
                        'source_type' => 'BLOG',
                        'title'       => 'Recent Campus Blogs',
                        'content'     => "Here are the latest articles published on the SOET Blog Portal:\n\n" . implode("\n", $list) . "\n\n🔗 [Explore All Blogs](/blogs.php)",
                        'source_url'  => '/blogs.php'
                    ];
                }
            }
        } catch (Exception $e) {}

        // 2. Search News
        try {
            $newsList = $this->db->fetchAll("SELECT id, title, slug, content, author_name, published_at FROM news WHERE status = 'published' ORDER BY published_at DESC LIMIT 5");
            if (!empty($newsList)) {
                $matchedNews = null;
                foreach ($newsList as $n) {
                    $text = mb_strtolower($n['title'] . ' ' . $n['content']);
                    foreach ($keywords as $kw) {
                        if (strlen($kw) >= 4 && mb_strpos($text, $kw) !== false) {
                            $matchedNews = $n;
                            break 2;
                        }
                    }
                }

                if ($matchedNews) {
                    $dateStr = !empty($matchedNews['published_at']) ? date('d M Y', strtotime($matchedNews['published_at'])) : 'Recent';
                    $excerpt = mb_substr(strip_tags($matchedNews['content']), 0, 260) . '...';
                    $url = '/news-detail.php?id=' . $matchedNews['id'];

                    return [
                        'found'       => true,
                        'source_type' => 'NEWS',
                        'title'       => $matchedNews['title'],
                        'content'     => "📢 **Campus News: {$matchedNews['title']}** ({$dateStr})\n\n{$excerpt}\n\n🔗 [Read Official Press Release]({$url})",
                        'source_url'  => $url
                    ];
                }

                if (preg_match('/\b(news|press|bulletin|updates)\b/i', $clean)) {
                    $list = [];
                    foreach ($newsList as $n) {
                        $list[] = "• **{$n['title']}**";
                    }
                    return [
                        'found'       => true,
                        'source_type' => 'NEWS',
                        'title'       => 'Latest Campus News',
                        'content'     => "Here are the latest news updates from SOET MGM University:\n\n" . implode("\n", $list) . "\n\n🔗 [View All News](/news.php)",
                        'source_url'  => '/news.php'
                    ];
                }
            }
        } catch (Exception $e) {}

        return null;
    }
}
