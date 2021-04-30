<?php

namespace App\Http\Controllers;

use App\Models\Officer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class OfficerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $officers = Officer::all();
        return view('user.pengguna', compact('officers'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'profile_path' => 'nullable|image|max:256',
            'name' => 'required',
            'email' => 'required|email',
            'designation' => 'required',
            'tel_no' => 'required',
            'dob' => 'required|date',
            'identification' => 'required',
            'address' => 'required'
        ]);

        $user = new User();
        $user->fill($request->all());
        $user->password = Hash::make('pass1234');

        if($request->hasFile('profile_path')){
            $fileName = 'profile_pic_'.$user->id.'.'.$request->file('profile_path')->getClientOriginalExtension();
            $path = $request->file('profile_path')->storeAs('public/images/users',$fileName);
            $user->profile_path = Str::of($path)->replace('public', 'storage');
            // return $attributes['profile_path'];
        }

        $user->save();

        return response()->json(['success'=>true]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Officer  $officer
     * @return \Illuminate\Http\Response
     */
    public function show(Officer $officer)
    {
        //
        $datas = [
            'success' => true,
            'data' => $officer
        ];

        return response()->json($datas);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Officer  $officer
     * @return \Illuminate\Http\Response
     */
    public function edit(Officer $officer)
    {
        //
        $datas = [
            'success' => true,
            'data' => $officer
        ];

        return response()->json($datas);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Officer  $officer
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Officer $officer)
    {
        //
        $request->validate([
            'email' => 'required|email',
            'name' => 'required',
            'designation' => 'required',
            'tel_no' => 'required',
            'dob' => 'required',
            'identification' => 'required',
            'address' => 'required'
        ]);

        $officer = $officer->fill($request->except('_token'));
        $officer->save();

        $datas = [
            'success' => true,
            'data' => $officer
        ];

        return response()->json($datas);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Officer  $officer
     * @return \Illuminate\Http\Response
     */
    public function destroy(Officer $officer)
    {
        //
        $officer->delete();

        return response()->json(['success' => true]);
    }
}
