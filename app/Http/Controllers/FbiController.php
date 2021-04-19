<?php

namespace App\Http\Controllers;

use App\Models\fbi;
use Illuminate\Http\Request;

class FbiController extends Controller
{
    public function index()
    {
        $fbis = fbi::all();
        return view('fbi.index', compact('fbis'));
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $request->validate([
            'rujukan' => 'required',
            'tarikh' => 'required',
            'kepada' => 'required',
            'perkara' => 'required',
            'jenis' => 'required'
        ]);

        $fbi = new fbi();
        $fbi->rujukan = $request->rujukan;
        $fbi->tarikh = $request->tarikh;
        $fbi->kepada = $request->kepada;
        $fbi->perkara = $request->perkara;
        $fbi->jenis = $request->jenis;
        $fbi->save();
        return response()->json(['success' => true]);
    }

    public function show(fbi $fbi)
    {
        $datas = [
            'success' => true,
            'data' => $fbi
        ];

        return response()->json($datas);
    }

    public function edit(fbi $fbi)
    {
        $datas = [
            'success' => true,
            'fbi' => $fbi
        ];

        return response()->json($datas);
    }

    public function update(Request $request, fbi $fbi)
    {
        $request->validate([
            'rujukan' => 'required',
            'tarikh' => 'required',
            'kepada' => 'required',
            'perkara' => 'required',
            'jenis' => 'required'
        ]);


        $fbi->rujukan = $request->rujukan;
        $fbi->tarikh = $request->tarikh;
        $fbi->kepada = $request->kepada;
        $fbi->perkara = $request->perkara;
        $fbi->jenis = $request->jenis;
        $fbi->save();
        return response()->json(['success' => true]);
    }

    public function destroy(fbi $fbi)
    {
        $fbi->delete();
        return response()->json(['success' => true]);
    }
}
