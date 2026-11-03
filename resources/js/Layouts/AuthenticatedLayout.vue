<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import { Link } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
</script>

<template>
    <div>
        <div class="min-h-screen bg-slate-50 text-slate-800 transition-colors dark:bg-slate-900 dark:text-slate-200">
            <nav class="border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="flex shrink-0 items-center">
                                <Link :href="route('dashboard')" class="flex items-center gap-2">
                                    <span class="text-xl">📓</span>
                                    <span class="text-base font-semibold tracking-tight text-slate-800 dark:text-slate-100">
                                        Personal Hub
                                    </span>
                                </Link>
                            </div>

                            <!-- Desktop Navigation -->
                            <div class="hidden space-x-1 sm:-my-px sm:ms-10 sm:flex">
                                <NavLink :href="route('capture')" :active="route().current('capture') || route().current('dashboard')">
                                    <span class="mr-1.5">📝</span>Capture
                                </NavLink>
                                <NavLink :href="route('board')" :active="route().current('board')">
                                    <span class="mr-1.5">📋</span>Board
                                </NavLink>
                                <NavLink :href="route('stream')" :active="route().current('stream')">
                                    <span class="mr-1.5">🌊</span>Stream
                                </NavLink>
                                <NavLink :href="route('review')" :active="route().current('review')">
                                    <span class="mr-1.5">📅</span>Review
                                </NavLink>
                                <NavLink :href="route('drinks')" :active="route().current('drinks')">
                                    <span class="mr-1.5">🍺</span>Drinks
                                </NavLink>
                                <NavLink :href="route('settings')" :active="route().current('settings')">
                                    <span class="mr-1.5">⚙️</span>Settings
                                </NavLink>
                            </div>
                        </div>

                        <div class="hidden sm:ms-6 sm:flex sm:items-center sm:gap-2">
                            <!-- Theme Toggle -->
                            <ThemeToggle />

                            <!-- User Dropdown -->
                            <div class="relative ms-3">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                class="inline-flex items-center rounded-lg border border-transparent px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-slate-100"
                                            >
                                                <span class="h-7 w-7 rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center text-white text-xs font-semibold mr-2">
                                                    {{ $page.props.auth.user.name?.charAt(0)?.toUpperCase() || 'U' }}
                                                </span>
                                                {{ $page.props.auth.user.name }}
                                                <svg class="-me-0.5 ms-2 h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <DropdownLink :href="route('profile.edit')">
                                            个人设置
                                        </DropdownLink>
                                        <DropdownLink :href="route('logout')" method="post" as="button">
                                            登出
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Mobile Hamburger -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <ThemeToggle />
                            <button
                                @click="showingNavigationDropdown = !showingNavigationDropdown"
                                class="ms-2 inline-flex items-center justify-center rounded-md p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-800"
                            >
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                    <path :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Mobile Menu -->
                <div
                    :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }"
                    class="sm:hidden border-t border-slate-200 dark:border-slate-800"
                >
                    <div class="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink :href="route('capture')" :active="route().current('capture') || route().current('dashboard')">
                            📝 Capture
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('board')" :active="route().current('board')">
                            📋 Board
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('stream')" :active="route().current('stream')">
                            🌊 Stream
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('review')" :active="route().current('review')">
                            📅 Review
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('drinks')" :active="route().current('drinks')">
                            🍺 Drinks
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('settings')" :active="route().current('settings')">
                            ⚙️ Settings
                        </ResponsiveNavLink>
                    </div>

                    <div class="border-t border-slate-200 dark:border-slate-800 pb-1 pt-4">
                        <div class="px-4">
                            <div class="text-base font-medium text-slate-800 dark:text-slate-100">
                                {{ $page.props.auth.user.name }}
                            </div>
                            <div class="text-sm font-medium text-slate-500 dark:text-slate-400">
                                {{ $page.props.auth.user.email }}
                            </div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')">
                                个人设置
                            </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('logout')" method="post" as="button">
                                登出
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header v-if="$slots.header" class="bg-white shadow-sm dark:bg-slate-900">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
