<?php
namespace App\Http\Controllers;
use App\Models\{Hearing,HearingReminder,LegalCase}; use App\Services\HearingReminderService; use Illuminate\Http\Request;
class HearingController extends Controller {
 private function fd($h){return ['title'=>$h->exists?'Edit hearing':'Schedule hearing','action'=>$h->exists?route('hearings.update',$h):route('hearings.store'),'method'=>$h->exists?'PUT':'POST','item'=>$h,'cases'=>LegalCase::orderBy('case_number')->get()];}
 public function index(){return view('hearings.index',['items'=>Hearing::with(['legalCase.client','reminders'])->latest('hearing_date')->paginate(15)]);}
 public function create(){return view('hearings.form',$this->fd(new Hearing));}
 public function store(Request $r,HearingReminderService $reminders){$hearing=Hearing::create($this->data($r));$reminders->createDueReminders($hearing);return redirect()->route('hearings.index')->with('success','Hearing scheduled and reminders configured.');}
 public function show(Hearing $hearing){$hearing->load(['legalCase','reminders']);return view('hearings.show',['hearing'=>$hearing]);}
 public function edit(Hearing $hearing){return view('hearings.form',$this->fd($hearing));}
 public function update(Request $r,Hearing $hearing,HearingReminderService $reminders){$hearing->update($this->data($r));$hearing->refresh();$reminders->createDueReminders($hearing);return redirect()->route('hearings.index')->with('success','Hearing updated.');}
 public function destroy(Hearing $hearing){$hearing->delete();return back()->with('success','Hearing deleted.');}
 private function data(Request $r){return $r->validate(['case_id'=>'required|exists:cases,id','hearing_date'=>'required|date','court_room'=>'nullable|string|max:100','purpose'=>'nullable|string|max:500','outcome'=>'nullable|string|max:3000','next_date'=>'nullable|date','status'=>'required|in:scheduled,completed,adjourned,cancelled']);}
 public function configureReminders(Hearing $hearing,HearingReminderService $reminders){$reminders->createDueReminders($hearing);return back()->with('success','Hearing reminder channels configured.');}
 public function sendReminder(Hearing $hearing,HearingReminder $reminder,HearingReminderService $service){abort_unless($reminder->hearing_id===$hearing->id,404);try{$service->sendNow($reminder);return back()->with('success','Reminder sent.');}catch(\Throwable $exception){return back()->withErrors(['reminder'=>$exception->getMessage()]);}}
}
