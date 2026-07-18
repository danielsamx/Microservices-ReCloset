<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->index();
            $t->string('type')->default('message');
            $t->string('title')->nullable();
            $t->string('body')->nullable();
            $t->jsonb('data')->nullable();
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('notifications'); }
};
