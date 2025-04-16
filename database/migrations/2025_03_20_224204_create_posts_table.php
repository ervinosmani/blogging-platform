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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('content');
            $table->string('category');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); //Lidhja me tabelen e perdoruesve
            $table->integer('likes')->default(0);
            $table->enum('status', ['published', 'draft'])->default('draft');
            $table->timestamps();
            $table->timestamp('published_at')->nullable(); //Kur eshte publikuar
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
