<?php

namespace App\Controllers;

use App\Models\PostModel;
use CodeIgniter\HTTP\ResponseInterface;

class Feed extends BaseController
{
    public function rss(): ResponseInterface
    {
        $postModel = new PostModel();

        $posts = $postModel
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->orderBy('published_at', 'DESC')
            ->limit(20)
            ->findAll();

        $baseURL = rtrim(config('App')->baseURL, '/');

        $data = [
            'posts'       => $posts,
            'title'       => config('App')->siteName,
            'baseURL'     => $baseURL,
            'feedURL'     => $baseURL . '/feed/rss',
            'description' => config('App')->siteName . ' — RSS Feed',
        ];

        $xml = view('feed/rss', $data);

        return $this->response
            ->setContentType('application/rss+xml; charset=UTF-8')
            ->setBody($xml);
    }
}
