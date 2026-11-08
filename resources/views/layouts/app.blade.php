<!-- [Design Read]: Apple HIG inspired web app layout featuring #F5F5F7 canvas gray, crisp #FFFFFF content surfaces, Apple system blue (#0071E3) interactive accents, hairline borders (#E5E5EA), and SF Pro-aligned typography. -->
<!DOCTYPE html>
<html lang="vi" class="h-full bg-[#F5F5F7] text-[#1D1D1F]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'AffiliateAuto - Apple Styled Engine' }}</title>
    
    <!-- Fonts: Inter & Plus Jakarta Sans with Apple-like rendering -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS Play CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', '-apple-system', 'BlinkMacSystemFont', '"SF Pro Text"', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'SFMono-Regular', 'Menlo', 'monospace'],
                    },
                    colors: {
                        apple: {
                            canvas: '#F5F5F7',
                            surface: '#FFFFFF',
                            subtle: '#EBEBED',
                            border: '#E5E5EA',
                            text: '#1D1D1F',
                            secondary: '#86868B',
                            tertiary: '#AEAEB2',
                            blue: '#0071E3',
                            'blue-hover': '#0077ED',
                            green: '#34C759',
                            orange: '#FF9500',
                            red: '#FF3B30',
                            purple: '#AF52DE',
                        }
                    },
                    boxShadow: {
                        'apple-card': '0 2px 10px rgba(0, 0, 0, 0.03), 0 1px 3px rgba(0, 0, 0, 0.02)',
                        'apple-modal': '0 20px 40px rgba(0, 0, 0, 0.12), 0 1px 4px rgba(0, 0, 0, 0.05)',
                        'apple-btn': '0 1px 2px rgba(0, 0, 0, 0.06)',
                    }
                }
            }
        }
    </script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @livewireStyles
</head>
<body class="h-full font-sans antialiased bg-[#F5F5F7] text-[#1D1D1F]">
    <div class="min-h-full flex">
        <!-- Sidebar Navigation (macOS Sidebar Style) -->
        <aside class="w-64 bg-[#FBFBFD] border-r border-[#E5E5EA] flex flex-col fixed inset-y-0 z-30 select-none">
            <!-- App Brand / Header -->
            <div class="h-16 flex items-center justify-between px-6 border-b border-[#E5E5EA]/80">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-xl bg-[#0071E3] flex items-center justify-center text-white shadow-apple-btn font-semibold text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <div>
                        <span class="text-sm font-semibold text-[#1D1D1F] tracking-tight">AffiliateAuto</span>
                        <div class="text-[11px] font-mono text-[#86868B]">Version 1.0</div>
                    </div>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 px-3 py-4 space-y-1">
                <div class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-[#86868B]">Menu Chính</div>

                <a href="{{ route('dashboard') }}" 
                   class="flex items-center space-x-3 px-3 py-2 rounded-xl text-xs font-medium transition-all {{ request()->routeIs('dashboard') ? 'bg-[#FFFFFF] text-[#0071E3] shadow-apple-card font-semibold' : 'text-[#515154] hover:text-[#1D1D1F] hover:bg-[#EBEBED]/60' }}">
                    <svg class="w-4 h-4 {{ request()->routeIs('dashboard') ? 'text-[#0071E3]' : 'text-[#86868B]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                    <span>Dashboard</span>
                </a>

                <a href="{{ route('channels.index') }}" 
                   class="flex items-center space-x-3 px-3 py-2 rounded-xl text-xs font-medium transition-all {{ request()->routeIs('channels.*') ? 'bg-[#FFFFFF] text-[#0071E3] shadow-apple-card font-semibold' : 'text-[#515154] hover:text-[#1D1D1F] hover:bg-[#EBEBED]/60' }}">
                    <svg class="w-4 h-4 {{ request()->routeIs('channels.*') ? 'text-[#0071E3]' : 'text-[#86868B]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    <span>Kênh Phân Phối</span>
                </a>

                <a href="{{ route('api-keys.index') }}" 
                   class="flex items-center space-x-3 px-3 py-2 rounded-xl text-xs font-medium transition-all {{ request()->routeIs('api-keys.*') ? 'bg-[#FFFFFF] text-[#0071E3] shadow-apple-card font-semibold' : 'text-[#515154] hover:text-[#1D1D1F] hover:bg-[#EBEBED]/60' }}">
                    <svg class="w-4 h-4 {{ request()->routeIs('api-keys.*') ? 'text-[#0071E3]' : 'text-[#86868B]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                    <span>Cấu hình API Keys</span>
                </a>

                <a href="{{ route('products.index') }}" 
                   class="flex items-center space-x-3 px-3 py-2 rounded-xl text-xs font-medium transition-all {{ request()->routeIs('products.*') ? 'bg-[#FFFFFF] text-[#0071E3] shadow-apple-card font-semibold' : 'text-[#515154] hover:text-[#1D1D1F] hover:bg-[#EBEBED]/60' }}">
                    <svg class="w-4 h-4 {{ request()->routeIs('products.*') ? 'text-[#0071E3]' : 'text-[#86868B]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    <span>Sản phẩm Giỏ hàng</span>
                </a>
            </nav>

            <!-- Bottom Status Indicator (macOS Status Style) -->
            <div class="p-4 mx-3 mb-4 rounded-2xl bg-[#FFFFFF] border border-[#E5E5EA] shadow-apple-card">
                <div class="text-[11px] font-semibold text-[#86868B] mb-2 flex items-center justify-between">
                    <span>HẠ TẦNG KẾT NỐI</span>
                    <span class="w-1.5 h-1.5 rounded-full bg-[#34C759]"></span>
                </div>
                <div class="space-y-1.5 text-xs">
                    <div class="flex items-center justify-between text-[#1D1D1F]">
                        <span class="text-[#86868B] text-[11px]">Database:</span>
                        <span class="font-mono text-[11px] font-medium text-[#1D1D1F]">Neon pgsql</span>
                    </div>
                    <div class="flex items-center justify-between text-[#1D1D1F]">
                        <span class="text-[#86868B] text-[11px]">Redis Queue:</span>
                        <span class="font-mono text-[11px] font-medium text-[#34C759]">Upstash TLS</span>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 pl-64 flex flex-col min-h-screen">
            <!-- Topbar (Apple Frosted Header) -->
            <header class="h-16 bg-[#F5F5F7]/80 backdrop-blur-md border-b border-[#E5E5EA] sticky top-0 z-20 flex items-center justify-between px-8">
                <div>
                    <h1 class="text-sm font-bold text-[#1D1D1F] tracking-tight">{{ $header ?? 'Trung tâm kiểm soát' }}</h1>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="px-2.5 py-1 rounded-full bg-[#E5E5EA]/70 text-[#515154] font-mono text-[11px]">
                        {{ now()->format('H:i') }} • {{ now()->format('d/m/Y') }}
                    </div>
                    <div class="h-4 w-px bg-[#E5E5EA]"></div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-[#E8F5E9] text-[#2E7D32]">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#34C759] mr-1.5"></span> Online
                    </span>
                </div>
            </header>

            <!-- Page Body -->
            <main class="flex-1 p-8">
                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>
