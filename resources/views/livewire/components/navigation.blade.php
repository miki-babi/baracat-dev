<header class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-luxury-light-gray/30 transition-all duration-300">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8" x-data="{ mobileMenu: false }">
        <div class="flex items-center justify-between h-16 lg:h-20">
            <!-- Logo -->
            <div class="flex-shrink-0">
                <h1 class="font-serif text-2xl lg:text-3xl font-bold tracking-tight text-luxury-black">Aurelia</h1>
            </div>

            <!-- Desktop Navigation -->
            <nav class="hidden lg:flex items-center space-x-8 lg:space-x-12 gap-4">
                @foreach ($this->collections as $collection)
                    <a class="luxury-caption text-luxury-charcoal hover:text-luxury-gold transition-colors duration-300 relative group"
                       href="{{ route('collection.view', $collection->defaultUrl->slug) }}"
                       wire:navigate
                    >
                        {{ $collection->translateAttribute('name') }}
                        <span class="absolute -bottom-1 left-0 w-0 h-0.5 bg-luxury-gold transition-all duration-300 group-hover:w-full"></span>
                    </a>
                @endforeach
            </nav>

            <!-- Icons / Actions -->
            <div class="flex items-center space-x-4 lg:space-x-6">
                <x-header.search class="max-w-sm hidden md:block" />
                @livewire('components.cart')

                <!-- Mobile Menu Button -->
                <button x-on:click="mobileMenu = !mobileMenu" class="lg:hidden hover:bg-luxury-whisper grid w-10 h-10 rounded-md place-items-center">
                    <span class="sr-only">Toggle Menu</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-luxury-charcoal" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile Navigation -->
        <div x-cloak x-transition x-show="mobileMenu" class="lg:hidden border-t border-luxury-light-gray/30 py-4">
            <nav class="flex flex-col space-y-4 lg:space-y-6">
                @foreach ($this->collections as $collection)
                    <a
                        href="{{ route('collection.view', $collection->defaultUrl->slug) }}"
                        wire:navigate
                        class="luxury-caption text-luxury-charcoal hover:text-luxury-gold transition-colors duration-300 px-2 py-1"
                        x-on:click="mobileMenu = false"
                    >
                        {{ $collection->translateAttribute('name') }}
                    </a>
                @endforeach
            </nav>
        </div>
    </div>
</header>
