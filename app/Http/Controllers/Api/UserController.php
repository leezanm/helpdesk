<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        //
        $connected = false;
        $ldap_user = null;
        $bp_user_id = null;
        $success = false;
        $bp_name = null;
        $bp_bahagian = null;
        $bp_email = null;

        $message = null;

        $user = User::firstWhere('username', $request->bp_username);

        if(filled($user)){

            $bp_name = $user->name;
            $bp_bahagian = $user->ldap_bahagian;
            $bp_email = $user->email;
            $bp_user_id = $user->id;

        } else{

            $ad = new \Adldap\Adldap();
            $ad->addProvider(config('ldap')['connections']['default']['settings']);

            try{
                $provider = $ad->connect();
                $connected = true;
            } catch(Exception $e){
                return response()->json(['success' => false, 'message' => 'Tidak dapat connect ke AD. Sila hubungi admin']);
            }

            if($connected){
                $ldap_user = $provider->search()->search(['cn', 'physicaldeliveryofficename', 'userprincipalname'])->findBy('samaccountname', $request->bp_username);
            }

            if(filled($ldap_user)){
                $user = User::create([]);
                $success = true;
                $bp_name = $ldap_user->cn;
                $bp_bahagian = $ldap_user->physicaldeliveryofficename;
                $bp_email = $ldap_user->userprincipalname;
            } else {
                return response()->json(['success' => false, 'message' => 'Pengguna tidak dijumpai']);
            }
        }

        $success = true;
        $datas = [
            'success' => $success,
            'bp_name' => $bp_name,
            'bp_bahagian' => $bp_bahagian,
            'bp_email' => $bp_email,
            'bp_user_id' => $bp_user_id,
            'message' => $message,
        ];

        return response()->json($datas);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function getUserProfile(Request $request){

        $user = User::findOrFail($request->user_id);
        $user->makeHidden('deleted_at', 'created_at', 'updated_at','profile_path', 'email_verified_at');
        $user->append('profile_picture');

        $datas = [
            'success' => true,
            'userProfile' => $user
        ];

        return response()->json($datas);
    }
}
