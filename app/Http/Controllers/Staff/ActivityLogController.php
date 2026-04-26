<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ActivityLogController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'staff']);
    }

    public function index()
    {
        // Lấy logs từ bảng activity_logs nếu có
        // Hoặc hiển thị trang trống nếu chưa có bảng
        try {
            $logs = DB::table('activity_logs')
                ->orderBy('created_at', 'desc')
                ->paginate(20);
        } catch (\Exception $e) {
            $logs = collect([]);
        }

        return view('staff.activity-logs.index', compact('logs'));
    }
}
