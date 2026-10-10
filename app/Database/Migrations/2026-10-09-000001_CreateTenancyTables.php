<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTenancyTables extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 160],
            'type' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'personal'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'active'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('tenants', true);

        $this->forge->addField([
            'tenant_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            // Must match CodeIgniter Shield's users.id (INT UNSIGNED).
            'user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'role' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'owner'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'active'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey(['tenant_id', 'user_id'], true);
        $this->forge->addKey(['user_id', 'status']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('tenant_users', true);

        $this->forge->addField([
            // Must match CodeIgniter Shield's users.id (INT UNSIGNED).
            'user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'currency' => ['type' => 'CHAR', 'constraint' => 3, 'default' => 'BRL'],
            'timezone' => ['type' => 'VARCHAR', 'constraint' => 64, 'default' => 'America/Sao_Paulo'],
            'notification_settings' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('user_id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('user_preferences', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('user_preferences', true);
        $this->forge->dropTable('tenant_users', true);
        $this->forge->dropTable('tenants', true);
    }
}
