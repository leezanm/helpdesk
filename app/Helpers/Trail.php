<?php

namespace App\Helpers;

use App\Models\ActivityTrail;

class Trail
{
    public static function setLog($obj, $message)
    {
        $obj_class = get_class($obj);
        $obj_id = $obj->id;

        $trail = new ActivityTrail();
        $trail->obj_class = $obj_class;
        $trail->obj_id = $obj_id;
        $trail->message = $message;
        $trail->save();

        return $trail;
    }

    public static function getLog($record)
    {
        $obj_class = get_class($record);
        $obj_id = $record->id;

        $logs = ActivityTrail::where('obj_class', $obj_class)->where('obj_id', $obj_id)->latest()->get();
        return $logs;
    }
}
