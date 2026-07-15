<script setup>
import { ref, onMounted, computed } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const showingNavigationDropdown = ref(false);
const isDark = ref(false);
const sidebarOpen = ref(false);

const toggleDarkMode = () => {
    isDark.value = !isDark.value;
    if (isDark.value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
};

const realTimeClock = ref('');

const updateClock = () => {
    const now = new Date();
    const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    
    const dayName = days[now.getDay()];
    const day = now.getDate();
    const monthName = months[now.getMonth()];
    const year = now.getFullYear();
    
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    
    realTimeClock.value = `${dayName}, ${day} ${monthName} ${year} - ${hours}:${minutes}:${seconds}`;
};

onMounted(() => {
    if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        isDark.value = true;
        document.documentElement.classList.add('dark');
    } else {
        isDark.value = false;
        document.documentElement.classList.remove('dark');
    }

    updateClock();
    setInterval(updateClock, 1000);
});

const sidebarCategories = computed(() => {
    const roles = page.props.auth.user.roles || [];
    const permissions = page.props.auth.user.permissions || [];

    const hasLogAccess = roles.some(r => ['Super Admin', 'Owner', 'Admin'].includes(r));
    const canViewInvoices = permissions.includes('invoices.view') || roles.includes('Super Admin');
    const canManageRoles = permissions.includes('roles.manage') || roles.includes('Super Admin');

    const categories = [
        {
            title: 'PELANGGAN',
            items: [
                {
                    name: 'Manajemen Pelanggan',
                    route: route('customers.index'),
                    active: route().current('customers.*'),
                    icon: `<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>`
                },
                {
                    name: 'Pelanggan Online',
                    route: route('online-customers.index'),
                    active: route().current('online-customers.*'),
                    icon: `<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>`
                },
                {
                    name: 'Riwayat Koneksi',
                    route: route('connection-history.index'),
                    active: route().current('connection-history.index'),
                    icon: `<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`
                }
            ]
        },
        {
            title: 'JARINGAN',
            items: [
                {
                    name: 'Dashboard NOC',
                    route: route('noc.index'),
                    active: route().current('noc.*'),
                    icon: `<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>`
                },
                {
                    name: 'Monitoring Trafik',
                    route: route('traffic.index'),
                    active: route().current('traffic.*'),
                    icon: `<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21h8a2 2 0 002-2v-9a2 2 0 00-2-2H8a2 2 0 00-2 2v9a2 2 0 002 2z" /></svg>`
                },
                {
                    name: 'Dashboard SLA',
                    route: route('sla.index'),
                    active: route().current('sla.*'),
                    icon: `<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`
                },
                {
                    name: 'Manajemen Router',
                    route: route('routers.index'),
                    active: route().current('routers.*'),
                    icon: `<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" /></svg>`
                },
                {
                    name: 'Paket Bandwidth',
                    route: route('packages.index'),
                    active: route().current('packages.*'),
                    icon: `<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>`
                },
                {
                    name: 'Peta Jaringan',
                    route: route('network-map.index'),
                    active: route().current('network-map.*'),
                    icon: `<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" /></svg>`
                },
                {
                    name: 'Secret PPPoE',
                    route: route('ppp-secrets.index'),
                    active: route().current('ppp-secrets.*'),
                    icon: `<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>`
                },
                {
                    name: 'Manajemen ODP',
                    route: route('odp.index'),
                    active: route().current('odp.*'),
                    icon: `<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>`
                }
            ]
        },
        {
            title: 'KEUANGAN',
            items: canViewInvoices ? [
                {
                    name: 'Invoice',
                    route: route('invoices.index'),
                    active: route().current('invoices.*'),
                    icon: `<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>`
                },
                {
                    name: 'Keuangan & Ledger',
                    route: route('finance.index'),
                    active: route().current('finance.*'),
                    icon: `<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`
                }
            ] : []
        },
        {
            title: 'OPERASIONAL',
            items: [
                ...(hasLogAccess ? [
                    {
                        name: 'Log Aktivitas',
                        route: route('audit-logs.index'),
                        active: route().current('audit-logs.*'),
                        icon: `<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`
                    }
                ] : []),
                {
                    name: 'Gangguan (Tickets)',
                    route: route('tickets.index'),
                    active: route().current('tickets.*'),
                    icon: `<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>`
                },
                {
                    name: 'Inventori & Stok',
                    route: route('inventory.index'),
                    active: route().current('inventory.*'),
                    icon: `<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>`
                },
                {
                    name: 'WhatsApp Center',
                    route: route('whatsapp.index'),
                    active: route().current('whatsapp.*'),
                    icon: `<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>`
                },
                ...(hasLogAccess ? [
                    {
                        name: 'Backup & Restore',
                        route: route('backups.index'),
                        active: route().current('backups.*'),
                        icon: `<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" /></svg>`
                    }
                ] : [])
            ]
        },
        {
            title: 'PENGATURAN',
            items: canManageRoles ? [
                {
                    name: 'Manajemen Role',
                    route: route('roles.index'),
                    active: route().current('roles.*'),
                    icon: `<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>`
                },
                {
                    name: 'Manajemen Staf',
                    route: route('users.index'),
                    active: route().current('users.*'),
                    icon: `<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>`
                }
            ] : []
        }
    ];

    return categories.filter(c => c.items.length > 0);
});

const menuItems = computed(() => {
    const list = [
        {
            name: 'Dashboard',
            route: route('dashboard'),
            active: route().current('dashboard')
        }
    ];
    sidebarCategories.value.forEach(c => {
        list.push(...c.items);
    });
    return list;
});

const currentTitle = computed(() => {
    const activeItem = menuItems.value.find(item => item.active);
    return activeItem ? activeItem.name : 'Dashboard';
});
</script>

<template>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900 flex transition-colors duration-300">
        <!-- Sidebar Navigation (Desktop) -->
        <aside :class="[sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0']" class="fixed inset-y-0 left-0 w-64 md:static md:w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700/50 flex flex-col z-40 transition-transform duration-300 md:transform-none">
            <!-- Brand Logo -->
            <div class="h-16 px-6 flex items-center border-b border-gray-200 dark:border-gray-700/50 gap-3 shrink-0">
                <Link :href="route('dashboard')" class="flex items-center gap-2">
                    <ApplicationLogo class="h-8 w-8 rounded-full object-cover shrink-0" />
                    <span class="text-md font-bold tracking-wider bg-gradient-to-r from-indigo-600 to-emerald-600 dark:from-indigo-400 dark:to-emerald-400 bg-clip-text text-transparent">
                        PAK DOEL NET
                    </span>
                </Link>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 px-4 py-6 space-y-4 overflow-y-auto">
                <!-- Dashboard (Always Top Standalone) -->
                <div>
                    <Link
                        :href="route('dashboard')"
                        :class="[
                            route().current('dashboard')
                                ? 'bg-indigo-600 text-white font-semibold shadow-md shadow-indigo-600/10'
                                : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/40 hover:text-gray-900 dark:hover:text-white'
                        ]"
                        class="flex items-center px-4 py-2.5 rounded-xl text-sm transition group"
                    >
                        <span
                            class="mr-3 shrink-0 transition-colors"
                            :class="[route().current('dashboard') ? 'text-white' : 'text-gray-400 dark:text-gray-500 group-hover:text-indigo-500']"
                            v-html="`<svg class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' /></svg>`"
                        ></span>
                        Dashboard
                    </Link>
                </div>

                <!-- Grouped Categories -->
                <div v-for="cat in sidebarCategories" :key="cat.title" class="space-y-1.5 pt-1">
                    <!-- Category Section Header with Line -->
                    <div class="flex items-center px-4 py-1 text-[10px] font-black tracking-widest text-gray-400 dark:text-gray-500 uppercase select-none">
                        <span class="mr-2 shrink-0">{{ cat.title }}</span>
                        <div class="flex-1 border-t border-gray-150 dark:border-gray-700/40 opacity-70"></div>
                    </div>
                    
                    <!-- Category Menu Items -->
                    <Link
                        v-for="item in cat.items"
                        :key="item.name"
                        :href="item.route"
                        :class="[
                            item.active
                                ? 'bg-indigo-600 text-white font-semibold shadow-md shadow-indigo-600/10'
                                : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/40 hover:text-gray-900 dark:hover:text-white'
                        ]"
                        class="flex items-center px-4 py-2.5 rounded-xl text-sm transition group"
                    >
                        <span
                            class="mr-3 shrink-0 transition-colors"
                            :class="[item.active ? 'text-white' : 'text-gray-400 dark:text-gray-500 group-hover:text-indigo-500']"
                            v-html="item.icon"
                        ></span>
                        {{ item.name }}
                    </Link>
                </div>
            </nav>

            <!-- User Info Sidebar Footer -->
            <div class="p-4 border-t border-gray-200 dark:border-gray-700/50 flex flex-col gap-2.5 shrink-0 bg-gray-50/50 dark:bg-gray-800/20">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-bold shadow-inner">
                        {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-semibold text-gray-800 dark:text-gray-200 truncate">{{ $page.props.auth.user.name }}</div>
                        <div class="text-xs text-gray-400 truncate capitalize">{{ $page.props.auth.user.roles[0] || 'Operator' }}</div>
                    </div>
                </div>
                <div class="pt-2 border-t border-gray-150 dark:border-gray-700/40 text-center text-[10px] text-gray-400 dark:text-gray-500 font-semibold tracking-wide">
                    <div>© 2026 PAK DOEL NET</div>
                    <div class="text-[9px] font-normal opacity-75 mt-0.5">v{{ $page.props.appVersion }}</div>
                </div>
            </div>
        </aside>

        <!-- Sidebar Mobile Backdrop overlay -->
        <div
            v-if="sidebarOpen"
            @click="sidebarOpen = false"
            class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-35 md:hidden transition-opacity duration-300"
        ></div>

        <!-- Main Workspace Area -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top Header Navbar -->
            <header class="h-16 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700/50 px-4 sm:px-6 lg:px-8 flex items-center justify-between shrink-0 sticky top-0 z-30 transition-colors duration-300">
                <!-- Left Side: Mobile Toggle & Breadcrumbs -->
                <div class="flex items-center gap-4">
                    <button
                        @click="sidebarOpen = !sidebarOpen"
                        class="md:hidden p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700"
                    >
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <!-- Breadcrumbs -->
                    <div class="hidden sm:flex items-center space-x-2 text-xs font-semibold text-gray-400 dark:text-gray-500">
                        <span>Portal Utama</span>
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                        <span class="text-gray-700 dark:text-gray-200 font-bold">{{ currentTitle }}</span>
                    </div>
                </div>

                <!-- Right Side: Clock, Theme, Settings -->
                <div class="flex items-center space-x-3">
                    <!-- Real-time Clock Component -->
                    <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 font-mono bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-700/50 px-3 py-1.5 rounded-lg mr-2 transition-all">
                        {{ realTimeClock }}
                    </div>

                    <!-- Dark Mode Toggle -->
                    <button
                        @click="toggleDarkMode"
                        class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 focus:outline-none transition-colors"
                    >
                        <svg v-if="isDark" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M14 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                    </button>

                    <!-- Settings Dropdown -->
                    <div class="relative ms-3">
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <span class="inline-flex rounded-md">
                                    <button
                                        type="button"
                                        class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none dark:bg-gray-800 dark:text-gray-400 dark:hover:text-gray-300"
                                    >
                                        {{ $page.props.auth.user.name }}
                                        <svg
                                            class="-me-0.5 ms-2 h-4 w-4"
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
                                </span>
                            </template>

                            <template #content>
                                <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>
                                <DropdownLink :href="route('logout')" method="post" as="button">Log Out</DropdownLink>
                            </template>
                        </Dropdown>
                    </div>
                </div>
            </header>

            <!-- Page Specific Header (Slot) -->
            <header class="bg-white shadow-sm dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700/50" v-if="$slots.header">
                <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Main Page Content Frame -->
            <main class="flex-1 overflow-y-auto">
                <slot />
            </main>
        </div>
    </div>
</template>
