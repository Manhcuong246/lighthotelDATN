<?php

namespace App\Http\Controllers;

use App\Models\SiteContent;
use App\Models\HotelInfo;

/**
 * DynamicPageController
 * 
 * Serves static pages (contact, help, policy) with dynamic content from database.
 * Falls back to default views if no content is found.
 */
class DynamicPageController extends Controller
{
    /**
     * Display contact page with dynamic content.
     */
    public function contact()
    {
        $hotelInfo = HotelInfo::first();
        $banner = SiteContent::getByType('contact_banner');
        $content = SiteContent::getByType('contact_info');
        
        return view('pages.contact', compact('hotelInfo', 'banner', 'content'));
    }

    /**
     * Display help page with dynamic content.
     */
    public function help()
    {
        $hotelInfo = HotelInfo::first();
        $banner = SiteContent::getByType('help_banner');
        $content = SiteContent::getByType('help_content');
        
        return view('pages.help', compact('hotelInfo', 'banner', 'content'));
    }

    /**
     * Display policy page with dynamic content.
     */
    public function policy()
    {
        $hotelInfo = HotelInfo::first();
        $banner = SiteContent::getByType('policy_banner');
        $content = SiteContent::getByType('policy_content');
        
        return view('pages.policy', compact('hotelInfo', 'banner', 'content'));
    }
}
