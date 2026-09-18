<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->unsignedBigInteger('province_id')
                ->nullable()
                ->after('full_address');

            $table->string('province_name')
                ->nullable()
                ->after('province_id');

            $table->unsignedBigInteger('city_id')
                ->nullable()
                ->after('province_name');
        });
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropColumn([
                'province_id',
                'province_name',
                'city_id',
            ]);
        });
    }
};