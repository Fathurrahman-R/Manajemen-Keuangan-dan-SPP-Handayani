@props([
    'delay' => '0ms',
    'as' => 'div',
])

<{{ $as }}
    x-data="{
        isVisible: false,
        observer: null,
        init() {
            this.observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.isVisible = true;
                        this.observer.unobserve(this.$el);
                    }
                });
            }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });
            this.observer.observe(this.$el);
        },
        destroy() {
            if (this.observer) this.observer.disconnect();
        }
    }"
    x-init="init()"
    :class="isVisible ? 'reveal reveal-in' : 'reveal'"
    style="transition-delay: {{ $delay }}"
    {{ $attributes->merge(['class' => '']) }}
>
    {{ $slot }}
</{{ $as }}>
