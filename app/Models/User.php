<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
class User extends Authenticatable {
 use Notifiable;
 protected $fillable=['name','email','password','role','role_id'];
 protected $hidden=['password','remember_token'];
 protected function casts():array{return ['password'=>'hashed','email_verified_at'=>'datetime'];}
 public function assignedTasks(){return $this->hasMany(Task::class,'assigned_to');}
 public function createdTasks(){return $this->hasMany(Task::class,'created_by');}
 public function roleRecord(){return $this->belongsTo(Role::class,'role_id');}
 public function isAdmin(): bool{return $this->role==='admin';}
 public function canManageUsers(): bool{return in_array($this->role,['admin','manager'],true);}
 public function canDelete(): bool{return in_array($this->role,['admin','manager'],true);}
 public function canUpdate(): bool{return in_array($this->role,['admin','manager','staff'],true);}
 public function canView(): bool{return in_array($this->role,self::roles(),true);}
 public static function roles(): array{return ['admin','manager','staff','viewer'];}
}
