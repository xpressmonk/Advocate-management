<?php
namespace Database\Seeders;
use App\Models\User;use Illuminate\Database\Seeder;
class DatabaseSeeder extends Seeder{public function run():void{User::firstOrCreate(['email'=>'admin@example.com'],['name'=>'System Administrator','password'=>'ChangeMe123!','role'=>'admin']);}}
