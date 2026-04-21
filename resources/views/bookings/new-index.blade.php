@extends('layouts.app')

@section('title', 'Đặt phòng khách sạn')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="bi bi-calendar-check me-2"></i>
                        Đặt phòng khách sạn
                    </h4>
                </div>
                <div class="card-body">
                    <form id="searchForm" method="POST" action="{{ route('bookings.search') }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Ngày nhận phòng *</label>
                                <input type="date" name="check_in" class="form-control" 
                                       value="{{ old('check_in', date('Y-m-d')) }}" 
                                       min="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Ngày trả phòng *</label>
                                <input type="date" name="check_out" class="form-control" 
                                       value="{{ old('check_out', date('Y-m-d', strtotime('+1 day'))) }}" 
                                       min="{{ date('Y-m-d', strtotime('+1 day')) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-search me-2"></i>
                                    Tìm phòng trống
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Xử lý form tìm phòng
document.getElementById('searchForm').addEventListener('submit', function(e) {
    const checkIn = document.querySelector('input[name="check_in"]').value;
    const checkOut = document.querySelector('input[name="check_out"]').value;
    
    // Kiểm tra ngày trả phòng phải sau ngày nhận phòng
    if (new Date(checkOut) <= new Date(checkIn)) {
        e.preventDefault();
        alert('Ngày trả phòng phải sau ngày nhận phòng!');
        return false;
    }
});
</script>
@endsection
