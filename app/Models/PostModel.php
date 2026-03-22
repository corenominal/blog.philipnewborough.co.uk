<?php

namespace App\Models;

use CodeIgniter\Model;

class PostModel extends Model
{
    protected $table            = 'posts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;

    protected $allowedFields = [
        'id',
        'uuid',
        'title',
        'title_html',
        'slug',
        'body',
        'body_html',
        'excerpt',
        'tags',
        'featured_image',
        'visibility',
        'status',
        'comment_status',
        'comment_count',
        'hitcounter',
        'published_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules = [
        'id'     => 'permit_empty|integer',
        'title'  => 'required|min_length[3]|max_length[255]',
        'slug'   => 'required|alpha_dash|is_unique[posts.slug,id,{id}]',
        'status' => 'required|in_list[draft,published,revision,trashed]',
        'uuid'   => 'required|is_unique[posts.uuid,id,{id}]',
    ];
}