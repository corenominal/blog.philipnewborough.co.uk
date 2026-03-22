<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterPostsPublishedAtNullable extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('posts', [
            'published_at' => [
                'name'    => 'published_at',
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->modifyColumn('posts', [
            'published_at' => [
                'name'    => 'published_at',
                'type'    => 'DATETIME',
                'null'    => false,
                'default' => '0000-00-00 00:00:00',
            ],
        ]);
    }
}
