@extends('layouts.admin')

@section('title', 'Chi tiết đổi phòng #' . $history->id)

@section('content')
@include('shared.room_change_show', [
    'history' => $history,
    'canRevert' => $canRevert,
    'bookingHistories' => $bookingHistories,
    'routePrefix' => 'admin',
])
@endsection
