<?php
namespace App\Http\Controllers;
use App\Models\{Client,Advocate,LegalCase,Hearing};
class DashboardController extends Controller {
 public function __invoke(){return view('dashboard',['clientCount'=>Client::count(),'advocateCount'=>Advocate::count(),'caseCount'=>LegalCase::count(),'activeCases'=>LegalCase::whereIn('status',['open','in_progress'])->count(),'recentCases'=>LegalCase::with('client')->latest()->limit(6)->get(),'upcomingHearings'=>Hearing::with('legalCase')->where('hearing_date','>=',now())->orderBy('hearing_date')->limit(6)->get()]);}
}
