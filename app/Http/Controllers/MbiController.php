<?php

namespace App\Http\Controllers;

use App\Models\mbi;
use Illuminate\Http\Request;

class MbiController extends Controller
{

    public function index()
    {
        $mbis = mbi::all();
        return view('mbi.index', compact('mbis'));
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

        $mbi = new mbi();
        $mbi->rujukan = $request->rujukan;
        $mbi->tarikh = $request->tarikh;
        $mbi->kepada = $request->kepada;
        $mbi->perkara = $request->perkara;
        $mbi->jenis = $request->jenis;
        $mbi->save();
        return response()->json(['success' => true]);
    }

    public function show(mbi $mbi)
    {
        $datas = [
            'success' => true,
            'data' => $mbi
        ];

        return response()->json($datas);
    }

    public function edit(mbi $mbi)
    {
        $datas = [
            'success' => true,
            'mbi' => $mbi
        ];

        return response()->json($datas);
    }

    public function update(Request $request, mbi $mbi)
    {
        $request->validate([
            'rujukan' => 'required',
            'tarikh' => 'required',
            'kepada' => 'required',
            'perkara' => 'required',
            'jenis' => 'required'
        ]);


        $mbi->rujukan = $request->rujukan;
        $mbi->tarikh = $request->tarikh;
        $mbi->kepada = $request->kepada;
        $mbi->perkara = $request->perkara;
        $mbi->jenis = $request->jenis;
        $mbi->save();
        return response()->json(['success' => true]);
    }

    public function destroy(mbi $mbi)
    {
        $mbi->delete();
        return response()->json(['success' => true]);
    }
}
