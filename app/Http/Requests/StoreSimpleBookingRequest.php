<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSimpleBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Thông tin booking
            'room_id'   => 'required|integer|exists:rooms,id',
            'check_in'  => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'rooms'     => 'required|integer|min:1|max:10',

            // Thông tin người đại diện - single fields
            'name'  => 'required|string|max:150|min:2',
            'cccd'  => 'required|string|size:12|regex:/^[0-9]{12}$/',
        ];
    }

    public function messages(): array
    {
        return [
            // Booking
            'room_id.required'  => 'Vui lòng chọn phòng.',
            'room_id.exists'    => 'Phòng không tồn tại.',
            'check_in.required' => 'Vui lòng chọn ngày nhận phòng.',
            'check_in.after_or_equal' => 'Ngày nhận phòng phải từ hôm nay trở đi.',
            'check_out.required'=> 'Vui lòng chọn ngày trả phòng.',
            'check_out.after'   => 'Ngày trả phòng phải sau ngày nhận phòng.',
            'rooms.required'    => 'Vui lòng chọn số phòng.',
            'rooms.min'         => 'Phải đặt ít nhất 1 phòng.',
            'rooms.max'         => 'Tối đa 10 phòng.',

            // Người đại diện
            'name.required'     => 'Vui lòng nhập tên người đại diện.',
            'name.min'          => 'Tên phải có ít nhất 2 ký tự.',
            'cccd.required'     => 'Vui lòng nhập CCCD người đại diện.',
            'cccd.size'         => 'CCCD phải đúng 12 số.',
            'cccd.regex'        => 'CCCD chỉ được chứa 12 chữ số.',
        ];
    }
}
