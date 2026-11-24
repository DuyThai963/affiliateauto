<!-- [Design Read]: Apple HIG macOS Dark Mode web app layout featuring deep #000000/#0D0D0E canvas, studio-grade #18181B surface containers, crisp #26262A hairline borders, Apple Dark Mode Blue (#0A84FF) interactive accents, and high-legibility SF Pro typography. -->
<!DOCTYPE html>
<html lang="vi" class="h-full bg-[#0D0D0E] text-[#F5F5F7] dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'AffiliateAuto - Apple Dark Mode Engine' }}</title>
    
    <!-- Fonts: Inter & Plus Jakarta Sans with Apple-like rendering -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    
    <!-- Compiled High-Performance Assets (Vite CSS: 7KB Gzip) -->
    @if (file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif

    <style>
        /* Apple HIG High-Performance Navigation Progress Bar */
        .livewire-progress-bar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 2.5px;
            background: linear-gradient(90deg, #0A84FF, #30D158);
            z-index: 99999;
            box-shadow: 0 0 10px rgba(10, 132, 255, 0.7);
            transition: width 0.2s ease;
        }
    </style>

    @livewireStyles
</head>
<body class="h-full font-sans antialiased bg-[#0D0D0E] text-[#F5F5F7]">
    <div class="min-h-full flex">
        <!-- Sidebar Navigation (macOS Dark Mode Sidebar) -->
        <aside class="w-64 bg-[#121214] border-r border-[#26262A] flex flex-col fixed inset-y-0 z-30 select-none">
            <!-- App Brand / Header -->
            <div class="h-16 flex items-center justify-between px-6 border-b border-[#26262A]">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-xl bg-[#FFFFFF] flex items-center justify-center text-[#000000] shadow-apple-btn font-semibold text-sm">
                        <svg class="w-4 h-4 text-[#000000]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <div>
                        <span class="text-sm font-semibold text-[#F5F5F7] tracking-tight">AffiliateAuto</span>
                        <div class="text-[11px] font-mono text-[#8E8E93]">Version 1.0</div>
                    </div>
                </div>
            </div>

            <!-- Navigation Links (Instant SPA navigation with wire:navigate.hover) -->
            <nav class="flex-1 px-3 py-4 space-y-1">
                <div class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-[#636366]">Menu Chính</div>

                <a wire:navigate.hover href="{{ route('dashboard') }}" 
                   class="flex items-center space-x-3 px-3 py-2 rounded-xl text-xs font-medium transition-all {{ request()->routeIs('dashboard') ? 'bg-[#1C1C1E] text-[#FFFFFF] border border-[#2E2E32] font-semibold shadow-apple-card' : 'text-[#8E8E93] hover:text-[#F5F5F7] hover:bg-[#18181B]' }}">
                    <svg class="w-4 h-4 {{ request()->routeIs('dashboard') ? 'text-[#FFFFFF]' : 'text-[#8E8E93]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                    <span>Dashboard</span>
                </a>

                <a wire:navigate.hover href="{{ route('channels.index') }}" 
                   class="flex items-center space-x-3 px-3 py-2 rounded-xl text-xs font-medium transition-all {{ request()->routeIs('channels.*') ? 'bg-[#1C1C1E] text-[#FFFFFF] border border-[#2E2E32] font-semibold shadow-apple-card' : 'text-[#8E8E93] hover:text-[#F5F5F7] hover:bg-[#18181B]' }}">
                    <svg class="w-4 h-4 {{ request()->routeIs('channels.*') ? 'text-[#FFFFFF]' : 'text-[#8E8E93]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    <span>Kênh Phân Phối</span>
                </a>

                <a wire:navigate.hover href="{{ route('api-keys.index') }}" 
                   class="flex items-center space-x-3 px-3 py-2 rounded-xl text-xs font-medium transition-all {{ request()->routeIs('api-keys.*') ? 'bg-[#1C1C1E] text-[#FFFFFF] border border-[#2E2E32] font-semibold shadow-apple-card' : 'text-[#8E8E93] hover:text-[#F5F5F7] hover:bg-[#18181B]' }}">
                    <svg class="w-4 h-4 {{ request()->routeIs('api-keys.*') ? 'text-[#FFFFFF]' : 'text-[#8E8E93]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                    <span>Cấu hình API Keys</span>
                </a>

                <a wire:navigate.hover href="{{ route('products.index') }}" 
                   class="flex items-center space-x-3 px-3 py-2 rounded-xl text-xs font-medium transition-all {{ request()->routeIs('products.*') ? 'bg-[#1C1C1E] text-[#FFFFFF] border border-[#2E2E32] font-semibold shadow-apple-card' : 'text-[#8E8E93] hover:text-[#F5F5F7] hover:bg-[#18181B]' }}">
                    <svg class="w-4 h-4 {{ request()->routeIs('products.*') ? 'text-[#FFFFFF]' : 'text-[#8E8E93]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    <span>Sản phẩm Giỏ hàng</span>
                </a>

                <a wire:navigate.hover href="{{ route('system-logs.index') }}" 
                   class="flex items-center space-x-3 px-3 py-2 rounded-xl text-xs font-medium transition-all {{ request()->routeIs('system-logs.*') ? 'bg-[#1C1C1E] text-[#FFFFFF] border border-[#2E2E32] font-semibold shadow-apple-card' : 'text-[#8E8E93] hover:text-[#F5F5F7] hover:bg-[#18181B]' }}">
                    <svg class="w-4 h-4 {{ request()->routeIs('system-logs.*') ? 'text-[#FFFFFF]' : 'text-[#8E8E93]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>Nhật Ký & Schedule</span>
                </a>
            </nav>

            <!-- Bottom Status Indicator (macOS Status Style) -->
            <div class="p-4 mx-3 mb-4 rounded-2xl bg-[#18181B] border border-[#26262A] shadow-apple-card">
                <div class="text-[11px] font-semibold text-[#8E8E93] mb-2 flex items-center justify-between">
                    <span>HẠ TẦNG KẾT NỐI</span>
                    <span class="w-1.5 h-1.5 rounded-full bg-[#30D158] shadow-[0_0_8px_rgba(48,209,88,0.5)]"></span>
                </div>
                <div class="space-y-1.5 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-[#8E8E93] text-[11px]">Database:</span>
                        <span class="font-mono text-[11px] font-medium text-[#F5F5F7]">Neon pgsql</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[#8E8E93] text-[11px]">Redis Queue:</span>
                        <span class="font-mono text-[11px] font-medium text-[#30D158]">Upstash TLS</span>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 pl-64 flex flex-col min-h-screen">
            <!-- Topbar (Apple Frosted Dark Header) -->
            <header class="h-16 bg-[#121214]/80 backdrop-blur-md border-b border-[#26262A] sticky top-0 z-20 flex items-center justify-between px-8">
                <div>
                    <h1 class="text-sm font-bold text-[#F5F5F7] tracking-tight">{{ $header ?? 'Trung tâm kiểm soát' }}</h1>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="px-2.5 py-1 rounded-full bg-[#1C1C1E] border border-[#26262A] text-[#8E8E93] font-mono text-[11px]">
                        {{ now()->format('H:i') }} • {{ now()->format('d/m/Y') }}
                    </div>
                    <div class="h-4 w-px bg-[#26262A]"></div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-[#30D158]/15 text-[#30D158] border border-[#30D158]/25">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#30D158] mr-1.5 shadow-[0_0_6px_rgba(48,209,88,0.6)]"></span> Online
                    </span>
                </div>
            </header>

            <!-- Page Body -->
            <main class="flex-1 p-8 bg-[#0D0D0E]">
                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>
