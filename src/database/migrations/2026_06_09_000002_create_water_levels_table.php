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
        Schema::create('water_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->constrained()->onDelete('cascade');
            $table->timestamp('observed_at')->comment('観測時刻');
            $table->decimal('level_m', 5, 2)->comment('水位 (m)');
            $table->enum('alert_status', ['normal', 'caution', 'warning', 'danger'])->comment('アラート状態');
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
        Schema::dropIfExists('water_levels');
    }
};
