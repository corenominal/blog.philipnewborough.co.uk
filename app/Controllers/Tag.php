<?php

namespace App\Controllers;

use App\Models\PostModel;
use App\Models\TagModel;

class Tag extends BaseController
{
    /**
     * Display posts for a given tag slug
     *
     * @param string $slug The tag slug
     * @return string The rendered tag view
     */
    public function show(string $slug): string
    {
        $tagModel = new TagModel();

        // Find all tag records matching this slug to determine the tag name and post IDs
        $tagRecords = $tagModel->where('slug', esc($slug, 'url'))->findAll();

        if (empty($tagRecords)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $tagName   = $tagRecords[0]['tag'];
        $postIds   = array_column($tagRecords, 'post_id');

        $postModel = new PostModel();
        $posts     = $postModel
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->whereIn('id', $postIds)
            ->orderBy('published_at', 'DESC')
            ->findAll();

        if (empty($posts)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        // Batch-load tags for all posts
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

        $data['posts']   = $posts;
        $data['tagName'] = $tagName;
        $data['tagSlug'] = $slug;
        $data['title']   = 'Posts tagged "' . esc($tagName) . '"';
        $data['css']     = ['home'];
        $data['js']      = ['home'];

        return view('tag', $data);
    }
}
