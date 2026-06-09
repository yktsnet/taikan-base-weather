import React from 'react';
import { Head, Link } from '@inertiajs/react';

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
