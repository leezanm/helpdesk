<?php

namespace App\Helpers;

use App\Models\Report;
use Carbon\Carbon;

class RefNo
{
    public static function ticketNo()
    {
        $year = Carbon::now()->year;
        $genRefNo = $year . "/000001";

        $latestRef = Report::whereYear('submitted_at', $year)->whereNotNull('ticket_no')->orderBy('submitted_at', 'desc')->first();

        if ($latestRef) {
            $latestNo = $latestRef->ticket_no;
            $refNoArr = explode('/', $latestNo);

            $refNo = $refNoArr[1];
            $refNo = $refNo + 1;
            $refNo = str_pad($refNo, 6, "0", STR_PAD_LEFT);

            $genRefNo = $year . "/" . $refNo;
        }

        return $genRefNo;
    }

}
