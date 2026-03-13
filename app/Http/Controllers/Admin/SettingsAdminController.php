<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HotelInfo;
use App\Models\SiteContent;
use Illuminate\Http\Request;

class SettingsAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
        $this->middleware('admin.only');
    }

    public function index()
    {
        $hotelInfo = HotelInfo::first();

        $banners = SiteContent::where('type', 'banner')->get();
        $abouts = SiteContent::where('type', 'about')->get();
        $policies = SiteContent::where('type', 'policy')->get();
        $footers = SiteContent::where('type', 'footer')->get();
        
        return view('admin.settings.index', compact('hotelInfo', 'banners', 'abouts', 'policies', 'footers'));
    }

    public function updateGeneral(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:150',
        ]);

        $hotelInfo = HotelInfo::firstOrCreate(['id' => 1]);
        $hotelInfo->update($validated);

        return redirect()->back()->with('success', 'Cập nhật thông tin khách sạn thành công.');
    }

    public function updateSiteContent(Request $request)
    {
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
            $content = SiteContent::create(array_merge($validated, ['type' => $type]));
        }

        return redirect()->back()->with('success', 'Cập nhật nội dung trang web thành công.');
    }
}