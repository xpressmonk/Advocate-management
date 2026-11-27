<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Client extends Model {
 protected $fillable=['name','email','phone','address','national_id','notes'];
 protected static function booted():void{static::deleting(function(Client $client):void{$client->documents()->get()->each(static fn(Document $document)=>$document->delete());});}
 public function cases(){return $this->hasMany(LegalCase::class);}
 public function documents(){return $this->hasMany(Document::class);}
 public function invoices(){return $this->hasMany(Invoice::class);}
}
