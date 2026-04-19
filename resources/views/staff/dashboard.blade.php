@extends('layouts.app')

@section('content')

<div class="container">

<h2>Dashboard Staff</h2>

<h4>Booking hôm nay</h4>

<table class="table">

<thead>
<tr>
    <th>ID</th>
    <th>Khách</th>
    <th>Check-in</th>
    <th>Check-out</th>
</tr>
</thead>

<tbody>

@foreach($todayBookings as $booking)

<tr>

<td>{{ $booking->id }}</td>

<td>{{ $booking->user->full_name ?? '' }}</td>

<td>{{ $booking->check_in_date }}</td>

<td>{{ $booking->check_out_date }}</td>

</tr>

@endforeach

</tbody>

</table>

</div>
{{-- Update UI for staff dashboard --}}

@endsection