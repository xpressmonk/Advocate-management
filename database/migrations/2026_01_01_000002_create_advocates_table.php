<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration{public function up():void{Schema::create('advocates',function(Blueprint $t){$t->id();$t->string('name');$t->string('email')->nullable();$t->string('phone',30)->nullable();$t->string('bar_council_id')->nullable()->unique();$t->string('specialization')->nullable();$t->text('chamber_address')->nullable();$t->string('status')->default('active');$t->timestamps();});}public function down():void{Schema::dropIfExists('advocates');}};
