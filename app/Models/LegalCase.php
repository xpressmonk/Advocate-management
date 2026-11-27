<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LegalCase extends Model {
 protected $table='cases';
 protected $fillable=['case_number','title','client_id','court_name','case_type','filing_date','status','description','next_hearing_date'];
 protected function casts():array{return ['filing_date'=>'date','next_hearing_date'=>'date'];}
 protected static function booted():void{static::deleting(function(LegalCase $case):void{$case->documents()->get()->each(static fn(Document $document)=>$document->delete());});}
 public function client(){return $this->belongsTo(Client::class);}
 public function advocates(){return $this->belongsToMany(Advocate::class,'case_advocate','legal_case_id','advocate_id');}
 public function hearings(){return $this->hasMany(Hearing::class,'case_id');}
 public function documents(){return $this->hasMany(Document::class,'case_id');}
 public function tasks(){return $this->hasMany(Task::class,'case_id');}
 public function invoices(){return $this->hasMany(Invoice::class,'case_id');}
 public function expenses(){return $this->hasMany(Expense::class,'case_id');}
}
