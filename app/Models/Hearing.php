<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Hearing extends Model {
 protected $fillable=['case_id','hearing_date','court_room','purpose','outcome','next_date','status'];
 protected function casts():array{return ['hearing_date'=>'datetime','next_date'=>'date'];}
 public function legalCase(){return $this->belongsTo(LegalCase::class,'case_id');}
 public function reminders(){return $this->hasMany(HearingReminder::class);}

 public function googleCalendarUrl(): string
 {
     $this->loadMissing('legalCase');
     $start = $this->hearing_date->copy()->utc();
     $end = $start->copy()->addHour();
     $title = 'Hearing - '.$this->legalCase->case_number;
     $details = implode("\n", array_filter([
         'Case: '.$this->legalCase->case_number.' - '.$this->legalCase->title,
         $this->court_room ? 'Court room: '.$this->court_room : null,
         $this->purpose ? 'Purpose: '.$this->purpose : null,
         'Status: '.ucfirst($this->status),
     ]));
     $location = $this->court_room ?: ($this->legalCase->court_name ?: '');

     return 'https://calendar.google.com/calendar/render?'.http_build_query([
         'action' => 'TEMPLATE',
         'text' => $title,
         'dates' => $start->format('Ymd\THis\Z').'/'.$end->format('Ymd\THis\Z'),
         'details' => $details,
         'location' => $location,
     ], '', '&', PHP_QUERY_RFC3986);
 }
}
