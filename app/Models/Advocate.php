<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Advocate extends Model {
 protected $fillable=['name','email','phone','bar_council_id','specialization','chamber_address','status'];
 public function cases(){return $this->belongsToMany(LegalCase::class,'case_advocate','advocate_id','legal_case_id');}
}
