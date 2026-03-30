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
        Schema::create('exhibition_enquiries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->string('name');
            $table->string('brand_name');
            $table->string('email');
            $table->string('contact_number', 15)->nullable();
            $table->string('other_category')->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();

            $table->string('social_media');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exhibition_enquiries');
    }
};
