<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Adds multi-size support for items.
 * items.size_id is KEPT (it stores the primary/first size) so existing data,
 * queries and API consumers keep working. The pivot is the source of truth
 * for "all available sizes".
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('item_size', function (Blueprint $t) {
            $t->id();
            $t->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $t->foreignId('size_id')->constrained('sizes')->cascadeOnDelete();
            $t->unique(['item_id', 'size_id']);
        });

        // Backfill: every existing item gets its current size in the pivot.
        DB::statement('
            INSERT INTO item_size (item_id, size_id)
            SELECT id, size_id FROM items
            ON CONFLICT (item_id, size_id) DO NOTHING
        ');
    }
    public function down(): void { Schema::dropIfExists('item_size'); }
};
