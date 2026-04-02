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
        html, body {
            margin: 0;
            padding: 0;
        }
        body {
            min-height: 100vh;
            background: #f5f7fb;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            display: flex;
            flex-direction: column;
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

        /* Hero */
        .lh-hero {
            background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 60%, #1e40af 100%);
            padding: 56px 0 80px;
            position: relative;
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
        .lh-hero-inner { position: relative; z-index: 2; overflow: visible; }
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
    </style>
    @stack('styles')
    <script type="module" src="https://unpkg.com/deep-chat@2.4.2/dist/deepChat.bundle.js"></script>
</head>
<body>
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
                        @if(auth()->user()->avatar_url)
                            <img src="{{ str_starts_with(auth()->user()->avatar_url, 'http') ? auth()->user()->avatar_url : asset('storage/' . auth()->user()->avatar_url) }}" alt="" class="avatar-header me-2">
                        @else
                            <span class="avatar-placeholder me-2">{{ strtoupper(mb_substr(auth()->user()->full_name ?? 'U', 0, 1)) }}</span>
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


