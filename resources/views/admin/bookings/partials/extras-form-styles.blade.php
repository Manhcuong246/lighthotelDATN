@once
    @push('styles')
        <style>
            .abs-extras-form-skin {
                --abs-x-surface: #ffffff;
                --abs-x-muted-bg: #f8fafc;
                --abs-x-border: #e2e8f0;
                --abs-x-muted-text: #64748b;
                --abs-x-text: #0f172a;
                --abs-x-accent: #0f766e;
                --abs-x-ring: rgba(15, 118, 110, 0.2);
            }
            .abs-extras-form-skin .abs-extras-rows {
                border-radius: 8px;
                background: var(--abs-x-muted-bg);
                padding: 0.35rem 0;
            }
            .abs-extras-form-skin .abs-extras-row {
                padding: 0.65rem 0.75rem;
                border-bottom: 1px solid rgba(226, 232, 240, 0.85);
            }
            .abs-extras-form-skin .abs-extras-row:last-child {
                border-bottom: none;
            }
            .abs-extras-form-skin .abs-extras-row .abs-extras-label {
                display: block;
                font-size: 0.75rem;
                font-weight: 600;
                color: var(--abs-x-muted-text);
                margin-bottom: 0.25rem;
                letter-spacing: 0.01em;
            }
            .abs-extras-form-skin .abs-extras-row .form-control,
            .abs-extras-form-skin .abs-extras-row .form-select {
                border-color: rgba(148, 163, 184, 0.45);
                background-color: var(--abs-x-surface);
            }
            .abs-extras-form-skin .abs-extras-row .form-control:focus,
            .abs-extras-form-skin .abs-extras-row .form-select:focus {
                border-color: var(--abs-x-accent);
                box-shadow: 0 0 0 0.2rem var(--abs-x-ring);
            }
            .abs-extras-form-skin .abs-extras-add {
                font-size: 0.8125rem;
                font-weight: 600;
                color: var(--abs-x-accent);
                padding: 0.25rem 0;
                border: none;
                background: none;
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
            }
            .abs-extras-form-skin .abs-extras-add:hover {
                color: #0d9488;
                text-decoration: underline;
            }
            .abs-extras-form-skin .abs-extras-add:focus-visible {
                outline: 2px solid var(--abs-x-accent);
                outline-offset: 2px;
                border-radius: 4px;
            }
            .abs-extras-form-skin .abs-extras-section-title {
                font-size: 0.8125rem;
                font-weight: 700;
                color: var(--abs-x-text);
                margin-bottom: 0.35rem;
            }
        </style>
    @endpush
@endonce
