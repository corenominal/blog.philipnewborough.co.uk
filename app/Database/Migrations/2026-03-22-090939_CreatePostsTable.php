<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreatePostsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'uuid' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'default'    => '',
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'default'    => '',
            ],
            'title_html' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'default'    => '',
            ],
            'slug' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'default'    => '',
            ],
            'body' => [
                'type' => 'TEXT',
            ],
            'body_html' => [
                'type' => 'TEXT',
            ],
            'excerpt' => [
                'type' => 'TEXT',
            ],
            'tags' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'default'    => '',
            ],
            'featured_image' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'default'    => '',
            ],
            'visibility' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'comment'    => '0 = visible, 1 = hidden',
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'default'    => 'draft',
                'comment'    => 'draft, published, revision, trashed',
            ],
            'comment_status' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'default'    => 'open',
                'comment'    => 'open, closed',
            ],
            'comment_count' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'hitcounter' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'published_at' => [
                'type'    => 'DATETIME',
                'default' => '0000-00-00 00:00:00',
            ],
            'created_at' => [
                'type'    => 'DATETIME',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
            'updated_at' => [
                'type'    => 'DATETIME',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('slug'); // Important for SEO lookup performance
        
        $this->forge->createTable('posts');
    }

    public function down()
    {
        $this->forge->dropTable('posts');
    }
}