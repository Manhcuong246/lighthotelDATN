<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'room_ids'       => 'required|array|min:1',
            'room_ids.*'     => 'required|integer|exists:rooms,id',
            'full_name'      => 'required|string|max:150|min:2',
            'email'          => 'required|email|max:150',
            'phone'          => 'required|string|min:10|max:20|regex:/^[0-9\+\-\s]+$/',
            'check_in'       => 'required|date|after_or_equal:today',
            'check_out'      => 'required|date|after:check_in',
            'payment_method' => 'required|in:vnpay',
            'bank_code'      => 'nullable|string|max:50',
            'coupon_code'    => 'nullable|string|max:50',
            'adults'         => 'required|array',
            'children_0_5'   => 'required|array',
            'children_6_11'  => 'required|array',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'room_ids.required'      => 'Vui lòng chọn ít nhất 1 phòng.',
            'room_ids.*.exists'      => 'Phòng không tồn tại.',
            'full_name.required'     => 'Vui lòng nhập họ tên.',
            'full_name.min'          => 'Họ tên phải có ít nhất 2 ký tự.',
            'email.required'         => 'Vui lòng nhập email.',
            'email.email'            => 'Email không hợp lệ.',
            'phone.required'         => 'Vui lòng nhập số điện thoại.',
            'phone.min'              => 'Số điện thoại phải có ít nhất 10 số.',
            'phone.regex'            => 'Số điện thoại chỉ được chứa số, dấu +, dấu - và khoảng trắng.',
            'check_in.required'      => 'Vui lòng chọn ngày nhận phòng.',
            'check_in.date'          => 'Ngày nhận phòng không hợp lệ.',
            'check_in.after_or_equal'=> 'Ngày nhận phòng phải từ hôm nay trở đi.',
            'check_out.required'     => 'Vui lòng chọn ngày trả phòng.',
            'check_out.date'         => 'Ngày trả phòng không hợp lệ.',
            'check_out.after'        => 'Ngày trả phòng phải sau ngày nhận phòng.',
        ];
    }
}
