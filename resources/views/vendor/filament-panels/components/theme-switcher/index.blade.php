<div
    x-data="{
        theme: null,
        toggleTheme() {
            this.theme = this.theme === 'dark' ? 'light' : 'dark'
            localStorage.setItem('theme', this.theme)
            $dispatch('theme-changed', this.theme)
        },
    }"
    x-init="
        theme = localStorage.getItem('theme') || @js(filament()->getDefaultThemeMode()->value)
    "
    class="fi-theme-switcher flex items-center gap-2 rounded-full border border-gray-200 bg-white p-1 shadow-sm dark:border-gray-700 dark:bg-gray-900"
>
    <button
        type="button"
        x-on:click="toggleTheme()"
        class="flex items-center gap-2 rounded-full px-3 py-1.5 text-sm font-medium transition"
        x-bind:class="theme === 'dark'
            ? 'bg-gray-900 text-white'
            : 'bg-amber-500 text-white'"
        :aria-label="theme === 'dark' ? 'التبديل إلى الوضع النهاري' : 'التبديل إلى الوضع الليلي'"
        x-tooltip="{
            content: theme === 'dark' ? 'الوضع النهاري' : 'الوضع الليلي',
            theme: $store.theme,
        }"
    >
        <svg
            x-show="theme === 'dark'"
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 20 20"
            fill="currentColor"
            class="h-4 w-4"
        >
            <path d="M10 2a.75.75 0 0 1 .75.75v1.25a.75.75 0 0 1-1.5 0V2.75A.75.75 0 0 1 10 2Zm5.657 2.343a.75.75 0 0 1 1.06 1.06l-.884.884a.75.75 0 1 1-1.06-1.06l.884-.884ZM18 9.25a.75.75 0 0 1 0 1.5h-1.25a.75.75 0 0 1 0-1.5H18ZM4.564 5.403a.75.75 0 0 1-1.06 1.06l-.884-.883a.75.75 0 0 1 1.06-1.06l.884.883ZM10 15.25a.75.75 0 0 1 .75.75v1.25a.75.75 0 0 1-1.5 0V16a.75.75 0 0 1 .75-.75ZM4 9.25a.75.75 0 0 1 0 1.5H2.75a.75.75 0 0 1 0-1.5H4Zm11.464 5.347a.75.75 0 1 1 1.06 1.06l-.884.884a.75.75 0 1 1-1.06-1.06l.884-.884ZM5.403 15.436a.75.75 0 0 1-1.06-1.06l.883-.884a.75.75 0 1 1 1.06 1.06l-.883.884Z" />
            <path d="M10 5.5a4.5 4.5 0 1 0 0 9 4.5 4.5 0 0 0 0-9Z" />
        </svg>

        <svg
            x-show="theme !== 'dark'"
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 20 20"
            fill="currentColor"
            class="h-4 w-4"
        >
            <path fill-rule="evenodd" d="M9.672 2.251a.75.75 0 0 1 .656 0 8.5 8.5 0 1 1-6.528 15.314.75.75 0 0 1 .662-1.335A7 7 0 1 0 9.67 2.25Z" clip-rule="evenodd" />
        </svg>

        <span x-text="theme === 'dark' ? 'ليلي' : 'نهاري'"></span>
    </button>
</div>
