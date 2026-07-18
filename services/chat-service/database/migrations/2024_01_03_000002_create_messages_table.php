<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $t->unsignedBigInteger('sender_id');
            $t->string('sender_name')->nullable();
            $t->text('body');
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
            $t->index(['conversation_id','id']);
        });
    }
    public function down(): void { Schema::dropIfExists('messages'); }
};
