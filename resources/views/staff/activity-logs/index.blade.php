@extends('layouts.admin')

@section('title', 'Activity Logs')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h4 class="mb-4">📜 Activity Logs</h4>

            <div class="card">
                <div class="card-body">
                    @if($logs->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Thời gian</th>
                                        <th>User</th>
                                        <th>Hành động</th>
                                        <th>Chi tiết</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($logs as $log)
                                        <tr>
                                            <td>{{ $log->created_at }}</td>
                                            <td>{{ $log->user_id ?? 'N/A' }}</td>
                                            <td>{{ $log->action ?? 'N/A' }}</td>
                                            <td>{{ $log->description ?? '' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        {{ $logs->links() }}
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-journal-text display-4 text-muted"></i>
                            <p class="mt-3 text-muted">Chưa có activity log nào.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
