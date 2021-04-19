<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Str;

class UserController extends Controller
{

    public function edit(User $user){
        return view('auth.profile', compact('user'));
    }

    public function update(Request $request, User $user){
        $request->validate([
            'name' => 'required',
            // 'email' => 'required|unique:users,email,'.Auth::user()->id,
            'image' => 'nullable|image|max:256',
            // 'address'=> 'required|max:255'
        ]);

        $attributes = $request->except('image', '_token');
        if($request->hasFile('image')){
            $fileName = 'profile_pic_'.$user->id.'.'.$request->file('image')->getClientOriginalExtension();
            $path = $request->file('image')->storeAs('public/images/users',$fileName);
            $attributes['profile_path'] = Str::of($path)->replace('public', 'storage');
            // return $attributes['profile_path'];
        }
        $user->update($attributes);
        return redirect()->back()->with('success','Berjaya kemaskini profil');
    }

    public function passwordEdit(User $user){
        return view('auth.forgot_password');
    }

    public function passwordUpdate(Request $request,User $user){
        $request->validate([
            'old_password' => 'required|password',
            'new_password' => 'required|confirmed',
            'new_password_confirmation' => 'required',
        ]);
        $user->update(['password' => Hash::make($request->new_password)]);
        Auth::logout();
        return redirect('/');
    }

}
