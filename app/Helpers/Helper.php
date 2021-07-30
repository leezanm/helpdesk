<?php


use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

function convertDateFormat($date, $format)
{
	if(blank($date)){
		return '';
	}
    // return Carbon::parse($date)->format($format);
    return Carbon::parse($date)->locale('ms')->isoFormat($format);
}

function formatBytes($bytes, $precision = 2)
{

	$units = array('B', 'KB', 'MB', 'GB', 'TB');

	$bytes = max($bytes, 0);
	$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
	$pow = min($pow, count($units) - 1);

	$bytes /= pow(1024, $pow);

	return round($bytes, $precision) . ' ' . $units[$pow];
}

// Modul mohon domain email
function getDuration($report)
{
	$submitted_at = $report->submitted_at;
	$completed_at = $report->completed_at;

	if ($submitted_at) {
		$start_date = \Carbon\Carbon::parse($submitted_at);

		if ($completed_at) {
			$end_date = \Carbon\Carbon::parse($completed_at);
		} else {
			$end_date = \Carbon\Carbon::now();
		}

        $kivDuration = 0;
		if ($report->kiv_start_at) {
			$kiv_start_date = \Carbon\Carbon::parse($report->kiv_start_at);

            if ($report->kiv_end_at) {
                $kiv_end_date = \Carbon\Carbon::parse($report->kiv_end_at);
            } else {
                $kiv_end_date = \Carbon\Carbon::now();
            }


			$kivDuration = $kiv_start_date->diffInDaysFiltered(function (\Carbon\Carbon $date) {
				return !$date->isWeekend();
			}, $kiv_end_date);

		}

		$diff = $start_date->diffInDaysFiltered(function (\Carbon\Carbon $date) {
			return !$date->isWeekend();
		}, $end_date);

		// $diff = $diff - 1;
		$diff = $diff - $kivDuration;

		if ($diff < 0) {
			$diff = 0;
		}

		if ($diff <= 3) {
			return "<span class='badge bg-success font-size-11'> " . $diff . " </span>";
		} else if ($diff <= 5) {
			return "<span class='badge bg-warning font-size-11'> " . $diff . " </span>";
		} else {
			return "<span class='badge bg-danger font-size-11'> " . $diff . " </span>";
		}
	} else {
		return '';
	}
}
