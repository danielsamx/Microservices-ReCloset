<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ItemMedia extends Model
{
    protected $table = 'item_media';
    protected $fillable = ['item_id','media_id','url','mime','position'];
    public $timestamps = false;
}
