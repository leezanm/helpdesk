<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserAsset\StoreRequest;
use App\Models\UserAsset;
use Illuminate\Http\Request;

class UserAssetController extends Controller
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
    public function store(StoreRequest $request)
    {
        //
        $userAsset = new UserAsset();
        $userAsset->fill($request->except('_token'));
        $userAsset->save();

        $datas = [
            'success' =>true
        ];

        return response()->json($datas);

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\UserAsset  $userAsset
     * @return \Illuminate\Http\Response
     */
    public function show(UserAsset $userAsset)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\UserAsset  $userAsset
     * @return \Illuminate\Http\Response
     */
    public function edit(UserAsset $userAsset)
    {
        //
        $datas = [
            'success' =>true,
            'data' => $userAsset
        ];

        return response()->json($datas);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\UserAsset  $userAsset
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, UserAsset $userAsset)
    {
        //
        $userAsset->fill($request->except('_token'));
        $userAsset->save();

        $datas = [
            'success' =>true
        ];

        return response()->json($datas);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\UserAsset  $userAsset
     * @return \Illuminate\Http\Response
     */
    public function destroy(UserAsset $userAsset)
    {
        //
        $userAsset->delete();
        return response()->json(['success' => true]);
    }
}
