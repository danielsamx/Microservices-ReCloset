<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('items', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('owner_id')->index();   // user id from Auth Service
            $t->string('owner_name')->nullable();
            $t->string('name');
            $t->text('description')->nullable();
            $t->foreignId('category_id')->constrained('categories');
            $t->foreignId('size_id')->constrained('sizes');
            $t->foreignId('color_id')->constrained('colors');
            $t->decimal('price', 10, 2)->default(0);
            $t->string('status')->default('available')->index();
            $t->timestamps();
        });
        Schema::create('item_media', function (Blueprint $t) {
            $t->id();
            $t->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $t->string('media_id');            // reference returned by Media Service
            $t->string('url');                 // public retrieval url
            $t->string('mime')->nullable();
            $t->unsignedInteger('position')->default(0);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('item_media');
        Schema::dropIfExists('items');
    }
};
