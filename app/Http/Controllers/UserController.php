<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * User management.
 *
 * The user list + create/edit CRUD is handled by Livewire
 * (App\Livewire\User\UserIndex + UserForm). This controller renders the list
 * page wrappers (passing a scope), the full-user page (assets), and retains
 * the separate account profile/password endpoints.
 */
class UserController extends Controller
{
    public function index()
    {
        return view('user.pengguna', ['scope' => 'pengguna', 'title' => 'Pengguna']);
    }

    public function indexPegTeknikal()
    {
        return view('user.pengguna', ['scope' => 'teknikal', 'title' => 'Pegawai Teknikal']);
    }

    public function indexPentadbir()
    {
        return view('user.pengguna', ['scope' => 'pentadbir', 'title' => 'Pentadbir Sistem']);
    }

    public function indexPengurusan()
    {
        return view('user.pengguna', ['scope' => 'pengurusan', 'title' => 'Pengurusan']);
    }

    /**
     * Full user page including asset management (mounts UserAssetManager).
     */
    public function showFullUser(User $user)
    {
        $user->load('assets');

        return view('user.show', compact('user'));
    }

    // ----- Account profile / password (separate account.* routes) -----

    public function edit(User $user)
    {
        return view('auth.profile', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'image' => 'nullable|image|max:256',
        ]);

        $attributes = $request->except('image', '_token');
        if ($request->hasFile('image')) {
            $fileName = 'profile_pic_'.$user->id.'.'.$request->file('image')->getClientOriginalExtension();
            $path = $request->file('image')->storeAs('public/images/users', $fileName);
            $attributes['profile_path'] = Str::of($path)->replace('public', 'storage');
        }
        $user->update($attributes);

        return redirect()->back()->with('success', 'Berjaya kemaskini profil');
    }

    public function passwordEdit(User $user)
    {
        return view('auth.forgot_password');
    }

    public function passwordUpdate(Request $request, User $user)
    {
        $request->validate([
            'old_password' => 'required|current_password',
            'password' => 'required|confirmed',
            'password_confirmation' => 'required',
        ]);
        $user->update(['password' => Hash::make($request->password)]);
        Auth::logout();

        return response()->json(['success' => true]);
    }
}
