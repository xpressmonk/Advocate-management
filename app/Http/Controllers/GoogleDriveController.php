<?php
namespace App\Http\Controllers;

use App\Models\GoogleDriveConnection;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class GoogleDriveController extends Controller
{
    public function settings()
    {
        return view('settings.google-drive', [
            'connection' => GoogleDriveConnection::first(),
        ]);
    }

    public function connect(GoogleDriveService $drive)
    {
        $state = Str::random(48);
        session(['google_drive_oauth_state' => $state]);
        try {
            return redirect()->away($drive->authorizationUrl($state));
        } catch (Throwable $exception) {
            report($exception);
            return back()->with('error', 'Google OAuth credentials file is missing or invalid.');
        }
    }

    public function callback(Request $request, GoogleDriveService $drive)
    {
        $expected = $request->session()->pull('google_drive_oauth_state');
        abort_unless($expected && hash_equals($expected, (string)$request->query('state')), 403, 'Invalid Google OAuth state.');
        if ($request->filled('error')) return redirect()->route('settings.google-drive')->with('error', 'Google Drive connection was cancelled.');

        $request->validate(['code' => ['required', 'string']]);
        try {
            $drive->connect($request->string('code')->toString());
            return redirect()->route('settings.google-drive')->with('success', 'Google Drive connected. The root folder is ready.');
        } catch (Throwable $exception) {
            report($exception);
            return redirect()->route('settings.google-drive')->with('error', 'Google Drive connection failed. Check OAuth settings and application logs.');
        }
    }
}
