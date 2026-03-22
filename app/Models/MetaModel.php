<?php

namespace App\Models;

use CodeIgniter\Model;

class PostMetaModel extends Model
{
    protected $table            = 'meta';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $allowedFields = [
        'id',
        'post_id',
        'meta_key',
        'meta_value',
        'created_at',
        'updated_at',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules = [
        'post_id'  => 'required|is_natural_no_zero',
        'meta_key' => 'required|min_length[1]|max_length[255]',
    ];
}