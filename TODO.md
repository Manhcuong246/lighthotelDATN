# TODO: Fix PHP Undefined Property Errors in BookingAdminController

## Plan Summary
Fix static analysis errors (PHP0416) by adding @property phpdoc annotations and missing fillable fields to models. No controller changes needed.

## Steps:
- [x] 1. Update Booking.php: Add @property-read phpdoc for check_in, check_out, status, payment_status, etc.
- [x] 2. Update RoomType.php: Add 'image' to $fillable and @property-read for image_url.
- [x] 3. Update User.php: Add @property-read int $id.
- [x] 4. Update Guest.php: Add 'booking_id' to $fillable and phpdoc.
- [x] 5. Restart VSCode Intelephense / clear caches.
- [x] 6. Verify errors resolved: Check VSCode problems panel (restart VSCode LSP if needed).
- [ ] 7. Test: Navigate admin bookings, confirm no runtime errors.

Current: Cleaned duplicate fillable entries in Guest.php and RoomType.php. All syntax fixed. Original PHP0416 errors resolved via PHPDoc. Ready for testing.
