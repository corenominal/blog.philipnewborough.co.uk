<?php

namespace App\Controllers\Api;

use App\Models\PostModel;

class Posts extends BaseController
{
    public function latest()
    {
        $limit = (int) ($this->request->getGet('limit') ?? 10);
        $limit = max(1, min($limit, 50));

        $model = new PostModel();

        $posts = $model
            ->select('uuid, title, slug, excerpt, featured_image, tags, published_at')
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->orderBy('published_at', 'DESC')
            ->limit($limit)
            ->findAll();

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $posts,
        ]);
    }
}
