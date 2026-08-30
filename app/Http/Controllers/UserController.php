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
        // Self-service profile only — a user may only view/edit their OWN
        // account here. (Admins manage other users via the Livewire UserForm.)
        abort_unless($user->is(Auth::user()), 403);

        return view('auth.profile', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        // Self-service only: prevent editing another user's profile (IDOR).
        abort_unless($user->is(Auth::user()), 403);

        // Only phone and address are user-editable; everything else (name,
        // email, designation, bahagian) is sourced from AD. Whitelist the
        // fields server-side so a crafted request cannot change AD-owned data.
        $attributes = $request->validate([
            'tel_no' => 'required|string|max:30',
            'address' => 'nullable|string|max:500',
            'image' => 'nullable|image|max:256',
        ], [
            'tel_no.required' => 'No. Telefon wajib diisi.',
        ]);

        unset($attributes['image']);

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
        // Self-service only: a user may only change their OWN password. Without
        // this an attacker could set another account's password (takeover),
        // since old_password validates against the LOGGED-IN user, not {user}.
        abort_unless($user->is(Auth::user()), 403);

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
