<?php

namespace App\Controllers\Admin;

use App\Libraries\Markdown;
use App\Models\PostModel;
use App\Models\TagModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use Ramsey\Uuid\Uuid;

class Posts extends BaseController
{
    /**
     * Show the "create new post" editor.
     *
     * @return string
     */
    public function create(): string
    {
        $data['post']   = null;
        $data['title']  = 'New Post';
        $data['js']     = ['admin/post_editor'];
        $data['css']    = ['admin/post_editor', 'post'];
        $data['action'] = site_url('admin/posts/store');
        $data['isNew']  = true;
        // Provide list of existing tags for the datalist
        $tagModel = new TagModel();
        $tags = $tagModel->select('tag')->distinct()->orderBy('tag')->findAll();
        $data['all_tags'] = array_column($tags, 'tag');

        return view('admin/post_editor', $data);
    }

    /**
     * Store a new post.
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function store(): \CodeIgniter\HTTP\ResponseInterface
    {
        helper('url');

        $input     = $this->request->getPost();
        $postModel = new PostModel();
        $tagModel  = new TagModel();

        [$titleRaw, $titleHtml, $bodyRaw, $bodyHtml] = $this->convertMarkdown(
            trim($input['title'] ?? ''),
            trim($input['body'] ?? '')
        );

        $slug    = $this->resolveSlug(trim($input['slug'] ?? ''), $titleRaw, $postModel);
        $tagsRaw = trim($input['tags'] ?? '');
        $status  = ($input['_save_action'] ?? '') === 'publish' ? 'published' : ($input['status'] ?? 'draft');
        $pubAt   = !empty($input['published_at']) ? $input['published_at'] : null;
        if ($status === 'published' && $pubAt === null) {
            $pubAt = date('Y-m-d H:i:s');
        }

        $postData = [
            'uuid'           => Uuid::uuid4()->toString(),
            'title'          => $titleRaw,
            'title_html'     => $titleHtml,
            'slug'           => $slug,
            'body'           => $bodyRaw,
            'body_html'      => $bodyHtml,
            'excerpt'        => trim($input['excerpt'] ?? ''),
            'tags'           => $tagsRaw,
            'featured_image' => trim($input['featured_image'] ?? ''),
            'visibility'     => (int) ($input['visibility'] ?? 0),
            'status'         => $status,
            'comment_status' => 'open',
            'hitcounter'     => 0,
            'published_at'   => $pubAt,
        ];

        // If publishing, require certain fields to be present
        if ($status === 'published') {
            $errors = [];
            if (empty($postData['title'])) {
                $errors['title'] = 'Title is required for published posts.';
            }
            if (empty($postData['body'])) {
                $errors['body'] = 'Body is required for published posts.';
            }
            if (empty($postData['tags'])) {
                $errors['tags'] = 'Tags are required for published posts.';
            }
            if (empty($postData['excerpt'])) {
                $errors['excerpt'] = 'Excerpt is required for published posts.';
            }

            if (!empty($errors)) {
                return redirect()->back()->withInput()->with('errors', $errors);
            }
        }

        if (!$postModel->save($postData)) {
            return redirect()->back()->withInput()->with('errors', $postModel->errors());
        }

        $postId = $postModel->getInsertID();
        $this->saveTags($tagModel, $postId, $tagsRaw);

        return redirect()->to(site_url('admin/posts/' . $postId . '/edit'))
            ->with('success', 'Post created successfully.');
    }

    /**
     * Show the editor for an existing post.
     *
     * @param int $id
     * @return string
     */
    public function edit(int $id): string
    {
        $postModel = new PostModel();
        $tagModel  = new TagModel();

        $post = $postModel->find($id);

        if (!$post) {
            throw new PageNotFoundException("Post #{$id} not found.");
        }

        $post['tags_list'] = $tagModel->where('post_id', $id)->findAll();

        // All existing tags for the datalist
        $tags = $tagModel->select('tag')->distinct()->orderBy('tag')->findAll();
        $allTags = array_column($tags, 'tag');

        $data['post']   = $post;
        $data['title']  = 'Edit Post';
        $data['js']     = ['admin/post_editor'];
        $data['css']    = ['admin/post_editor', 'post'];
        $data['action'] = site_url('admin/posts/' . $id . '/update');
        $data['isNew']  = false;
        $data['all_tags'] = $allTags;

        return view('admin/post_editor', $data);
    }

    /**
     * Update an existing post.
     *
     * @param int $id
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function update(int $id): \CodeIgniter\HTTP\ResponseInterface
    {
        helper('url');

        $postModel = new PostModel();
        $tagModel  = new TagModel();

        $existing = $postModel->find($id);

        if (!$existing) {
            throw new PageNotFoundException("Post #{$id} not found.");
        }

        $input = $this->request->getPost();

        [$titleRaw, $titleHtml, $bodyRaw, $bodyHtml] = $this->convertMarkdown(
            trim($input['title'] ?? ''),
            trim($input['body'] ?? '')
        );

        $slug    = $existing['status'] === 'published'
            ? $existing['slug']
            : $this->resolveSlug(trim($input['slug'] ?? ''), $titleRaw, $postModel, $id);
        $tagsRaw = trim($input['tags'] ?? '');
        $status  = ($input['_save_action'] ?? '') === 'publish' ? 'published' : ($input['status'] ?? 'draft');
        $pubAt   = !empty($input['published_at']) ? $input['published_at'] : null;
        if ($status === 'published' && $pubAt === null) {
            $pubAt = date('Y-m-d H:i:s');
        }

        $postData = [
            'id'             => $id,
            'title'          => $titleRaw,
            'title_html'     => $titleHtml,
            'slug'           => $slug,
            'body'           => $bodyRaw,
            'body_html'      => $bodyHtml,
            'excerpt'        => trim($input['excerpt'] ?? ''),
            'tags'           => $tagsRaw,
            'featured_image' => trim($input['featured_image'] ?? ''),
            'visibility'     => (int) ($input['visibility'] ?? 0),
            'status'         => $status,
            'published_at'   => $pubAt,
        ];

        // If publishing, require certain fields to be present
        if ($status === 'published') {
            $errors = [];
            if (empty($postData['title'])) {
                $errors['title'] = 'Title is required for published posts.';
            }
            if (empty($postData['body'])) {
                $errors['body'] = 'Body is required for published posts.';
            }
            if (empty($postData['tags'])) {
                $errors['tags'] = 'Tags are required for published posts.';
            }
            if (empty($postData['excerpt'])) {
                $errors['excerpt'] = 'Excerpt is required for published posts.';
            }

            if (!empty($errors)) {
                return redirect()->back()->withInput()->with('errors', $errors);
            }
        }

        if (!$postModel->save($postData)) {
            return redirect()->back()->withInput()->with('errors', $postModel->errors());
        }

        // Rebuild tags
        \Config\Database::connect()->table('tags')->where('post_id', $id)->delete();
        $this->saveTags($tagModel, $id, $tagsRaw);

        return redirect()->to(site_url('admin/posts/' . $id . '/edit'))
            ->with('success', 'Post updated successfully.');
    }

    /**
     * AJAX endpoint: convert Markdown body to HTML for the live preview.
     * Expects a JSON body: { "markdown": "..." }
     * Returns JSON: { "body_html": "..." }
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function preview(): \CodeIgniter\HTTP\ResponseInterface
    {
        $input   = $this->request->getJSON(true);
        $bodyRaw = trim($input['markdown'] ?? '');

        if (empty($bodyRaw)) {
            return $this->response->setJSON(['body_html' => '']);
        }

        try {
            $markdown = new Markdown();
            $markdown->setMarkdown($bodyRaw);
            $result   = $markdown->convert();
            $bodyHtml = $result['html'] ?? '';
        } catch (\Exception $e) {
            $bodyHtml = '';
        }

        return $this->response->setJSON(['body_html' => $bodyHtml]);
    }

    /**
     * AJAX endpoint: upload a featured image file.
     * Expects a file field named `featured_image` in the multipart/form-data body.
     * Validates mime/type and exact dimensions (1200x630) and moves the file
     * into `public/media` as `og-{uuid}.{ext}`.
     * Returns JSON: { success: bool, filename: string, url: string, error?: string }
     */
    public function upload_featured_image(): \CodeIgniter\HTTP\ResponseInterface
    {
        $file = $this->request->getFile('featured_image');

        if (!$file || !$file->isValid()) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => 'No file uploaded.']);
        }

        $mime = $file->getMimeType();
        $allowed = ['image/png', 'image/jpeg', 'image/webp', 'image/gif'];
        if (!in_array($mime, $allowed, true)) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => 'Invalid file type.']);
        }

        $tmpName = $file->getTempName();
        $sizeInfo = @getimagesize($tmpName);
        if (!$sizeInfo) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => 'Unable to read image.']);
        }

        [$width, $height] = [$sizeInfo[0], $sizeInfo[1]];
        if ($width !== 1200 || $height !== 630) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => 'Image must be exactly 1200 x 630 pixels.']);
        }

        $ext = $file->getClientExtension() ?: pathinfo($file->getName(), PATHINFO_EXTENSION);
        $ext = strtolower($ext);
        if ($ext === 'jpeg') {
            $ext = 'jpg';
        }

        $uuid = Uuid::uuid4()->toString();
        $filename = 'og-' . $uuid . '.' . $ext;

        $destDir = FCPATH . 'media/';
        if (!is_dir($destDir)) {
            @mkdir($destDir, 0755, true);
        }

        try {
            $file->move($destDir, $filename);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'error' => 'Failed to move uploaded file.']);
        }

        $url = site_url('media/' . $filename);

        return $this->response->setJSON(['success' => true, 'filename' => $filename, 'url' => $url]);
    }

    /**
     * AJAX endpoint: remove a featured image file from public/media.
     * Expects POST field `filename`.
     */
    public function remove_featured_image(): \CodeIgniter\HTTP\ResponseInterface
    {
        $filename = $this->request->getPost('filename');
        if (empty($filename)) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => 'No filename provided.']);
        }

        $basename = basename($filename);
        $path = FCPATH . 'media/' . $basename;
        if (is_file($path)) {
            try {
                @unlink($path);
            } catch (\Exception $e) {
                // ignore unlink errors
            }
        }

        return $this->response->setJSON(['success' => true]);
    }

    /**
     * AJAX endpoint: list existing featured images in public/media
     * Returns JSON: { files: [ { filename, url }, ... ] }
     */
    public function list_featured_images(): \CodeIgniter\HTTP\ResponseInterface
    {
        $dir = FCPATH . 'media/';
        $files = [];

        if (is_dir($dir)) {
            $items = scandir($dir);
            foreach ($items as $f) {
                if ($f === '.' || $f === '..') continue;
                if (strpos($f, 'og-') !== 0) continue;
                $path = $dir . $f;
                if (!is_file($path)) continue;
                $files[] = [
                    'filename' => $f,
                    'url'      => site_url('media/' . $f),
                ];
            }
        }

        return $this->response->setJSON(['files' => $files]);
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    /**
     * Convert title and body from Markdown to HTML.
     * Falls back gracefully if the Markdown service is unavailable.
     *
     * @return array{0: string, 1: string, 2: string, 3: string}
     *              [titleRaw, titleHtml, bodyRaw, bodyHtml]
     */
    private function convertMarkdown(string $titleRaw, string $bodyRaw): array
    {
        $titleHtml = $titleRaw;
        $bodyHtml  = '';

        try {
            $markdown = new Markdown();

            if (!empty($titleRaw)) {
                $markdown->setMarkdown('# ' . $titleRaw);
                $result    = $markdown->convert();
                $titleHtml = strip_tags($result['html'] ?? $titleRaw);
            }

            if (!empty($bodyRaw)) {
                $markdown->setMarkdown($bodyRaw);
                $result   = $markdown->convert();
                $bodyHtml = $result['html'] ?? '';
            }
        } catch (\Exception $e) {
            // Markdown service unavailable; store raw values only
            $bodyHtml = nl2br(esc($bodyRaw));
        }

        return [$titleRaw, $titleHtml, $bodyRaw, $bodyHtml];
    }

    /**
     * Derive a unique URL slug from the provided value or the post title.
     * If the base slug is already taken, appends -1, -2, etc. until unique.
     *
     * @param int|null $excludeId Post ID to exclude from the uniqueness check (used on update).
     */
    private function resolveSlug(string $slug, string $title, PostModel $postModel, ?int $excludeId = null): string
    {
        $base      = !empty($slug) ? $slug : url_title($title, '-', true);
        $candidate = $base;
        $i         = 1;

        while (true) {
            $builder = $postModel->where('slug', $candidate);
            if ($excludeId !== null) {
                $builder = $builder->where('id !=', $excludeId);
            }
            if ($builder->countAllResults() === 0) {
                break;
            }
            $candidate = $base . '-' . $i;
            $i++;
        }

        return $candidate;
    }

    /**
     * Persist tags from a comma-separated string for a given post.
     */
    private function saveTags(TagModel $tagModel, int $postId, string $tagsRaw): void
    {
        if (empty($tagsRaw)) {
            return;
        }

        $tagList = array_filter(array_map('trim', explode(',', $tagsRaw)));

        foreach ($tagList as $tag) {
            $tagSlug = url_title($tag, '-', true);
            $tagModel->skipValidation(true)->save([
                'post_id' => $postId,
                'tag'     => $tag,
                'slug'    => $tagSlug,
            ]);
        }
    }
}
