<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Item extends Model
{
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_RESERVED  = 'reserved';
    public const STATUS_SOLD      = 'sold';
    public const STATUSES = [self::STATUS_AVAILABLE, self::STATUS_RESERVED, self::STATUS_SOLD];

    protected $fillable = [
        'owner_id','owner_name','name','description',
        'category_id','size_id','color_id','price','status',
    ];
    protected $casts = ['price' => 'decimal:2'];

    public function media(): HasMany { return $this->hasMany(ItemMedia::class)->orderBy('position'); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function size(): BelongsTo { return $this->belongsTo(Size::class); }
    public function color(): BelongsTo { return $this->belongsTo(Color::class); }
}
