<?php
namespace App\Http\Controllers;
use App\Services\GoogleDriveService;
use Illuminate\Support\Facades\Log;
use App\Models\{LegalCase,Client,Advocate}; use Illuminate\Http\Request; use Illuminate\Validation\Rule;
class CaseController extends Controller {
 private function formData($case){return ['title'=>$case->exists?'Edit case':'Register case','action'=>$case->exists?route('cases.update',$case):route('cases.store'),'method'=>$case->exists?'PUT':'POST','item'=>$case,'clients'=>Client::orderBy('name')->get(),'advocates'=>Advocate::where('status','active')->orderBy('name')->get()];}
 public function index(){return view('cases.index',['items'=>LegalCase::with('client')->latest()->paginate(15)]);}
 public function create(){return view('cases.form',$this->formData(new LegalCase));}
 public function store(Request $r, GoogleDriveService $drive){
  $case=LegalCase::create($this->data($r));
  $case->advocates()->sync($r->input('advocates',[]));
  $folderCreated = false;
  try {
   if ($drive->isConnected()) { $drive->createCaseFolder($case); $folderCreated = true; }
  } catch (\Throwable $e) {
   Log::error('Google Drive case folder creation failed', ['case_id'=>$case->id,'error'=>$e->getMessage()]);
   return redirect()->route('cases.show',$case)->with('error','Case registered, but its Google Drive folder could not be created. Check Drive connection and retry from the case documents page.');
  }
  return redirect()->route('cases.index')->with('success', $folderCreated ? 'Case registered and Google Drive folder created.' : 'Case registered. Connect Google Drive to create its folder; the folder will also be created automatically when the first case document is uploaded.');
}
 public function show(LegalCase $case){$case->load(['client','advocates','hearings','tasks','invoices','expenses','documents']);return view('cases.show',['case'=>$case]);}
 public function edit(LegalCase $case){return view('cases.form',$this->formData($case));}
 public function update(Request $r,LegalCase $case){$case->update($this->data($r,$case));$case->advocates()->sync($r->input('advocates',[]));return redirect()->route('cases.index')->with('success','Case updated.');}
 public function destroy(LegalCase $case){$case->delete();return back()->with('success','Case deleted.');}
 private function data(Request $r,$case=null){return $r->validate(['case_number'=>['required','string','max:80',Rule::unique('cases')->ignore($case?->id)],'title'=>'required|string|max:200','client_id'=>'required|exists:clients,id','court_name'=>'nullable|string|max:180','case_type'=>'required|string|max:100','filing_date'=>'nullable|date','status'=>'required|in:open,in_progress,adjourned,closed,won,lost','description'=>'nullable|string|max:10000','next_hearing_date'=>'nullable|date','advocates'=>'sometimes|array','advocates.*'=>'exists:advocates,id']);}
}
