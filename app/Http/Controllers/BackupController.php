<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
class BackupController extends Controller
{
    //
    public function index(Request $request)
	{
        if(!File::exists( storage_path('app/'. Str::slug( config('app.name') )))) {
            FIle::makeDirectory(  storage_path('app/'. Str::slug( config('app.name') )) );
        }

        $files = File::files( storage_path('app/'. Str::slug( config('app.name') ))  );

		$backups = array();

		foreach ($files as $file) {

			$temp = (object) array();

			$temp->fileName = $file->getFilename();
			$temp->fileSize = $file->getSize();
			$temp->fileCreatedAt = Carbon::createFromTimestamp($file->getMTime())->format('d M Y, g:i a');

			$backups[] = $temp;
		}
		return view('superadmin.backup.index', compact('backups'));
	}

	public function download($filename)
	{
        $file_path = storage_path('app/'. Str::slug( config('app.name') ). '/'. $filename);
		return response()->download($file_path);
	}

	public function destroy($filename) {

        File::delete( storage_path('app/'. Str::slug( config('app.name') ). '/'. $filename) );

		Session::flash('successMessage', 'Backup Berjaya Dipadam');

        return redirect()->route('backup.index');
	}

	public function backupFull(Request $request)
	{
		$exitCode = Artisan::call('backup:run');

		Session::flash('successMessage', 'Backup Berjaya Dicipta');

        return redirect()->route('backup.index');
	}

	public function backupDB(Request $request)
	{
		$exitCode = Artisan::call('backup:run', [
			'--only-db' => true
		]);

		return redirect()->route('backup.index');
	}

	public function backupFile(Request $request)
	{
		$exitCode = Artisan::call('backup:run', [
			'--only-files' => true
		]);

		Session::flash('successMessage', 'Backup Berjaya Dicipta');

        return redirect()->route('backup.index');
	}
}
