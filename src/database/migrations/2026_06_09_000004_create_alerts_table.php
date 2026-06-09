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
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->constrained();
            $table->timestamp('triggered_at')->comment('トリガー時刻');
            $table->enum('level', ['caution', 'warning', 'danger'])->comment('アラートレベル');
            $table->decimal('level_m', 5, 2)->comment('トリガー時の水位 (m)');
            $table->boolean('notified')->default(false)->comment('SES送信済みフラグ');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
