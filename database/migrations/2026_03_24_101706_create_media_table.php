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
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('level')->default(0);

            $table->string('title');
            $table->text('description')->nullable();
            $table->string('cover_photo')->nullable();

            $table->foreign('parent_id')->references('id')->on('media')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
