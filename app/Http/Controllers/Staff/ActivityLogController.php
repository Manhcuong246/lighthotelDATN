<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Carbon\Carbon;

class ActivityLogController extends Controller
{
    public function index()
    {
        // Danh sách log
        $logs = ActivityLog::with('user')
            ->latest()
            ->paginate(15);

        // Ngày hôm nay
        $today = Carbon::today();

        // Tổng hoạt động hôm nay
        $todayLogsCount = ActivityLog::whereDate(
            'created_at',
            $today
        )->count();

        // Check-in hôm nay
        $checkInLogsCount = ActivityLog::where(
            'action',
            'Check-in'
        )->whereDate(
            'created_at',
            $today
        )->count();

        // Check-out hôm nay
        $checkOutLogsCount = ActivityLog::where(
            'action',
            'Check-out'
        )->whereDate(
            'created_at',
            $today
        )->count();

        return view(
            'staff.activity_logs.index', // ✅ ĐÚNG path của bạn
            compact(
                'logs',
                'todayLogsCount',
                'checkInLogsCount',
                'checkOutLogsCount'
            )
        );
    }
}