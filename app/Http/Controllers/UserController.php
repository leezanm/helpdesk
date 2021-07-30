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
        $title = 'Pengguna';
        $users = User::with('roles')->whereDoesntHave('roles', function($query){
            $query->whereIn('name', ['jtk', 'pptm', 'ptm', 'helpdesk.admin', 'superadmin', 'pengurusan']);
        })->orderBy('name', 'asc')->get();

        if( auth()->user()->hasRole('superadmin')){
            $roles = Role::orderBy('desc')->get();

        } else {
            $roles = Role::where('name', '!=', 'superadmin')->orderBy('desc')->get();
        }

        return view('user.pengguna', compact('users', 'roles', 'title'));
    }

    public function indexPegTeknikal()
    {
        //
        $title = 'Pegawai Teknikal';
        $users = User::permission('aduan.updateTask')->with('roles')->orderBy('name', 'asc')->get();

        if( auth()->user()->hasRole('superadmin')){
            $roles = Role::orderBy('desc')->get();

        } else {
            $roles = Role::where('name', '!=', 'superadmin')->orderBy('desc')->get();
        }

        return view('user.pengguna', compact('users', 'roles', 'title'));
    }

    public function indexPentadbir()
    {
        //
        $title = 'Pentadbir Sistem';
        $users = User::permission('manage.user')->with('roles')->orderBy('name', 'asc')->get();

        if( auth()->user()->hasRole('superadmin')){
            $roles = Role::orderBy('desc')->get();

        } else {
            $roles = Role::where('name', '!=', 'superadmin')->orderBy('desc')->get();
        }

        return view('user.pengguna', compact('users', 'roles', 'title'));
    }

    public function indexPengurusan()
    {
        //
        $title = 'Pengurusan';
        $users = User::role('pengurusan')->with('roles')->orderBy('name', 'asc')->get();

        if( auth()->user()->hasRole('superadmin')){
            $roles = Role::orderBy('desc')->get();

        } else {
            $roles = Role::where('name', '!=', 'superadmin')->orderBy('desc')->get();
        }

        return view('user.pengguna', compact('users', 'roles', 'title'));
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
            'password' => 'required|confirmed',
            'password_confirmation' => 'required',
        ]);
        $user->update(['password' => Hash::make($request->password)]);
        Auth::logout();

        $datas = [
            'success' => true
        ];
        return response()->json($datas);
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'profile_path' => 'nullable|image|max:256',
            'name' => 'required',
            'username' => 'required|unique:users,username',
            'email' => 'required|email',
            'designation' => 'nullable',
            'tel_no' => 'nullable|numeric|digits_between:10,11',
            'ic_no' => 'nullable|digits:12',
            'address' => 'nullable',
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

    public function showFullUser(User $user)
    {
        $user->load('assets');
        return view('user.show', compact('user'));
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
            'designation' => 'nullable',
            'tel_no' => 'nullable|numeric|digits_between:10,11',
            'ic_no' => 'nullable|numeric|digits:12|unique:users,ic_no,'.$request->user_id,
            'address' => 'nullable',
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
