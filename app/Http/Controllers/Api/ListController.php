<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class ListController extends Controller
{
    //

    public function getUsersByRole(Request $request){

        $users = User::role($request->role_name)->where('is_active', true)->get();
        $datas = [
            'success' => true,
            'users' => $users
        ];

        return response()->json($datas);
    }
}
