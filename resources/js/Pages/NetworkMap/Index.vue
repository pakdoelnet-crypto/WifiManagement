<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import axios from 'axios';

const props = defineProps({
    customers: Array,
    onlineUsernames: Array,
    networkPoints: Array,
    fiberRoutes: Array,
    routers: Array,
    packages: Array,
    canManage: Boolean,
});

const onlineUsernamesList = ref([...props.onlineUsernames]);
const customersList = ref([...props.customers]);

const mapMode = ref(localStorage.getItem('network_map_mode') || 'dark');

// Layer Toggles
const showCustomers = ref(true);
const showInfrastructure = ref(true);
const showRadius = ref(true);
const showCables = ref(true);

// Search Properties
const searchQuery = ref('');
const searchResults = ref([]);
const isSearching = ref(false);

// Modals State
const isPointModalOpen = ref(false);
const isCableModalOpen = ref(false);
const isCustomerModalOpen = ref(false);

const clickLat = ref(0);
const clickLng = ref(0);

// Forms
const pointForm = useForm({
    id: null,
    type: 'odp',
    name: '',
    lat: 0,
    lng: 0,
    capacity: '',
    radius_meters: '',
    parent_id: '',
    notes: '',
});

const cableForm = useForm({
    id: null,
    from_point_id: '',
    to_point_id: '',
    length_meters: '',
});

const customerForm = useForm({
    router_id: '',
    package_id: '',
    name: '',
    phone: '',
    whatsapp: '',
    email: '',
    ktp_number: '',
    ktp_photo: null,
    photo: null,
    address: '',
    lat: 0,
    lng: 0,
    pppoe_username: '',
    pppoe_password: '',
});

// Leaflet Map state
let map = null;
let leaflet = null;
const mapMarkers = ref([]);
const mapCircles = ref([]);
const mapLines = ref([]);

const loadLeaflet = () => {
    return new Promise((resolve) => {
        if (window.L) {
            resolve(window.L);
            return;
        }
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
        document.head.appendChild(link);

        const script = document.createElement('script');
        script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        script.onload = () => {
            resolve(window.L);
        };
        document.head.appendChild(script);
    });
};

// Custom Marker Creators
const getCustomerIcon = (L, customer) => {
    const isOnline = onlineUsernamesList.value.includes(customer.pppoe_username);
    let color = '#9CA3AF'; // grey offline
    let badgeColor = 'bg-gray-400';
    let pulseBg = '';
    let shadowStyle = '';
    let containerStyle = '';

    if (mapMode.value === 'clean') {
        // Mode CLEAN: solid warna biasa tanpa glow
        if (customer.status === 'isolir' || customer.status === 'suspended') {
            color = '#EF4444'; // solid red
            badgeColor = 'bg-red-500';
        } else if (isOnline) {
            color = '#10B981'; // solid green
            badgeColor = 'bg-emerald-500';
        }
    } else {
        // Mode GELAP & HYBRID: neon glow
        if (customer.status === 'isolir' || customer.status === 'suspended') {
            color = '#EF4444'; // red isolir
            badgeColor = 'bg-red-500';
            shadowStyle = 'filter: drop-shadow(0 0 6px #EF4444) drop-shadow(0 0 12px #EF4444);';
        } else if (isOnline) {
            color = '#00E5FF'; // cyan online glow!
            badgeColor = 'bg-cyan-500';
            pulseBg = 'bg-cyan-400';
            shadowStyle = 'filter: drop-shadow(0 0 6px #00E5FF) drop-shadow(0 0 12px #00B0FF);';
        } else {
            shadowStyle = 'filter: drop-shadow(0 0 4px #9CA3AF);';
        }

        if (mapMode.value === 'hybrid') {
            // Mode HYBRID: tambahkan kontras background & outline putih di atas citra satelit
            containerStyle = 'background: rgba(15, 23, 42, 0.6); border: 1.5px solid #FFFFFF; border-radius: 9999px; padding: 2px;';
        }
    }

    // Live pulsing dot and outer ring if online
    const pulseElement = isOnline 
        ? `<span class="absolute -top-0.5 -right-0.5 flex h-2.5 w-2.5">
               <span class="animate-ping absolute inline-flex h-full w-full rounded-full ${pulseBg || 'bg-emerald-400'} opacity-75"></span>
               <span class="relative inline-flex rounded-full h-2.5 w-2.5 ${badgeColor}"></span>
           </span>`
        : `<span class="absolute -top-0.5 -right-0.5 flex h-2 w-2">
               <span class="relative inline-flex rounded-full h-2 w-2 ${badgeColor}"></span>
           </span>`;

    const svgIcon = `
    <div class="relative p-0.5 flex items-center justify-center" style="${containerStyle}">
        ${pulseElement}
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="${color}" class="w-7 h-7" style="${shadowStyle}">
            <path d="M12 21a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM12 15a4 4 0 0 0-3.8 2.8c-.1.4.1.8.5.9.4.1.8-.1.9-.5A2.5 2.5 0 0 1 12 16.5c1.1 0 2.1.7 2.4 1.7.1.4.5.6.9.5.4-.1.6-.5.5-.9A4 4 0 0 0 12 15zm0-6a10 10 0 0 0-9.2 6.1c-.2.4 0 .8.4.9.4.2.8 0 .9-.4A8.5 8.5 0 0 1 12 10.5c3.8 0 7.2 2.5 8 5.1.1.4.5.6.9.5.4-.1.6-.5.5-.9A10 10 0 0 0 12 9zm0-6a16 16 0 0 0-9.9 3.3c-.3.3-.3.8 0 1.1.3.3.8.3 1.1 0A14.5 14.5 0 0 1 12 4.5c6.5 0 12.3 4.2 13.5 10.1.1.4.5.6.9.5.4-.1.6-.5.5-.9A16 16 0 0 0 12 3z"/>
        </svg>
    </div>`;

    return L.divIcon({
        className: 'custom-customer-icon',
        html: svgIcon,
        iconSize: [36, 36],
        iconAnchor: [18, 18],
        popupAnchor: [0, -18]
    });
};

const getInfrastructureIcon = (L, type) => {
    let svgIcon = '';
    let size = [28, 28];
    let anchor = [14, 14];
    let shadowStyle = '';
    let containerStyle = '';

    if (mapMode.value === 'hybrid') {
        containerStyle = 'background: rgba(15, 23, 42, 0.65); border: 1.5px solid #FFFFFF; border-radius: 6px; padding: 2px;';
    }

    if (mapMode.value === 'clean') {
        // Mode CLEAN: solid biasa tanpa drop-shadow
        if (type === 'odc') {
            svgIcon = `
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#8B5CF6" class="w-8 h-8">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2" stroke="#FFFFFF" stroke-width="2" />
                <line x1="3" y1="9" x2="21" y2="9" stroke="#FFFFFF" stroke-width="1.5" />
                <circle cx="12" cy="15" r="2.5" fill="#FFFFFF" />
            </svg>`;
            size = [32, 32];
            anchor = [16, 16];
        } else if (type === 'odp') {
            svgIcon = `
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#3B82F6" class="w-6 h-6">
                <rect x="4" y="4" width="16" height="16" rx="2" ry="2" stroke="#FFFFFF" stroke-width="1.5" />
                <line x1="4" y1="10" x2="20" y2="10" stroke="#FFFFFF" stroke-width="1" />
                <circle cx="12" cy="14" r="1.5" fill="#FFFFFF" />
            </svg>`;
            size = [24, 24];
            anchor = [12, 12];
        } else if (type === 'tiang') {
            svgIcon = `
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-5 h-8">
                <line x1="12" y1="2" x2="12" y2="22" stroke="#F97316" stroke-width="4.5" stroke-linecap="round" />
                <circle cx="12" cy="3" r="3" fill="#FFFFFF" stroke="#F97316" stroke-width="1.5" />
            </svg>`;
            size = [20, 32];
            anchor = [10, 16];
        } else if (type === 'htb') {
            svgIcon = `
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#0D9488" class="w-6.5 h-6.5">
                <polygon points="12,2 22,12 12,22 2,12" stroke="#FFFFFF" stroke-width="1.5" />
                <circle cx="12" cy="12" r="2.5" fill="#FFFFFF" />
            </svg>`;
            size = [26, 26];
            anchor = [13, 13];
        }
    } else {
        // Mode GELAP & HYBRID: neon glow
        if (type === 'odc') {
            shadowStyle = 'filter: drop-shadow(0 0 6px #8B5CF6);';
            svgIcon = `
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#8B5CF6" class="w-8 h-8" style="${shadowStyle}">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2" stroke="#FFFFFF" stroke-width="2" />
                <line x1="3" y1="9" x2="21" y2="9" stroke="#FFFFFF" stroke-width="1.5" />
                <circle cx="12" cy="15" r="2.5" fill="#FFFFFF" />
            </svg>`;
            size = [32, 32];
            anchor = [16, 16];
        } else if (type === 'odp') {
            shadowStyle = 'filter: drop-shadow(0 0 6px #00E5FF) drop-shadow(0 0 12px #00B0FF);';
            svgIcon = `
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#00E5FF" class="w-6 h-6" style="${shadowStyle}">
                <rect x="4" y="4" width="16" height="16" rx="2" ry="2" stroke="#FFFFFF" stroke-width="1.5" />
                <line x1="4" y1="10" x2="20" y2="10" stroke="#FFFFFF" stroke-width="1" />
                <circle cx="12" cy="14" r="1.5" fill="#FFFFFF" />
            </svg>`;
            size = [24, 24];
            anchor = [12, 12];
        } else if (type === 'tiang') {
            shadowStyle = 'filter: drop-shadow(0 0 5px #F97316);';
            svgIcon = `
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-5 h-8" style="${shadowStyle}">
                <line x1="12" y1="2" x2="12" y2="22" stroke="#F97316" stroke-width="4.5" stroke-linecap="round" />
                <circle cx="12" cy="3" r="3" fill="#FFFFFF" stroke="#F97316" stroke-width="1.5" />
            </svg>`;
            size = [20, 32];
            anchor = [10, 16];
        } else if (type === 'htb') {
            shadowStyle = 'filter: drop-shadow(0 0 5px #0D9488);';
            svgIcon = `
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#0D9488" class="w-6.5 h-6.5" style="${shadowStyle}">
                <polygon points="12,2 22,12 12,22 2,12" stroke="#FFFFFF" stroke-width="1.5" />
                <circle cx="12" cy="12" r="2.5" fill="#FFFFFF" />
            </svg>`;
            size = [26, 26];
            anchor = [13, 13];
        }
    }

    return L.divIcon({
        className: `custom-infra-${type}-icon`,
        html: `<div class="flex items-center justify-center" style="${containerStyle}">${svgIcon}</div>`,
        iconSize: size,
        iconAnchor: anchor,
        popupAnchor: [0, -anchor[1]]
    });
};

// Render Layer elements
const drawMapElements = () => {
    if (!map || !leaflet) return;

    // Clear existing
    mapMarkers.value.forEach(m => map.removeLayer(m));
    mapCircles.value.forEach(c => map.removeLayer(c));
    mapLines.value.forEach(l => map.removeLayer(l));

    mapMarkers.value = [];
    mapCircles.value = [];
    mapLines.value = [];

    // 1. Draw Fiber Cable Routes & ODP to Customer drop lines
    if (showCables.value) {
        // Draw Fiber Routes
        props.fiberRoutes.forEach(route => {
            if (route.from_point && route.to_point) {
                if (mapMode.value === 'clean') {
                    // Clean mode: single clean solid-dashed line, no glow
                    const solidLine = leaflet.polyline(
                        [
                            [route.from_point.lat, route.from_point.lng],
                            [route.to_point.lat, route.to_point.lng]
                        ],
                        {
                            color: '#10B981',
                            weight: 3.5,
                            opacity: 0.85,
                            dashArray: '5, 10',
                            lineCap: 'round'
                        }
                    ).addTo(map);

                    if (props.canManage) {
                        solidLine.bindPopup(`
                            <div class="p-2">
                                <div class="font-semibold text-xs mb-2">Jalur Kabel: ${route.from_point.name} &rarr; ${route.to_point.name}</div>
                                <button onclick="window.deleteCable(${route.id})" class="px-2 py-1 bg-red-600 text-white rounded text-[10px] font-bold">Hapus Jalur</button>
                            </div>
                        `);
                    }
                    mapLines.value.push(solidLine);
                } else {
                    // Double-layer neon green fiber route glow (Dark & Hybrid)
                    const glowLine = leaflet.polyline(
                        [
                            [route.from_point.lat, route.from_point.lng],
                            [route.to_point.lat, route.to_point.lng]
                        ],
                        {
                            color: '#10B981',
                            weight: 8,
                            opacity: 0.25,
                            lineCap: 'round'
                        }
                    ).addTo(map);
                    mapLines.value.push(glowLine);

                    const solidLine = leaflet.polyline(
                        [
                            [route.from_point.lat, route.from_point.lng],
                            [route.to_point.lat, route.to_point.lng]
                        ],
                        {
                            color: '#34D399',
                            weight: 2.5,
                            opacity: 0.95,
                            dashArray: '6, 12',
                            lineCap: 'round'
                        }
                    ).addTo(map);

                    if (props.canManage) {
                        solidLine.bindPopup(`
                            <div class="p-2">
                                <div class="font-semibold text-xs mb-2">Jalur Kabel: ${route.from_point.name} &rarr; ${route.to_point.name}</div>
                                <button onclick="window.deleteCable(${route.id})" class="px-2 py-1 bg-red-600 text-white rounded text-[10px] font-bold">Hapus Jalur</button>
                            </div>
                        `);
                    }
                    solidLine.addTo(map);
                    mapLines.value.push(solidLine);
                }
            }
        });

        // Draw ODP-to-Customer drop lines
        customersList.value.forEach(cust => {
            if (cust.lat && cust.lng && cust.odp_id) {
                const odp = props.networkPoints.find(p => p.id === cust.odp_id);
                if (odp && odp.lat && odp.lng) {
                    if (mapMode.value === 'clean') {
                        // Clean mode: simple solid blue line
                        const solidLine = leaflet.polyline(
                            [
                                [cust.lat, cust.lng],
                                [odp.lat, odp.lng]
                            ],
                            {
                                color: '#3B82F6',
                                weight: 2,
                                opacity: 0.7,
                                dashArray: '4, 8',
                                lineCap: 'round'
                            }
                        ).addTo(map);
                        mapLines.value.push(solidLine);
                    } else {
                        // Dark & Hybrid modes: neon blue glow lines
                        const glowLine = leaflet.polyline(
                            [
                                [cust.lat, cust.lng],
                                [odp.lat, odp.lng]
                            ],
                            {
                                color: '#00E5FF',
                                weight: 6,
                                opacity: 0.22,
                                lineCap: 'round'
                            }
                        ).addTo(map);
                        mapLines.value.push(glowLine);

                        const solidLine = leaflet.polyline(
                            [
                                [cust.lat, cust.lng],
                                [odp.lat, odp.lng]
                            ],
                            {
                                color: '#00B0FF',
                                weight: 1.5,
                                opacity: 0.95,
                                lineCap: 'round',
                                dashArray: '3, 6'
                            }
                        ).addTo(map);
                        mapLines.value.push(solidLine);
                    }
                }
            }
        });
    }

    // 2. Draw Infrastructure Points
    if (showInfrastructure.value) {
        props.networkPoints.forEach(point => {
            const marker = leaflet.marker([point.lat, point.lng], {
                icon: getInfrastructureIcon(leaflet, point.type)
            }).addTo(map);

            let popupContent = `
                <div class="p-2 space-y-1">
                    <div class="font-bold text-sm text-gray-900">${point.name}</div>
                    <div class="text-xs uppercase font-semibold text-indigo-500">Tipe: ${point.type}</div>
                    ${point.capacity ? `<div class="text-xs">Kapasitas: ${point.capacity} Port</div>` : ''}
                    ${point.radius_meters ? `<div class="text-xs">Radius Jangkauan: ${point.radius_meters} m</div>` : ''}
                    ${point.notes ? `<div class="text-xs text-gray-500 italic mt-1">${point.notes}</div>` : ''}
            `;

            if (props.canManage) {
                popupContent += `
                    <div class="pt-2 flex gap-1">
                        <button onclick="window.editPoint(${JSON.stringify(point).replace(/"/g, '&quot;')})" class="px-2 py-1 bg-indigo-600 text-white rounded text-[10px] font-bold">Edit</button>
                        <button onclick="window.deletePoint(${point.id})" class="px-2 py-1 bg-red-600 text-white rounded text-[10px] font-bold">Hapus</button>
                    </div>
                `;
            }

            popupContent += `</div>`;
            marker.bindPopup(popupContent);
            
            // Permanent ODP text label direct on map
            if (point.type === 'odp') {
                marker.bindTooltip(point.name, {
                    permanent: true,
                    direction: 'bottom',
                    className: 'odp-label-tooltip',
                    offset: [0, 8]
                });
            }

            mapMarkers.value.push(marker);

            // Draw Radius Circle if active
            if (showRadius.value && point.radius_meters && point.radius_meters > 0) {
                let color = '#C084FC'; // purple for odc
                if (point.type === 'odp') color = '#93C5FD'; // blue for odp
                else if (point.type === 'htb') color = '#2DD4BF'; // teal for htb

                const circle = leaflet.circle([point.lat, point.lng], {
                    radius: point.radius_meters,
                    color: color,
                    fillColor: color,
                    fillOpacity: 0.15,
                    weight: 1
                }).addTo(map);
                mapCircles.value.push(circle);
            }
        });
    }

    // 3. Draw Customers
    if (showCustomers.value) {
        customersList.value.forEach(cust => {
            if (cust.lat && cust.lng) {
                const marker = leaflet.marker([cust.lat, cust.lng], {
                    icon: getCustomerIcon(leaflet, cust)
                }).addTo(map);

                const isOnline = onlineUsernamesList.value.includes(cust.pppoe_username);
                let statusBadge = '<span class="text-emerald-500 font-bold">Online</span>';
                if (cust.status === 'isolir' || cust.status === 'suspended') {
                    statusBadge = '<span class="text-red-500 font-bold">Isolir</span>';
                } else if (!isOnline) {
                    statusBadge = '<span class="text-gray-400 font-semibold">Offline</span>';
                }

                let popupContent = `
                    <div class="p-2 space-y-1">
                        <div class="font-bold text-sm text-gray-900">${cust.name}</div>
                        <div class="text-xs font-mono font-semibold">${cust.pppoe_username}</div>
                        <div class="text-xs">Paket: ${cust.package?.name || 'N/A'}</div>
                        <div class="text-xs flex items-center gap-1">Status: ${statusBadge}</div>
                        <div class="text-xs">HP/WA: ${cust.whatsapp}</div>
                        <div class="pt-2">
                            <a href="${route('customers.index')}" class="inline-block px-2.5 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-[10px] font-bold text-center no-underline">Lihat Detail CRUD</a>
                        </div>
                    </div>
                `;

                marker.bindPopup(popupContent);
                mapMarkers.value.push(marker);
            }
        });
    }
};

let activeTileLayers = [];

const initMapTileLayer = (mode) => {
    if (!map || !leaflet) return;

    // Remove existing tile layers
    activeTileLayers.forEach(layer => map.removeLayer(layer));
    activeTileLayers = [];

    // Add new tile layers based on mode
    if (mode === 'dark') {
        const darkLayer = leaflet.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; OpenStreetMap &copy; CARTO',
            subdomains: 'abcd',
            maxZoom: 20
        });
        darkLayer.addTo(map);
        activeTileLayers.push(darkLayer);
    } else if (mode === 'hybrid') {
        const baseLayer = leaflet.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: 'Tiles &copy; Esri',
            maxZoom: 19
        });
        const labelLayer = leaflet.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', {
            maxZoom: 19
        });
        baseLayer.addTo(map);
        labelLayer.addTo(map);
        activeTileLayers.push(baseLayer, labelLayer);
    } else if (mode === 'clean') {
        const cleanLayer = leaflet.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; OpenStreetMap &copy; CARTO',
            subdomains: 'abcd',
            maxZoom: 20
        });
        cleanLayer.addTo(map);
        activeTileLayers.push(cleanLayer);
    }
};

const changeMapMode = (mode) => {
    mapMode.value = mode;
    localStorage.setItem('network_map_mode', mode);
    initMapTileLayer(mode);
    triggerRedraw();
};

let searchTimeout = null;
const handleSearchInput = () => {
    clearTimeout(searchTimeout);
    if (!searchQuery.value || searchQuery.value.trim().length < 3) {
        searchResults.value = [];
        return;
    }
    searchTimeout = setTimeout(() => {
        performSearch();
    }, 600);
};

const performSearch = async () => {
    if (!searchQuery.value || searchQuery.value.trim().length < 3) return;
    isSearching.value = true;
    try {
        const response = await axios.get(`https://nominatim.openstreetmap.org/search`, {
            params: {
                format: 'json',
                q: searchQuery.value,
                limit: 5
            },
            headers: {
                'Accept-Language': 'id'
            }
        });
        searchResults.value = response.data;
    } catch (err) {
        console.error("Search error: ", err);
    } finally {
        isSearching.value = false;
    }
};

const goToLocation = (result) => {
    const lat = parseFloat(result.lat);
    const lng = parseFloat(result.lon);
    if (map) {
        map.flyTo([lat, lng], 16, {
            animate: true,
            duration: 1.5
        });
        
        // Highlight location with animation marker
        if (window.searchHighlightMarker) {
            map.removeLayer(window.searchHighlightMarker);
        }
        
        window.searchHighlightMarker = leaflet.marker([lat, lng], {
            icon: leaflet.divIcon({
                className: 'custom-search-highlight-icon',
                html: `<div class="relative w-8 h-8 flex items-center justify-center">
                    <div class="absolute w-8 h-8 bg-indigo-500 rounded-full opacity-40 animate-ping"></div>
                    <div class="w-4 h-4 bg-indigo-600 rounded-full border-2 border-white shadow-lg"></div>
                </div>`,
                iconSize: [32, 32],
                iconAnchor: [16, 16]
            })
        }).addTo(map);

        setTimeout(() => {
            if (window.searchHighlightMarker) {
                map.removeLayer(window.searchHighlightMarker);
                window.searchHighlightMarker = null;
            }
        }, 5000);
    }
    searchResults.value = [];
};

onMounted(async () => {
    leaflet = await loadLeaflet();
    await nextTick();

    // Map Init (Kepanjen default center)
    map = leaflet.map('network-map-container', { doubleClickZoom: false }).setView([-8.130000, 112.570000], 14);
    initMapTileLayer(mapMode.value);

    // Auto-fit Bounds if coordinates exist
    const bounds = [];
    props.networkPoints.forEach(p => bounds.push([p.lat, p.lng]));
    props.customers.forEach(c => {
        if (c.lat && c.lng) bounds.push([c.lat, c.lng]);
    });

    if (bounds.length > 0) {
        map.fitBounds(bounds, { padding: [50, 50], maxZoom: 16 });
    }

    // Click Map handler
    map.on('click', (e) => {
        clickLat.value = parseFloat(e.latlng.lat.toFixed(6));
        clickLng.value = parseFloat(e.latlng.lng.toFixed(6));
        
        // Show inline coordinates action popup
        leaflet.popup()
            .setLatLng(e.latlng)
            .setContent(`
                <div class="p-2 space-y-2">
                    <div class="text-[10px] font-mono text-gray-500">${clickLat.value}, ${clickLng.value}</div>
                    <div class="flex flex-col gap-1.5">
                        <button onclick="window.openAddPoint()" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-xs font-bold transition">Tambah Infrastruktur</button>
                        <button onclick="window.openAddCustomer()" class="px-3 py-1.5 bg-slate-700 hover:bg-slate-800 text-white rounded text-xs font-bold transition">Tambah Pelanggan Baru</button>
                    </div>
                </div>
            `)
            .openOn(map);
    });

    // Register Global Callback helper so inline raw html click works
    window.openAddPoint = () => {
        if (!props.canManage) {
            alert('Anda tidak memiliki akses untuk menambah infrastruktur.');
            return;
        }
        pointForm.reset();
        pointForm.lat = clickLat.value;
        pointForm.lng = clickLng.value;
        isPointModalOpen.value = true;
        map.closePopup();
    };

    window.openAddCustomer = () => {
        customerForm.reset();
        customerForm.lat = clickLat.value;
        customerForm.lng = clickLng.value;
        if (props.routers.length > 0) customerForm.router_id = props.routers[0].id;
        if (props.packages.length > 0) customerForm.package_id = props.packages[0].id;
        isCustomerModalOpen.value = true;
        map.closePopup();
    };

    window.editPoint = (point) => {
        pointForm.reset();
        pointForm.id = point.id;
        pointForm.type = point.type;
        pointForm.name = point.name;
        pointForm.lat = point.lat;
        pointForm.lng = point.lng;
        pointForm.capacity = point.capacity || '';
        pointForm.radius_meters = point.radius_meters || '';
        pointForm.parent_id = point.parent_id || '';
        pointForm.notes = point.notes || '';
        isPointModalOpen.value = true;
        map.closePopup();
    };

    window.deletePoint = async (id) => {
        if (confirm('Apakah Anda yakin ingin menghapus titik infrastruktur ini? Jalur kabel terhubung juga akan terhapus.')) {
            try {
                await axios.delete(route('network-points.destroy', id));
                router.reload();
            } catch (err) {
                alert('Gagal menghapus titik.');
            }
        }
        map.closePopup();
    };

    window.deleteCable = async (id) => {
        if (confirm('Apakah Anda yakin ingin menghapus jalur kabel ini?')) {
            try {
                await axios.delete(route('fiber-routes.destroy', id));
                router.reload();
            } catch (err) {
                alert('Gagal menghapus jalur.');
            }
        }
        map.closePopup();
    };

    drawMapElements();
});

// Update map on toggle changes
const triggerRedraw = () => {
    drawMapElements();
};

const handleCustomerKtpUpload = (e) => {
    customerForm.ktp_photo = e.target.files[0];
};

const handleCustomerPhotoUpload = (e) => {
    customerForm.photo = e.target.files[0];
};

// Form Submissions
const submitPoint = () => {
    const isEdit = pointForm.id !== null;
    const url = isEdit ? route('network-points.update', pointForm.id) : route('network-points.store');
    
    axios.post(url, pointForm.data())
        .then(response => {
            isPointModalOpen.value = false;
            router.reload();
        })
        .catch(error => {
            alert('Gagal menyimpan infrastruktur: ' + (error.response?.data?.message || 'Data input tidak valid.'));
        });
};

const submitCable = () => {
    axios.post(route('fiber-routes.store'), cableForm.data())
        .then(response => {
            isCableModalOpen.value = false;
            router.reload();
        })
        .catch(error => {
            alert('Gagal menghubungkan jalur: ' + (error.response?.data?.message || 'Data input tidak valid.'));
        });
};

const submitCustomer = () => {
    customerForm.post(route('customers.store'), {
        onSuccess: () => {
            isCustomerModalOpen.value = false;
            router.reload();
        }
    });
};

const fetchLiveMapStatus = async () => {
    try {
        const response = await axios.get(route('network-map.live'));
        onlineUsernamesList.value = response.data.onlineUsernames;
        
        response.data.customers.forEach(c => {
            const localCust = customersList.value.find(lc => lc.id === c.id);
            if (localCust) {
                localCust.status = c.status;
            }
        });
        
        drawMapElements();
    } catch (e) {
        console.error("Failed to poll map status:", e);
    }
};

let mapIntervalId = null;
onMounted(() => {
    mapIntervalId = setInterval(fetchLiveMapStatus, 15000);
});

onUnmounted(() => {
    if (mapIntervalId) clearInterval(mapIntervalId);
});
</script>

<template>
    <Head title="Peta Jaringan" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Peta Jaringan & Radius Infrastruktur
            </h2>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                
                <!-- Main Grid: Map & Controls -->
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    
                    <!-- Controls Sidebar -->
                    <div class="lg:col-span-1 bg-slate-900 border border-slate-800/80 p-6 rounded-2xl shadow-xl space-y-6 h-fit text-slate-100">
                        <div v-if="canManage">
                            <button
                                @click="cableForm.reset(); isCableModalOpen = true;"
                                class="w-full px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-sm transition shadow-md flex items-center justify-center gap-2"
                            >
                                <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                </svg>
                                Hubungkan Jalur Kabel
                            </button>
                        </div>

                        <div>
                            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Filter Layers</h3>
                            <div class="space-y-3">
                                <label class="flex items-center gap-2.5 text-xs font-bold text-slate-350 cursor-pointer hover:text-indigo-400 transition">
                                    <input type="checkbox" v-model="showCustomers" @change="triggerRedraw" class="rounded border-slate-800 bg-slate-950 text-indigo-500 focus:ring-indigo-500" />
                                    Pelanggan (Online/Offline)
                                </label>
                                <label class="flex items-center gap-2.5 text-xs font-bold text-slate-350 cursor-pointer hover:text-indigo-400 transition">
                                    <input type="checkbox" v-model="showInfrastructure" @change="triggerRedraw" class="rounded border-slate-800 bg-slate-950 text-indigo-500 focus:ring-indigo-500" />
                                    Infrastruktur (ODC/ODP/Tiang/HTB)
                                </label>
                                <label class="flex items-center gap-2.5 text-xs font-bold text-slate-355 cursor-pointer hover:text-indigo-400 transition">
                                    <input type="checkbox" v-model="showRadius" @change="triggerRedraw" class="rounded border-slate-800 bg-slate-950 text-indigo-500 focus:ring-indigo-500" />
                                    Radius Jangkauan Circle
                                </label>
                                <label class="flex items-center gap-2.5 text-xs font-bold text-slate-355 cursor-pointer hover:text-indigo-400 transition">
                                    <input type="checkbox" v-model="showCables" @change="triggerRedraw" class="rounded border-slate-800 bg-slate-950 text-indigo-500 focus:ring-indigo-500" />
                                    Jalur Kabel & Hubungan ODP
                                </label>
                            </div>
                        </div>

                        <!-- ODP List Panel (Sidebar) -->
                        <div class="pt-4 border-t border-slate-800/60">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
                                    <span class="h-1.5 w-1.5 rounded-full bg-cyan-500 animate-pulse"></span>
                                    Daftar Box ODP
                                </h3>
                                <button
                                    @click="fetchLiveMapStatus"
                                    class="p-1.5 bg-slate-850 hover:bg-slate-800 border border-slate-800 hover:border-cyan-500/30 text-cyan-400 rounded-lg text-xs font-bold transition flex items-center gap-1 shadow-sm"
                                    title="Refresh Status Realtime"
                                >
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89H18" />
                                    </svg>
                                </button>
                            </div>
                            
                            <div class="max-h-56 overflow-y-auto space-y-2 pr-1 select-none scrollbar-thin scrollbar-thumb-slate-800 scrollbar-track-transparent">
                                <div
                                    v-for="odp in props.networkPoints.filter(p => p.type === 'odp')"
                                    :key="odp.id"
                                    @click="map.setView([odp.lat, odp.lng], 17)"
                                    class="p-2.5 bg-slate-950/60 border border-slate-800 hover:border-cyan-500/40 rounded-xl cursor-pointer transition flex justify-between items-center group"
                                >
                                    <div>
                                        <div class="text-[11px] font-bold text-slate-200 group-hover:text-cyan-400 font-mono transition duration-150">{{ odp.name }}</div>
                                        <div class="text-[8px] text-slate-500 font-mono">{{ odp.lat.toFixed(6) }}, {{ odp.lng.toFixed(6) }}</div>
                                    </div>
                                    <div class="text-[9px] font-extrabold text-cyan-400 bg-cyan-950/30 border border-cyan-500/10 px-2 py-0.5 rounded-md font-mono">
                                        {{ odp.capacity || 8 }} Port
                                    </div>
                                </div>
                                <div v-if="props.networkPoints.filter(p => p.type === 'odp').length === 0" class="text-center text-[10px] text-slate-500 py-4">
                                    Tidak ada ODP terdaftar
                                </div>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-slate-800/60">
                            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Legenda Peta</h3>
                            <div class="space-y-2.5 text-[10px] text-slate-400 font-medium">
                                <div class="flex items-center gap-2">
                                    <div class="h-2.5 w-2.5 rounded-full bg-cyan-400 shadow-[0_0_6px_#00e5ff]"></div>
                                    <span>Pelanggan Online (Glowing Cyan)</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="h-2.5 w-2.5 rounded-full bg-slate-550"></div>
                                    <span>Pelanggan Offline</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="h-2.5 w-2.5 rounded-full bg-rose-500 shadow-[0_0_6px_#ef4444]"></div>
                                    <span>Pelanggan Isolir (Glowing Red)</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="h-2.5 w-2.5 rounded-sm bg-purple-500 shadow-[0_0_6px_#8b5cf6]"></div>
                                    <span>ODC Box (Purple Glow)</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="h-2.5 w-2.5 rounded-sm bg-cyan-450 shadow-[0_0_6px_#00e5ff]"></div>
                                    <span>ODP Box (Cyan Glow)</span>
                                </div>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-slate-800/60 text-[10px] text-slate-500 leading-relaxed font-medium">
                            <svg class="h-3.5 w-3.5 inline mr-1 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Klik di area kosong peta untuk menambah ODP / Pelanggan secara instan.
                        </div>
                    </div>

                    <!-- Map Container -->
                    <div class="lg:col-span-3 bg-slate-900 border border-slate-800/80 rounded-2xl shadow-xl p-2 overflow-hidden h-[600px] relative">
                        <!-- Search Box Overlay Panel -->
                        <div class="absolute top-4 right-4 z-[1000] w-72 bg-white/95 dark:bg-gray-900/95 backdrop-blur-md p-3 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 space-y-2">
                            <div class="flex gap-2">
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Cari lokasi/alamat..."
                                    @input="handleSearchInput"
                                    @keyup.enter="performSearch"
                                    class="flex-1 text-xs rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm px-2.5 py-1.5"
                                />
                                <button
                                    @click="performSearch"
                                    class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md text-xs font-bold transition flex items-center justify-center"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </button>
                            </div>
                            
                            <!-- Search Results Dropdown -->
                            <div v-if="searchResults.length > 0" class="max-h-48 overflow-y-auto divide-y divide-gray-150 dark:divide-gray-800 text-xs bg-white dark:bg-gray-900 rounded-md border border-gray-200 dark:border-gray-700 shadow-inner">
                                <button
                                    v-for="result in searchResults"
                                    :key="result.place_id"
                                    @click="goToLocation(result)"
                                    class="w-full text-left px-3 py-2 hover:bg-slate-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 transition truncate block"
                                    :title="result.display_name"
                                >
                                    {{ result.display_name }}
                                </button>
                            </div>
                            <div v-else-if="isSearching" class="text-xs text-gray-400 px-3 py-1 flex items-center gap-1.5 justify-center">
                                <svg class="animate-spin h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Mencari lokasi...
                            </div>
                        </div>

                        <!-- Map Mode Switcher Pill Control -->
                        <div class="absolute top-4 left-4 z-[1000] flex gap-1.5 bg-slate-950/80 border border-slate-800 p-1 rounded-full shadow-lg backdrop-blur-md">
                            <button
                                type="button"
                                @click="changeMapMode('dark')"
                                :class="mapMode === 'dark' ? 'bg-amber-500 text-slate-950 font-black shadow-md' : 'text-slate-400 hover:text-slate-200'"
                                class="px-3.5 py-1 text-[9px] font-extrabold uppercase rounded-full tracking-wider transition-all duration-200"
                            >
                                Gelap
                            </button>
                            <button
                                type="button"
                                @click="changeMapMode('hybrid')"
                                :class="mapMode === 'hybrid' ? 'bg-amber-500 text-slate-950 font-black shadow-md' : 'text-slate-400 hover:text-slate-200'"
                                class="px-3.5 py-1 text-[9px] font-extrabold uppercase rounded-full tracking-wider transition-all duration-200"
                            >
                                Hybrid
                            </button>
                            <button
                                type="button"
                                @click="changeMapMode('clean')"
                                :class="mapMode === 'clean' ? 'bg-amber-500 text-slate-950 font-black shadow-md' : 'text-slate-400 hover:text-slate-200'"
                                class="px-3.5 py-1 text-[9px] font-extrabold uppercase rounded-full tracking-wider transition-all duration-200"
                            >
                                Clean
                            </button>
                        </div>

                        <div id="network-map-container" class="w-full h-full rounded-lg z-10"></div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Add/Edit Point Modal -->
        <Modal :show="isPointModalOpen" @close="isPointModalOpen = false" max-width="md">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-6">
                    {{ pointForm.id ? 'Edit Titik Infrastruktur' : 'Tambah Titik Infrastruktur' }}
                </h3>

                <form @submit.prevent="submitPoint" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="pt_type" value="Tipe Infrastruktur" />
                            <select
                                id="pt_type"
                                v-model="pointForm.type"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                                required
                            >
                                <option value="odc">ODC (Distribution Center)</option>
                                <option value="odp">ODP (Access Point)</option>
                                <option value="tiang">Tiang Distribusi</option>
                                <option value="htb">HTB Media Converter</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel for="pt_name" value="Nama Titik" />
                            <TextInput id="pt_name" v-model="pointForm.name" type="text" class="mt-1 block w-full" required placeholder="Contoh: ODP-SUJITO-01" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="pt_lat" value="Latitude" />
                            <TextInput id="pt_lat" v-model="pointForm.lat" type="number" step="0.000001" class="mt-1 block w-full" required readonly />
                        </div>
                        <div>
                            <InputLabel for="pt_lng" value="Longitude" />
                            <TextInput id="pt_lng" v-model="pointForm.lng" type="number" step="0.000001" class="mt-1 block w-full" required readonly />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="pt_capacity" value="Kapasitas Port (Opsional)" />
                            <TextInput id="pt_capacity" v-model="pointForm.capacity" type="number" class="mt-1 block w-full" placeholder="Contoh: 8 atau 16" />
                        </div>
                        <div>
                            <InputLabel for="pt_radius" value="Radius Jangkauan (Meter)" />
                            <TextInput id="pt_radius" v-model="pointForm.radius_meters" type="number" class="mt-1 block w-full" placeholder="Contoh: 150" />
                        </div>
                    </div>

                    <div>
                        <InputLabel for="pt_parent" value="Parent Infrastruktur (Untuk ODP/HTB &rarr; ODC)" />
                        <select
                            id="pt_parent"
                            v-model="pointForm.parent_id"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                        >
                            <option value="">Tidak ada parent</option>
                            <option v-for="pt in networkPoints.filter(p => p.id !== pointForm.id)" :key="pt.id" :value="pt.id">
                                {{ pt.name }} ({{ pt.type.toUpperCase() }})
                            </option>
                        </select>
                    </div>

                    <div>
                        <InputLabel for="pt_notes" value="Catatan / Notes" />
                        <textarea
                            id="pt_notes"
                            v-model="pointForm.notes"
                            rows="2"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                            placeholder="Detail lokasi tiang/port..."
                        ></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <SecondaryButton @click="isPointModalOpen = false">Batal</SecondaryButton>
                        <PrimaryButton>Simpan</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Connect Fiber Route Modal -->
        <Modal :show="isCableModalOpen" @close="isCableModalOpen = false" max-width="md">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-6">
                    Hubungkan Jalur Kabel Fiber Optik
                </h3>

                <form @submit.prevent="submitCable" class="space-y-4">
                    <div>
                        <InputLabel for="cb_from" value="Dari Titik (Infrastruktur)" />
                        <select
                            id="cb_from"
                            v-model="cableForm.from_point_id"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                            required
                        >
                            <option value="">-- Pilih Titik Asal --</option>
                            <option v-for="pt in networkPoints" :key="pt.id" :value="pt.id">
                                {{ pt.name }} ({{ pt.type.toUpperCase() }})
                            </option>
                        </select>
                    </div>

                    <div>
                        <InputLabel for="cb_to" value="Ke Titik (Infrastruktur)" />
                        <select
                            id="cb_to"
                            v-model="cableForm.to_point_id"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                            required
                        >
                            <option value="">-- Pilih Titik Tujuan --</option>
                            <option v-for="pt in networkPoints.filter(p => p.id != cableForm.from_point_id)" :key="pt.id" :value="pt.id">
                                {{ pt.name }} ({{ pt.type.toUpperCase() }})
                            </option>
                        </select>
                    </div>

                    <div>
                        <InputLabel for="cb_length" value="Panjang Kabel / Jarak Estimasi (Meter, Opsional)" />
                        <TextInput id="cb_length" v-model="cableForm.length_meters" type="number" class="mt-1 block w-full" placeholder="Contoh: 120" />
                    </div>

                    <div class="flex justify-end gap-2 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <SecondaryButton @click="isCableModalOpen = false">Batal</SecondaryButton>
                        <PrimaryButton>Simpan</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Fast Add Customer Modal -->
        <Modal :show="isCustomerModalOpen" @close="isCustomerModalOpen = false" max-width="4xl">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-6">
                    Tambah Pelanggan Baru di Koordinat Terpilih
                </h3>

                <form @submit.prevent="submitCustomer" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Left column -->
                    <div class="space-y-4">
                        <div>
                            <InputLabel for="cust_name" value="Nama Lengkap" />
                            <TextInput id="cust_name" v-model="customerForm.name" type="text" class="mt-1 block w-full" required />
                            <InputError :message="customerForm.errors.name" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="cust_phone" value="No. Telepon / HP" />
                                <TextInput id="cust_phone" v-model="customerForm.phone" type="text" class="mt-1 block w-full" required />
                                <InputError :message="customerForm.errors.phone" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="cust_whatsapp" value="WhatsApp" />
                                <TextInput id="cust_whatsapp" v-model="customerForm.whatsapp" type="text" class="mt-1 block w-full" required />
                                <InputError :message="customerForm.errors.whatsapp" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="cust_email" value="Email (Opsional)" />
                                <TextInput id="cust_email" v-model="customerForm.email" type="email" class="mt-1 block w-full" />
                                <InputError :message="customerForm.errors.email" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="cust_ktp_number" value="Nomor KTP (Opsional)" />
                                <TextInput id="cust_ktp_number" v-model="customerForm.ktp_number" type="text" class="mt-1 block w-full" />
                                <InputError :message="customerForm.errors.ktp_number" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="cust_ktp_photo" value="Upload Foto KTP" />
                                <input
                                    id="cust_ktp_photo"
                                    type="file"
                                    accept="image/*"
                                    @change="handleCustomerKtpUpload"
                                    class="mt-1 block w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-slate-100 dark:file:bg-slate-800 dark:file:text-slate-350 file:text-slate-700 hover:file:bg-slate-200"
                                />
                                <InputError :message="customerForm.errors.ktp_photo" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="cust_photo" value="Upload Foto Pelanggan" />
                                <input
                                    id="cust_photo"
                                    type="file"
                                    accept="image/*"
                                    @change="handleCustomerPhotoUpload"
                                    class="mt-1 block w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-slate-100 dark:file:bg-slate-800 dark:file:text-slate-350 file:text-slate-700 hover:file:bg-slate-200"
                                />
                                <InputError :message="customerForm.errors.photo" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="cust_address" value="Alamat Lengkap" />
                            <textarea
                                id="cust_address"
                                v-model="customerForm.address"
                                rows="2"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                                required
                            ></textarea>
                            <InputError :message="customerForm.errors.address" class="mt-2" />
                        </div>
                    </div>

                    <!-- Right column -->
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="cust_router_id" value="Router MikroTik" />
                                <select
                                    id="cust_router_id"
                                    v-model="customerForm.router_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                                    required
                                >
                                    <option v-for="routerItem in routers" :key="routerItem.id" :value="routerItem.id">
                                        {{ routerItem.name }}
                                    </option>
                                </select>
                                <InputError :message="customerForm.errors.router_id" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="cust_package_id" value="Paket Internet" />
                                <select
                                    id="cust_package_id"
                                    v-model="customerForm.package_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                                    required
                                >
                                    <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">
                                        {{ pkg.name }}
                                    </option>
                                </select>
                                <InputError :message="customerForm.errors.package_id" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="cust_username" value="PPPoE Username" />
                                <TextInput id="cust_username" v-model="customerForm.pppoe_username" type="text" class="mt-1 block w-full font-mono" required />
                                <InputError :message="customerForm.errors.pppoe_username" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="cust_password" value="PPPoE Password" />
                                <TextInput id="cust_password" v-model="customerForm.pppoe_password" type="text" class="mt-1 block w-full font-mono" required />
                                <InputError :message="customerForm.errors.pppoe_password" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel value="Selected Latitude" />
                                <TextInput :model-value="customerForm.lat" type="number" class="mt-1 block w-full font-mono bg-gray-50 dark:bg-gray-900" readonly />
                            </div>
                            <div>
                                <InputLabel value="Selected Longitude" />
                                <TextInput :model-value="customerForm.lng" type="number" class="mt-1 block w-full font-mono bg-gray-50 dark:bg-gray-900" readonly />
                            </div>
                        </div>
                    </div>

                    <div class="col-span-1 lg:col-span-2 flex justify-end gap-2 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <SecondaryButton @click="isCustomerModalOpen = false" :disabled="customerForm.processing">Batal</SecondaryButton>
                        <PrimaryButton :disabled="customerForm.processing">Tambah Pelanggan</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<style>
/* Push Leaflet top-left controls down below map mode switcher */
.leaflet-top.leaflet-left {
    margin-top: 54px !important;
}

.odp-label-tooltip {
    background-color: rgba(17, 24, 39, 0.9) !important;
    border: 1px solid rgba(75, 85, 99, 0.5) !important;
    color: #f3f4f6 !important;
    font-weight: 700 !important;
    font-size: 10px !important;
    padding: 2px 6px !important;
    border-radius: 4px !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2) !important;
    white-space: nowrap !important;
}

/* Remove default tooltip triangle pointer arrow to look clean */
.odp-label-tooltip::before {
    display: none !important;
}
</style>
