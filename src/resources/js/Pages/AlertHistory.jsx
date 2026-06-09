import React from 'react';
import { Head, Link } from '@inertiajs/react';

export default function AlertHistory({ alerts }) {
    const getLevelBadge = (level) => {
        const badges = {
            warning: 'bg-orange-100 text-orange-800',
            danger: 'bg-red-100 text-red-800',
        };
        const badgeClass = badges[level] || 'bg-gray-100 text-gray-800';
        return (
            <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${badgeClass}`}>
                {level || 'unknown'}
            </span>
        );
    };

    return (
        <div className="min-h-screen bg-gray-100 py-8">
            <Head title="Alert History" />
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="mb-6 flex justify-between items-center">
                    <h1 className="text-3xl font-bold text-gray-900">Alert History</h1>
                    <Link
                        href="/"
                        className="text-indigo-600 hover:text-indigo-900 font-medium"
                    >
                        &larr; Back to Dashboard
                    </Link>
                </div>

                <div className="bg-white shadow overflow-hidden sm:rounded-md">
                    <ul className="divide-y divide-gray-200">
                        {alerts.map((alert) => (
                            <li key={alert.id}>
                                <div className="px-4 py-4 sm:px-6 flex items-center justify-between">
                                    <div className="flex flex-col">
                                        <div className="text-sm font-medium text-indigo-600 truncate">
                                            {alert.station?.name || `Station ID: ${alert.station_id}`}
                                        </div>
                                        <div className="mt-1 flex items-center text-sm text-gray-500">
                                            <span className="truncate">Triggered at: {alert.triggered_at}</span>
                                        </div>
                                    </div>
                                    <div className="flex items-center space-x-4">
                                        <div className="text-sm text-gray-900">
                                            Water Level: {alert.level_m} m
                                        </div>
                                        <div>
                                            {getLevelBadge(alert.level)}
                                        </div>
                                    </div>
                                </div>
                            </li>
                        ))}
                        {alerts.length === 0 && (
                            <li className="px-4 py-6 text-center text-sm text-gray-500">
                                No alerts found.
                            </li>
                        )}
                    </ul>
                </div>
            </div>
        </div>
    );
}
