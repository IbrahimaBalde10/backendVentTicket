<?php
// Model HeureDeDepart
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HeureDeDepart extends Model
{
    use HasFactory;

    protected $table = 'heuresDeparts';
    protected $fillable = ['heureDepart', 'trajet_id'];

    public function trajet()
    {
        return $this->belongsTo(Trajet::class);
    }
}
