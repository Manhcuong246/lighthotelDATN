@extends('layouts.admin')

@section('title', 'Quản lý dịch vụ')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0">
        <i class="bi bi-gear-wide-connected me-2"></i>
        Quản lý dịch vụ
    </h3>

    <a href="{{ route('admin.services.create') }}" 
       class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>
        Thêm dịch vụ
    </a>
</div>

<div class="card shadow-sm border-0 rounded-4">
<div class="card-body">

<table class="table align-middle table-hover">

<thead class="table-light">
<tr>

<th width="5%">#</th>
<th>Tên dịch vụ</th>
<th width="15%">Giá</th>
<th>Mô tả</th>
<th width="18%" class="text-center">
Hành động
</th>

</tr>
</thead>

<tbody>

@forelse($services as $key => $service)

<tr>

<td>
{{ $services->firstItem() + $key }}
</td>

<td class="fw-semibold">
{{ $service->name }}
</td>

<td class="text-success fw-bold">
{{ number_format($service->price) }} VNĐ
</td>

<td>
{{ $service->description ?? '—' }}
</td>

<td class="text-center">

<a href="{{ route('admin.services.edit', $service->id) }}"
   class="btn btn-sm btn-warning">

<i class="bi bi-pencil-square"></i>

</a>

<form action="{{ route('admin.services.destroy', $service->id) }}"
      method="POST"
      class="d-inline"
      onsubmit="return confirm('Bạn có chắc muốn xóa dịch vụ này?');">

@csrf
@method('DELETE')

<button class="btn btn-sm btn-danger">

<i class="bi bi-trash"></i>

</button>

</form>

</td>

</tr>

@empty

<tr>
<td colspan="5" class="text-center text-muted py-4">
Chưa có dịch vụ nào
</td>
</tr>

@endforelse

</tbody>

</table>

<div class="mt-3">

{{ $services->links() }}

</div>

</div>
</div>

@endsection