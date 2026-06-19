import React from 'react';
import { Head, Link } from '@inertiajs/react';

export default function AlertHistory({ alerts }) {
    const getLevelBadge = (level) => {
        const badges = {
            warning: 'bg-orange-100 text-orange-800',
            danger: 'bg-red-100 text-red-800',
        };
        const badgeNames = {
            warning: '警告',
            danger: '危険',
        };
        const badgeClass = badges[level] || 'bg-gray-100 text-gray-800';
        const badgeName = badgeNames[level] || level || '不明';
        return (
            <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${badgeClass}`}>
                {badgeName}
            </span>
        );
    };

    return (
        <div className="min-h-screen bg-gray-100 py-8">
            <Head title="アラート履歴" />
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="flex justify-between items-center mb-6">
                    <div className="flex items-center space-x-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-indigo-600">
                            <path d="M2 6c.6.5 1.2 1 2.5 1C6 7 7 6 8.5 6c1.5 0 2.5 1 4 1s2.5-1 4-1 2.5 1 4 1"/>
                            <path d="M2 12c.6.5 1.2 1 2.5 1 1.5 0 2.5-1 4-1 1.5 0 2.5 1 4 1s2.5-1 4-1 2.5 1 4 1"/>
                            <path d="M2 18c.6.5 1.2 1 2.5 1 1.5 0 2.5-1 4-1 1.5 0 2.5 1 4 1s2.5-1 4-1 2.5 1 4 1"/>
                        </svg>
                        <h1 className="text-3xl font-bold text-gray-900 flex items-center">
                            kawa-watch <span className="ml-3 text-xs font-semibold px-2.5 py-1 rounded-full bg-blue-100 text-blue-800">アラート履歴</span>
                        </h1>
                    </div>
                    <Link
                        href="/"
                        className="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition"
                    >
                        &larr; ダッシュボードへ戻る
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
                                            <span className="truncate">検知日時: {alert.triggered_at}</span>
                                        </div>
                                    </div>
                                    <div className="flex items-center space-x-4">
                                        <div className="text-sm text-gray-900">
                                            水位: {alert.level_m} m
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
                                アラート履歴はありません。
                            </li>
                        )}
                    </ul>
                </div>
            </div>
        </div>
    );
}
