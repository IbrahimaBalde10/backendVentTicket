<?php
// Model DateDeDepart
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DateDeDepart extends Model
{
    use HasFactory;

    protected $table = 'datesDeparts';
    protected $fillable = ['dateDepart', 'trajet_id'];

    public function trajet()
    {
        return $this->belongsTo(Trajet::class);
    }
}
