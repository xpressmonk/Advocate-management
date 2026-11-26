<?php
namespace App\Http\Controllers;
use App\Models\Advocate; use Illuminate\Http\Request;
class AdvocateController extends Controller {
 private function fields(){return ['name'=>'Name','phone'=>'Phone','email'=>'Email','bar_council_id'=>'Bar Council ID','specialization'=>'Specialization','chamber_address'=>'Chamber address','status'=>'Status'];}
 public function index(){return view('advocates.index',['items'=>Advocate::latest()->paginate(15)]);}
 public function create(){return view('shared.form',['title'=>'Add advocate','action'=>route('advocates.store'),'item'=>new Advocate,'fields'=>$this->fields()]);}
 public function store(Request $r){Advocate::create($this->data($r));return redirect()->route('advocates.index')->with('success','Advocate created.');}
 public function show(Advocate $advocate){return view('shared.show',['title'=>'Advocate details','item'=>$advocate]);}
 public function edit(Advocate $advocate){return view('shared.form',['title'=>'Edit advocate','action'=>route('advocates.update',$advocate),'method'=>'PUT','item'=>$advocate,'fields'=>$this->fields()]);}
 public function update(Request $r,Advocate $advocate){$advocate->update($this->data($r));return redirect()->route('advocates.index')->with('success','Advocate updated.');}
 public function destroy(Advocate $advocate){$advocate->cases()->detach();$advocate->delete();return back()->with('success','Advocate deleted.');}
 private function data(Request $r){return $r->validate(['name'=>'required|string|max:160','phone'=>'nullable|string|max:30','email'=>'nullable|email','bar_council_id'=>'nullable|string|max:100','specialization'=>'nullable|string|max:160','chamber_address'=>'nullable|string|max:1000','status'=>'required|in:active,inactive']);}
}
