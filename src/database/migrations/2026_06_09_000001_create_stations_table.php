<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stations', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('国交省観測所コード');
            $table->string('name')->comment('観測所名');
            $table->string('river_name')->comment('河川名');
            $table->string('prefecture')->comment('都道府県');
            $table->decimal('lat', 10, 7)->comment('緯度');
            $table->decimal('lng', 10, 7)->comment('経度');
            $table->decimal('warning_level', 5, 2)->nullable()->comment('警戒水位 (m)');
            $table->decimal('danger_level', 5, 2)->nullable()->comment('危険水位 (m)');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stations');
    }
};
