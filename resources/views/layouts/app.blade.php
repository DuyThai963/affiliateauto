<!-- [Design Read]: Slate-900 foundation with crisp borders (border-slate-800), clean typography (sans), emerald accents for active/growth states, violet for affiliate badges, and solid semantic surfaces without gratuitous gradients. -->
<!DOCTYPE html>
<html lang="vi" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'AffiliateAuto - Hệ Thống Sản Xuất & Xuất Bản Video Tự Động' }}</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS (Play CDN for instant styling without local npm build step) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    },
                    colors: {
                        slate: {
                            850: '#151f32',
                            900: '#0f172a',
                            950: '#070b14',
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @livewireStyles
</head>
<body class="h-full font-sans antialiased bg-slate-950 text-slate-200">
    <div class="min-h-full flex">
        <!-- Sidebar Navigation -->
        <aside class="w-64 bg-slate-900 border-r border-slate-800/80 flex flex-col fixed inset-y-0 z-30">
            <!-- App Brand / Header -->
            <div class="h-16 flex items-center justify-between px-5 border-b border-slate-800/80">
                <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 rounded-lg bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 font-bold text-lg">
                        ⚡
                    </div>
                    <div>
                        <span class="text-base font-bold text-white tracking-tight">AffiliateAuto</span>
                        <div class="text-[11px] font-mono text-emerald-400">Core Engine v1.0</div>
                    </div>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 px-3 py-4 space-y-1">
                <a href="{{ route('dashboard') }}" 
                   class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('dashboard') ? 'bg-slate-800 text-white font-semibold border-l-4 border-emerald-500' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-850' }}">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span>Dashboard</span>
                </a>

                <a href="{{ route('channels.index') }}" 
                   class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('channels.*') ? 'bg-slate-800 text-white font-semibold border-l-4 border-emerald-500' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-850' }}">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    <span>Kênh của tôi (Channels)</span>
                </a>

                <a href="{{ route('api-keys.index') }}" 
                   class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('api-keys.*') ? 'bg-slate-800 text-white font-semibold border-l-4 border-emerald-500' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-850' }}">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                    <span>Cấu hình API Keys</span>
                </a>

                <a href="{{ route('products.index') }}" 
                   class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('products.*') ? 'bg-slate-800 text-white font-semibold border-l-4 border-emerald-500' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-850' }}">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    <span>Danh mục Giỏ hàng</span>
                </a>
            </nav>

            <!-- Status Box at bottom of sidebar -->
            <div class="p-4 border-t border-slate-800/80 bg-slate-900/50">
                <div class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Trạng thái hạ tầng</div>
                <div class="space-y-1.5 text-xs">
                    <div class="flex items-center justify-between text-slate-300">
                        <span class="flex items-center">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 mr-2"></span> Redis Queue
                        </span>
                        <span class="font-mono text-emerald-400 text-[11px]">Ready (C=1)</span>
                    </div>
                    <div class="flex items-center justify-between text-slate-300">
                        <span class="flex items-center">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 mr-2"></span> Buffer Vault
                        </span>
                        <span class="font-mono text-slate-400 text-[11px]">storage/vault</span>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 pl-64 flex flex-col min-h-screen">
            <!-- Topbar Header -->
            <header class="h-16 bg-slate-900/60 backdrop-blur border-b border-slate-800/80 sticky top-0 z-20 flex items-center justify-between px-8">
                <div>
                    <h1 class="text-lg font-bold text-slate-100">{{ $header ?? 'Bảng điều khiển trung tâm' }}</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-xs text-slate-400 font-mono">
                        {{ now()->format('d/m/Y - H:i:s') }}
                    </div>
                    <div class="h-4 w-px bg-slate-800"></div>
                    <div class="flex items-center space-x-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-950 text-emerald-400 border border-emerald-800">
                            Sprint 1 Active
                        </span>
                    </div>
                </div>
            </header>

            <!-- Page Body -->
            <main class="flex-1 p-8 bg-slate-950">
                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>

