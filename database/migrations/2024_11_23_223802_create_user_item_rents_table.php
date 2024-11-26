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
        Schema::create('user_item_rents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_item_id')->constrained()->onDelete('cascade');
            $table->timestamp('start_date');
            $table->timestamp('end_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_item_rents', function (Blueprint $table) {
            $table->dropForeign(['user_item_id']);
        });
        Schema::dropIfExists('user_item_rents');
    }
};
