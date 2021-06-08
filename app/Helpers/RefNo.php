<?php

namespace App\Helpers;

use App\Models\Fdd;
use App\Models\Franchisor;
use App\Models\FranchisorDetail;
use App\Models\PindaanMatan;
use Carbon\Carbon;

class RefNo
{
    public static function fdd()
    {
        $year = Carbon::now()->year;
        $genRefNo = $year . "/000001";

        $latestRef = Fdd::whereYear('created_at', $year)->whereNotNull('ref_no')->orderBy('id', 'desc')->first();
        if ($latestRef) {
            $latestNo = $latestRef->ref_no;
            $refNoArr = explode('/', $latestNo);

            $refNo = $refNoArr[1];
            $refNo = $refNo + 1;
            $refNo = str_pad($refNo, 6, "0", STR_PAD_LEFT);

            $genRefNo = $year . "/" . $refNo;
        }

        return $genRefNo;
    }

    public static function franchisorDetail()
    {
        $year = Carbon::now()->year;
        $genRefNo = $year . "/000001";

        $latestRef = FranchisorDetail::whereYear('created_at', $year)->whereNotNull('application_ref_no')->latest()->first();
        if ($latestRef) {
            $latestNo = $latestRef->application_ref_no;
            $refNoArr = explode('/', $latestNo);

            $refNo = $refNoArr[1];
            $refNo = $refNo + 1;
            $refNo = str_pad($refNo, 6, "0", STR_PAD_LEFT);

            $genRefNo = $year . "/" . $refNo;
        }

        return $genRefNo;
    }



    public static function franchisorReg()
    {
        $year = Carbon::now()->year;
        $genRefNo = $year . "/000001";

        $latestRef = Franchisor::whereYear('created_at', $year)->whereNotNull('registration_no')->where('is_approved_registration', true)->latest()->first();
        if ($latestRef) {

            $latestNo = $latestRef->registration_no;
            $refNoArr = explode('/', $latestNo);

            $refNo = $refNoArr[1];
            $refNo = $refNo + 1;
            $refNo = str_pad($refNo, 6, "0", STR_PAD_LEFT);

            $genRefNo = $year . "/" . $refNo;
        }

        return $genRefNo;
    }

    public static function pindaanRef()
    {
        $year = Carbon::now()->year;
        $genRefNo = $year . "/000001";

        $latestRef = PindaanMatan::whereYear('created_at', $year)->whereNotNull('ref_no')->latest()->first();
        if ($latestRef) {

            $latestNo = $latestRef->ref_no;
            $refNoArr = explode('/', $latestNo);

            $refNo = $refNoArr[1];
            $refNo = $refNo + 1;
            $refNo = str_pad($refNo, 6, "0", STR_PAD_LEFT);

            $genRefNo = $year . "/" . $refNo;
        }

        return $genRefNo;
    }
}
