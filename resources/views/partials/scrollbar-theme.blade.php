{{-- Thanh cuộn mảnh (gọn) nhưng track/thumb vẫn nhìn rõ — không trong suốt --}}
:root {
    --lh-scrollbar-size: 7px;
    --lh-scrollbar-radius: 100px;
    /* Nền rãnh: xám đậm vừa, luôn thấy trên nền trắng */
    --lh-scrollbar-track: #e2e8f0;
    --lh-scrollbar-thumb: #94a3b8;
    --lh-scrollbar-thumb-hover: #64748b;
}

/* Firefox */
html {
    scrollbar-width: thin;
    scrollbar-color: var(--lh-scrollbar-thumb) var(--lh-scrollbar-track);
}
body,
.dropdown-menu,
.modal-body,
.offcanvas-body,
.table-responsive,
#sidebar {
    scrollbar-width: thin !important;
    scrollbar-color: var(--lh-scrollbar-thumb) var(--lh-scrollbar-track) !important;
}

/* WebKit */
html::-webkit-scrollbar,
body::-webkit-scrollbar,
.dropdown-menu::-webkit-scrollbar,
.modal-body::-webkit-scrollbar,
.offcanvas-body::-webkit-scrollbar,
.table-responsive::-webkit-scrollbar,
*::-webkit-scrollbar {
    width: var(--lh-scrollbar-size) !important;
    height: var(--lh-scrollbar-size) !important;
}

html::-webkit-scrollbar-track,
body::-webkit-scrollbar-track,
.dropdown-menu::-webkit-scrollbar-track,
.modal-body::-webkit-scrollbar-track,
.offcanvas-body::-webkit-scrollbar-track,
.table-responsive::-webkit-scrollbar-track,
*::-webkit-scrollbar-track {
    background: var(--lh-scrollbar-track);
    border-radius: var(--lh-scrollbar-radius);
}

html::-webkit-scrollbar-thumb,
body::-webkit-scrollbar-thumb,
.dropdown-menu::-webkit-scrollbar-thumb,
.modal-body::-webkit-scrollbar-thumb,
.offcanvas-body::-webkit-scrollbar-thumb,
.table-responsive::-webkit-scrollbar-thumb,
*::-webkit-scrollbar-thumb {
    background: var(--lh-scrollbar-thumb);
    border-radius: var(--lh-scrollbar-radius);
    /* Viền mỏng cùng màu track để thumb tách nền, không “mất hút” */
    border: 1px solid var(--lh-scrollbar-track);
    background-clip: padding-box;
}

html::-webkit-scrollbar-thumb:hover,
body::-webkit-scrollbar-thumb:hover,
.dropdown-menu::-webkit-scrollbar-thumb:hover,
.modal-body::-webkit-scrollbar-thumb:hover,
.offcanvas-body::-webkit-scrollbar-thumb:hover,
.table-responsive::-webkit-scrollbar-thumb:hover,
*::-webkit-scrollbar-thumb:hover {
    background: var(--lh-scrollbar-thumb-hover);
    background-clip: padding-box;
}

*::-webkit-scrollbar-corner {
    background: var(--lh-scrollbar-track);
}

/* Admin: sidebar nền tối — track tối hơn một chút, thumb sáng rõ */
#sidebar {
    scrollbar-color: rgba(255, 255, 255, 0.55) rgba(0, 0, 0, 0.35) !important;
}

#sidebar::-webkit-scrollbar-track {
    background: rgba(30, 41, 59, 0.85);
}

#sidebar::-webkit-scrollbar-thumb {
    background: rgba(203, 213, 225, 0.65);
    border: 1px solid rgba(15, 23, 42, 0.5);
    background-clip: padding-box;
}

#sidebar::-webkit-scrollbar-thumb:hover {
    background: rgba(241, 245, 249, 0.85);
    background-clip: padding-box;
}
