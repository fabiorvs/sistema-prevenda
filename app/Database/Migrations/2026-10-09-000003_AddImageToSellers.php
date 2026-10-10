<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddImageToSellers extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('sellers', [
            'image_path' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'website'],
            'image_mime_type' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'after' => 'image_path'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('sellers', ['image_path', 'image_mime_type']);
    }
}
