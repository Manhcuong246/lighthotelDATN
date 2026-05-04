<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Payment;
use App\Services\UnpaidBookingObliterateService;
use Illuminate\Console\Command;

class PurgePendingBookingsCommand extends Command
{
    protected $signature = 'bookings:purge-pending
                            {--dry-run : Chỉ hiển thị số lượng, không ghi DB}
                            {--force : Bỏ qua xác nhận tương tác}';

    protected $description = 'Xóa hẳn các đơn có tiến trình pending chưa thanh toán; đơn pending nhưng đã ghi nhận thanh toán/cọc được chuyển sang confirmed.';

    public function handle(UnpaidBookingObliterateService $obliterate): int
    {
        $dry = (bool) $this->option('dry-run');

        $pendingIds = Booking::query()
            ->where('status', 'pending')
            ->orderBy('id')
            ->pluck('id');

        if ($pendingIds->isEmpty()) {
            $this->info('Không có đơn pending.');

            return self::SUCCESS;
        }

        $toPromote = [];
        $toDelete = [];

        foreach ($pendingIds as $id) {
            /** @var Booking|null $booking */
            $booking = Booking::query()->find($id);
            if (! $booking) {
                continue;
            }

            if ($this->shouldPromotePendingBooking($booking)) {
                $toPromote[] = $id;
            } else {
                $toDelete[] = $id;
            }
        }

        $this->table(
            ['Nhóm', 'Số lượng'],
            [
                ['Chuyển sang confirmed (đã có TT/cọc)', (string) count($toPromote)],
                ['Xóa hẳn (chưa thanh toán)', (string) count($toDelete)],
            ]
        );

        if ($dry) {
            $this->warn('Dry-run: không thay đổi database.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Thực hiện thao tác trên database?', true)) {
            $this->warn('Đã hủy.');

            return self::FAILURE;
        }

        $promoted = 0;
        foreach ($toPromote as $id) {
            $b = Booking::query()->find($id);
            if ($b) {
                $b->update(['status' => 'confirmed']);
                $promoted++;
            }
        }

        $deleted = 0;
        foreach ($toDelete as $id) {
            $b = Booking::query()->find($id);
            if ($b && $obliterate->obliterateIfUnpaid($b)) {
                $deleted++;
            }
        }

        $this->info("Đã chuyển confirmed: {$promoted}. Đã xóa hẳn: {$deleted}.");

        return self::SUCCESS;
    }

    /**
     * Giữ lại đơn pending khi đã có tiền / ghi nhận thanh toán hoặc cọc — không xóa, chỉ đổi tiến trình sang confirmed.
     */
    private function shouldPromotePendingBooking(Booking $booking): bool
    {
        if ($booking->isPaymentRecordedPaid()) {
            return true;
        }

        $ps = (string) ($booking->payment_status ?? '');
        if (in_array($ps, ['paid', 'partial'], true)) {
            return true;
        }

        return Payment::query()
            ->where('booking_id', $booking->id)
            ->whereIn('status', ['paid'])
            ->exists();
    }
}
