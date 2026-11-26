<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LegalCase extends Model {
 protected $table='cases';
 protected $fillable=['case_number','title','client_id','court_name','case_type','filing_date','status','description','next_hearing_date'];
 protected function casts():array{return ['filing_date'=>'date','next_hearing_date'=>'date'];}
 public function client(){return $this->belongsTo(Client::class);}
 public function advocates(){return $this->belongsToMany(Advocate::class,'case_advocate','legal_case_id','advocate_id');}
 public function hearings(){return $this->hasMany(Hearing::class,'case_id');}
}
