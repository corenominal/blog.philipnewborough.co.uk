<?php

namespace App\Controllers;

use App\Models\PostModel;
use App\Models\TagModel;

class Home extends BaseController
{
    /**
     * Display the home page
     *
     * Renders the home view with associated stylesheets and scripts.
     * Sets up the page title and passes data to the view layer.
     *
     * @return string The rendered home view
     */
    public function index(): string
    {
        $postModel = new PostModel();

        $latestPost = $postModel
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->orderBy('published_at', 'DESC')
            ->first();

        $otherPosts = [];
        if ($latestPost) {
            $otherPosts = $postModel
                ->where('status', 'published')
                ->where('visibility', 'public')
                ->where('id !=', $latestPost['id'])
                ->orderBy('published_at', 'DESC')
                ->findAll();
        }

        // Fetch all tags for these posts in one query, grouped by post_id
        $allPostIds = [];
        if ($latestPost) {
            $allPostIds[] = $latestPost['id'];
        }
        foreach ($otherPosts as $p) {
            $allPostIds[] = $p['id'];
        }

        $tagsByPost = [];
        if (!empty($allPostIds)) {
            $tagModel = new TagModel();
            $tags = $tagModel->whereIn('post_id', $allPostIds)->findAll();
            foreach ($tags as $tag) {
                $tagsByPost[$tag['post_id']][] = $tag;
            }
        }

        if ($latestPost) {
            $latestPost['tags_list'] = $tagsByPost[$latestPost['id']] ?? [];
            $latestPost['body_html'] = str_replace('<img ', '<img loading="lazy" ', $latestPost['body_html']);
        }
        foreach ($otherPosts as &$p) {
            $p['tags_list'] = $tagsByPost[$p['id']] ?? [];
        }
        unset($p);

        // Array of javascript files to include
        $data['js'] = ['home'];
        // Array of CSS files to include
        $data['css'] = ['home'];
        // Set the page title
        $data['title'] = 'Home';
        $data['latestPost'] = $latestPost;
        $data['otherPosts'] = $otherPosts;

        return view('home', $data);
    }
}
