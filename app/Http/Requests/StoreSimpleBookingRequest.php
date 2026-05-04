<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreSimpleBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($user instanceof User && ! $user->roles()->whereIn('name', ['admin', 'staff'])->exists()) {
            $this->merge([
                'email' => $user->email,
                'full_name' => $user->full_name,
                'phone' => $this->filled('phone') ? $this->input('phone') : ($user->phone ?? ''),
            ]);
        }
    }

    public function rules(): array
    {
        $emailRules = ['required', 'email', 'max:150'];
        /** @var User|null $user */
        $user = Auth::user();
        if ($user instanceof User && ! $user->roles()->whereIn('name', ['admin', 'staff'])->exists()) {
            $emailRules[] = Rule::in([$user->email]);
        }

        return [
            'room_type_id' => [
                'required',
                'integer',
                Rule::exists('room_types', 'id'),
            ],
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'rooms' => 'required|integer|min:1|max:10',

            'full_name' => 'required|string|max:150|min:2',
            'email' => $emailRules,
            'phone' => ['required', 'string', 'min:10', 'max:20', 'regex:/^[0-9\+\-\s]+$/'],

            'name' => 'required|string|max:150|min:2',
            'cccd' => 'required|string|size:12|regex:/^[0-9]{12}$/',
        ];
    }

    public function messages(): array
    {
        return [
            'room_type_id.required' => 'Vui lòng chọn loại phòng.',
            'room_type_id.exists' => 'Loại phòng không tồn tại.',
            'check_in.required' => 'Vui lòng chọn ngày nhận phòng.',
            'check_in.after_or_equal' => 'Ngày nhận phòng phải từ hôm nay trở đi.',
            'check_out.required' => 'Vui lòng chọn ngày trả phòng.',
            'check_out.after' => 'Ngày trả phòng phải sau ngày nhận phòng.',
            'rooms.required' => 'Vui lòng chọn số phòng.',
            'rooms.min' => 'Phải đặt ít nhất 1 phòng.',
            'rooms.max' => 'Tối đa 10 phòng.',

            'full_name.required' => 'Vui lòng nhập họ tên.',
            'full_name.min' => 'Họ tên phải có ít nhất 2 ký tự.',
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.',
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.min' => 'Số điện thoại phải có ít nhất 10 số.',
            'phone.regex' => 'Số điện thoại chỉ được chứa số, dấu +, dấu - và khoảng trắng.',

            'name.required' => 'Vui lòng nhập tên người đại diện.',
            'name.min' => 'Tên người đại diện phải có ít nhất 2 ký tự.',
            'cccd.required' => 'Vui lòng nhập CCCD người đại diện.',
            'cccd.size' => 'CCCD phải đúng 12 số.',
            'cccd.regex' => 'CCCD chỉ được chứa 12 chữ số.',
        ];
    }
}
