<?php

namespace App\Controllers;

use App\Models\MetaModel;
use App\Models\PostModel;
use App\Models\TagModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Post extends BaseController
{
    /**
     * Display a single post by slug
     *
     * @param string $slug The post slug
     * @return string The rendered post view
     * @throws PageNotFoundException
     */
    public function show(string $slug): string
    {
        $postModel = new PostModel();

        $post = $postModel
            ->where('slug', $slug)
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->first();

        if (! $post) {
            throw new PageNotFoundException();
        }

        $tagModel = new TagModel();
        $post['tags_list'] = $tagModel->where('post_id', $post['id'])->findAll();

        $metaModel = new MetaModel();
        $videoMeta = $metaModel->where('post_id', $post['id'])->where('meta_key', 'post_video')->first();
        $post['post_video'] = $videoMeta ? $videoMeta['meta_value'] : '';

        // Find similar posts by shared tags
        $similarPosts   = [];
        $similarHeading = 'Latest posts';

        if (!empty($post['tags_list'])) {
            $tagSlugs        = array_column($post['tags_list'], 'slug');
            $matchingTags    = $tagModel->whereIn('slug', $tagSlugs)->where('post_id !=', $post['id'])->findAll();
            $matchingPostIds = array_unique(array_column($matchingTags, 'post_id'));

            if (!empty($matchingPostIds)) {
                $similarPosts = $postModel
                    ->whereIn('id', $matchingPostIds)
                    ->where('status', 'published')
                    ->where('visibility', 'public')
                    ->orderBy('published_at', 'DESC')
                    ->findAll();

                if (!empty($similarPosts)) {
                    $similarHeading = 'Similar posts';
                }
            }
        }

        // Fallback: 5 latest posts
        if (empty($similarPosts)) {
            $similarPosts = $postModel
                ->where('status', 'published')
                ->where('visibility', 'public')
                ->where('id !=', $post['id'])
                ->orderBy('published_at', 'DESC')
                ->limit(5)
                ->findAll();
        }

        // Load tags for similar posts in one query
        if (!empty($similarPosts)) {
            $similarPostIds = array_column($similarPosts, 'id');
            $similarTags    = $tagModel->whereIn('post_id', $similarPostIds)->findAll();
            $tagsByPost     = [];
            foreach ($similarTags as $tag) {
                $tagsByPost[$tag['post_id']][] = $tag;
            }
            foreach ($similarPosts as &$sp) {
                $sp['tags_list'] = $tagsByPost[$sp['id']] ?? [];
            }
            unset($sp);
        }

        $post['body_html'] = str_replace('<img ', '<img loading="lazy" ', $post['body_html']);

        $og = [
            'type'      => 'article',
            'title'     => $post['title'],
            'url'       => current_url(),
            'site_name' => config('App')->siteName,
        ];

        if (!empty($post['excerpt'])) {
            $og['description'] = $post['excerpt'];
        }

        if (!empty($post['featured_image'])) {
            $og['image']        = rtrim(config('App')->baseURL, '/') . '/media/' . $post['featured_image'];
            $og['image_width']  = 1200;
            $og['image_height'] = 630;
        }

        $data['css']            = ['post'];
        $data['js']             = ['post'];
        $data['title']          = $post['title'];
        $data['og']             = $og;
        $data['post']           = $post;
        $data['similarPosts']   = $similarPosts;
        $data['similarHeading'] = $similarHeading;

        return view('post', $data);
    }

    /**
     * Return a single post as JSON
     *
     * @param string $slug The post slug
     * @return \CodeIgniter\HTTP\ResponseInterface
     * @throws PageNotFoundException
     */
    public function showJson(string $slug): \CodeIgniter\HTTP\ResponseInterface
    {
        $postModel = new PostModel();

        $post = $postModel
            ->where('slug', $slug)
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->first();

        if (! $post) {
            throw new PageNotFoundException();
        }

        $tagModel        = new TagModel();
        $post['tags_list'] = $tagModel->where('post_id', $post['id'])->findAll();

        $payload = [
            'title'        => $post['title'],
            'slug'         => $post['slug'],
            'excerpt'      => $post['excerpt'],
            'body'         => $post['body'],
            'tags'         => array_column($post['tags_list'], 'name'),
            'published_at' => $post['published_at'],
            'url'          => rtrim(config('App')->baseURL, '/') . '/posts/' . $post['slug'],
        ];

        if (!empty($post['featured_image'])) {
            $payload['featured_image'] = rtrim(config('App')->baseURL, '/') . '/media/' . $post['featured_image'];
        }

        return $this->response
            ->setContentType('application/json')
            ->setBody(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Return the raw Markdown source of a single post
     *
     * @param string $slug The post slug
     * @return \CodeIgniter\HTTP\ResponseInterface
     * @throws PageNotFoundException
     */
    public function showMarkdown(string $slug): \CodeIgniter\HTTP\ResponseInterface
    {
        $postModel = new PostModel();

        $post = $postModel
            ->where('slug', $slug)
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->first();

        if (! $post) {
            throw new PageNotFoundException();
        }

        $markdown = '# ' . $post['title'] . "\n\n" . $post['body'];

        return $this->response
            ->setContentType('text/markdown; charset=UTF-8')
            ->setHeader('Content-Disposition', 'inline; filename="' . $post['slug'] . '.md"')
            ->setBody($markdown);
    }
}
