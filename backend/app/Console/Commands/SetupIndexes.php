<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use MongoDB\Client as MongoClient;

/**
 * SetupIndexes — creates all required MongoDB indexes for ComplySmart.
 *
 * Run once after connecting to MongoDB Atlas:
 *   php artisan complysmart:setup-indexes
 *
 * Safe to re-run (createIndexes is idempotent).
 */
class SetupIndexes extends Command
{
    protected $signature   = 'complysmart:setup-indexes';
    protected $description = 'Create all MongoDB indexes for ComplySmart collections';

    public function handle(): int
    {
        $mongoUri = config('database.connections.mongodb.dsn');
        $dbName   = config('database.connections.mongodb.database');

        $this->info("Connecting to MongoDB: {$dbName}");

        try {
            $client = new MongoClient($mongoUri);
            $db     = $client->selectDatabase($dbName);
        } catch (\Throwable $e) {
            $this->error("Failed to connect: {$e->getMessage()}");
            return self::FAILURE;
        }

        $indexes = [

            // ─── users ────────────────────────────────────────────────────────
            'users' => [
                ['key' => ['email' => 1], 'unique' => true, 'name' => 'users_email_unique'],
                ['key' => ['business_id' => 1], 'name' => 'users_business_id'],
                ['key' => ['role' => 1], 'name' => 'users_role'],
            ],

            // ─── businesses ───────────────────────────────────────────────────
            'businesses' => [
                ['key' => ['owner_id' => 1], 'name' => 'businesses_owner_id'],
                ['key' => ['registration_no' => 1], 'sparse' => true, 'name' => 'businesses_registration_no'],
                ['key' => ['compliance_score' => -1], 'name' => 'businesses_compliance_score_desc'],
            ],

            // ─── documents ────────────────────────────────────────────────────
            'documents' => [
                ['key' => ['business_id' => 1], 'name' => 'documents_business_id'],
                ['key' => ['status' => 1], 'name' => 'documents_status'],
                ['key' => ['expiry_date' => 1], 'sparse' => true, 'name' => 'documents_expiry_date'],
                ['key' => ['business_id' => 1, 'status' => 1], 'name' => 'documents_business_status'],
            ],

            // ─── tasks ────────────────────────────────────────────────────────
            'tasks' => [
                ['key' => ['business_id' => 1], 'name' => 'tasks_business_id'],
                ['key' => ['status' => 1], 'name' => 'tasks_status'],
                ['key' => ['due_date' => 1], 'sparse' => true, 'name' => 'tasks_due_date'],
                ['key' => ['business_id' => 1, 'status' => 1], 'name' => 'tasks_business_status'],
                ['key' => ['assigned_to' => 1], 'sparse' => true, 'name' => 'tasks_assigned_to'],
            ],

            // ─── renewals ─────────────────────────────────────────────────────
            'renewals' => [
                ['key' => ['business_id' => 1], 'name' => 'renewals_business_id'],
                ['key' => ['due_date' => 1], 'name' => 'renewals_due_date'],
                ['key' => ['status' => 1], 'name' => 'renewals_status'],
                ['key' => ['business_id' => 1, 'due_date' => 1], 'name' => 'renewals_business_due'],
            ],

            // ─── notifications ────────────────────────────────────────────────
            'notifications' => [
                ['key' => ['user_id' => 1], 'name' => 'notifications_user_id'],
                ['key' => ['user_id' => 1, 'read_at' => 1], 'name' => 'notifications_user_read'],
                ['key' => ['created_at' => -1], 'name' => 'notifications_created_desc'],
            ],

            // ─── audit_logs ───────────────────────────────────────────────────
            'audit_logs' => [
                ['key' => ['user_id' => 1], 'name' => 'audit_user_id'],
                ['key' => ['entity_type' => 1, 'entity_id' => 1], 'name' => 'audit_entity'],
                ['key' => ['created_at' => -1], 'name' => 'audit_created_desc'],
                // TTL index: automatically delete audit logs older than 2 years
                ['key' => ['created_at' => 1], 'expireAfterSeconds' => 63072000, 'name' => 'audit_ttl_2y'],
            ],

            // ─── personal_access_tokens (Sanctum) ────────────────────────────
            'personal_access_tokens' => [
                ['key' => ['tokenable_id' => 1], 'name' => 'tokens_tokenable_id'],
                ['key' => ['token' => 1], 'unique' => true, 'name' => 'tokens_token_unique'],
            ],

            // ─── ai_chat_histories ────────────────────────────────────────────
            'ai_chat_histories' => [
                ['key' => ['user_id' => 1, 'business_id' => 1], 'name' => 'ai_user_business'],
                ['key' => ['created_at' => -1], 'name' => 'ai_created_desc'],
            ],
        ];

        $created = 0;
        $failed  = 0;

        foreach ($indexes as $collection => $collectionIndexes) {
            $this->line("  <fg=cyan>→ {$collection}</>");
            $col = $db->selectCollection($collection);

            foreach ($collectionIndexes as $indexSpec) {
                $key     = $indexSpec['key'];
                $options = array_diff_key($indexSpec, ['key' => null]);

                try {
                    $col->createIndex($key, $options);
                    $this->line("    <fg=green>✓</> {$indexSpec['name']}");
                    $created++;
                } catch (\Throwable $e) {
                    $this->error("    ✗ {$indexSpec['name']}: {$e->getMessage()}");
                    $failed++;
                }
            }
        }

        $this->newLine();
        $this->info("Done. Created/verified: {$created} indexes. Failed: {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
