<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * SiteContentAdminController
 * 
 * Allows admin to manage static page content directly from the website.
 * Manages content for: Contact, Help, Policy pages and banners.
 */
class SiteContentAdminController extends Controller
{
    /**
     * Initialize with admin middleware.
     */
    public function __construct()
    {
        $this->middleware('admin');
        $this->middleware('only_admin');
    }

    /**
     * Display list of all site contents.
     */
    public function index()
    {
        $contents = SiteContent::orderBy('type')->orderBy('created_at', 'desc')->get();
        
        // Group by type for better display
        $groupedContents = $contents->groupBy('type');
        
        return view('admin.site-contents.index', compact('groupedContents'));
    }

    /**
     * Show form to create new content.
     */
    public function create()
    {
        $types = [
            'contact_info' => 'Thông tin liên hệ',
            'contact_banner' => 'Banner trang liên hệ',
            'help_content' => 'Nội dung trợ giúp',
            'help_banner' => 'Banner trang trợ giúp',
            'policy_content' => 'Nội dung chính sách',
            'policy_banner' => 'Banner trang chính sách',
            'about_content' => 'Nội dung giới thiệu',
            'about_banner' => 'Banner trang giới thiệu',
        ];
        
        return view('admin.site-contents.create', compact('types'));
    }

    /**
     * Store new content.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|max:50',
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'image_url' => 'nullable|image|max:2048',
            'is_active' => 'boolean',
        ]);

        // Handle image upload
        if ($request->hasFile('image_url')) {
            $path = $request->file('image_url')->store('site-contents', 'public');
            $validated['image_url'] = '/storage/' . $path;
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        try {
            SiteContent::create($validated);
            
            return redirect()
                ->route('admin.site-contents.index')
                ->with('success', 'Tạo nội dung thành công!');
        } catch (\Exception $e) {
            Log::error('SiteContent creation failed: ' . $e->getMessage());
            
            return back()
                ->withInput()
                ->with('error', 'Có lỗi xảy ra khi tạo nội dung.');
        }
    }

    /**
     * Show form to edit existing content.
     */
    public function edit(SiteContent $siteContent)
    {
        $types = [
            'contact_info' => 'Thông tin liên hệ',
            'contact_banner' => 'Banner trang liên hệ',
            'help_content' => 'Nội dung trợ giúp',
            'help_banner' => 'Banner trang trợ giúp',
            'policy_content' => 'Nội dung chính sách',
            'policy_banner' => 'Banner trang chính sách',
            'about_content' => 'Nội dung giới thiệu',
            'about_banner' => 'Banner trang giới thiệu',
        ];
        
        return view('admin.site-contents.edit', compact('siteContent', 'types'));
    }

    /**
     * Update existing content.
     */
    public function update(Request $request, SiteContent $siteContent)
    {
        $validated = $request->validate([
            'type' => 'required|string|max:50',
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'image_url' => 'nullable|image|max:2048',
            'is_active' => 'boolean',
        ]);

        // Handle image upload
        if ($request->hasFile('image_url')) {
            // Delete old image if exists
            if ($siteContent->image_url) {
                $oldPath = str_replace('/storage/', '', $siteContent->image_url);
                Storage::disk('public')->delete($oldPath);
            }
            
            $path = $request->file('image_url')->store('site-contents', 'public');
            $validated['image_url'] = '/storage/' . $path;
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        try {
            $siteContent->update($validated);
            
            return redirect()
                ->route('admin.site-contents.index')
                ->with('success', 'Cập nhật nội dung thành công!');
        } catch (\Exception $e) {
            Log::error('SiteContent update failed: ' . $e->getMessage());
            
            return back()
                ->withInput()
                ->with('error', 'Có lỗi xảy ra khi cập nhật nội dung.');
        }
    }

    /**
     * Delete content.
     */
    public function destroy(SiteContent $siteContent)
    {
        try {
            // Delete associated image if exists
            if ($siteContent->image_url) {
                $path = str_replace('/storage/', '', $siteContent->image_url);
                Storage::disk('public')->delete($path);
            }
            
            $siteContent->delete();
            
            return redirect()
                ->route('admin.site-contents.index')
                ->with('success', 'Xóa nội dung thành công!');
        } catch (\Exception $e) {
            Log::error('SiteContent deletion failed: ' . $e->getMessage());
            
            return back()
                ->with('error', 'Có lỗi xảy ra khi xóa nội dung.');
        }
    }
}
