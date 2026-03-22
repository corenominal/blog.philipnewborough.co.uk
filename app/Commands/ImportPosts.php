<?php

/**
 * ImportPosts Spark Command
 *
 * Imports posts, tags and meta from a JSON export file into the database.
 * UUIDs are generated for each post at import time.
 *
 * Featured images are downloaded to public/media/ and renamed to og-{uuid}.{ext}.
 * Body images are downloaded to public/media/ and renamed to {uuid}.{ext}.
 * URLs for body images are updated in both the body and body_html fields.
 *
 * Posts with a slug that already exists in the database are skipped.
 *
 * Usage:
 *   php spark import:posts
 *   php spark import:posts /absolute/path/to/export.json
 *
 * When no file path is provided, the most recently modified JSON file in the
 * imports/ directory is used.
 *
 * Examples:
 *   php spark import:posts
 *   php spark import:posts imports/blog-export-2026-03-21-223221.json
 */

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Ramsey\Uuid\Uuid;

class ImportPosts extends BaseCommand
{
    protected $group       = 'Import';
    protected $name        = 'import:posts';
    protected $description = 'Import posts, tags and meta from a JSON export file.';
    protected $usage       = 'import:posts [file]';
    protected $arguments   = [
        'file' => '(Optional) Path to the JSON import file. Defaults to the first JSON file found in the imports/ directory.',
    ];

    public function run(array $params): void
    {
        $mediaPath = FCPATH . 'media' . DIRECTORY_SEPARATOR;
        $baseUrl   = rtrim(config('App')->baseURL, '/') . '/media/';

        $filePath = $params[0] ?? '';

        if (empty($filePath)) {
            $importDir = ROOTPATH . 'imports' . DIRECTORY_SEPARATOR;
            $files     = glob($importDir . '*.json');

            if (empty($files)) {
                CLI::error('No JSON import file found in the imports/ directory.');
                return;
            }

            usort($files, static fn ($a, $b) => filemtime($b) - filemtime($a));
            $filePath = $files[0];
        }

        if (!file_exists($filePath)) {
            CLI::error("File not found: {$filePath}");
            return;
        }

        CLI::write("Reading: {$filePath}", 'green');

        $raw  = file_get_contents($filePath);
        $data = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            CLI::error('Failed to parse JSON: ' . json_last_error_msg());
            return;
        }

        if (isset($data['posts']) && is_array($data['posts'])) {
            $posts = $data['posts'];
        } elseif (is_array($data) && array_key_exists(0, $data)) {
            $posts = $data;
        } else {
            CLI::error('Unexpected JSON structure. Expected an array or {"posts": [...]}.');
            return;
        }

        $total    = count($posts);
        $imported = 0;
        $skipped  = 0;

        CLI::write("Found {$total} posts to import.", 'green');
        CLI::newLine();

        $db = \Config\Database::connect();

        foreach ($posts as $index => $post) {
            $num  = $index + 1;
            $slug = $post['slug'] ?? '';

            if (!empty($slug) && $db->table('posts')->where('slug', $slug)->countAllResults() > 0) {
                CLI::write("[{$num}/{$total}] Skipping (duplicate slug): {$slug}", 'yellow');
                $skipped++;
                continue;
            }

            $uuid = Uuid::uuid4()->toString();

            $featuredImage = '';

            if (!empty($post['featured_image'])) {
                $featuredImage = $this->downloadFeaturedImage($post['featured_image'], $uuid, $mediaPath);
            }

            $body     = $post['body'] ?? '';
            $bodyHtml = $post['body_html'] ?? '';
            [$body, $bodyHtml] = $this->processBodyImages($body, $bodyHtml, $mediaPath, $baseUrl);

            $postRow = [
                'uuid'           => $uuid,
                'title'          => $post['title'] ?? '',
                'title_html'     => $post['title_html'] ?? '',
                'slug'           => $slug,
                'body'           => $body,
                'body_html'      => $bodyHtml,
                'excerpt'        => $post['excerpt'] ?? '',
                'tags'           => $post['tags'] ?? '',
                'featured_image' => $featuredImage,
                'visibility'     => (int) ($post['visibility'] ?? 0),
                'status'         => $post['status'] ?? 'draft',
                'comment_status' => $post['comment_status'] ?? 'open',
                'comment_count'  => (int) ($post['comment_count'] ?? 0),
                'hitcounter'     => (int) ($post['hitcounter'] ?? 0),
                'published_at'   => ($post['published_at'] ?: '0000-00-00 00:00:00'),
                'created_at'     => $post['created_at'] ?? date('Y-m-d H:i:s'),
                'updated_at'     => $post['updated_at'] ?? date('Y-m-d H:i:s'),
                'deleted_at'     => ($post['deleted_at'] ?: null),
            ];

            $db->table('posts')->insert($postRow);
            $postId = $db->insertID();

            if (empty($postId)) {
                CLI::error("[{$num}/{$total}] Failed to insert: " . ($post['title'] ?? '(no title)'));
                continue;
            }

            if (!empty($post['tags_list'])) {
                foreach ($post['tags_list'] as $tag) {
                    $db->table('tags')->insert([
                        'post_id'    => $postId,
                        'tag'        => $tag['tag'] ?? '',
                        'slug'       => $tag['slug'] ?? '',
                        'created_at' => $tag['created_at'] ?? date('Y-m-d H:i:s'),
                        'updated_at' => $tag['updated_at'] ?? date('Y-m-d H:i:s'),
                    ]);
                }
            }

            if (!empty($post['custom_fields'])) {
                foreach ($post['custom_fields'] as $field) {
                    $metaKey   = $field['meta_key'] ?? $field['key'] ?? '';
                    $metaValue = $field['meta_value'] ?? $field['value'] ?? '';

                    if (empty($metaKey)) {
                        continue;
                    }

                    $db->table('meta')->insert([
                        'post_id'    => $postId,
                        'meta_key'   => $metaKey,
                        'meta_value' => $metaValue,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            }

            CLI::write("[{$num}/{$total}] Imported: " . ($post['title'] ?? '(no title)') . " [{$uuid}]", 'green');
            $imported++;
        }

        CLI::newLine();
        CLI::write("Import complete. Imported: {$imported}, Skipped: {$skipped}.", 'green');
    }

    private function downloadFeaturedImage(string $url, string $postUuid, string $mediaPath): string
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }

        $ext = $this->extensionFromUrl($url);

        $filename = "og-{$postUuid}.{$ext}";
        $destPath = $mediaPath . $filename;
        $content  = $this->fetchUrl($url);

        if ($content === false) {
            CLI::write("  Warning: could not download featured image: {$url}", 'yellow');
            return '';
        }

        file_put_contents($destPath, $content);
        CLI::write("  -> Featured image: {$filename}", 'cyan');

        return $filename;
    }

    private function processBodyImages(string $body, string $bodyHtml, string $mediaPath, string $baseUrl): array
    {
        $urlMap = [];

        // Collect image URLs from Markdown: ![alt](url) or ![alt](url "title")
        preg_match_all('/!\[[^\]]*\]\(([^)\s"]+)(?:\s+"[^"]*")?\)/', $body, $mdMatches);

        // Collect image URLs from HTML img src attributes
        preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/', $bodyHtml, $htmlMatches);

        $allUrls = array_unique(array_merge($mdMatches[1], $htmlMatches[1]));

        foreach ($allUrls as $url) {
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }

            $ext      = $this->extensionFromUrl($url);
            $imgUuid  = Uuid::uuid4()->toString();
            $filename = "{$imgUuid}.{$ext}";
            $destPath = $mediaPath . $filename;
            $content  = $this->fetchUrl($url);

            if ($content === false) {
                CLI::write("  Warning: could not download body image: {$url}", 'yellow');
                continue;
            }

            file_put_contents($destPath, $content);
            CLI::write("  -> Body image: {$filename}", 'cyan');

            $urlMap[$url] = $baseUrl . $filename;
        }

        if (!empty($urlMap)) {
            $body     = str_replace(array_keys($urlMap), array_values($urlMap), $body);
            $bodyHtml = str_replace(array_keys($urlMap), array_values($urlMap), $bodyHtml);
        }

        return [$body, $bodyHtml];
    }

    private function extensionFromUrl(string $url): string
    {
        $path      = parse_url($url, PHP_URL_PATH) ?? '';
        $ext       = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif'];

        return in_array($ext, $allowedExts, true) ? $ext : 'jpg';
    }

    private function fetchUrl(string $url): string|false
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; BlogImporter/1.0)',
        ]);

        $content  = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($content === false || $httpCode !== 200) {
            return false;
        }

        return $content;
    }
}
