<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Str;

class UserController extends Controller
{
    public function index()
    {
        //
        $users = User::role('pengguna')->orderBy('name', 'asc')->get();
        $roles = Role::where('name', '!=', 'superadmin')->get();
        return view('user.pengguna', compact('users', 'roles'));
    }

    public function indexPegTeknikal()
    {
        //
        $users = User::permission('aduan.updateTask')->orderBy('name', 'asc')->get();
        $roles = Role::where('name', '!=', 'superadmin')->get();
        return view('user.pengguna', compact('users', 'roles'));
    }

    public function indexPentadbir()
    {
        //
        $users = User::permission('manage.user')->orderBy('name', 'asc')->get();
        $roles = Role::where('name', '!=', 'superadmin')->get();
        return view('user.pengguna', compact('users', 'roles'));
    }

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

    public function storeUser(Request $request)
    {
        $request->validate([
            'profile_path' => 'nullable|image|max:256',
            'name' => 'required',
            'username' => 'required|unique:users,username',
            'email' => 'required|email',
            'designation' => 'required',
            'tel_no' => 'required',
            'ic_no' => 'required',
            'address' => 'required',
            'roles' => 'required',
            'roles.*' => 'required|exists:roles,name'
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

        $user->syncRoles($request->roles);

        return response()->json(['success'=>true]);
    }

    public function showUser(User $user)
    {
        $datas = [
            'success' => true,
            'data' => $user->append('profile_picture')
        ];

        return response()->json($datas);
    }

    public function editUser(User $user)
    {
        $user_roles = $user->roles;
        $datas = [
            'success' => true,
            'data' => $user->append('profile_picture'),
            'user_roles' => $user_roles
        ];

        return response()->json($datas);
    }

    public function updateUser(Request $request, User $user)
    {
        $request->validate([
            'email' => 'required|email',
            'name' => 'required',
            'designation' => 'required',
            'tel_no' => 'required',
            'ic_no' => 'nullable|unique:users,ic_no,'.$request->user_id,
            'address' => 'required',
            'roles' => 'required',
            'roles.*' => 'required|exists:roles,name'
        ]);

        $user = $user->fill($request->except('_token'));
        $user->save();
        $user->syncRoles($request->roles);

        $datas = [
            'success' => true,
            'data' => $user
        ];

        return response()->json($datas);
    }

    public function destroy(User $user)
    {
        $user->is_active = false;
        $user->save();

        return response()->json(['success' => true]);
    }

    public function restore(User $user)
    {
        $user->is_active = true;
        $user->save();

        return redirect()->back();
    }
}
