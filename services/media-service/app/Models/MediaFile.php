<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MediaFile extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'id','item_id','original_name','mime','extension','size','path','checksum',
    ];
    protected $casts = ['size' => 'integer'];
}
