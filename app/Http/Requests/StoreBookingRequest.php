<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (Auth::check() && Auth::user() && ! Auth::user()->canAccessAdmin()) {
            $this->merge([
                'email' => Auth::user()->email,
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $emailRules = ['required', 'email', 'max:150'];
        if (Auth::check() && Auth::user() && ! Auth::user()->canAccessAdmin()) {
            $emailRules[] = Rule::in([Auth::user()->email]);
        }

        return [
            'room_ids'       => [
                'required',
                'array',
                'min:1',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_array($value)) {
                        return;
                    }
                    if (count($value) !== count(array_unique($value))) {
                        $fail('Trong một đơn không được chọn trùng cùng một phòng.');
                    }
                },
            ],
            'room_ids.*'     => 'required|integer|exists:rooms,id',
            'full_name'      => 'required|string|max:150|min:2',
            'email'          => $emailRules,
            'phone'          => 'required|string|min:10|max:20|regex:/^[0-9\+\-\s]+$/',
            'check_in'       => 'required|date|after_or_equal:today',
            'check_out'      => 'required|date|after:check_in',
            'payment_method' => 'required|in:vnpay',
            'bank_code'      => 'nullable|string|max:50',
            'coupon_code'    => 'nullable|string|max:50',
            'adults'         => 'required|array',
            'children_0_5'   => 'required|array',
            'children_6_11'  => 'required|array',
            'guests'         => 'nullable|array',
           'guests.*.name'  => 'required_with:guests|string|max:150',
           'guests.*.cccd'  => 'nullable|string|regex:/^[0-9]{12}$/',
            'guests.*.type'  => 'required_with:guests|in:adult,child',
            'guests.*.room_index' => 'nullable|integer|min:0',
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
              'guests.required'        => 'Vui lòng cung cấp thông tin khách.',
            'guests.*.name.required' => 'Vui lòng nhập tên khách.',
            'guests.*.name.max'      => 'Tên khách không được vượt quá 150 ký tự.',
            'guests.*.cccd.regex'    => 'CCCD phải gồm 12 số.',
            'guests.*.type.required' => 'Vui lòng chọn loại khách.',
            'guests.*.type.in'       => 'Loại khách không hợp lệ.',
            'guests.*.room_index.integer' => 'Index phòng không hợp lệ.',
            'guests.*.room_index.min' => 'Index phòng không được âm.',
        ];
    }
}
