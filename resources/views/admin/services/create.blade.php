@extends('layouts.admin')

@section('title', 'Thêm dịch vụ')

@section('content')

<div class="mb-4">
    <h3 class="fw-bold">
        <i class="bi bi-plus-circle me-2"></i>
        Thêm dịch vụ
    </h3>
</div>

<div class="card shadow-sm border-0 rounded-4">
<div class="card-body">

<form action="{{ route('admin.services.store') }}" 
      method="POST">

@csrf

<div class="mb-3">

<label class="form-label fw-semibold">

Tên dịch vụ

</label>

<input type="text"
       name="name"
       class="form-control"
       placeholder="Ví dụ: Ăn sáng buffet"
       value="{{ old('name') }}"
       required>

</div>

<div class="mb-3">

<label class="form-label fw-semibold">

Giá (VNĐ)

</label>

<input type="number"
       name="price"
       class="form-control"
       placeholder="Ví dụ: 100000"
       value="{{ old('price') }}"
       required>

</div>

<div class="mb-3">

<label class="form-label fw-semibold">

Mô tả

</label>

<textarea name="description"
          rows="4"
          class="form-control"
          placeholder="Mô tả dịch vụ...">{{ old('description') }}</textarea>

</div>

<div class="d-flex gap-2">

<button class="btn btn-success">

<i class="bi bi-check-circle me-1"></i>

Lưu dịch vụ

</button>

<a href="{{ route('admin.services.index') }}"
   class="btn btn-secondary">

Quay lại

</a>

</div>

</form>

</div>
</div>

@endsection