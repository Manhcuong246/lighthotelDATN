@extends('layouts.admin')

@section('title', 'Cập nhật dịch vụ')

@section('content')

<div class="mb-4">
    <h3 class="fw-bold">
        <i class="bi bi-pencil-square me-2"></i>
        Cập nhật dịch vụ
    </h3>
</div>

<div class="card shadow-sm border-0 rounded-4">
<div class="card-body">

<form action="{{ route('admin.services.update', $service->id) }}"
      method="POST">

@csrf
@method('PUT')

<div class="mb-3">

<label class="form-label">

Tên dịch vụ

</label>

<input type="text"
       name="name"
       class="form-control"
       value="{{ old('name', $service->name) }}"
       required>

</div>

<div class="mb-3">

<label class="form-label">

Giá (VNĐ)

</label>

<input type="number"
       name="price"
       class="form-control"
       value="{{ old('price', $service->price) }}"
       required>

</div>

<div class="mb-3">

<label class="form-label">

Mô tả

</label>

<textarea name="description"
          rows="4"
          class="form-control">{{ old('description', $service->description) }}</textarea>

</div>

<div class="d-flex gap-2">

<button class="btn btn-primary">

<i class="bi bi-save me-1"></i>

Cập nhật

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