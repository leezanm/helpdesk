<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\ReportTrail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class LaporanController extends Controller
{
    //
    public function index(Request $request){

        return view('laporan.index');
    }

    public function viewByStatus(Request $request){
        $title = 'Mengikut Status';
        $from = $request->from;
        $to = $request->to;
        $status = $request->status;
        $achievement = null;

        $laporans =  \DB::table('_report_statuses as report_status')
                    ->select('id', 'desc')
                    ->selectRaw('ifnull((select count(*) from reports where report_status_id = report_status.id and if(?,submitted_at >= ?, true) and if(?,submitted_at <= ?, true) group by report_status_id),0) as jumlah', [filled($from), Carbon::parse($from), filled($to), Carbon::parse($to)])
                    // ->whereIn('id', [2,3,4,5])
                    ->when(filled($status), function($query) use ($status){
                        $query->where('desc', $status);
                    },
                    function($query){
                        $query->whereIn('id', [2,3,4,5]);
                    })
                    ->orderBy('id')
                    ->get();

        if(blank($status)){
            $completed_report = $laporans->where('id', 5)->first();
            $achievement = $completed_report->jumlah > 0 ? ($completed_report->jumlah / $laporans->sum('jumlah') ) * 100 : 00.00;
            $achievement = number_format((float)$achievement, 2, '.', '');
        }

        $reports = Report::when(filled($from), function($query) use($from){
                                $query->whereDate('submitted_at', '>=', Carbon::parse($from));
                            })
                            ->when(filled($to), function($query) use($to){
                                $query->whereDate('submitted_at', '<=', Carbon::parse($to));
                            })
                            ->when(filled($status), function($query) use($status){
                                $query->whereHas('status', function($subQuery) use($status){
                                    $subQuery->where('desc', $status);
                                });
                            })
                            ->orderBy('submitted_at', 'desc')->get();

        return view('laporan.show_by_status', compact('laporans', 'reports' , 'from', 'to', 'title', 'achievement', 'status'));
    }

    public function viewByCategory(Request $request){
        $title = 'Mengikut Kategori';
        $from = $request->from;
        $to = $request->to;
        $category = $request->category;

        $laporans =  \DB::table('report_categories as category')
                    ->select('desc')
                    ->selectRaw('ifnull((select count(*) from reports where report_category_id = category.id and report_status_id in (2,3,4,5) and if(?,submitted_at >= ?, true) and if(?,submitted_at <= ?, true) group by report_category_id),0) as jumlah', [filled($from), Carbon::parse($from), filled($to), Carbon::parse($to)])
                    ->when(filled($category), function($query) use($category){
                        $query->where('desc', $category);
                    })
                    ->orderBy('desc', 'asc')
                    ->get();

        $reports = Report::when(filled($from), function($query) use($from){
                                $query->whereDate('submitted_at', '>=', Carbon::parse($from));
                            })
                            ->when(filled($to), function($query) use($to){
                                $query->whereDate('submitted_at', '<=', Carbon::parse($to));
                            })
                            ->when(filled($category), function($query) use($category){
                                $query->whereHas('category', function($subQuery) use($category){
                                    $subQuery->where('desc', $category);
                                });
                            })
                            ->orderBy('submitted_at', 'desc')->get();

        return view('laporan.show_by_category', compact('laporans', 'reports' , 'from', 'to', 'title', 'category'));
    }

    public function viewByRating(Request $request){
        $title = 'Mengikut Rating Pengguna';
        $from = $request->from;
        $to = $request->to;

        $laporans =  \DB::table('reports')
                    ->select('rating')
                    ->selectRaw('count(*) as total')
                    ->where('report_status_id', 5)
                    ->when(filled($from), function($query) use($from){
                        $query->where('submitted_at', '>=', $from);
                    })
                    ->when(filled($to), function($query) use($to){
                        $query->where('submitted_at', '<=', $to);
                    })
                    ->groupBy('rating')
                    ->orderBy('rating', 'desc')
                    ->get();

        $reports = Report::when(filled($from), function($query) use($from){
                                $query->whereDate('submitted_at', '>=', Carbon::parse($from));
                            })
                            ->when(filled($to), function($query) use($to){
                                $query->whereDate('submitted_at', '<=', Carbon::parse($to));
                            })
                            ->where('report_status_id', 5)
                            ->orderBy('submitted_at', 'desc')->get();

        return view('laporan.show_by_rating', compact('laporans', 'reports' , 'from', 'to', 'title'));
    }

    public function viewByRole(Request $request){
        $title = 'Mengikut Pegawai Teknikal';
        $from = $request->from;
        $to = $request->to;
        $peranan = $request->peranan;

        $role_ids = Role::whereHas('permissions', function($query){
            $query->where('name', 'aduan.updateTask');
        })
        ->pluck('id', 'id')->forget(6);

        $laporans = \DB::table('roles')
                    ->select('name', 'desc')
                    ->selectRaw('ifnull((select count(*) from report_trails join reports on reports.id = report_id where assigned_to_role_id = roles.id and status_id in (2,3,4) and if(?,submitted_at >= ?, true) and if(?,submitted_at <= ?, true) group by roles.id),0) as jumlah', [filled($from), Carbon::parse($from), filled($to), Carbon::parse($to)])
                    ->orderBy('desc', 'asc')
                    // ->whereIn('id', [1,2,3])
                    ->when(filled($peranan), function($query) use ($peranan){
                        $query->where('name', $peranan);
                    },
                    function($query) use ($role_ids){
                        $query->whereIn('id', $role_ids);
                    })
                    ->get();

        $report_trails = ReportTrail::join('reports', 'reports.id', '=', 'report_trails.report_id')
                        ->select('report_trails.*', 'reports.ticket_no', 'reports.subject')
                        ->when(filled($peranan), function($query) use($peranan){
                            $query->whereHas('assignedToRole', function($subQuery) use($peranan){
                                $subQuery->where('name',$peranan);
                            });
                        })
                        ->whereNotNull('assigned_to_id')
                        ->orderBy('report_trails.id', 'desc')
                        ->get();

        return view('laporan.show_by_role', compact('laporans', 'report_trails' , 'from', 'to', 'title', 'peranan'));
    }

    public function viewByUser(Request $request){

        $from = $request->from;
        $to = $request->to;
        $username = $request->username;
        $department = $request->department;

        $role_ids = Role::whereHas('permissions', function($query){
                                $query->where('name', 'aduan.updateTask');
                            })
                            ->pluck('id', 'id')->forget(6);
                            // return $role_ids;
        $laporans = \DB::table('users')
                    ->leftJoin('report_trails', 'users.id', '=', 'report_trails.assigned_to_id')
                    ->select('name', 'username')
                    ->selectRaw('count(report_trails.id) as jumlah')
                    ->whereExists(function($query) use ($role_ids){
                        $query->select('*')
                              ->from('model_has_roles')
                              ->where('model_type', 'App\\Models\\User')
                              ->whereIn('role_id', $role_ids)
                              ->whereColumn('model_id', 'users.id');
                    })
                    ->when(filled($username), function($query) use($username){
                        $query->where('username', $username);
                    })
                    ->when(filled($department), function($query) use($department){
                        $query->where('ldap_bahagian', $department);
                    })
                    ->groupBy('name', 'username')
                    ->orderBy('name', 'asc')
                    ->get();

        $report_trails = ReportTrail::join('reports', 'reports.id', '=', 'report_trails.report_id')
                        ->when(filled($username) || filled($department), function($query) use($username, $department){
                            $query->whereExists(function($subQuery) use ($username, $department){
                                $subQuery->select(\DB::raw(1))
                                            ->from('users')
                                            ->whereColumn('users.id', 'report_trails.assigned_to_id')
                                            ->whereRaw('if(?,username = ?, true) and if(?,ldap_bahagian = ?, true)',[filled($username), $username, filled($department), $department]);
                            });
                        })
                        ->whereNotNull('assigned_to_id')
                        ->get();
        $departments = \DB::table('users')->whereNotNull('ldap_bahagian')->distinct()->pluck('ldap_bahagian');

        return view('laporan.show_by_user', compact('laporans', 'report_trails' , 'from', 'to', 'departments', 'username', 'department'));
    }

    public function viewByDepartment(Request $request){

        $from = $request->from;
        $to = $request->to;
        $department = $request->department;

        $departments = \DB::table('users')->whereNotNull('ldap_bahagian')->distinct()->pluck('ldap_bahagian');

        $laporans = \DB::table('users')
                    ->selectRaw('distinct(ldap_bahagian) as bahagian, ( select count(id) from reports where bahagian_pengadu = bahagian and report_status_id in (2,3,4,5) ) as jumlah')
                    ->when(filled($department), function($query) use($department){
                        $query->where('ldap_bahagian', $department);
                    })
                    ->groupBy('bahagian')
                    ->orderBy('bahagian', 'asc')
                    ->get();

        $reports = Report::when(filled($department), function($query) use ($department){
                                $query->where('bahagian_pengadu', $department);
                            })->get();

        return view('laporan.show_by_department', compact('laporans', 'reports' , 'from', 'to', 'departments', 'department'));
    }


    //return response json
    public function laporanByStatusApi(Request $request){
        $from = Carbon::parse($request->from);
        $from_check = filled($request->from);
        $to = Carbon::parse($request->to);
        $to_check = filled($request->to);

        $laporans =  \DB::table('_report_statuses as report_status')
                    ->select('id', 'desc')
                    ->selectRaw('ifnull((select count(*) from reports where report_status_id = report_status.id and if(?,submitted_at >= ?, true) and if(?,submitted_at <= ?, true) group by report_status_id),0) as jumlah', [$from_check, $from, $to_check, $to])
                    ->whereIn('id', [2,3,4,5])
                    ->orderBy('id')
                    ->get();

        $laporans->transform(function($item,$key){
            $item->url = route('laporan.view-by-status',['status' => $item->desc]);
            return $item;
        });

        $datas =[
            'success' => true,
            'laporan' => $laporans
        ];

        return response()->json($datas);
    }

    public function laporanByCategoryApi(Request $request){

        $from = Carbon::parse($request->from);
        $from_check = filled($request->from);
        $to = Carbon::parse($request->to);
        $to_check = filled($request->to);

        $laporans =  \DB::table('report_categories as category')
                    ->select('desc')
                    ->selectRaw('ifnull((select count(*) from reports where report_category_id = category.id and report_status_id in (2,3,4,5) and if(?,submitted_at >= ?, true) and if(?,submitted_at <= ?, true) group by report_category_id),0) as jumlah', [$from_check, $from, $to_check, $to])
                    ->orderBy('desc', 'asc')
                    ->get();

        $laporans->transform(function($item,$key){
            $item->url = route('laporan.view-by-category',['category' => $item->desc]);
            return $item;
        });

        $datas =[
            'success' => true,
            'laporan' => $laporans
        ];
        return response()->json($datas);
    }

    public function laporanByRoleyApi(Request $request){

        $from = Carbon::parse($request->from);
        $from_check = filled($request->from);
        $to = Carbon::parse($request->to);
        $to_check = filled($request->to);

        $laporans = \DB::table('roles')
                    ->select('name','desc')
                    ->selectRaw('ifnull((select count(*) from report_trails join reports on reports.id = report_id where assigned_to_role_id = roles.id and status_id in (2,3,4) and if(?,submitted_at >= ?, true) and if(?,submitted_at <= ?, true) group by roles.id),0) as jumlah', [$from_check, $from, $to_check, $to])
                    ->orderBy('desc', 'asc')
                    ->whereIn('id', [1,2,3])
                    ->get();

        $laporans->transform(function($item,$key){
            $item->url = route('laporan.view-by-role',['peranan' => $item->name]);
            return $item;
        });

        $datas =[
            'success' => true,
            'laporan' => $laporans
        ];
        return response()->json($datas);
    }

    public function laporanByRatingApi(Request $request){

        $from = Carbon::parse($request->from);
        $from_check = filled($request->from);
        $to = Carbon::parse($request->to);
        $to_check = filled($request->to);

        $ratings =  \DB::table('reports')
                    ->select('rating')
                    ->selectRaw('count(*) as total')
                    ->where('report_status_id', 5)
                    ->when($from_check, function($query) use($from){
                        $query->where('submitted_at', '>=', $from);
                    })
                    ->when($to_check, function($query) use($to){
                        $query->where('submitted_at', '<=', $to);
                    })
                    ->groupBy('rating')
                    ->orderBy('rating', 'desc')
                    ->get();
        $datas =[
            'success' => true,
            'ratings' => $ratings,
            'total' => $ratings->sum('total')
        ];
        return response()->json($datas);
    }

    public function laporanByUserApi(Request $request){
        $from = Carbon::parse($request->from);
        $from_check = filled($request->from);
        $to = Carbon::parse($request->to);
        $to_check = filled($request->to);

        $role_ids = Role::whereHas('permissions', function($query){
            $query->where('name', 'aduan.updateTask');
        })
        ->pluck('id', 'id')->forget(6);
        // return $role_ids;
        $laporans = \DB::table('users')
                    ->leftJoin('report_trails', 'users.id', '=', 'report_trails.assigned_to_id')
                    ->select('name as desc', 'username')
                    ->selectRaw('count(report_trails.id) as jumlah')
                    ->whereExists(function($query) use ($role_ids){
                        $query->select('*')
                            ->from('model_has_roles')
                            ->where('model_type', 'App\\Models\\User')
                            ->whereIn('role_id', $role_ids)
                            ->whereColumn('model_id', 'users.id');
                    })
                    ->when($from_check, function($query) use($from){
                        $query->where('report_trails.created_at', '>=', $from);
                    })
                    ->when($to_check, function($query) use($to){
                        $query->where('report_trails.created_at', '<=', $to);
                    })
                    ->groupBy('desc', 'username')
                    ->orderBy('desc', 'asc')
                    ->get();

        $laporans->transform(function($item,$key){
            $item->url = route('laporan.view-by-user',['username' => $item->username]);
            return $item;
        });

        $datas =[
            'success' => true,
            'laporan' => $laporans
        ];

        return response()->json($datas);
    }

    public function laporanByDepartmentApi(Request $request){
        $from = Carbon::parse($request->from);
        $from_check = filled($request->from);
        $to = Carbon::parse($request->to);
        $to_check = filled($request->to);

        // return $role_ids;
        $laporans = \DB::table('users')
                    ->selectRaw('distinct(ldap_bahagian) as bahagian, ( select count(id) from reports where bahagian_pengadu = bahagian and report_status_id in (2,3,4,5) ) as jumlah')
                    ->groupBy('bahagian')
                    ->orderBy('bahagian', 'asc')
                    ->get();

        $laporans->transform(function($item,$key){
            $item->url = route('laporan.view-by-department',['department' => $item->bahagian]);
            return $item;
        });

        $datas =[
            'success' => true,
            'laporan' => $laporans
        ];

        return response()->json($datas);
    }
}
