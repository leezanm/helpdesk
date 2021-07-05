<?php

namespace App\Helpers;

use App\Models\Report;
use Carbon\Carbon;

class RefNo
{
    public static function ticketNo()
    {
        $today = today();
        $genRefNo = $today->format('ymd') . "/HQ/01";

        $latestRef = Report::whereDate('submitted_at', $today)->whereNotNull('ticket_no')->orderBy('submitted_at', 'desc')->first();

        if ($latestRef) {
            $latestNo = $latestRef->ticket_no;
            $refNoArr = explode('/', $latestNo);

            $refNo = $refNoArr[2];
            $refNo = $refNo + 1;
            $refNo = str_pad($refNo, 2, "0", STR_PAD_LEFT);

            $genRefNo = $today->format('ymd') . "/HQ/" . $refNo;
        }

        return $genRefNo;
    }

}
