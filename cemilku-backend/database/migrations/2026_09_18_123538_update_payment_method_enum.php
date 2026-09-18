<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("
            ALTER TABLE payments
            MODIFY method ENUM(
                'transfer',
                'cod',
                'midtrans'
            ) NOT NULL DEFAULT 'transfer'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE payments
            MODIFY method ENUM(
                'transfer',
                'cod'
            ) NOT NULL DEFAULT 'transfer'
        ");
    }
};