<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('slug')->unique();
        });
        Schema::create('sizes', function (Blueprint $t) {
            $t->id(); $t->string('label'); $t->unsignedInteger('position')->default(0);
        });
        Schema::create('colors', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('hex', 7);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('categories');
        Schema::dropIfExists('sizes');
        Schema::dropIfExists('colors');
    }
};
