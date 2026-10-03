<nav x-data="{ open: false }" class="bg-green-800 border-b border-green-900">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="flex items-center space-x-2">
                        <svg class="h-8 w-8 text-green-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="text-white font-bold text-lg">KALIKASAN WATCH</span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-4 sm:-my-px sm:ms-8 sm:flex">
                    @auth
                    <a href="{{ route('dashboard') }}"
                       class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md {{ request()->routeIs('dashboard') ? 'text-white bg-green-900' : 'text-green-200 hover:text-white hover:bg-green-700' }} transition">
                        Dashboard
                    </a>
                    <a href="{{ route('reports.index') }}"
                       class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md {{ request()->routeIs('reports.*') ? 'text-white bg-green-900' : 'text-green-200 hover:text-white hover:bg-green-700' }} transition">
                        Reports
                    </a>
                    @endauth
                    <a href="{{ route('map.index') }}"
                       class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md {{ request()->routeIs('map.*') ? 'text-white bg-green-900' : 'text-green-200 hover:text-white hover:bg-green-700' }} transition">
                        Map
                    </a>
                    @auth
                        @if(Auth::user()->isCitizen())
                            <a href="{{ route('reports.create') }}"
                               class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md bg-green-600 text-white hover:bg-green-500 transition">
                                + New Report
                            </a>
                        @endif
                    @endauth
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                @auth
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 text-sm leading-4 font-medium rounded-md text-green-200 hover:text-white focus:outline-none transition ease-in-out duration-150">
                            <div class="flex items-center space-x-2">
                                <span>{{ Auth::user()->name }}</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ Auth::user()->isAdmin() ? 'bg-red-100 text-red-800' : (Auth::user()->isOfficer() ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800') }}">
                                    {{ ucfirst(Auth::user()->role) }}
                                </span>
                            </div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
                @else
                <div class="flex items-center space-x-3">
                    <a href="{{ route('login') }}" class="text-sm font-medium text-green-200 hover:text-white transition">Log in</a>
                    <a href="{{ route('register') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md bg-green-600 text-white hover:bg-green-500 transition">Register</a>
                </div>
                @endauth
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-green-200 hover:text-white hover:bg-green-700 focus:outline-none transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden bg-green-900">
        <div class="pt-2 pb-3 space-y-1 px-3">
            @auth
            <a href="{{ route('dashboard') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('dashboard') ? 'text-white bg-green-800' : 'text-green-200 hover:text-white hover:bg-green-700' }}">
                Dashboard
            </a>
            <a href="{{ route('reports.index') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('reports.*') ? 'text-white bg-green-800' : 'text-green-200 hover:text-white hover:bg-green-700' }}">
                Reports
            </a>
            @endauth
            <a href="{{ route('map.index') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('map.*') ? 'text-white bg-green-800' : 'text-green-200 hover:text-white hover:bg-green-700' }}">
                Map
            </a>
            @auth
                @if(Auth::user()->isCitizen())
                    <a href="{{ route('reports.create') }}" class="block px-3 py-2 rounded-md text-base font-medium bg-green-600 text-white hover:bg-green-500">
                        + New Report
                    </a>
                @endif
            @endauth
        </div>

        <!-- Responsive Settings Options -->
        @auth
        <div class="pt-4 pb-1 border-t border-green-700">
            <div class="px-4">
                <div class="font-medium text-base text-white">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-green-300">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1 px-3">
                <a href="{{ route('profile.edit') }}" class="block px-3 py-2 rounded-md text-base font-medium text-green-200 hover:text-white hover:bg-green-700">
                    Profile
                </a>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="block w-full text-left px-3 py-2 rounded-md text-base font-medium text-green-200 hover:text-white hover:bg-green-700">
                        Log Out
                    </button>
                </form>
            </div>
        </div>
        @else
        <div class="pt-4 pb-1 border-t border-green-700 px-6 space-y-2">
            <a href="{{ route('login') }}" class="block px-3 py-2 rounded-md text-base font-medium text-green-200 hover:text-white hover:bg-green-700">Log in</a>
            <a href="{{ route('register') }}" class="block px-3 py-2 rounded-md text-base font-medium bg-green-600 text-white hover:bg-green-500">Register</a>
        </div>
        @endauth
    </div>
</nav>
