<script setup>
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';

const page = usePage();
const showingNavigationDropdown = ref(false);

const authUser = computed(() => page.props.auth?.user ?? null);

const mainLinks = computed(() => {
    const links = [];

    if (authUser.value) {
        links.push(
            { href: route('dashboard'), label: 'Dashboard', active: route().current('dashboard') },
            { href: route('dreams.index'), label: 'Dreams', active: route().current('dreams.*') },
            { href: route('symbols.index'), label: 'Symbols', active: route().current('symbols.*') },
        );
    }

    links.push(
        { href: route('globe'), label: 'Globe', active: route().current('globe') },
        { href: route('library'), label: 'Library', active: route().current('library') },
    );

    return links;
});

const logoRoute = computed(() => (authUser.value ? route('dashboard') : '/'));
</script>

<template>
    <div class="pnv-page">
        <nav class="sticky top-0 z-50 border-b border-slate-800/80 bg-slate-950/85 backdrop-blur">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <div class="flex items-center gap-8">
                        <Link :href="logoRoute" class="flex shrink-0 items-center">
                            <ApplicationLogo class="block h-9 w-auto" />
                        </Link>

                        <div class="hidden items-center gap-1 sm:flex">
                            <NavLink
                                v-for="link in mainLinks"
                                :key="link.label"
                                :href="link.href"
                                :active="link.active"
                            >
                                {{ link.label }}
                            </NavLink>
                        </div>
                    </div>

                    <div v-if="authUser" class="hidden sm:flex sm:items-center">
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button
                                    type="button"
                                    class="inline-flex items-center rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm font-medium text-slate-200 transition hover:bg-slate-800"
                                >
                                    {{ authUser.name }}
                                    <svg
                                        class="ms-2 h-4 w-4"
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </button>
                            </template>

                            <template #content>
                                <DropdownLink
                                    :href="route('profile.edit', {
                                        profile: authUser.id,
                                    })"
                                >
                                    Profile
                                </DropdownLink>
                                <DropdownLink
                                    :href="route('logout')"
                                    method="post"
                                    as="button"
                                >
                                    Log Out
                                </DropdownLink>
                            </template>
                        </Dropdown>
                    </div>

                    <div v-else class="hidden items-center gap-2 sm:flex">
                        <Link
                            :href="route('login')"
                            class="rounded-md border border-slate-700 px-3 py-1.5 text-sm font-medium text-slate-200 transition hover:bg-slate-800"
                        >
                            Log In
                        </Link>
                        <Link
                            :href="route('register')"
                            class="rounded-md bg-sky-500 px-3 py-1.5 text-sm font-semibold text-slate-950 transition hover:bg-sky-400"
                        >
                            Register
                        </Link>
                    </div>

                    <div class="flex items-center sm:hidden">
                        <button
                            @click="showingNavigationDropdown = !showingNavigationDropdown"
                            class="inline-flex items-center justify-center rounded-md p-2 text-slate-300 transition hover:bg-slate-800 hover:text-white focus:outline-none"
                        >
                            <svg
                                class="h-6 w-6"
                                stroke="currentColor"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    :class="{
                                        hidden: showingNavigationDropdown,
                                        'inline-flex': !showingNavigationDropdown,
                                    }"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16"
                                />
                                <path
                                    :class="{
                                        hidden: !showingNavigationDropdown,
                                        'inline-flex': showingNavigationDropdown,
                                    }"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <div
                :class="{
                    block: showingNavigationDropdown,
                    hidden: !showingNavigationDropdown,
                }"
                class="border-t border-slate-800 bg-slate-950/95 sm:hidden"
            >
                <div class="space-y-1 px-4 py-3">
                    <ResponsiveNavLink
                        v-for="link in mainLinks"
                        :key="`mobile-${link.label}`"
                        :href="link.href"
                        :active="link.active"
                    >
                        {{ link.label }}
                    </ResponsiveNavLink>
                </div>

                <div v-if="authUser" class="border-t border-slate-800 px-4 py-3">
                    <div class="text-base font-medium text-slate-100">
                        {{ authUser.name }}
                    </div>
                    <div class="text-sm text-slate-400">
                        {{ authUser.email }}
                    </div>

                    <div class="mt-3 space-y-1">
                        <ResponsiveNavLink
                            :href="route('profile.edit', { profile: authUser.id })"
                            :active="route().current('profile.edit')"
                        >
                            Profile
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('logout')"
                            method="post"
                            as="button"
                        >
                            Log Out
                        </ResponsiveNavLink>
                    </div>
                </div>
                <div v-else class="border-t border-slate-800 px-4 py-3">
                    <div class="space-y-1">
                        <ResponsiveNavLink :href="route('login')" :active="route().current('login')">
                            Log In
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('register')" :active="route().current('register')">
                            Register
                        </ResponsiveNavLink>
                    </div>
                </div>
            </div>
        </nav>

        <header
            v-if="$slots.header"
            class="border-b border-slate-800/80 bg-slate-950/60"
        >
            <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <main>
            <slot />
        </main>
    </div>
</template>
