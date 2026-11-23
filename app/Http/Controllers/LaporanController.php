<?php

namespace App\Http\Controllers;

use App\Models\Ref\RiskLevel;
use App\Models\Report;
use App\Support\ReportStatuses;
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

    /**
     * Combined report with all filters (date range / bahagian / status /
     * pegawai) on a single page. Rendering + filtering handled by the Livewire
     * component App\Livewire\Laporan\ReportAll.
     */
    public function viewByAll(Request $request){

        return view('laporan.show_by_all');
    }

    public function viewByStatus(Request $request){
        $title = 'Mengikut Status';
        $from = $request->from;
        $to = $request->to;
        $status = $request->status;
        $achievement = null;

        $aduans = \DB::table('reports')
                    ->selectRaw('report_status_id, count(*) as jumlah')
                    ->whereIn('report_status_id', ReportStatuses::COUNTED)
                    ->when(filled($from), function($query) use($from){
                        $query->whereDate('submitted_at', '>=', Carbon::parse($from));
                    })
                    ->when(filled($to), function($query) use($to){
                        $query->whereDate('submitted_at', '<=', Carbon::parse($to));
                    })
                   ->groupBy('report_status_id');
                 //  ->orderBy('report_status_id')
                 //  ->orderBy('report_status_id')
                  //  ->get();

        // Draf excluded from both the rows and the denominator: it is not a
        // submitted aduan, and counting it made the achievement % too low.
        $laporans = \DB::table('_report_statuses as report_status')
        ->leftJoinSub($aduans, 'aduans', function ($join) {
            $join->on('report_status.id', '=', 'aduans.report_status_id');
        })->whereIn('report_status.id', ReportStatuses::COUNTED)->get();
        // $laporans =  \DB::table('_report_statuses as report_status')
        //             ->select('id', 'desc')
        //             ->selectRaw('ifnull((select count(*) from reports where report_status_id = report_status.id and if(?,submitted_at >= ?, true) and if(?,submitted_at <= ?, true) group by report_status_id),0) as jumlah', [filled($from), Carbon::parse($from), filled($to), Carbon::parse($to)])
        //             // ->whereIn('id', [2,3,4,5])
        //             ->when(filled($status), function($query) use ($status){
        //                 $query->where('desc', $status);
        //             },
        //             function($query){
        //                 $query->whereIn('id', [2,3,4,5,6]);
        //             })
        //             ->orderBy('id')
        //             ->toSql();
        //return $laporans;

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
                            ->where('report_status_id', '<>', 1)
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
                    ->selectRaw('ifnull((select count(*) from reports where report_category_id = category.id and report_status_id <> 1 and if(?,submitted_at >= ?, true) and if(?,submitted_at <= ?, true) group by report_category_id),0) as jumlah', [filled($from), Carbon::parse($from), filled($to), Carbon::parse($to)])
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
                            ->where('report_status_id', '<>', 1)
                            ->orderBy('submitted_at', 'desc')->get();

        return view('laporan.show_by_category', compact('laporans', 'reports' , 'from', 'to', 'title', 'category'));
    }

    /**
     * Aduan grouped by risk level (tahap risiko). Filtered by `risk` = the
     * numeric level (1..5), which is stable, unlike the editable label.
     * Aduan not yet classified by the helpdesk are reported as a separate
     * "Belum Ditetapkan" row.
     */
    public function viewByRisk(Request $request){
        $title = 'Mengikut Tahap Risiko';
        $from = $request->from;
        $to = $request->to;
        $risk = $request->risk;

        $counts = $this->riskCounts($from, $to);

        $laporans = RiskLevel::orderBy('level')
                    ->when(filled($risk), function($query) use($risk){
                        $query->where('level', $risk);
                    })
                    ->get()
                    ->map(function($level) use($counts){
                        return (object) [
                            'level' => $level->level,
                            'desc' => $level->label,
                            'class' => $level->class,
                            'jumlah' => (int) ($counts[$level->id] ?? 0),
                        ];
                    });

        if(blank($risk)){
            $laporans->push((object) [
                'level' => null,
                'desc' => 'Belum Ditetapkan',
                'class' => 'badge bg-light text-dark',
                'jumlah' => (int) ($counts[''] ?? 0),
            ]);
        }

        $reports = Report::with('riskLevel')
                            ->when(filled($from), function($query) use($from){
                                $query->whereDate('submitted_at', '>=', Carbon::parse($from));
                            })
                            ->when(filled($to), function($query) use($to){
                                $query->whereDate('submitted_at', '<=', Carbon::parse($to));
                            })
                            ->when(filled($risk), function($query) use($risk){
                                $query->whereHas('riskLevel', function($subQuery) use($risk){
                                    $subQuery->where('level', $risk);
                                });
                            })
                            ->where('report_status_id', '<>', 1)
                            ->orderBy('submitted_at', 'desc')->get();

        return view('laporan.show_by_risk', compact('laporans', 'reports' , 'from', 'to', 'title', 'risk'));
    }

    /**
     * risk_level_id => bilangan aduan (excluding drafts), within the optional
     * date range. Unclassified aduan are keyed under ''.
     *
     * @return \Illuminate\Support\Collection<array-key, int>
     */
    private function riskCounts($from, $to)
    {
        return Report::query()
                ->selectRaw('risk_level_id, count(*) as jumlah')
                ->where('report_status_id', '<>', 1)
                ->when(filled($from), function($query) use($from){
                    $query->whereDate('submitted_at', '>=', Carbon::parse($from));
                })
                ->when(filled($to), function($query) use($to){
                    $query->whereDate('submitted_at', '<=', Carbon::parse($to));
                })
                ->groupBy('risk_level_id')
                ->pluck('jumlah', 'risk_level_id');
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
                    ->selectRaw('ifnull((select count(*) from report_trails  where id = ( select max(id) from report_trails as rt where rt.report_id = report_trails.report_id and status_id in (4,6) )  and assigned_to_role_id = roles.id and status_id in (4,6) and if(?,report_trails.created_at >= ?, true) and if(?,report_trails.created_at <= ?, true) group by roles.id),0) as jumlah', [filled($from), Carbon::parse($from), filled($to), Carbon::parse($to)])
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
                        ->when(filled($from), function($query) use($from){
                            $query->whereDate('report_trails.created_at', '>=', Carbon::parse($from));
                        })
                        ->when(filled($to), function($query) use($to){
                            $query->whereDate('report_trails.created_at', '<=', Carbon::parse($to));
                        })
                        ->whereRaw('report_trails.id = ( select max(id) from report_trails as rt where rt.report_id = report_trails.report_id and status_id in (4,6)  )')
                        ->whereIn('status_id', [4,6])
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

    /**
     * Satu baris per `users.ldap_bahagian` — termasuk bahagian NULL, seperti
     * sebelum ini — dengan `bahagian` dan `jumlah`, disusun menaik mengikut
     * bahagian. `jumlah` ialah bilangan aduan yang `bahagian_pengadu`-nya
     * sama dengan bahagian itu dan bukan Draf (`report_status_id <> 1`);
     * bahagian tanpa aduan sedemikian memberi 0.
     *
     * Ditulis dengan SQL standard sahaja. Versi terdahulu merujuk alias
     * senarai select (`bahagian`) dari dalam WHERE subkueri, iaitu sambungan
     * MySQL yang ditolak SQLite, jadi `GET /laporan/mengikut-bahagian`
     * menghasilkan 500 di bawah sambungan ujian. Di sini ungkapan lajur
     * `users.ldap_bahagian` diulang di dalam subkueri, dan subkueri itu
     * berkorelasi dengan lajur GROUP BY itu sendiri, jadi ia sah pada
     * kedua-dua pemacu dan memberi hasil yang sama.
     *
     * viewByDepartment() dan laporanByDepartmentApi() berkongsi pembina ini
     * supaya kedua-dua pertanyaan tidak boleh menyimpang lagi.
     *
     * @param  string|null  $department  hadkan kepada satu bahagian sahaja
     * @return \Illuminate\Database\Query\Builder
     */
    private function departmentCounts($department = null)
    {
        return \DB::table('users')
                ->select('users.ldap_bahagian as bahagian')
                ->selectSub(function($query){
                    $query->from('reports')
                            ->selectRaw('count(reports.id)')
                            ->whereColumn('reports.bahagian_pengadu', 'users.ldap_bahagian')
                            ->where('reports.report_status_id', '<>', 1);
                }, 'jumlah')
                ->when(filled($department), function($query) use($department){
                    $query->where('users.ldap_bahagian', $department);
                })
                ->groupBy('users.ldap_bahagian')
                ->orderBy('users.ldap_bahagian', 'asc');
    }

    public function viewByDepartment(Request $request){

        $from = $request->from;
        $to = $request->to;
        $department = $request->department;

        $departments = \DB::table('users')->whereNotNull('ldap_bahagian')->distinct()->pluck('ldap_bahagian');

        $laporans = $this->departmentCounts($department)->get();

        $reports = Report::when(filled($department), function($query) use ($department){
                                $query->where('bahagian_pengadu', $department);
                            })
                            ->when(filled($from), function($query) use($from){
                                $query->whereDate('submitted_at', '>=', Carbon::parse($from));
                            })
                            ->when(filled($to), function($query) use($to){
                                $query->whereDate('submitted_at', '<=', Carbon::parse($to));
                            })
                            ->where('report_status_id', '<>', 1)
                            ->get();

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
                    ->whereIn('id', ReportStatuses::COUNTED)
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
                    ->selectRaw('ifnull((select count(*) from reports where report_category_id = category.id and report_status_id <> 1 and if(?,submitted_at >= ?, true) and if(?,submitted_at <= ?, true) group by report_category_id),0) as jumlah', [$from_check, $from, $to_check, $to])
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

    public function laporanByRiskApi(Request $request){

        $counts = $this->riskCounts($request->from, $request->to);

        $laporans = RiskLevel::orderBy('level')->get()->map(function($level) use($counts){
            return (object) [
                'desc' => $level->label,
                'jumlah' => (int) ($counts[$level->id] ?? 0),
                'url' => route('laporan.view-by-risk', ['risk' => $level->level]),
            ];
        });

        $laporans->push((object) [
            'desc' => 'Belum Ditetapkan',
            'jumlah' => (int) ($counts[''] ?? 0),
            'url' => route('laporan.view-by-risk'),
        ]);

        $datas =[
            'success' => true,
            'laporan' => $laporans->values()
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
                    ->selectRaw('ifnull((select count(*) from report_trails where id = ( select max(id) from report_trails as rt where rt.report_id = report_trails.report_id and status_id in (4,6) ) and assigned_to_role_id = roles.id and status_id in (2,3,4,6) and if(?,created_at >= ?, true) and if(?,created_at <= ?, true) group by roles.id),0) as jumlah', [$from_check, $from, $to_check, $to])
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
                    ->whereIn('status_id', [4,6])
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

        // Pembina yang sama seperti viewByDepartment(), tanpa penapis
        // bahagian: modal laporan sentiasa menyenaraikan semua bahagian.
        $laporans = $this->departmentCounts()->get();

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
