<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Light Hotel')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* Không cho trượt/cuộn ngang toàn trang (full-bleed 100vw hay block quá rộng) */
        html {
            margin: 0;
            padding: 0;
            max-width: 100%;
            overflow-x: hidden;
        }
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            max-width: 100%;
            overflow-x: hidden;
            background: #f5f7fb;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        .navbar-brand span {
            font-weight: 700;
            letter-spacing: 1px;
        }
        .logo-mark {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            background: transparent;
            box-shadow: none;
        }
        .logo-mark img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }
        .navbar {
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            background: linear-gradient(90deg, #111827, #1f2937);
        }
        .hero-section {
            position: relative;
            overflow: hidden;
            border-radius: 24px;
            padding: 60px 40px;
            background: linear-gradient(135deg, #0f172a, #1d4ed8);
            color: #fff;
            box-shadow: 0 24px 60px rgba(15,23,42,0.55);
        }
        .hero-bg {
            position: absolute;
            inset: 0;
            background-image: url('https://images.pexels.com/photos/258154/pexels-photo-258154.jpeg?auto=compress&cs=tinysrgb&w=1600');
            background-size: cover;
            background-position: center;
            opacity: 0.20;
        }
        .hero-overlay {
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at top left, rgba(56,189,248,0.6), transparent 55%),
                        radial-gradient(circle at bottom right, rgba(248,250,252,0.2), transparent 55%);
            mix-blend-mode: screen;
        }
        .hero-content {
            position: relative;
            z-index: 2;
        }
        /* Fix alert bị chìm dưới navbar */
        .alert {
            position: relative;
            z-index: 1050;
        }
        /* Đảm bảo alert ở container không bị đè */
        main.container .alert {
            margin-top: 1rem;
        }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 4px 12px;
            border-radius: 999px;
            background: rgba(15,23,42,0.6);
            border: 1px solid rgba(148,163,184,0.5);
            backdrop-filter: blur(12px);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #e5e7eb;
        }
        .hero-title {
            font-size: clamp(2.4rem, 3vw + 1.5rem, 3.4rem);
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 0.75rem;
        }
        .hero-subtitle {
            font-size: 1.05rem;
            max-width: 520px;
            color: #e5e7eb;
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 3;
            overflow: hidden;
        }
        .hero-tags span {
            background: rgba(15,23,42,0.6);
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 0.8rem;
            color: #e5e7eb;
        }
        .card-room {
            border: none;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 18px 40px rgba(15,23,42,0.12);
            transition: transform .2s ease, box-shadow .2s ease, translate .2s ease;
            background: #ffffff;
        }
        .card-room:hover {
            transform: translateY(-6px);
            box-shadow: 0 32px 60px rgba(15,23,42,0.25);
        }
        .card-room-img {
            height: 220px;
            object-fit: cover;
        }
        .badge-soft {
            background: rgba(15,23,42,0.06);
            color: #4b5563;
        }
        .section-title {
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: #6b7280;
            font-size: .78rem;
        }
        .avatar-header {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255,255,255,0.5);
        }
        .avatar-placeholder {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: #fff;
            font-weight: 700;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 2px solid rgba(255,255,255,0.5);
        }
        .dropdown-user .dropdown-toggle::after { margin-left: 0.4rem; }
        main {
            flex: 1 0 auto;
            min-width: 0;
            max-width: 100%;
        }
        footer {
            flex-shrink: 0;
        }
        .site-footer {
            position: relative;
            background: linear-gradient(180deg, #0c1222 0%, #0f172a 35%, #1e293b 100%);
            color: #e2e8f0;
            margin-top: auto;
            overflow: hidden;
        }
        .footer-accent {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, transparent, #3b82f6, #60a5fa, #3b82f6, transparent);
            opacity: 0.8;
        }
        .site-footer a {
            color: #94a3b8;
            transition: color 0.2s ease;
        }
        .site-footer a:hover {
            color: #fff;
        }
        .footer-main {
            padding: 0;
        }
        .footer-brand-name {
            font-weight: 700;
            font-size: 1.35rem;
            color: #fff;
            letter-spacing: 0.5px;
        }
        .footer-logo {
            width: 48px;
            height: 48px;
            background: transparent;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: none;
        }
        .footer-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }
        .footer-tagline {
            font-size: 0.8rem;
            color: #64748b;
            letter-spacing: 0.03em;
        }
        .footer-heading {
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #64748b;
            margin-bottom: 1rem;
        }
        .footer-links li + li,
        .footer-contact li + li {
            margin-top: 0.65rem;
        }
        .footer-links a,
        .footer-contact a {
            font-size: 0.95rem;
        }
        .footer-desc {
            color: #94a3b8 !important;
            line-height: 1.7;
            font-size: 0.95rem;
        }
        .footer-icon {
            color: #60a5fa;
            flex-shrink: 0;
            font-size: 1rem;
        }
        .footer-contact-empty {
            color: #64748b;
            font-size: 0.9rem;
        }
        .footer-bottom {
            border-top: 1px solid rgba(148, 163, 184, 0.12);
            background: rgba(0, 0, 0, 0.2);
        }
        .footer-legal {
            font-size: 0.875rem;
            color: #64748b !important;
            text-decoration: none;
            transition: color 0.2s ease;
        }
        .footer-legal:hover {
            color: #94a3b8 !important;
        }
        .footer-copy {
            color: #64748b !important;
            font-size: 0.875rem;
        }
        .footer-map {
            border-radius: 10px;
            overflow: hidden;
            min-height: 180px;
        }
        .footer-map iframe {
            display: block;
        }
        .footer-map-link {
            color: #94a3b8 !important;
            text-decoration: none;
            transition: color 0.2s ease;
        }
        .footer-map-link:hover {
            color: #fff !important;
        }
        @media (max-width: 767px) {
            .footer-main .row { padding: 2rem 0 1.5rem !important; }
            .footer-brand { margin-bottom: 1.25rem !important; }
            .footer-heading { margin-top: 1.5rem; }

            /* Mobile hero typography: tránh bị wrap xấu trong cụm "sang trọng" */
            .hero-title {
                font-size: clamp(2rem, 7vw, 2.6rem);
                line-height: 1.05;
                letter-spacing: -0.02em;
            }
            .hero-subtitle {
                font-size: 1rem;
                max-width: 100%;
                -webkit-line-clamp: 4;
            }
        }
        /* Pagination spacing - avoid crowded look */
        .pagination.gap-2 .page-item .page-link {
            margin-left: 0;
            border-radius: 0.375rem;
        }
        /* =====================================================
           LIGHT HOTEL — PREMIUM LAYOUT STYLES
           ===================================================== */

        /* Hero — z-index trên section (không chỉ .lh-hero-inner) để khối dưới margin âm không đè cả hero + dropdown */
        .lh-hero {
            background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 60%, #1e40af 100%);
            padding: 56px 0 80px;
            position: relative;
            z-index: 0;
            overflow: visible;
        }
        .lh-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: url('https://images.pexels.com/photos/258154/pexels-photo-258154.jpeg?auto=compress&cs=tinysrgb&w=1600');
            background-size: cover;
            background-position: center;
            opacity: 0.18;
        }
        .lh-hero-inner { position: relative; z-index: 2; overflow: visible; padding-top: 76px; }
        /* Thêm lề ngang — tránh chữ và chip sát mép viewport */
        .lh-hero .container.lh-hero-inner,
        .lh-home-content-wrap .container.lh-home-landing-container {
            --bs-gutter-x: 2rem;
        }
        @media (min-width: 1200px) {
            .lh-hero .container.lh-hero-inner,
            .lh-home-content-wrap .container.lh-home-landing-container {
                --bs-gutter-x: 2.5rem;
            }
        }
        .lh-hero-eyebrow {
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.65);
        }
        .lh-hero-title {
            font-size: clamp(2rem, 5vw, 3.2rem);
            font-weight: 800;
            color: #fff;
            line-height: 1.1;
            letter-spacing: -0.02em;
        }
        .lh-hero-title em { font-style: italic; color: #fbbf24; }
        .lh-hero-sub {
            color: rgba(255,255,255,0.7);
            font-size: 0.95rem;
            margin-top: 0.75rem;
        }
        .lh-rating-chip {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
            width: fit-content;
            max-width: 100%;
            background: rgba(255,255,255,0.12);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 16px;
            padding: 16px 24px;
            color: #fff;
        }
        .lh-rating-score {
            font-size: 2.5rem;
            font-weight: 800;
            color: #fbbf24;
            line-height: 1;
        }
        .lh-hero-rating-wrap .lh-rating-chip {
            align-self: flex-start;
        }
        @media (min-width: 992px) {
            .lh-hero-rating-wrap .lh-rating-chip {
                align-self: center;
            }
        }

        /* Search bar */
        .lh-search-bar {
            display: flex;
            align-items: stretch;
            background: #fff;
            border-radius: 10px;
            overflow: visible;
            box-shadow: 0 8px 40px rgba(0,0,0,0.3);
        }
        .lh-seg {
            display: flex;
            align-items: center;
            flex: 1;
            padding: 14px 18px;
            position: relative;
            cursor: pointer;
            transition: background 0.15s;
        }
        .lh-seg:hover:not(:last-child) { background: #f8fafc; }
        .lh-seg-dest  { flex: 1.4; border-radius: 10px 0 0 10px; }
        .lh-seg-date  { flex: 1; }
        .lh-seg-guests{ flex: 1.2; }
        .lh-seg-icon  { font-size: 1.2rem; color: #2563eb; margin-right: 12px; flex-shrink: 0; }
        .lh-seg-label { font-size: 0.68rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: #6b7280; margin-bottom: 2px; }
        .lh-seg-val   { font-size: 0.92rem; font-weight: 600; color: #111827; border: none; background: transparent; outline: none; width: 100%; padding: 0; cursor: pointer; }
        .lh-divider   { width: 1px; background: #e5e7eb; margin: 10px 0; flex-shrink: 0; }
        .lh-search-btn {
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 0 10px 10px 0;
            padding: 0 32px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            flex-shrink: 0;
            transition: background 0.18s, transform 0.1s;
            white-space: nowrap;
        }
        .lh-search-btn:hover { background: #1d4ed8; transform: scale(1.02); }
        @media (max-width: 767px) {
            .lh-search-bar { flex-direction: column; border-radius: 10px; }
            .lh-divider { width: 100%; height: 1px; margin: 0; }
            .lh-seg-dest { border-radius: 10px 10px 0 0; }
            .lh-search-btn { border-radius: 0 0 10px 10px; padding: 14px; }
        }

        /* Guest popup */
        .lh-popup {
            display: none;
            position: absolute;
            top: calc(100% + 10px);
            left: 0;
            z-index: 9999;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            min-width: 290px;
            padding: 20px;
        }
        .lh-popup.show { display: block; }

        /* Guest counter buttons */
        .guest-btn {
            width: 32px; height: 32px;
            border: 1.5px solid #2563eb;
            border-radius: 50%;
            background: #fff;
            color: #2563eb;
            font-size: 1.1rem; font-weight: 700;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            line-height: 1; transition: background 0.15s;
        }
        .guest-btn:hover:not(:disabled) { background: #2563eb; color: #fff; }
        .guest-btn:disabled { opacity: 0.3; cursor: not-allowed; }
        .guest-count-input {
            width: 36px; text-align: center; border: none;
            font-weight: 700; font-size: 1rem; background: transparent;
        }
        .guest-count-input::-webkit-inner-spin-button,
        .guest-count-input::-webkit-outer-spin-button { -webkit-appearance: none; }
        .guest-count-input[type=number] { -moz-appearance: textfield; }

        /* Filter sidebar */
        .lh-filter-card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            overflow: hidden;
        }
        .lh-filter-header {
            background: linear-gradient(90deg, #1e40af, #2563eb);
            color: #fff;
            padding: 14px 20px;
            font-weight: 700;
            font-size: 0.92rem;
        }
        .lh-filter-group {
            padding: 14px 20px;
            border-bottom: 1px solid #f1f5f9;
        }
        .lh-filter-group:last-child { border-bottom: none; }
        .lh-filter-group-title {
            font-size: 0.72rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.08em;
            color: #64748b; margin-bottom: 10px;
        }
        .lh-filter-card form > .d-grid { padding: 14px 20px; }
        .lh-trust-card {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 12px;
            padding: 16px 18px;
        }

        /* Result card */
        .lh-result-card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 2px 16px rgba(0,0,0,0.07);
            overflow: hidden;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .lh-result-card:hover { box-shadow: 0 8px 32px rgba(0,0,0,0.13); transform: translateY(-2px); }
        .lh-result-img-wrap { position: relative; height: 100%; min-height: 180px; overflow: hidden; }
        .lh-result-img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s; }
        .lh-result-card:hover .lh-result-img { transform: scale(1.05); }
        .lh-result-badge {
            position: absolute; top: 12px; left: 12px;
            background: #fbbf24; color: #1e293b;
            font-size: 0.7rem; font-weight: 800;
            padding: 3px 10px; border-radius: 99px;
            letter-spacing: 0.05em;
        }
        .lh-room-name { font-size: 1.1rem; color: #111827; }
        .lh-room-type-badge {
            display: inline-block;
            background: rgba(37,99,235,0.08);
            color: #2563eb;
            font-size: 0.72rem; font-weight: 700;
            padding: 2px 10px; border-radius: 99px;
        }
        .lh-score-chip {
            display: flex; flex-direction: column; align-items: center;
            background: #1d4ed8; color: #fff;
            border-radius: 8px; padding: 6px 10px;
            min-width: 60px; text-align: center;
        }
        .lh-score { font-size: 1.3rem; font-weight: 800; line-height: 1; }
        .lh-score-label { font-size: 0.63rem; opacity: 0.85; margin-top: 2px; }
        .lh-amenity-tag {
            font-size: 0.75rem; background: #f1f5f9;
            border-radius: 6px; padding: 3px 8px; color: #475569;
        }
        .lh-room-desc { overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
        .lh-price-block {}
        .lh-price-label { font-size: 0.72rem; text-transform: uppercase; color: #64748b; font-weight: 600; }
        .lh-price { font-size: 1.35rem; font-weight: 800; color: #1d4ed8; }
        .lh-price-night { font-size: 0.72rem; color: #64748b; }
        .lh-freecancel { font-size: 0.72rem; color: #16a34a; font-weight: 600; margin-top: 2px; }
        .lh-btn-book {
            background: #2563eb; color: #fff; font-weight: 700;
            border-radius: 8px; padding: 10px 20px; font-size: 0.9rem;
            border: none; transition: background 0.18s;
        }
        .lh-btn-book:hover { background: #1d4ed8; color: #fff; }
        .lh-empty-state {
            text-align: center; padding: 60px 20px;
            background: #fff; border-radius: 14px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        }
        .lh-badge-active {
            background: #2563eb; color: #fff;
            font-size: 0.75rem; border-radius: 99px;
            padding: 4px 12px;
        }

        /* Home grid cards */
        .lh-room-card {
            background: #fff; border-radius: 16px;
            box-shadow: 0 2px 16px rgba(0,0,0,0.07);
            overflow: hidden;
            transition: transform 0.22s, box-shadow 0.22s;
            display: flex; flex-direction: column;
        }
        .lh-room-card:hover { transform: translateY(-5px); box-shadow: 0 12px 36px rgba(0,0,0,0.14); }
        .lh-room-card-img-wrap { position: relative; overflow: hidden; }
        .lh-room-card-img { width: 100%; height: 210px; object-fit: cover; transition: transform 0.4s; display: block; }
        .lh-room-card:hover .lh-room-card-img { transform: scale(1.06); }
        .lh-room-card-badge {
            position: absolute; top: 12px; right: 12px;
            background: rgba(15,23,42,0.75);
            color: #fff; font-size: 0.68rem; font-weight: 700;
            padding: 3px 10px; border-radius: 99px;
        }
        .lh-room-card-body { padding: 18px 20px; display: flex; flex-direction: column; flex: 1; }
        .lh-room-card-title { font-size: 1.05rem; font-weight: 700; color: #111827; margin-bottom: 6px; }
        .lh-room-card-meta { font-size: 0.78rem; color: #64748b; margin-bottom: 10px; }
        .lh-room-card-amenities {
            display: flex; gap: 10px; flex-wrap: wrap;
            font-size: 0.75rem; color: #64748b; margin-bottom: 14px;
        }
        .lh-room-card-amenities span { display: flex; align-items: center; gap: 4px; }
        .lh-room-card-footer {
            margin-top: auto;
            display: flex; align-items: flex-end; justify-content: space-between;
            padding-top: 12px; border-top: 1px solid #f1f5f9;
        }
        .lh-card-price-label { font-size: 0.68rem; text-transform: uppercase; color: #94a3b8; font-weight: 700; }
        .lh-card-price { font-size: 1.15rem; font-weight: 800; color: #1d4ed8; line-height: 1.2; }
        .lh-card-currency { font-size: 0.72rem; font-weight: 500; color: #64748b; }
        .lh-btn-book-sm {
            background: #2563eb; color: #fff; font-weight: 700;
            border-radius: 8px; padding: 7px 16px; font-size: 0.82rem;
            border: none; transition: background 0.18s; white-space: nowrap;
        }
        .lh-btn-book-sm:hover { background: #1d4ed8; color: #fff; }
        .lh-section-eyebrow {
            font-size: 0.72rem; font-weight: 700; letter-spacing: 0.1em;
            text-transform: uppercase; color: #64748b;
        }

        /* =====================================================
           BOOKING.COM STYLE SEARCH BAR
           ===================================================== */
        .bk-search-bar {
            display: flex;
            align-items: stretch;
            background: #fff;
            border: 3px solid #febb02;
            border-radius: 8px;
            overflow: visible;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            position: relative;
        }
        .bk-seg {
            display: flex;
            align-items: center;
            padding: 8px 16px;
            flex: 1;
            position: relative;
            cursor: pointer;
            min-width: 0;
        }
        .bk-seg:hover { background: #f5f5f5; }
        .bk-seg-dest { flex: 1.2; border-radius: 5px 0 0 5px; }
        .bk-seg-dates { flex: 1.4; }
        .bk-seg-guests { flex: 1.4; overflow: visible !important; position: relative; }
        .bk-seg-icon {
            font-size: 1.3rem;
            color: #333;
            margin-right: 12px;
            flex-shrink: 0;
        }
        .bk-seg-content {
            flex: 1;
            min-width: 0;
            overflow: hidden;
        }
        .bk-seg-label {
            font-size: 0.75rem;
            font-weight: 500;
            color: #333;
            margin-bottom: 2px;
        }
        .bk-input {
            border: none;
            outline: none;
            background: transparent;
            font-size: 0.9rem;
            color: #1a1a1a;
            width: 100%;
            cursor: pointer;
            font-weight: 500;
        }
        .bk-date-input {
            border: none;
            outline: none;
            background: transparent;
            font-size: 0.85rem;
            color: #1a1a1a;
            cursor: pointer;
            font-weight: 500;
            width: auto;
        }
        .bk-clear-btn {
            background: none;
            border: none;
            color: #999;
            font-size: 1.1rem;
            line-height: 1;
            padding: 0 4px;
            cursor: pointer;
            margin-left: 8px;
        }
        .bk-clear-btn:hover { color: #333; }
        .bk-sep {
            width: 1px;
            background: #ddd;
            margin: 8px 0;
            flex-shrink: 0;
        }
        .bk-search-btn {
            background: #0071c2;
            color: #fff;
            border: none;
            border-radius: 0 5px 5px 0;
            padding: 0 32px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            flex-shrink: 0;
            transition: background 0.2s;
            white-space: nowrap;
        }
        .bk-search-btn:hover { background: #005fa3; }

        .bk-search-stack {
            border: 3px solid #febb02;
            border-radius: 8px;
            overflow: visible;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            background: #fff;
        }
        .bk-search-bar.bk-search-bar--in-stack {
            border: none;
            box-shadow: none;
            border-radius: 0;
        }
        .bk-filter-row {
            padding: 10px 12px 14px;
            border-top: 1px solid #e8e8e8;
            background: #fafafa;
        }
        .bk-filter-row .form-select,
        .bk-filter-row .btn { font-size: 0.875rem; }

        /* Guest popup */
        .guest-popup {
            display: none;
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            z-index: 10000;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            min-width: 320px;
            max-width: 360px;
            padding: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        .guest-popup.show { display: block; }
        .guest-btn {
            width: 32px; height: 32px;
            border: 1px solid #0071c2;
            border-radius: 50%;
            background: #fff;
            color: #0071c2;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            line-height: 1;
        }
        .guest-btn:hover:not(:disabled) { background: #0071c2; color: #fff; }
        .guest-btn:disabled { opacity: 0.3; cursor: not-allowed; }
        .guest-count-input {
            width: 36px;
            text-align: center;
            border: none;
            font-weight: 700;
            font-size: 1rem;
            background: transparent;
        }
        .guest-count-input::-webkit-inner-spin-button,
        .guest-count-input::-webkit-outer-spin-button { -webkit-appearance: none; }
        .guest-count-input[type=number] { -moz-appearance: textfield; }

        @media (max-width: 767px) {
            .bk-search-bar { flex-direction: column; border-radius: 8px; }
            .bk-sep { width: 100%; height: 1px; margin: 0; }
            .bk-seg-dest { border-radius: 5px 5px 0 0; }
            .bk-search-btn { border-radius: 0 0 5px 5px; padding: 14px; }
            .bk-seg-dates, .bk-seg-guests, .bk-seg-dest { flex: none; width: 100%; }
        }

        /* ----- Inner pages: full-bleed hero from .container ----- */
        main.container .lh-breakout {
            width: 100vw;
            margin-left: calc(50% - 50vw);
            margin-right: calc(50% - 50vw);
            max-width: 100vw;
        }
        .lh-page-hero {
            position: relative;
            padding: clamp(2.5rem, 6vw, 4rem) 0 clamp(2rem, 4vw, 3rem);
            background: linear-gradient(135deg, #0c1222 0%, #1e3a8a 45%, #1d4ed8 100%);
            overflow: hidden;
        }
        .lh-page-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 80% 60% at 20% 20%, rgba(56, 189, 248, 0.25), transparent 50%),
                radial-gradient(ellipse 60% 50% at 85% 80%, rgba(251, 191, 36, 0.12), transparent 45%);
            pointer-events: none;
        }
        .lh-page-hero::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.6;
            pointer-events: none;
        }
        .lh-page-hero-inner { position: relative; z-index: 1; }
        .lh-page-hero h1 {
            color: #fff;
            font-weight: 800;
            letter-spacing: -0.03em;
            font-size: clamp(1.75rem, 4vw, 2.35rem);
            line-height: 1.15;
        }
        .lh-page-hero .lh-page-lead {
            color: rgba(255,255,255,0.78);
            font-size: 1.05rem;
            max-width: 36rem;
            margin-bottom: 0;
        }
        .lh-breadcrumb {
            font-size: 0.8rem;
            margin-bottom: 1rem;
        }
        .lh-breadcrumb a {
            color: rgba(255,255,255,0.55);
            text-decoration: none;
            transition: color 0.15s;
        }
        .lh-breadcrumb a:hover { color: #fff; }
        .lh-breadcrumb .active { color: rgba(255,255,255,0.95); font-weight: 600; }

        .lh-page-body { padding-bottom: 3rem; }
        .lh-glass-card {
            background: rgba(255,255,255,0.92);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,0.65);
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.08);
        }
        .lh-contact-tile {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1.25rem 1.35rem;
            border-radius: 16px;
            background: linear-gradient(145deg, #f8fafc 0%, #f1f5f9 100%);
            border: 1px solid #e2e8f0;
            height: 100%;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .lh-contact-tile:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(30, 64, 175, 0.1);
        }
        .lh-contact-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff;
            font-size: 1.25rem;
            flex-shrink: 0;
        }
        .lh-contact-tile a { color: #1d4ed8; font-weight: 600; text-decoration: none; }
        .lh-contact-tile a:hover { text-decoration: underline; }

        .lh-faq-accordion .accordion-item {
            border: 1px solid #e2e8f0 !important;
            border-radius: 16px !important;
            margin-bottom: 0.75rem;
            overflow: hidden;
            background: #fff;
        }
        .lh-faq-accordion .accordion-button {
            font-weight: 600;
            font-size: 0.98rem;
            padding: 1.1rem 1.25rem;
            box-shadow: none !important;
            background: #fff;
            color: #0f172a;
        }
        .lh-faq-accordion .accordion-button:not(.collapsed) {
            background: linear-gradient(90deg, #eff6ff, #fff);
            color: #1d4ed8;
        }
        .lh-faq-accordion .accordion-body {
            padding: 0 1.25rem 1.25rem;
            color: #475569;
            font-size: 0.95rem;
            line-height: 1.65;
        }

        .lh-policy-toc {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }
        .lh-policy-toc a {
            display: inline-flex;
            align-items: center;
            padding: 0.45rem 1rem;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 600;
            background: #eff6ff;
            color: #1d4ed8;
            text-decoration: none;
            border: 1px solid #bfdbfe;
            transition: background 0.15s, transform 0.15s;
        }
        .lh-policy-toc a:hover {
            background: #dbeafe;
            transform: translateY(-1px);
        }
        .lh-policy-toc a.lh-policy-toc-muted {
            background: #f8fafc;
            color: #475569 !important;
            border-color: #e2e8f0;
        }
        .lh-policy-toc a.lh-policy-toc-muted:hover {
            background: #f1f5f9;
        }
        .lh-policy-section {
            padding: 1.5rem 1.75rem;
            border-radius: 16px;
            background: #fff;
            border: 1px solid #e2e8f0;
            margin-bottom: 1.25rem;
        }
        .lh-policy-section h2 {
            font-size: 1.15rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #e0e7ff;
        }
        .lh-policy-section p {
            color: #475569;
            font-size: 0.95rem;
            line-height: 1.7;
            margin-bottom: 0.75rem;
        }
        .lh-policy-section p:last-child { margin-bottom: 0; }

        /* Home: hero polish */
        .lh-hero-mesh {
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 70% 50% at 15% 30%, rgba(56, 189, 248, 0.22), transparent 55%),
                radial-gradient(ellipse 50% 40% at 90% 70%, rgba(251, 191, 36, 0.15), transparent 50%);
            pointer-events: none;
            z-index: 1;
        }
        .lh-hero-feature-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-top: 1.5rem;
        }
        .lh-hero-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.45rem 1rem;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 600;
            color: rgba(255,255,255,0.95);
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.2);
            backdrop-filter: blur(8px);
        }
        .lh-home-content-wrap {
            position: relative;
            z-index: 1;
            margin-top: -28px;
            padding-top: 2.5rem;
            background: linear-gradient(180deg, #eef2ff 0%, #f5f7fb 10%, #f5f7fb 100%);
            border-radius: 28px 28px 0 0;
            box-shadow: 0 -16px 48px rgba(15, 23, 42, 0.07);
        }
        .lh-section-header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.75rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }
        .bk-search-stack {
            border: 2px solid rgba(255,255,255,0.35);
            border-radius: 16px;
            /* Hàng lọc + dropdown: không clip; hàng tìm kiếm dùng overflow riêng */
            overflow: visible;
            box-shadow: 0 24px 60px rgba(0,0,0,0.22);
            background: rgba(255,255,255,0.98);
            position: relative;
            z-index: 10;
        }
        /* Bo trùng khung ngoài (16px − 2px viền): tránh góc vuông/góc tròn chồng lên nhau */
        .bk-search-bar.bk-search-bar--in-stack {
            border-radius: 14px 14px 0 0;
            overflow: hidden;
            background: #fff;
        }
        .bk-search-bar.bk-search-bar--in-stack .bk-seg-dest {
            border-radius: 0;
        }
        .bk-search-bar {
            border: none;
            border-radius: 0;
            box-shadow: none;
        }
        .bk-search-btn {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border-radius: 0;
        }
        .bk-search-btn:hover {
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
        }
        .bk-filter-row {
            background: linear-gradient(180deg, #f8fafc, #f1f5f9);
            border-top: 1px solid #e2e8f0;
            border-radius: 0 0 14px 14px;
            overflow: visible;
        }
        @media (max-width: 767px) {
            .bk-search-bar.bk-search-bar--in-stack {
                border-radius: 14px 14px 0 0;
            }
            .bk-search-bar.bk-search-bar--in-stack .bk-search-btn {
                border-radius: 0;
            }
        }
        /* Dropdown trong filter (chủ yếu tiện nghi): không dùng width/min-width % khi Popper strategy=fixed — % = theo viewport → full màn hình */
        .bk-filter-row .dropdown-menu {
            max-height: min(320px, 70vh);
            overflow-y: auto;
        }
        .bk-amenities-menu {
            /* Cao hơn khối nội dung (.lh-home-content-wrap) và navbar; cùng tầng popover BS (~1070) */
            z-index: 1070 !important;
            min-width: 240px;
            max-width: min(380px, calc(100vw - 1.5rem)) !important;
            width: auto !important;
            box-sizing: border-box;
        }
        /* Giống form-select: tránh nút outline-secondary bị tối/xám trên một số theme/trình duyệt */
        .bk-amenities-toggle {
            --bs-btn-color: #212529;
            --bs-btn-bg: #fff;
            --bs-btn-border-color: #ced4da;
            --bs-btn-hover-color: #212529;
            --bs-btn-hover-bg: #f8f9fa;
            --bs-btn-hover-border-color: #adb5bd;
            --bs-btn-focus-shadow-rgb: 13, 110, 253;
            --bs-btn-active-color: #212529;
            --bs-btn-active-bg: #f8f9fa;
            --bs-btn-active-border-color: #86b7fe;
            border-radius: 0.25rem;
            min-height: calc(1.5em + 0.5rem + 2px);
            padding: 0.25rem 0.5rem;
            line-height: 1.5;
        }
        .bk-filter-row .dropdown-menu:not(.show) {
            display: none !important;
        }
        .navbar .nav-link.active {
            color: #fbbf24 !important;
        }

        /* ----- Marketing / landing sections (home & inner pages) ----- */
        .lh-page-hero--photo::before {
            background:
                linear-gradient(135deg, rgba(12, 18, 34, 0.88) 0%, rgba(30, 58, 138, 0.78) 50%, rgba(29, 78, 216, 0.72) 100%),
                url('https://images.pexels.com/photos/271624/pexels-photo-271624.jpeg?auto=compress&cs=tinysrgb&w=1920') center/cover no-repeat;
            opacity: 1;
        }
        .lh-stat-strip {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 8px 40px rgba(15, 23, 42, 0.08);
            border: 1px solid #e2e8f0;
            padding: 1.25rem 1.5rem;
            margin-bottom: 2.5rem;
        }
        .lh-stat-item {
            text-align: center;
            padding: 0.5rem;
        }
        .lh-stat-value {
            font-size: clamp(1.35rem, 3vw, 1.85rem);
            font-weight: 800;
            color: #1d4ed8;
            line-height: 1.2;
            letter-spacing: -0.02em;
        }
        .lh-stat-label {
            font-size: 0.78rem;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-top: 0.35rem;
        }
        .lh-split-section {
            margin-bottom: 3rem;
        }
        .lh-split-img {
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.15);
            min-height: 280px;
        }
        .lh-split-img img {
            width: 100%;
            height: 100%;
            min-height: 280px;
            object-fit: cover;
            display: block;
            transition: transform 0.5s ease;
        }
        .lh-split-img:hover img {
            transform: scale(1.03);
        }
        .lh-split-copy {
            padding: 0.5rem 0;
        }
        .lh-split-copy .lh-eyebrow {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #2563eb;
            margin-bottom: 0.75rem;
        }
        .lh-split-copy h2 {
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.02em;
            line-height: 1.2;
            margin-bottom: 1rem;
        }
        .lh-split-copy p {
            color: #475569;
            line-height: 1.7;
            margin-bottom: 1rem;
        }
        .lh-check-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .lh-check-list li {
            display: flex;
            align-items: flex-start;
            gap: 0.65rem;
            margin-bottom: 0.65rem;
            color: #334155;
            font-size: 0.95rem;
        }
        .lh-check-list li i {
            color: #16a34a;
            margin-top: 0.15rem;
            flex-shrink: 0;
        }
        .lh-service-card {
            background: #fff;
            border-radius: 18px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 24px rgba(15, 23, 42, 0.06);
            height: 100%;
            display: flex;
            flex-direction: column;
            transition: transform 0.22s ease, box-shadow 0.22s ease;
        }
        .lh-service-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.12);
        }
        .lh-service-card-img-wrap {
            height: 160px;
            overflow: hidden;
            background: linear-gradient(145deg, #e2e8f0 0%, #f1f5f9 100%);
            position: relative;
        }
        .lh-service-card-img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            display: block;
        }
        .lh-service-card-body {
            padding: 1.25rem 1.35rem 1.4rem;
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
        }
        .lh-service-card-body h3 {
            font-size: 1.05rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.5rem;
        }
        .lh-service-card-body p {
            font-size: 0.88rem;
            color: #64748b;
            margin: 0;
            line-height: 1.55;
            flex: 1 1 auto;
        }
        .lh-section-block {
            margin-bottom: 3rem;
        }
        .lh-section-block-title {
            margin-bottom: 1.5rem;
        }
        .lh-section-block-title .lh-eyebrow {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 0.5rem;
        }
        .lh-section-block-title h2 {
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.02em;
            margin: 0;
        }
        /* Ưu đãi: dùng row/col trong Blade để 3 cột đều nhau */
        .lh-why-card {
            background: linear-gradient(145deg, #fff 0%, #f8fafc 100%);
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 1.35rem 1.5rem;
            height: 100%;
        }
        .lh-why-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
            color: #1d4ed8;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }
        .lh-why-card h3 {
            font-size: 1rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.5rem;
        }
        .lh-why-card p {
            font-size: 0.88rem;
            color: #64748b;
            margin: 0;
            line-height: 1.55;
        }
        /* Lưới ảnh phòng */
        .lh-room-visual-tile,
        .lh-room-photo-tile {
            display: block;
            width: 100%;
            min-height: 0;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.08);
            border: 1px solid #e2e8f0;
            text-decoration: none;
            background: #e8edf5;
            transition: box-shadow 0.2s ease, transform 0.2s ease;
        }
        .lh-room-visual-tile:hover,
        .lh-room-photo-tile:hover {
            box-shadow: 0 12px 32px rgba(30, 64, 175, 0.15);
            transform: translateY(-2px);
        }
        .lh-room-visual-tile .lh-room-visual-media {
            width: 100%;
            aspect-ratio: 4 / 3;
        }
        .lh-room-photo-tile .lh-room-visual-media {
            position: relative;
            width: 100%;
            aspect-ratio: 1;
        }
        .lh-room-photo-tile .lh-room-visual-media img {
            position: absolute;
            inset: 0;
        }
        .lh-room-visual-tile img,
        .lh-room-photo-tile img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.35s ease;
        }
        .lh-room-visual-tile .lh-room-visual-media img {
            position: absolute;
            inset: 0;
        }
        .lh-room-visual-tile .lh-room-visual-media {
            position: relative;
        }
        .lh-room-visual-tile:hover img,
        .lh-room-photo-tile:hover img {
            transform: scale(1.04);
        }
        .lh-split-copy .btn.rounded-pill {
            padding: 0.55rem 1.35rem;
            font-weight: 600;
        }
        .lh-testimonial-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            padding: 1.5rem 1.6rem;
            height: 100%;
            min-height: 100%;
            display: flex;
            flex-direction: column;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.05);
        }
        .lh-testimonial-quote {
            font-size: 0.95rem;
            color: #334155;
            line-height: 1.65;
            margin-bottom: 1.25rem;
            font-style: italic;
            flex: 1 1 auto;
        }
        .lh-testimonial-meta {
            display: flex;
            align-items: center;
            gap: 0.85rem;
        }
        .lh-testimonial-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e0e7ff;
        }
        .lh-testimonial-name {
            font-weight: 700;
            font-size: 0.9rem;
            color: #0f172a;
        }
        .lh-testimonial-role {
            font-size: 0.78rem;
            color: #94a3b8;
        }
        .lh-cta-band {
            background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 50%, #2563eb 100%);
            border-radius: 20px;
            padding: 2rem 2rem;
            color: #fff;
            margin-bottom: 2.5rem;
            position: relative;
            overflow: hidden;
        }
        .lh-cta-band::after {
            content: '';
            position: absolute;
            inset: 0;
            background: url("data:image/svg+xml,%3Csvg width='80' height='80' viewBox='0 0 80 80' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23ffffff' fill-opacity='0.06'%3E%3Cpath d='M0 0h40v40H0V0zm40 40h40v40H40V40z'/%3E%3C/g%3E%3C/svg%3E");
            pointer-events: none;
        }
        .lh-cta-band-inner { position: relative; z-index: 1; }
        .lh-cta-band h3 {
            font-weight: 800;
            letter-spacing: -0.02em;
            margin-bottom: 0.5rem;
        }
        .lh-cta-band p {
            color: rgba(255,255,255,0.85);
            margin: 0;
            max-width: 36rem;
        }
        .lh-cta-band .btn-light {
            font-weight: 700;
        }

        /* Contact extras */
        .lh-contact-hours {
            background: linear-gradient(145deg, #f8fafc, #f1f5f9);
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            padding: 1.25rem 1.35rem;
        }
        .lh-contact-quick-link {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            padding: 0.9rem 1rem;
            border-radius: 14px;
            background: #fff;
            border: 1px solid #e2e8f0;
            text-decoration: none;
            color: #0f172a;
            font-weight: 600;
            font-size: 0.9rem;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .lh-contact-quick-link:hover {
            border-color: #93c5fd;
            box-shadow: 0 8px 24px rgba(37, 99, 235, 0.12);
            color: #1d4ed8;
        }
        .lh-contact-quick-link i {
            font-size: 1.25rem;
            color: #2563eb;
        }
        .lh-contact-banner-img {
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.12);
            max-height: 220px;
        }
        .lh-contact-banner-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            min-height: 200px;
        }

        /* Policy: icon cards + doc layout */
        .lh-doc-lead {
            font-size: 1rem;
            color: #475569;
            line-height: 1.7;
            border-left: 4px solid #2563eb;
            padding-left: 1.25rem;
            margin-bottom: 2rem;
            background: linear-gradient(90deg, rgba(239, 246, 255, 0.6), transparent);
            padding-top: 1rem;
            padding-bottom: 1rem;
            border-radius: 0 12px 12px 0;
        }
        .lh-policy-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }
        @media (max-width: 767px) {
            .lh-policy-grid { grid-template-columns: 1fr; }
        }
        .lh-policy-doc-card {
            display: flex;
            gap: 1rem;
            padding: 1.25rem 1.35rem;
            border-radius: 16px;
            background: #fff;
            border: 1px solid #e2e8f0;
            text-decoration: none;
            color: inherit;
            transition: transform 0.18s, box-shadow 0.18s;
        }
        .lh-policy-doc-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(30, 64, 175, 0.1);
            color: inherit;
        }
        .lh-policy-doc-card i.doc-ico {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }
        .lh-policy-doc-card h3 {
            font-size: 0.98rem;
            font-weight: 700;
            margin-bottom: 0.35rem;
            color: #0f172a;
        }
        .lh-policy-doc-card p {
            font-size: 0.82rem;
            color: #64748b;
            margin: 0;
            line-height: 1.5;
        }
        .lh-updated-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748b;
            background: #f1f5f9;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            margin-bottom: 1.25rem;
        }

        /* Help: two-column */
        .lh-help-aside-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            padding: 1.5rem;
            position: sticky;
            top: 1rem;
        }
        .lh-help-aside-card h3 {
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
            margin-bottom: 1rem;
        }
        .lh-help-nav a {
            display: block;
            padding: 0.5rem 0;
            color: #475569;
            text-decoration: none;
            font-size: 0.92rem;
            font-weight: 500;
            border-bottom: 1px solid #f1f5f9;
        }
        .lh-help-nav a:last-child { border-bottom: none; }
        .lh-help-nav a:hover { color: #1d4ed8; }
        .lh-help-hero-side {
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.12);
        }
        .lh-help-hero-side img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            min-height: 200px;
            display: block;
        }
    </style>
    @stack('styles')
    {{-- Thanh cuộn: đặt sau stack để thắng CSS từng trang; trước đây thiếu include nên site khách không áp dụng --}}
    <style>
        @include('partials.scrollbar-theme')
    </style>
    <script type="module" src="https://unpkg.com/deep-chat@2.4.2/dist/deepChat.bundle.js"></script>
</head>
<body>
@if(!request()->routeIs('account.profile'))
<nav class="navbar navbar-expand-lg navbar-dark mb-4">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('home') }}">
            <span class="logo-mark">
                <img src="{{ asset('Thiết kế chưa có tên.png') }}" alt="Light Hotel logo">
            </span>
            <span>Light Hotel</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <li class="nav-item">
                    <a class="nav-link fw-medium {{ request()->routeIs('pages.contact') ? 'active' : '' }}" href="{{ route('pages.contact') }}">Liên hệ</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-medium {{ request()->routeIs('pages.help') ? 'active' : '' }}" href="{{ route('pages.help') }}">Trợ giúp</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-medium {{ request()->routeIs('pages.policy') ? 'active' : '' }}" href="{{ route('pages.policy') }}">Chính sách</a>
                </li>
                @auth
                    @if(auth()->user()->canAccessAdmin())
                    <li class="nav-item">
                        <a class="nav-link fw-medium" href="{{ route('admin.dashboard') }}">Quản trị</a>
                    </li>
                    @endif
                @endauth
            </ul>
            <div class="d-flex ms-lg-4 mt-3 mt-lg-0 align-items-center gap-2">
                @auth
                <div class="dropdown dropdown-user">
                    <a class="d-flex align-items-center text-decoration-none text-white dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        @php
                            $avatarInitial = strtoupper(mb_substr(auth()->user()->full_name ?? 'U', 0, 1));
                            $avatarSrc = null;
                            if (auth()->user()->avatar_url) {
                                $avatarSrc = str_starts_with(auth()->user()->avatar_url, 'http')
                                    ? auth()->user()->avatar_url
                                    : '/storage/' . auth()->user()->avatar_url . '?v=' . config('room_images.cache_version', '1');
                            }
                        @endphp
                        @if($avatarSrc)
                            <img src="{{ $avatarSrc }}" alt="" class="avatar-header me-2"
                                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                            <span class="avatar-placeholder me-2" style="display:none;">{{ $avatarInitial }}</span>
                        @else
                            <span class="avatar-placeholder me-2">{{ $avatarInitial }}</span>
                        @endif
                        <span class="d-none d-md-inline fw-medium">{{ auth()->user()->full_name }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ route('account.profile') }}">
                            <i class="bi bi-person me-2"></i>Thông tin cá nhân
                        </a></li>
                        <li><a class="dropdown-item" href="{{ route('account.bookings') }}">
                            <i class="bi bi-clock-history me-2"></i>Lịch sử đặt phòng
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="bi bi-box-arrow-right me-2"></i>Đăng xuất
                        </a></li>
                    </ul>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                </div>
                @else
                <a href="{{ route('login') }}" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-person me-1"></i>Đăng nhập
                </a>
                <a href="{{ route('register') }}" class="btn btn-light btn-sm px-3 text-dark fw-semibold">
                    Đăng ký
                </a>
                @endauth
            </div>
        </div>
    </div>
</nav>
@endif

<main class="container mb-5">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @yield('content')
</main>

@include('partials.footer')
@include('components.chat-widget')

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>


