<?php
namespace App\Http\Controllers;
use App\Models\Client; use Illuminate\Http\Request;
class ClientController extends Controller {
 public function index(){return view('clients.index',['items'=>Client::latest()->paginate(15)]);}
 public function create(){return view('shared.form',['title'=>'Add client','action'=>route('clients.store'),'item'=>new Client,'fields'=>['name'=>'Name','phone'=>'Phone','email'=>'Email','national_id'=>'National ID','address'=>'Address','notes'=>'Notes']]);}
 public function store(Request $r){Client::create($this->data($r));return redirect()->route('clients.index')->with('success','Client created.');}
 public function show(Client $client){$client->load('cases');return view('shared.show',['title'=>'Client details','item'=>$client]);}
 public function edit(Client $client){return view('shared.form',['title'=>'Edit client','action'=>route('clients.update',$client),'method'=>'PUT','item'=>$client,'fields'=>['name'=>'Name','phone'=>'Phone','email'=>'Email','national_id'=>'National ID','address'=>'Address','notes'=>'Notes']]);}
 public function update(Request $r,Client $client){$client->update($this->data($r));return redirect()->route('clients.index')->with('success','Client updated.');}
 public function destroy(Client $client){if($client->cases()->exists())return back()->with('error','Client has linked cases.');$client->delete();return back()->with('success','Client deleted.');}
 private function data(Request $r){return $r->validate(['name'=>'required|string|max:160','phone'=>'required|string|max:30','email'=>'nullable|email','national_id'=>'nullable|string|max:80','address'=>'nullable|string|max:1000','notes'=>'nullable|string|max:5000']);}
}
