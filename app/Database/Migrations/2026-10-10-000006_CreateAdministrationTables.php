<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAdministrationTables extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'slug' => ['type' => 'VARCHAR', 'constraint' => 60],
            'name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'description' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'active'],
            'user_limit' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('plans', true);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'plan_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'active'],
            'starts_at' => ['type' => 'DATETIME', 'null' => true],
            'ends_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('tenant_id');
        $this->forge->addKey(['plan_id', 'status']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('plan_id', 'plans', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('tenant_plan_assignments', true);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'actor_user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'target_user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'tenant_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'action' => ['type' => 'VARCHAR', 'constraint' => 100],
            'metadata' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['actor_user_id', 'created_at']);
        $this->forge->addKey(['tenant_id', 'created_at']);
        $this->forge->addForeignKey('actor_user_id', 'users', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('target_user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('admin_audit_logs', true);

        $now = gmdate('Y-m-d H:i:s');
        $this->db->table('plans')->insertBatch([
            ['slug' => 'free', 'name' => 'Gratuito', 'description' => 'Plano inicial sem cobrança.', 'status' => 'active', 'user_limit' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'collector', 'name' => 'Colecionador', 'description' => 'Estrutura preparada para expansão futura.', 'status' => 'active', 'user_limit' => 3, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $freePlan = $this->db->table('plans')->select('id')->where('slug', 'free')->get()->getRowArray();
        foreach ($this->db->table('tenants')->select('id')->get()->getResultArray() as $tenant) {
            $this->db->table('tenant_plan_assignments')->insert([
                'tenant_id' => $tenant['id'],
                'plan_id' => $freePlan['id'],
                'status' => 'active',
                'starts_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        $this->forge->dropTable('admin_audit_logs', true);
        $this->forge->dropTable('tenant_plan_assignments', true);
        $this->forge->dropTable('plans', true);
    }
}
