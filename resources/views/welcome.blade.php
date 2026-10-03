<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>KALIKASAN WATCH - Municipality of Apparri</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white font-sans antialiased">

    {{-- Navigation --}}
    <nav class="bg-white/90 backdrop-blur-md border-b border-green-100 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center space-x-3">
                    <span class="text-3xl">🌿</span> 
                    <span class="text-xl font-extrabold text-green-800 tracking-tight">KALIKASAN WATCH</span>
                </div>
                <div class="flex items-center space-x-4">
                    @auth
                        <a href="{{ url('/dashboard') }}"
                           class="inline-flex items-center px-5 py-2 bg-green-600 text-white text-sm font-semibold rounded-lg hover:bg-green-700 transition">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                           class="text-sm font-semibold text-green-700 hover:text-green-900 transition">
                            Log in
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}"
                               class="inline-flex items-center px-5 py-2 bg-green-600 text-white text-sm font-semibold rounded-lg hover:bg-green-700 transition">
                                Register
                            </a>
                        @endif
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    {{-- Hero Section --}}
    <section class="relative overflow-hidden bg-gradient-to-br from-green-700 via-green-600 to-emerald-500 text-white">
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" viewBox="0 0 800 600" fill="none">
                <circle cx="200" cy="300" r="250" fill="white"/>
                <circle cx="650" cy="150" r="180" fill="white"/>
                <circle cx="500" cy="500" r="200" fill="white"/>
            </svg>
        </div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 sm:py-32 lg:py-40">
            <div class="text-center max-w-3xl mx-auto">
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight leading-tight">
                    Protect Our Environment,
                    <span class="block text-green-200">Report What You See</span>
                </h1>
                <p class="mt-6 text-lg sm:text-xl text-green-100 leading-relaxed max-w-2xl mx-auto">
                    KALIKASAN WATCH empowers the citizens of Apparri to report environmental concerns
                    and work together with local officials to protect our natural resources.
                </p>
                <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                    @auth
                        <a href="{{ route('reports.create') }}"
                           class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-3.5 bg-white text-green-700 text-base font-bold rounded-xl shadow-lg hover:bg-green-50 transition">
                            Submit a Report
                        </a>
                    @else
                        <a href="{{ route('register') }}"
                           class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-3.5 bg-white text-green-700 text-base font-bold rounded-xl shadow-lg hover:bg-green-50 transition">
                            Get Started
                        </a>
                    @endauth
                    <a href="{{ route('map.index') }}"
                       class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-3.5 border-2 border-white/50 text-white text-base font-bold rounded-xl hover:bg-white/10 transition">
                        View Map
                    </a>
                </div>
            </div>
        </div>
        <div class="absolute bottom-0 left-0 right-0">
            <svg viewBox="0 0 1440 120" fill="none" class="w-full">
                <path d="M0,64L60,69.3C120,75,240,85,360,80C480,75,600,53,720,48C840,43,960,53,1080,58.7C1200,64,1320,64,1380,64L1440,64L1440,120L1380,120C1320,120,1200,120,1080,120C960,120,840,120,720,120C600,120,480,120,360,120C240,120,120,120,60,120L0,120Z" fill="white"/>
            </svg>
        </div>
    </section>

    {{-- Features Section --}}
    <section class="py-20 sm:py-28 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900">
                    How You Can Help
                </h2>
                <p class="mt-4 text-lg text-gray-500 max-w-2xl mx-auto">
                    Every report makes a difference. Our platform connects citizens directly with municipal officers.
                </p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 lg:gap-12">
                <div class="bg-green-50 rounded-2xl p-8 text-center hover:shadow-lg transition-shadow">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-2xl mb-6">
                        <span class="text-3xl">📋</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Report Issues</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Spot illegal logging, water pollution, waste dumping, or other environmental concerns? Report it with photos and exact location.
                    </p>
                </div>
                <div class="bg-blue-50 rounded-2xl p-8 text-center hover:shadow-lg transition-shadow">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-100 rounded-2xl mb-6">
                        <span class="text-3xl">📍</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Track Progress</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Follow the status of your reports in real-time. See when officers are investigating and when issues are resolved.
                    </p>
                </div>
                <div class="bg-amber-50 rounded-2xl p-8 text-center hover:shadow-lg transition-shadow">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-amber-100 rounded-2xl mb-6">
                        <span class="text-3xl">🗺️</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Interactive Map</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Explore an interactive map showing all reported environmental concerns across the Municipality of Apparri.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- How It Works Section --}}
    <section class="py-20 sm:py-28 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900">
                    How It Works
                </h2>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-14 h-14 bg-green-600 text-white text-xl font-bold rounded-full mb-5">1</div>
                    <h3 class="font-bold text-gray-900 mb-2">Register</h3>
                    <p class="text-sm text-gray-500">Create your free account as a citizen of Apparri.</p>
                </div>
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-14 h-14 bg-green-600 text-white text-xl font-bold rounded-full mb-5">2</div>
                    <h3 class="font-bold text-gray-900 mb-2">Report</h3>
                    <p class="text-sm text-gray-500">Submit an environmental concern with details and location.</p>
                </div>
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-14 h-14 bg-green-600 text-white text-xl font-bold rounded-full mb-5">3</div>
                    <h3 class="font-bold text-gray-900 mb-2">Investigate</h3>
                    <p class="text-sm text-gray-500">Municipal officers review and investigate your report.</p>
                </div>
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-14 h-14 bg-green-600 text-white text-xl font-bold rounded-full mb-5">4</div>
                    <h3 class="font-bold text-gray-900 mb-2">Resolve</h3>
                    <p class="text-sm text-gray-500">Issues are addressed and the environment is protected.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Categories Section --}}
    <section class="py-20 sm:py-28 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900">
                    What Can You Report?
                </h2>
                <p class="mt-4 text-lg text-gray-500 max-w-2xl mx-auto">
                    Help us monitor a wide range of environmental concerns in our community.
                </p>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                @php
                    $categories = [
                        ['icon' => '💧', 'name' => 'Water Pollution'],
                        ['icon' => '🌫️', 'name' => 'Air Pollution'],
                        ['icon' => '🪓', 'name' => 'Illegal Logging'],
                        ['icon' => '🗑️', 'name' => 'Waste Dumping'],
                        ['icon' => '🌊', 'name' => 'Flooding'],
                        ['icon' => '⛰️', 'name' => 'Soil Erosion'],
                        ['icon' => '🦎', 'name' => 'Wildlife Concern'],
                        ['icon' => '🔊', 'name' => 'Noise Pollution'],
                    ];
                @endphp
                @foreach($categories as $category)
                    <div class="flex items-center space-x-3 bg-gray-50 rounded-xl p-4 hover:bg-green-50 transition-colors">
                        <span class="text-2xl">{{ $category['icon'] }}</span>
                        <span class="text-sm font-semibold text-gray-700">{{ $category['name'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA Section --}}
    <section class="py-20 sm:py-28 bg-gradient-to-r from-green-700 to-emerald-600 text-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl sm:text-4xl font-extrabold mb-6">
                Ready to Make a Difference?
            </h2>
            <p class="text-lg text-green-100 mb-10 max-w-2xl mx-auto">
                Join hundreds of Apparri citizens who are actively protecting our environment.
                Together, we can keep our community clean, green, and sustainable.
            </p>
            @guest
                <a href="{{ route('register') }}"
                   class="inline-flex items-center px-10 py-4 bg-white text-green-700 text-lg font-bold rounded-xl shadow-lg hover:bg-green-50 transition">
                    Join KALIKASAN WATCH Today
                </a>
            @else
                <a href="{{ route('reports.create') }}"
                   class="inline-flex items-center px-10 py-4 bg-white text-green-700 text-lg font-bold rounded-xl shadow-lg hover:bg-green-50 transition">
                    Submit Your First Report
                </a>
            @endguest
        </div>
    </section>

    {{-- Footer --}}
    <footer class="bg-gray-900 text-gray-400 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                <div class="flex items-center space-x-3">
                    <span class="text-2xl">🌿</span>
                    <span class="text-lg font-bold text-white">KALIKASAN WATCH</span>
                </div>
                <p class="text-sm text-center md:text-right">
                    &copy; {{ date('Y') }} Municipality of Apparri &mdash; Environmental Protection Unit.
                    <br class="sm:hidden"> All rights reserved.
                </p>
            </div>
        </div>
    </footer>

</body>
</html>
