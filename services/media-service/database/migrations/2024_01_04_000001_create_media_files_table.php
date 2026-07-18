<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('media_files', function (Blueprint $t) {
            $t->uuid('id')->primary();                  // unique media identifier
            $t->unsignedBigInteger('item_id')->nullable()->index();
            $t->string('original_name');
            $t->string('mime');
            $t->string('extension', 10);
            $t->unsignedBigInteger('size');             // bytes
            $t->string('path');                         // path inside the blob volume
            $t->string('checksum', 64)->nullable();     // sha256, dedupe/integrity
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('media_files'); }
};
