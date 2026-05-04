<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

/**
 * Trang lịch sử / chi tiết đơn cho khách qua link có chữ ký (email sau khi thanh toán).
 */
class GuestBookingPortalController extends Controller
{
    private function portalTtlDays(): int
    {
        return max(1, (int) config('booking.signed_booking_show_ttl_days', 90));
    }

    private function portalExpires(): \Carbon\Carbon
    {
        return now()->addDays($this->portalTtlDays());
    }

    private function ensureGuestPortalAllowed(User $user): void
    {
        if ($user->canAccessAdmin()) {
            abort(403);
        }
    }

    public function index(User $user): View
    {
        $this->ensureGuestPortalAllowed($user);

        $expires = $this->portalExpires();

        $bookings = $user
            ->bookings()
            ->with(['room', 'rooms.roomType', 'bookingRooms.roomType', 'bookingRooms.room.roomType', 'payment'])
            ->withCount('bookingServices')
            ->latest('id')
            ->limit(50)
            ->get();

        $bookingDetailUrls = [];
        foreach ($bookings as $b) {
            $bookingDetailUrls[$b->id] = URL::temporarySignedRoute(
                'bookings.show',
                $expires,
                ['booking' => $b->id, 'portal_user' => $user->id],
                false
            );
        }

        return view('account.bookings', [
            'bookings' => $bookings,
            'bookingDetailUrls' => $bookingDetailUrls,
            'guestPortalUser' => $user,
            'guestPortalSubtitle' => true,
        ]);
    }

}
