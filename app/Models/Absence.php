<?php

namespace App\Models;

use Exception;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class Absence extends Model
{

    protected $appends = ['is_late', 'time_out_mod'];

    protected $fillable = [
        'id_user', 'date', 'time_in', 'time_out', 'id_shift', 'type', 'id_office', 'date_out', 'media', 'snapshot_shift', 'snapshot_office', 'edited_by',
    ];

    protected $casts = [
        'snapshot_shift' => 'array',
        'snapshot_office' => 'array',
    ];

    public function getTimeOutModAttribute()
    {
//        $time_out = $this->attributes['time_out'];
//        if (is_null($time_out))
//            return null;
//
//        try {
//            $date = $this->attributes['date'];
//            $day = Carbon::createFromFormat('Y-m-d H:i', $date.' '.$time_out);
//            dd($day->format('l'));
//
//        } catch (Exception $e) {
//            Log::info("Absence-timeout-mod", ['data' => $this->attributes, 'err' => $e->getMessage()]);
//            report($e);
//        }
//
//        return $time_out;
    }

    public function getIsLateAttribute() {
        // $shift = $this->shifttime;
        // if (! $shift)
        //     return true;
        //
        // if (is_null($this->time_in))
        //     return true;
        //
        // try {
        //     $shift = Carbon::parse(sprintf('%s %s', $this->date, $shift->start));
        //     $in = Carbon::parse(sprintf('%s %s', $this->date, $this->time_in));
        //     $diff = $shift->diffInMinutes($in, false);
        //     if ($diff > 30)
        //         return true;
        //     else return false;
        // } catch (Exception $e) {
        //     report($e);
        //     return true;
        // }
    }

    public function location()
    {
        return $this->hasMany('App\Models\Location', 'id_absence');
    }

    public function location_in()
    {
        return $this->hasMany(AbsenceLocation::class, 'id_absence')->where('type', 'i');
    }

    public function location_out()
    {
        return $this->hasMany(AbsenceLocation::class, 'id_absence')->where('type', 'o');
    }

    public function user()
    {
        return $this->belongsTo('App\User', 'id_user');
    }
    public function office()
    {
        return $this->belongsTo('App\Models\Location', 'id_office')->withDefault(['name' => 'no office found']);
    }

    public function shifttime()
    {
        return $this->belongsTo('App\Models\Shift', 'id_shift')->withDefault([
            'id' => 0,
            'name' => 'no found shift',
            'start' => '',
            'end' => ''
        ]);
    }
}
