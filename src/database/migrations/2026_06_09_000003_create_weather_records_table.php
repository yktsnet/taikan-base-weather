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
        Schema::create('weather_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->constrained()->onDelete('cascade');
            $table->timestamp('observed_at')->comment('観測時刻');
            $table->decimal('precipitation_mm', 5, 1)->comment('降水量 (mm/h)');
            $table->decimal('temperature_c', 4, 1)->comment('気温 (℃)');
            $table->timestamps();

            // 複合インデックス
            $table->index(['station_id', 'observed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weather_records');
    }
};
