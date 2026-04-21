<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request validation cho chức năng đổi phòng
 */
class RoomChangeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Chỉ admin mới có quyền đổi phòng
        return auth()->check() && auth()->user()->role === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'old_room_id' => [
                'required',
                'integer',
                'exists:rooms,id',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    // Kiểm tra phòng cũ có thuộc booking này không
                    $booking = $this->route('booking');
                    if ($booking) {
                        $hasRoom = $booking->bookingRooms()
                            ->where('room_id', $value)
                            ->exists();
                        if (!$hasRoom) {
                            $fail('Phòng này không thuộc đơn đặt phòng hiện tại.');
                        }
                    }
                },
            ],
            'new_room_id' => [
                'required',
                'integer',
                'exists:rooms,id',
                'different:old_room_id',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    // Kiểm tra phòng mới không được trùng với phòng khác trong cùng booking
                    $booking = $this->route('booking');
                    if ($booking) {
                        $existsInBooking = $booking->bookingRooms()
                            ->where('room_id', $value)
                            ->exists();
                        if ($existsInBooking) {
                            $fail('Phòng mới đã có trong đơn đặt phòng này.');
                        }
                    }
                },
            ],
            'reason' => [
                'nullable',
                'string',
                'max:500',
            ],
            'keep_price' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'old_room_id.required' => 'Vui lòng chọn phòng cần đổi.',
            'old_room_id.exists' => 'Phòng cũ không tồn tại trong hệ thống.',
            'new_room_id.required' => 'Vui lòng chọn phòng mới.',
            'new_room_id.exists' => 'Phòng mới không tồn tại trong hệ thống.',
            'new_room_id.different' => 'Phòng mới phải khác phòng cũ.',
            'reason.max' => 'Lý do đổi phòng không được vượt quá 500 ký tự.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert keep_price to boolean
        if ($this->has('keep_price')) {
            $this->merge([
                'keep_price' => filter_var($this->keep_price, FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }
}
