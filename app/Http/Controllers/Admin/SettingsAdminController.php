<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HotelInfo;
use App\Models\SiteContent;
use Illuminate\Http\Request;

class SettingsAdminController extends Controller
{
    private const POLICY_TYPES = [
        'policy_privacy' => 'Chính sách bảo mật',
        'policy_terms' => 'Điều khoản sử dụng',
        'policy_booking' => 'Đặt phòng & thanh toán',
        'policy_cancellation' => 'Hủy phòng & hoàn tiền',
        'policy_cookies' => 'Cookie & công nghệ tương tự',
    ];

    public function __construct()
    {
        $this->middleware('admin');
        $this->middleware('only_admin');
    }

    public function index(Request $request)
    {
        $hotelInfo = HotelInfo::first();

        $types = array_keys(self::POLICY_TYPES);
        $existingPolicies = SiteContent::query()->whereIn('type', $types)->get()->keyBy('type');

        $policyBlocks = collect(self::POLICY_TYPES)
            ->map(function (string $label, string $type) use ($existingPolicies) {
                return [
                    'type' => $type,
                    'label' => $label,
                    'record' => $existingPolicies->get($type),
                ];
            })
            ->values();

        $allowedTabs = ['general', 'policies'];
        $tab = $request->query('tab', 'general');
        if (! in_array($tab, $allowedTabs, true)) {
            $tab = 'general';
        }

        return view('admin.settings.index', compact('hotelInfo', 'policyBlocks', 'tab'));
    }

    public function updateGeneral(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:150',
            'rating_avg' => 'nullable|numeric|min:0|max:5',
            'bank_id' => 'nullable|string|max:50',
            'bank_account' => 'nullable|string|max:50',
            'bank_account_name' => 'nullable|string|max:255',
        ]);

        $hotelInfo = HotelInfo::firstOrCreate(['id' => 1]);
        $hotelInfo->update($validated);

        return redirect()
            ->route('admin.settings.index', ['tab' => 'general'])
            ->with('success', 'Đã lưu thông tin khách sạn & cấu hình thanh toán.');
    }

    public function updateSiteContent(Request $request)
    {
        if ($request->has('contents')) {
            $request->validate([
                'contents' => 'required|array',
                'contents.*.type' => 'required|string|max:100',
                'contents.*.content_id' => 'nullable|integer|exists:site_contents,id',
                'contents.*.content' => 'nullable|string',
            ]);

            foreach ($request->contents as $item) {
                if (empty($item['type'])) {
                    continue;
                }

                $content = null;
                if (! empty($item['content_id'])) {
                    $content = SiteContent::find($item['content_id']);
                }

                if ($content) {
                    $content->update(['content' => $item['content'] ?? '']);
                } else {
                    SiteContent::create([
                        'type' => $item['type'],
                        'title' => (self::POLICY_TYPES[$item['type']] ?? $item['type']),
                        'content' => $item['content'] ?? '',
                        'is_active' => true,
                    ]);
                }
            }

            return redirect()
                ->route('admin.settings.index', ['tab' => 'policies'])
                ->with('success', 'Đã cập nhật các chính sách & điều khoản.');
        }

        $type = $request->input('type');
        $contentId = $request->input('content_id');

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'image_url' => 'nullable|url',
        ]);

        if ($contentId) {
            $content = SiteContent::find($contentId);
            if ($content) {
                $content->update($validated);
            }
        } else {
            SiteContent::create(array_merge($validated, ['type' => $type]));
        }

        return redirect()
            ->route('admin.settings.index', ['tab' => 'policies'])
            ->with('success', 'Đã cập nhật nội dung trang web.');
    }

    public static function defaultPolicyBody(string $type): string
    {
        return match ($type) {
            'policy_privacy' => "Chúng tôi thu thập thông tin cần thiết để xử lý đặt phòng (họ tên, email, số điện thoại, chi tiết lưu trú, và khi cần thông tin thanh toán theo kênh bạn chọn) và bảo mật theo quy định hiện hành tại Việt Nam.\n\nDữ liệu được dùng để xác nhận đơn, liên hệ khi cần, xử lý thanh toán/hoàn tiền và cải thiện dịch vụ. Chúng tôi không bán thông tin cá nhân cho bên thứ ba cho mục đích tiếp thị.\n\nHệ thống có thể ghi nhật ký kỹ thuật (địa chỉ IP, loại trình duyệt, thời gian truy cập) nhằm bảo mật và phân tích lỗi — dữ liệu này được lưu giữ có thời hạn và hạn chế quyền truy cập nội bộ.",
            'policy_terms' => "Khi sử dụng website để tìm kiếm và đặt phòng, bạn đồng ý cung cấp thông tin trung thực và tuân thủ quy định của khách sạn trong thời gian lưu trú, bao gồm an ninh, an toàn cháy nổ và trật tự chung.\n\nNội dung, hình ảnh và mô tả trên website thuộc quyền sở hữu hoặc được cấp phép sử dụng. Việc sao chép cho mục đích thương mại cần có sự đồng ý bằng văn bản.\n\nKhách sạn có quyền từ chối phục vụ trong trường hợp vi phạm nội quy, an ninh hoặc gây ảnh hưởng đến khách khác.",
            'policy_booking' => "Giá hiển thị theo từng loại phòng và ngày có thể thay đổi theo thời điểm, số lượng phòng còn trống và chương trình khuyến mãi. Giá cuối cùng được xác nhận tại bước thanh toán trước khi bạn hoàn tất đơn.\n\nĐơn đặt chỉ được coi là xác nhận sau khi hệ thống ghi nhận thanh toán thành công (hoặc theo điều kiện “giữ phòng” nếu được hiển thị rõ) và bạn nhận thông báo/email xác nhận từ khách sạn.",
            'policy_cancellation' => "Điều kiện hủy miễn phí, hủy có phí và mức hoàn tiền phụ thuộc gói giá và thời điểm bạn gửi yêu cầu. Thông tin cụ thể được hiển thị trên trang đặt phòng và trong email xác nhận.\n\nTrường hợp bất khả kháng (thiên tai, sự cố hệ thống ngân hàng…), khách sạn sẽ phối hợp xử lý theo chính sách từng thời kỳ và thông báo qua kênh liên hệ đã đăng ký.",
            'policy_cookies' => "Website có thể sử dụng cookie phiên và cookie chức năng để duy trì phiên đăng nhập (nếu có), ghi nhớ tùy chọn ngôn ngữ và bảo vệ chống lạm dụng (CSRF). Bạn có thể điều chỉnh trình duyệt để từ chối cookie, tuy nhiên một số tính năng có thể không hoạt động đầy đủ.\n\nChúng tôi không dùng cookie để theo dõi hành vi nhạy cảm ngoài phạm vi vận hành website và xử lý đặt phòng.",
            default => '',
        };
    }
}
