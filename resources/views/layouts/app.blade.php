<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'JST Machine Tools')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@500;600;700&family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --ink: #0f2744;
            --ink-2: #16365c;
            --steel: #1e4e79;
            --steel-2: #2f6fa3;
            --slate: #475569;
            --line: #d5dee8;
            --paper: #e7eef5;
            --card: #ffffff;
        }
        html, body {
            height: 100%;
            overflow: hidden;
        }
        body {
            font-family: "Source Sans 3", sans-serif;
            background: var(--paper);
            color: #1e293b;
        }
        h1, h2, h3, h4, h5, .brand-word {
            font-family: Barlow, sans-serif;
            letter-spacing: .01em;
        }
        .app-shell {
            display: flex;
            height: 100vh;
            height: 100dvh;
            overflow: hidden;
        }
        .sidebar {
            width: 248px;
            background: linear-gradient(180deg, var(--ink) 0%, #0a1c33 100%);
            color: #d7e4f0;
            flex-shrink: 0;
            height: 100%;
            overflow-x: hidden;
            overflow-y: auto;
            transition: width .22s ease, margin .22s ease, opacity .18s ease;
        }
        .app-shell.sidebar-collapsed .sidebar {
            width: 0;
            margin: 0;
            opacity: 0;
            pointer-events: none;
            border: 0;
        }
        .brand {
            display: flex;
            gap: .75rem;
            align-items: center;
            padding: 1.1rem 1rem .9rem;
            border-bottom: 1px solid rgba(255,255,255,.08);
        }
        .brand-mark {
            width: 40px; height: 40px;
            border-radius: 8px;
            background: var(--steel-2);
            color: #fff;
            display: grid; place-items: center;
            font-weight: 700;
            font-family: Barlow, sans-serif;
        }
        .brand small { color: #93a9c0; display: block; font-size: .75rem; }
        .side-label {
            padding: .9rem 1rem .25rem;
            font-size: .68rem;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: #7f96ad;
        }
        .side-link {
            display: flex;
            align-items: center;
            gap: .65rem;
            color: #d7e4f0;
            text-decoration: none;
            padding: .48rem 1rem;
            border-left: 3px solid transparent;
            font-weight: 600;
        }
        .side-link:hover { background: rgba(255,255,255,.05); color: #fff; }
        .side-link.active {
            background: rgba(47, 111, 163, .35);
            border-left-color: #8ec5ef;
            color: #fff;
        }
        .main {
            flex: 1;
            min-width: 0;
            min-height: 0;
            height: 100%;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .topbar {
            background: #fff;
            border-bottom: 1px solid var(--line);
            padding: .75rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-shrink: 0;
            z-index: 20;
        }
        .content {
            padding: 1.25rem;
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
        }
        .card { border: 1px solid var(--line); border-radius: 12px; }
        .btn-primary { background: var(--steel); border-color: var(--steel); }
        .btn-primary:hover { background: var(--ink-2); border-color: var(--ink-2); }
        .btn-outline-primary { color: var(--steel); border-color: var(--steel); }
        .btn-outline-primary:hover { background: var(--steel); border-color: var(--steel); }
        .stat-card { border-top: 3px solid var(--steel); }
        .table thead th {
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: var(--slate);
            background: #f8fafc;
            white-space: nowrap;
        }
        .sidebar-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            padding: 0;
            border-radius: 9px;
        }
        .sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 39, 68, .42);
            z-index: 1035;
        }
        .sidebar-backdrop.show { display: block; }
        @media (max-width: 991.98px) {
            .sidebar {
                position: fixed;
                z-index: 1040;
                left: 0;
                top: 0;
                width: 248px !important;
                margin: 0 !important;
                opacity: 1 !important;
                pointer-events: auto !important;
                transform: translateX(-105%);
                transition: transform .22s ease;
            }
            .app-shell.sidebar-collapsed .sidebar {
                width: 248px !important;
                opacity: 1 !important;
                pointer-events: auto !important;
            }
            .sidebar.show { transform: none; }
            .content { padding: 1rem; }
        }
        .list-hero {
            position: relative;
            overflow: hidden;
            border-radius: 16px;
            background:
                radial-gradient(1000px 240px at 100% -30%, rgba(142, 197, 239, .26), transparent 55%),
                linear-gradient(135deg, #0f2744 0%, #16365c 50%, #1e4e79 100%);
            color: #e8f1f8;
            padding: 1.25rem 1.4rem;
            margin-bottom: 1.1rem;
            box-shadow: 0 18px 40px rgba(15, 39, 68, .16);
        }
        .list-hero .kicker {
            font-size: .72rem;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: #93b4d0;
            font-weight: 600;
            margin-bottom: .2rem;
        }
        .list-hero h1 {
            font-family: Barlow, sans-serif;
            font-size: clamp(1.25rem, 2vw, 1.65rem);
            font-weight: 700;
            color: #fff;
            margin: 0;
        }
        .list-hero p { margin: .35rem 0 0; color: #b7cee3; max-width: 38rem; }
        .list-toolbar {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: .75rem;
            margin-bottom: 1rem;
        }
        .list-toolbar .search-wrap {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
            flex: 1;
            min-width: 240px;
        }
        .list-toolbar .form-control,
        .list-toolbar .form-select {
            border-radius: 10px;
            min-height: 40px;
            max-width: 280px;
        }
        .list-panel {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 14px;
            box-shadow: 0 8px 20px rgba(15, 39, 68, .04);
            overflow: hidden;
        }
        .list-panel-head {
            padding: .9rem 1.15rem;
            border-bottom: 1px solid var(--line);
            background: linear-gradient(180deg, #f8fafc 0%, #fff 100%);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
        }
        .list-panel-head h2 {
            margin: 0;
            font-size: .95rem;
            font-weight: 700;
            color: var(--ink);
        }
        .list-table thead th {
            font-size: .72rem;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: #64748b;
            background: #f8fafc;
            border-bottom-color: var(--line);
            padding: .75rem 1rem;
            white-space: nowrap;
        }
        .list-table tbody td {
            padding: .85rem 1rem;
            vertical-align: middle;
            border-color: #edf2f7;
        }
        .list-table tbody tr:hover { background: #f8fafc; }
        .list-table .title { font-weight: 700; color: var(--ink); }
        .list-table .sub { color: #64748b; font-size: .86rem; margin-top: .12rem; }
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            padding: .22rem .55rem;
            border-radius: 999px;
            font-size: .75rem;
            font-weight: 700;
        }
        .status-pill.on { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
        .status-pill.off { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .icon-actions { display: inline-flex; align-items: center; gap: .35rem; }
        .icon-btn {
            width: 34px;
            height: 34px;
            border-radius: 9px;
            display: inline-grid;
            place-items: center;
            border: 1px solid var(--line);
            background: #fff;
            color: var(--steel);
            text-decoration: none;
            transition: background .12s ease, border-color .12s ease, color .12s ease;
            padding: 0;
            line-height: 1;
            cursor: pointer;
        }
        .icon-btn:hover { background: #eef5fb; border-color: #9db8d1; color: var(--ink); }
        .icon-btn.danger { color: #b91c1c; }
        .icon-btn.danger:hover { background: #fef2f2; border-color: #fca5a5; color: #991b1b; }
        .list-empty { text-align: center; padding: 2.2rem 1rem; color: #64748b; }
        .list-empty i { font-size: 1.5rem; color: #94a3b8; display: block; margin-bottom: .45rem; }
        .thumb-sm {
            width: 44px; height: 44px;
            border-radius: 10px;
            object-fit: cover;
            border: 1px solid var(--line);
            background: #f8fafc;
        }
        @media (max-width: 767.98px) {
            .list-toolbar .search-wrap {
                width: 100%;
                min-width: 0;
            }
            .list-toolbar .form-control,
            .list-toolbar .form-select {
                max-width: none;
                flex: 1 1 100%;
            }
            .list-panel,
            .master-panel:has(.list-table),
            .master-panel:has(.master-table),
            .dash-panel:has(.dash-table) {
                background: transparent;
                border: 0;
                box-shadow: none;
                overflow: visible;
            }
            .list-panel-head,
            .master-panel:has(.master-table) > .master-panel-head,
            .dash-panel:has(.dash-table) > .dash-panel-head {
                background: #fff;
                border: 1px solid var(--line);
                border-radius: 12px;
                margin-bottom: .75rem;
                box-shadow: 0 6px 16px rgba(15, 39, 68, .04);
            }
            .list-panel .table-responsive,
            .master-panel .table-responsive,
            .dash-panel .table-responsive,
            .dash-panel-body.table-responsive {
                overflow: visible;
            }
            table.list-table,
            table.master-table,
            table.dash-table {
                display: block;
                width: 100%;
            }
            table.list-table thead,
            table.master-table thead,
            table.dash-table thead {
                display: none;
            }
            table.list-table tbody,
            table.master-table tbody,
            table.dash-table tbody {
                display: block;
            }
            table.list-table tbody tr,
            table.master-table tbody tr,
            table.dash-table tbody tr {
                display: block;
                background: #fff;
                border: 1px solid var(--line);
                border-radius: 14px;
                margin-bottom: .75rem;
                padding: .35rem 0 .15rem;
                box-shadow: 0 8px 18px rgba(15, 39, 68, .05);
            }
            table.list-table tbody tr:hover,
            table.master-table tbody tr:hover,
            table.dash-table tbody tr:hover {
                background: #fff;
            }
            table.list-table tbody td,
            table.master-table tbody td,
            table.dash-table tbody td {
                display: grid;
                grid-template-columns: minmax(5.5rem, 34%) minmax(0, 1fr);
                align-items: start;
                column-gap: .85rem;
                width: 100%;
                border: 0 !important;
                padding: .55rem 1rem !important;
                text-align: right !important;
            }
            table.list-table tbody td::before,
            table.master-table tbody td::before,
            table.dash-table tbody td::before {
                content: attr(data-label);
                font-size: .68rem;
                letter-spacing: .06em;
                text-transform: uppercase;
                font-weight: 700;
                color: #64748b;
                text-align: left;
                padding-top: .15rem;
            }
            table.list-table tbody td[data-label=""]::before,
            table.master-table tbody td[data-label=""]::before,
            table.dash-table tbody td[data-label=""]::before,
            table.list-table tbody td.is-media::before,
            table.list-table tbody td.is-actions::before,
            table.master-table tbody td.is-actions::before,
            table.dash-table tbody td.is-actions::before {
                display: none;
            }
            table.list-table tbody td > *,
            table.master-table tbody td > *,
            table.dash-table tbody td > * {
                min-width: 0;
                justify-self: end;
                text-align: right;
            }
            table.list-table tbody td.is-media {
                grid-template-columns: 1fr;
                padding-bottom: .25rem !important;
                text-align: left !important;
            }
            table.list-table tbody td.is-media > * {
                justify-self: start;
                text-align: left;
            }
            table.list-table tbody td.is-actions,
            table.master-table tbody td.is-actions,
            table.dash-table tbody td.is-actions {
                grid-template-columns: 1fr;
                border-top: 1px solid #edf2f7 !important;
                margin-top: .2rem;
                padding-top: .75rem !important;
                padding-bottom: .75rem !important;
            }
            table.list-table tbody td.is-actions > *,
            table.master-table tbody td.is-actions > *,
            table.dash-table tbody td.is-actions > * {
                justify-self: end;
            }
            table.list-table tbody td[colspan],
            table.master-table tbody td[colspan],
            table.dash-table tbody td[colspan] {
                display: block;
                text-align: center !important;
            }
            table.list-table tbody td[colspan]::before,
            table.master-table tbody td[colspan]::before,
            table.dash-table tbody td[colspan]::before {
                display: none;
            }
            table.list-table tbody td[colspan] > *,
            table.master-table tbody td[colspan] > *,
            table.dash-table tbody td[colspan] > * {
                text-align: center;
            }
        }
        .thumb-placeholder {
            width: 44px; height: 44px;
            border-radius: 10px;
            border: 1px solid var(--line);
            background: #f8fafc;
            color: #94a3b8;
            display: grid;
            place-items: center;
            font-size: .65rem;
            font-weight: 700;
        }
    </style>
    @stack('styles')
</head>
<body>
@php
    $navMain = [
        ['route' => 'dashboard', 'perm' => 'dashboard.view', 'icon' => 'speedometer2', 'label' => 'Dashboard', 'match' => 'dashboard'],
        ['route' => 'customers.index', 'perm' => 'customers.view', 'icon' => 'buildings', 'label' => 'Customers', 'match' => 'customers.*'],
        ['route' => 'suppliers.index', 'perm' => 'suppliers.view', 'icon' => 'truck', 'label' => 'Suppliers', 'match' => 'suppliers.*'],
        ['route' => 'products.index', 'perm' => 'products.view', 'icon' => 'gear-wide-connected', 'label' => 'Products', 'match' => 'products.*'],
        ['route' => 'quotations.index', 'perm' => 'quotations.view', 'icon' => 'file-earmark-text', 'label' => 'Quotations', 'match' => 'quotations.*'],
        ['route' => 'invoices.index', 'perm' => 'invoices.view', 'icon' => 'receipt', 'label' => 'Invoices', 'match' => 'invoices.*'],
        ['route' => 'stock.index', 'perm' => 'stock.view', 'icon' => 'box-seam', 'label' => 'Stock', 'match' => 'stock.*'],
        ['route' => 'receipts.index', 'perm' => 'receipts.view', 'icon' => 'cash-coin', 'label' => 'Receipts', 'match' => 'receipts.*'],
        ['route' => 'reports.index', 'perm' => 'reports.view', 'icon' => 'bar-chart', 'label' => 'Reports', 'match' => 'reports.*'],
        ['route' => 'communication.index', 'perm' => 'communication.view', 'icon' => 'envelope-paper', 'label' => 'Communication', 'match' => 'communication.*'],
    ];
    $navAdmin = [
        ['route' => 'masters.index', 'perm' => 'masters.view', 'icon' => 'collection', 'label' => 'Masters', 'match' => 'masters.*'],
        ['route' => 'settings.index', 'perm' => 'settings.view', 'icon' => 'sliders', 'label' => 'Settings', 'match' => 'settings.*'],
        ['route' => 'users.index', 'perm' => 'users.view', 'icon' => 'person-badge', 'label' => 'Users', 'match' => 'users.*'],
    ];
@endphp
<div class="app-shell" id="appShell">
    <aside class="sidebar" id="sidebar" aria-label="Main navigation">
        <div class="brand">
            <div class="brand-mark">JST</div>
            <div>
                <div class="brand-word">{{ $company->trade_name ?? $company->name ?? 'JST Machine Tools' }}</div>
                <small>Invoicing</small>
            </div>
        </div>
        <div class="side-label">Operations</div>
        @foreach($navMain as $item)
            @if(auth()->user()->hasPermission($item['perm']))
                <a class="side-link {{ request()->routeIs($item['match']) ? 'active' : '' }}" href="{{ route($item['route']) }}">
                    <i class="bi bi-{{ $item['icon'] }}"></i> {{ $item['label'] }}
                </a>
            @endif
        @endforeach
        <div class="side-label">Administration</div>
        @foreach($navAdmin as $item)
            @if(auth()->user()->hasPermission($item['perm']))
                <a class="side-link {{ request()->routeIs($item['match']) ? 'active' : '' }}" href="{{ route($item['route']) }}">
                    <i class="bi bi-{{ $item['icon'] }}"></i> {{ $item['label'] }}
                </a>
            @endif
        @endforeach
    </aside>
    <div class="sidebar-backdrop" id="sidebarBackdrop" hidden></div>
    <div class="main">
        <header class="topbar">
            <div class="d-flex align-items-center gap-2">
                <button
                    class="btn btn-outline-secondary btn-sm sidebar-toggle"
                    type="button"
                    id="sidebarToggle"
                    aria-controls="sidebar"
                    aria-expanded="true"
                    title="Show or hide menu"
                >
                    <i class="bi bi-layout-sidebar-inset" id="sidebarToggleIcon"></i>
                </button>
                <div>
                    <div class="fw-semibold">@yield('page', 'Dashboard')</div>
                    <div class="text-muted small">@yield('subtitle')</div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="text-end">
                    <div class="fw-semibold">{{ auth()->user()->name }}</div>
                    <div class="text-muted small">{{ auth()->user()->role->name ?? 'User' }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-outline-secondary btn-sm" type="submit">Logout</button>
                </form>
            </div>
        </header>
        <main class="content">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('warning'))
                <div class="alert alert-warning">{{ session('warning') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
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
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    const shell = document.getElementById('appShell');
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('sidebarToggle');
    const icon = document.getElementById('sidebarToggleIcon');
    const backdrop = document.getElementById('sidebarBackdrop');
    const storageKey = 'jst.sidebarCollapsed';

    function isMobile() {
        return window.matchMedia('(max-width: 991.98px)').matches;
    }

    function setIcon(collapsedOrClosed) {
        if (!icon) return;
        icon.className = collapsedOrClosed ? 'bi bi-layout-sidebar' : 'bi bi-layout-sidebar-inset';
    }

    function setDesktopCollapsed(collapsed) {
        shell?.classList.toggle('sidebar-collapsed', collapsed);
        toggle?.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        setIcon(collapsed);
        try { localStorage.setItem(storageKey, collapsed ? '1' : '0'); } catch (e) {}
    }

    function setMobileOpen(open) {
        sidebar?.classList.toggle('show', open);
        if (backdrop) {
            backdrop.hidden = !open;
            backdrop.classList.toggle('show', open);
        }
        toggle?.setAttribute('aria-expanded', open ? 'true' : 'false');
        setIcon(!open);
    }

    function syncForViewport() {
        if (isMobile()) {
            shell?.classList.remove('sidebar-collapsed');
            setMobileOpen(false);
        } else {
            sidebar?.classList.remove('show');
            if (backdrop) {
                backdrop.hidden = true;
                backdrop.classList.remove('show');
            }
            let collapsed = false;
            try { collapsed = localStorage.getItem(storageKey) === '1'; } catch (e) {}
            setDesktopCollapsed(collapsed);
        }
    }

    toggle?.addEventListener('click', function () {
        if (isMobile()) {
            setMobileOpen(!sidebar?.classList.contains('show'));
        } else {
            setDesktopCollapsed(!shell?.classList.contains('sidebar-collapsed'));
        }
    });

    backdrop?.addEventListener('click', function () {
        setMobileOpen(false);
    });

    sidebar?.querySelectorAll('a.side-link').forEach(function (link) {
        link.addEventListener('click', function () {
            if (isMobile()) setMobileOpen(false);
        });
    });

    window.addEventListener('resize', syncForViewport);
    syncForViewport();

    function enhanceMobileRowCards() {
        document.querySelectorAll('table.list-table, table.master-table, table.dash-table').forEach(function (table) {
            const headers = Array.from(table.querySelectorAll('thead th')).map(function (th) {
                return (th.textContent || '').replace(/\s+/g, ' ').trim();
            });
            table.querySelectorAll('tbody tr').forEach(function (row) {
                const cells = Array.from(row.children).filter(function (el) {
                    return el.tagName === 'TD';
                });
                if (cells.length === 1 && cells[0].hasAttribute('colspan')) {
                    return;
                }
                cells.forEach(function (cell, index) {
                    if (!cell.hasAttribute('data-label')) {
                        cell.setAttribute('data-label', headers[index] || '');
                    }
                    const label = (cell.getAttribute('data-label') || '').toLowerCase();
                    if (cell.querySelector('.icon-actions') || label === 'actions') {
                        cell.classList.add('is-actions');
                    }
                    if (cell.querySelector('.thumb-sm, .thumb-placeholder') || label === '') {
                        if (cell.querySelector('.thumb-sm, .thumb-placeholder')) {
                            cell.classList.add('is-media');
                        }
                    }
                });
            });
        });
    }

    enhanceMobileRowCards();
})();
</script>
@stack('scripts')
</body>
</html>
