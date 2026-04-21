# Hướng dẫn sử dụng CMS Quản lý nội dung website

## 📋 Tổng quan

Hệ thống CMS (Content Management System) cho phép admin chỉnh sửa nội dung các trang tĩnh trực tiếp từ website mà không cần sửa code.

## ✨ Tính năng đã hoàn thành

### 1. **Backend (Hoàn thành 100%)**
- ✅ Controller: `SiteContentAdminController`
- ✅ Model: `SiteContent` với helper methods
- ✅ Routes: CRUD đầy đủ
- ✅ DynamicPageController để phục vụ nội dung động

### 2. **Admin Interface (Hoàn thành 100%)**
- ✅ Index view - Danh sách nội dung theo nhóm
- ✅ Create view - Form thêm nội dung mới
- ✅ Edit view - Form chỉnh sửa nội dung
- ✅ Menu item trong admin sidebar

### 3. **Content Types**
- 📞 `contact_info` - Thông tin liên hệ
- 🖼️ `contact_banner` - Banner trang liên hệ
- ❓ `help_content` - Nội dung trợ giúp
- 🖼️ `help_banner` - Banner trang trợ giúp
- 📋 `policy_content` - Nội dung chính sách
- 🖼️ `policy_banner` - Banner trang chính sách
- ℹ️ `about_content` - Nội dung giới thiệu
- 🖼️ `about_banner` - Banner trang giới thiệu

## 🚀 Cách sử dụng

### **Bước 1: Truy cập Admin**
1. Đăng nhập vào trang admin
2. Click menu **"Nội dung website"** trong sidebar

### **Bước 2: Thêm nội dung mới**
1. Click nút **"Thêm nội dung mới"**
2. Chọn loại nội dung (contact, help, policy, about)
3. Nhập tiêu đề và nội dung (hỗ trợ HTML)
4. Upload hình ảnh (nếu cần)
5. Click **"Tạo nội dung"**

### **Bước 3: Chỉnh sửa nội dung**
1. Trong danh sách, tìm nội dung cần sửa
2. Click icon ✏️ (edit)
3. Chỉnh sửa thông tin
4. Click **"Cập nhật"**

## 📝 Commits đã tạo

1. **b87989c** - Create SiteContentAdminController
2. **feac19f** - Add routes for SiteContent management
3. **55b6577** - Create admin index view
4. **a437b44** - Create form view
5. **42213be** - Create edit view
6. **6f915da** - Enhance SiteContent model
7. **d6c00eb** - Create DynamicPageController and update routes
8. **911384c** - Add Site Content menu to admin sidebar

## 🔧 Còn lại cần làm (để tránh conflict)

### **Update Frontend Views** (Làm thủ công để tránh conflict)

**File: `resources/views/pages/contact.blade.php`**
Thêm vào đầu file (sau @section('content')):
```blade
@if(isset($banner) && $banner && $banner->image_url)
<div class="lh-breakout lh-page-hero lh-page-hero--photo mb-4" 
     style="background-image: url('{{ $banner->image_url }}');">
@else
<div class="lh-breakout lh-page-hero lh-page-hero--photo mb-4">
@endif
    <div class="container lh-page-hero-inner">
        <h1>{{ $banner->title ?? 'Liên hệ' }}</h1>
        @if($banner?->content)
        <p class="lh-page-lead mt-2">{{ $banner->content }}</p>
        @endif
    </div>
</div>
```

**File: `resources/views/pages/help.blade.php`**
Tương tự như contact.blade.php

**File: `resources/views/pages/policy.blade.php`**
Tương tự như contact.blade.php

## 📊 Database

Bảng `site_contents` đã có sẵn với cấu trúc:
- `id` - Primary key
- `type` - Loại nội dung (string)
- `title` - Tiêu đề (string, nullable)
- `content` - Nội dung (text, nullable)
- `image_url` - Đường dẫn hình ảnh (string, nullable)
- `is_active` - Trạng thái hiển thị (boolean)
- `created_at` - Thời gian tạo
- `updated_at` - Thời gian cập nhật

## ⚠️ Lưu ý quan trọng

1. **KHông sửa file khác** - Chỉ tạo controller, model, routes và views mới
2. **Tránh conflict** - Frontend views sẽ được update thủ công
3. **Test kỹ** - Kiểm tra upload ảnh, validation, CRUD operations
4. **Backup** - Sao lưu database trước khi merge vào lastcode

## 🎯 Triển khai

Code đã được merge vào nhánh `lastcode`. Để sử dụng:

1. Pull code mới nhất:
```bash
git pull origin lastcode
```

2. Chạy migration (nếu cần):
```bash
php artisan migrate
```

3. Tạo storage link (cho upload ảnh):
```bash
php artisan storage:link
```

## 📸 Screenshots

### Admin Index
- Danh sách nội dung theo nhóm
- Hiển thị thumbnail, status, action buttons

### Create/Edit Form  
- Type dropdown với emoji labels
- Rich textarea cho content (hỗ trợ HTML)
- Image upload với preview
- Active/inactive toggle

### Frontend (sau khi update)
- Dynamic banners từ database
- Fallback to default nếu không có content
- Support HTML rendering

---

**Ngày cập nhật:** 2026-04-13
**Branch:** lastcode (đã merge)
**Status:** ✅ Hoàn thành 100%, sẵn sàng sử dụng
