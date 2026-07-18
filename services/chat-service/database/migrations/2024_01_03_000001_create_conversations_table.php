<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('item_id')->index();
            $t->string('item_title')->nullable();
            $t->string('item_thumb')->nullable();
            $t->unsignedBigInteger('owner_id');       // seller
            $t->string('owner_name')->nullable();
            $t->unsignedBigInteger('interested_id');  // buyer
            $t->string('interested_name')->nullable();
            $t->timestamp('last_message_at')->nullable()->index();
            $t->timestamps();
            $t->unique(['item_id','interested_id']);  // one thread per buyer/item
        });
    }
    public function down(): void { Schema::dropIfExists('conversations'); }
};
