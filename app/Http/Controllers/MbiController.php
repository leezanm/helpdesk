<?php

namespace App\Http\Controllers;

use App\Models\Division;
use App\Models\mbi;
use App\Models\Type;
use Illuminate\Http\Request;

class MbiController extends Controller
{

    public function index()
    {
        $mbis = mbi::all();
        $types = Type::orderBy('desc')->get();
        $divisions = Division::orderBy('desc')->get();
        return view('mbi.index', compact('mbis', 'types', 'divisions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'rujukan' => 'required',
            'tarikh' => 'required',
            'kepada' => 'required',
            'perkara' => 'required',
            'type_id' => 'required',
            'division_id' => 'required'
        ]);

        $mbi = new mbi();
        $mbi->rujukan = $request->rujukan;
        $mbi->tarikh = $request->tarikh;
        $mbi->kepada = $request->kepada;
        $mbi->perkara = $request->perkara;
        $mbi->type_id = $request->type_id;
        $mbi->division_id = $request->division_id;
        $mbi->save();
        return response()->json(['success' => true]);
    }

    public function show(mbi $mbi)
    {
        $mbi->load('type', 'division');
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
            'type_id' => 'required',
            'division_id' => 'required'
        ]);


        $mbi->rujukan = $request->rujukan;
        $mbi->tarikh = $request->tarikh;
        $mbi->kepada = $request->kepada;
        $mbi->perkara = $request->perkara;
        $mbi->type_id = $request->type_id;
        $mbi->division_id = $request->division_id;
        $mbi->save();
        return response()->json(['success' => true]);
    }

    public function destroy(mbi $mbi)
    {
        $mbi->delete();
        return response()->json(['success' => true]);
    }
}
