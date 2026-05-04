@extends('layouts.admin')

@section('title', 'Sửa hóa đơn')

@section('content')
<div class="container-fluid px-3 px-lg-4">
    <div class="mb-4">
        <a href="{{ route('admin.invoices.show', $invoice) }}" class="btn btn-sm btn-outline-secondary btn-admin-icon rounded-2 mb-2"><i class="bi bi-arrow-left"></i></a>
        <h1 class="h2 fw-bold mb-0">Sửa hóa đơn <code>{{ $invoice->invoice_number }}</code></h1>
    </div>

    @if($errors->any())
        <div class="alert alert-danger rounded-3">{{ $errors->first() }}</div>
    @endif

    <div class="card border-0 shadow-sm rounded-3" style="max-width: 520px;">
        <div class="card-body p-4">
            <p class="small text-muted">Tiền phòng và dịch vụ trên hóa đơn giữ nguyên; chỉ điều chỉnh giảm giá, thuế và ghi chú.</p>
            <form action="{{ route('admin.invoices.update', $invoice) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label class="form-label" for="discount_amount">Giảm giá (VNĐ)</label>
                    <input type="number" name="discount_amount" id="discount_amount" class="form-control form-control-sm" min="0" step="1000" value="{{ old('discount_amount', $invoice->discount_amount) }}">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="tax_amount">Thuế &amp; phí (VNĐ)</label>
                    <input type="number" name="tax_amount" id="tax_amount" class="form-control form-control-sm" min="0" step="1000" value="{{ old('tax_amount', $invoice->tax_amount) }}">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="notes">Ghi chú</label>
                    <textarea name="notes" id="notes" rows="3" class="form-control form-control-sm" maxlength="1000">{{ old('notes', $invoice->notes) }}</textarea>
                </div>
                <button type="submit" class="btn btn-primary rounded-2">Lưu</button>
            </form>
        </div>
    </div>
</div>
@endsection
