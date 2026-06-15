import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { MapContainer, TileLayer, Marker, Popup } from 'react-leaflet';
import 'leaflet/dist/leaflet.css';
import L from 'leaflet';

// Custom markers based on status using L.divIcon and SVG
const createMarkerIcon = (status) => {
    const colorClasses = {
        normal: 'text-emerald-500',
        caution: 'text-amber-500',
        warning: 'text-orange-500',
        danger: 'text-red-500',
        default: 'text-blue-500',
    };
    const colorClass = colorClasses[status] || colorClasses.default;

    // Pulse animation element is added for non-normal statuses to emphasize urgency
    const pulseHtml = status !== 'normal' ? '<div class="marker-pulse"></div>' : '';

    return L.divIcon({
        className: `custom-pin-container ${status || 'default'}`,
        html: `
            <div class="marker-pin-wrapper">
                ${pulseHtml}
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="currentColor" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="marker-pin-svg ${colorClass}">
                    <path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/>
                    <circle cx="12" cy="10" r="3" fill="white"/>
                </svg>
            </div>
        `,
        iconSize: [32, 32],
        iconAnchor: [16, 32],
        popupAnchor: [0, -32],
    });
};

const icons = {
    normal: createMarkerIcon('normal'),
    caution: createMarkerIcon('caution'),
    warning: createMarkerIcon('warning'),
    danger: createMarkerIcon('danger'),
    default: createMarkerIcon('default')
};

export default function Dashboard({ stations }) {
    const getStatusBadge = (status) => {
        const badges = {
            normal: 'bg-green-100 text-green-800',
            caution: 'bg-yellow-100 text-yellow-800',
            warning: 'bg-orange-100 text-orange-800',
            danger: 'bg-red-100 text-red-800',
        };
        const badgeClass = badges[status] || 'bg-gray-100 text-gray-800';
        return (
            <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${badgeClass}`}>
                {status || 'unknown'}
            </span>
        );
    };

    const mapCenter = stations.length > 0
        ? [stations[0].lat || 34.6937, stations[0].lng || 135.5023] // Default to Osaka if no coords
        : [34.6937, 135.5023];

    return (
        <div className="min-h-screen bg-gray-100 py-8">
            <Head title="Dashboard" />
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="flex justify-between items-center mb-6">
                    <h1 className="text-3xl font-bold text-gray-900">Dashboard</h1>
                    <Link
                        href="/alerts"
                        className="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"
                    >
                        Alert History
                    </Link>
                </div>

                {/* Map Section */}
                <div className="mb-8 bg-white overflow-hidden shadow rounded-lg p-4">
                    <h2 className="text-xl font-semibold mb-4">Observation Stations Map</h2>
                    <div className="h-96 w-full rounded-lg overflow-hidden border border-gray-300">
                        <MapContainer center={mapCenter} zoom={9} style={{ height: '100%', width: '100%' }}>
                            <TileLayer
                                attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                                url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                            />
                            {stations.map(station => {
                                if (!station.lat || !station.lng) return null;
                                const status = station.latest_water_level?.alert_status || 'default';
                                const icon = icons[status] || icons.default;
                                return (
                                    <Marker
                                        key={station.id}
                                        position={[station.lat, station.lng]}
                                        icon={icon}
                                    >
                                        <Popup>
                                            <div className="font-sans">
                                                <h3 className="font-bold text-lg mb-1">{station.name}</h3>
                                                <p className="text-sm text-gray-600 mb-2">{station.river_name}</p>
                                                <p className="text-sm mb-2">
                                                    <strong>Water Level: </strong>
                                                    {station.latest_water_level?.level_m ?? 'N/A'} m
                                                </p>
                                                <Link
                                                    href={`/stations/${station.id}`}
                                                    className="text-indigo-600 hover:text-indigo-900 text-sm font-medium"
                                                >
                                                    View Details &rarr;
                                                </Link>
                                            </div>
                                        </Popup>
                                    </Marker>
                                );
                            })}
                        </MapContainer>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    {stations.map((station) => (
                        <div key={station.id} className="bg-white overflow-hidden shadow rounded-lg">
                            <div className="px-4 py-5 sm:p-6">
                                <div className="flex items-center justify-between mb-4">
                                    <h3 className="text-lg leading-6 font-medium text-gray-900">
                                        {station.name}
                                    </h3>
                                    {getStatusBadge(station.latest_water_level?.alert_status)}
                                </div>
                                <div className="mt-2 text-sm text-gray-500">
                                    <p>River: {station.river_name}</p>
                                    <p>Prefecture: {station.prefecture}</p>
                                </div>
                                <div className="mt-4 border-t border-gray-200 pt-4">
                                    <dl className="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                                        <div className="sm:col-span-1">
                                            <dt className="text-sm font-medium text-gray-500">Water Level</dt>
                                            <dd className="mt-1 text-sm text-gray-900">
                                                {station.latest_water_level?.level_m ?? 'N/A'} m
                                            </dd>
                                        </div>
                                        <div className="sm:col-span-1">
                                            <dt className="text-sm font-medium text-gray-500">Precipitation</dt>
                                            <dd className="mt-1 text-sm text-gray-900">
                                                {station.latest_weather_record?.precipitation_mm ?? 'N/A'} mm
                                            </dd>
                                        </div>
                                        <div className="sm:col-span-1">
                                            <dt className="text-sm font-medium text-gray-500">Temperature</dt>
                                            <dd className="mt-1 text-sm text-gray-900">
                                                {station.latest_weather_record?.temperature_c ?? 'N/A'} °C
                                            </dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                            <div className="bg-gray-50 px-4 py-4 sm:px-6">
                                <Link
                                    href={`/stations/${station.id}`}
                                    className="text-indigo-600 hover:text-indigo-900 text-sm font-medium"
                                >
                                    View Details &rarr;
                                </Link>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}
