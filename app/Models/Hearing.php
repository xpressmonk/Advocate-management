<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Hearing extends Model {
 protected $fillable=['case_id','hearing_date','court_room','purpose','outcome','next_date','status'];
 protected function casts():array{return ['hearing_date'=>'datetime','next_date'=>'date'];}
 public function legalCase(){return $this->belongsTo(LegalCase::class,'case_id');}
}
