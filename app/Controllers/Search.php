<?php

namespace App\Controllers;

use App\Models\PostModel;
use App\Models\TagModel;

class Search extends BaseController
{
    public function index(): string
    {
        $query = trim((string) $this->request->getGet('q'));

        $posts = [];

        if ($query !== '') {
            $postModel = new PostModel();

            $terms = array_values(array_unique(array_filter(preg_split('/\s+/', $query))));

            $postModel
                ->where('status', 'published')
                ->where('visibility', 'public')
                ->groupStart();

            foreach ($terms as $term) {
                $postModel
                    ->orLike('title', $term)
                    ->orLike('excerpt', $term)
                    ->orLike('body', $term);
            }

            $postModel->groupEnd();

            $posts = $postModel
                ->orderBy('published_at', 'DESC')
                ->findAll();

            if (!empty($posts)) {
                $tagModel   = new TagModel();
                $allPostIds = array_column($posts, 'id');
                $tags       = $tagModel->whereIn('post_id', $allPostIds)->findAll();

                $tagsByPost = [];
                foreach ($tags as $tag) {
                    $tagsByPost[$tag['post_id']][] = $tag;
                }

                foreach ($posts as &$post) {
                    $post['tags_list'] = $tagsByPost[$post['id']] ?? [];
                }
                unset($post);
            }
        }

        $data['query']  = $query;
        $data['posts']  = $posts;
        $data['title']  = $query !== '' ? 'Search: ' . esc($query) : 'Search';
        $data['css']    = ['home'];
        $data['js']     = ['home'];

        return view('search', $data);
    }
}
