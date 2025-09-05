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
        Schema::create('telegram_configs', function (Blueprint $table) {
    $table->id();
    $table->string('bot_token');
    $table->string('bot_username');       // @YourBotName
    $table->string('channel_username');   // @ChannelName
    $table->text('bot_description')->nullable();
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_configs');
    }
};
