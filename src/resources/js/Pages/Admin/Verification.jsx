import React, { useState, useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import axios from 'axios';

export default function Verification() {
    const [loadCount, setLoadCount] = useState(1000);
    const [isTriggering, setIsTriggering] = useState(false);
    const [triggerMessage, setTriggerMessage] = useState('');
    const [metrics, setMetrics] = useState({
        water_queue: { pending: 0, in_flight: 0 },
        weather_queue: { pending: 0, in_flight: 0 },
        dlq: { pending: 0, in_flight: 0 },
        db_records: { water_levels_count_5m: 0, weather_records_count_5m: 0 }
    });
    const [isLoadingMetrics, setIsLoadingMetrics] = useState(true);

    // Fetch metrics from the API
    const fetchMetrics = async () => {
        try {
            const response = await axios.get('/admin/api/metrics');
            setMetrics(response.data);
            setIsLoadingMetrics(false);
        } catch (error) {
            console.error('Failed to fetch metrics', error);
        }
    };

    // Set up polling
    useEffect(() => {
        fetchMetrics();
        const interval = setInterval(fetchMetrics, 2000); // Poll every 2 seconds
        return () => clearInterval(interval);
    }, []);

    // Handle load test trigger
    const handleLoadTest = async (e) => {
        e.preventDefault();
        setIsTriggering(true);
        setTriggerMessage('');

        try {
            const response = await axios.post('/admin/api/load-test', { count: loadCount });
            setTriggerMessage(response.data.message);
            // Instantly fetch metrics after triggering
            fetchMetrics();
        } catch (error) {
            setTriggerMessage('負荷シミュレーションの起動に失敗しました。');
            console.error(error);
        } finally {
            setIsTriggering(false);
            // Clear message after 5 seconds
            setTimeout(() => setTriggerMessage(''), 5000);
        }
    };

    return (
        <div className="min-h-screen bg-gray-100 py-8 text-gray-900 font-sans">
            <Head title="検証モード" />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                {/* Header (Aligned with Dashboard layout) */}
                <div className="flex justify-between items-center mb-6">
                    <div className="flex items-center space-x-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-indigo-600">
                            <path d="M2 6c.6.5 1.2 1 2.5 1C6 7 7 6 8.5 6c1.5 0 2.5 1 4 1s2.5-1 4-1 2.5 1 4 1"/>
                            <path d="M2 12c.6.5 1.2 1 2.5 1 1.5 0 2.5-1 4-1 1.5 0 2.5 1 4 1s2.5-1 4-1 2.5 1 4 1"/>
                            <path d="M2 18c.6.5 1.2 1 2.5 1 1.5 0 2.5-1 4-1 1.5 0 2.5 1 4 1s2.5-1 4-1 2.5 1 4 1"/>
                        </svg>
                        <h1 className="text-3xl font-bold text-gray-900 flex items-center">
                            kawa-watch <span className="ml-3 text-xs font-semibold px-2.5 py-1 rounded-full bg-indigo-100 text-indigo-800">管理者検証パネル</span>
                        </h1>
                    </div>
                    <div className="flex space-x-3">
                        <Link
                            href="/"
                            className="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition"
                        >
                            一般ダッシュボード
                        </Link>
                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            className="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 transition cursor-pointer"
                        >
                            ログアウト
                        </Link>
                    </div>
                </div>
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 w-full">
                    
                    {/* Column 1: Load Test Controller */}
                    <div className="lg:col-span-1 bg-white shadow rounded-lg border border-gray-200 p-6 flex flex-col justify-between w-full">
                        <div>
                            <h2 className="text-lg font-bold text-gray-900 border-b border-gray-200 pb-3 mb-4 flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" className="text-amber-500 mr-2 flex-shrink-0">
                                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                                </svg>
                                負荷シミュレーター
                            </h2>
                            <p className="text-sm text-gray-500 mb-6 leading-relaxed">
                                大規模な水位・気象観測イベントを擬似的に生成し、一括で SQS キューへ投入します。最適化されたバルクワーカーの処理能力を測定可能です。
                            </p>

                            <form onSubmit={handleLoadTest} className="space-y-6">
                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-3">
                                        投入メッセージ数 (件)
                                    </label>
                                    <div className="grid grid-cols-3 gap-2">
                                        {[1000, 5000, 10000].map((num) => (
                                            <button
                                                key={num}
                                                type="button"
                                                onClick={() => setLoadCount(num)}
                                                className={`py-2 px-3 text-xs font-semibold rounded-md border transition cursor-pointer ${
                                                    loadCount === num
                                                        ? 'bg-indigo-600 text-white border-indigo-600'
                                                        : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                                                }`}
                                            >
                                                {num.toLocaleString()} 件
                                            </button>
                                        ))}
                                    </div>
                                </div>

                                <button
                                    type="submit"
                                    disabled={isTriggering}
                                    className="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-md shadow-sm text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition cursor-pointer"
                                >
                                    {isTriggering ? '処理送信中...' : '負荷シミュレーション開始'}
                                </button>
                            </form>

                            {triggerMessage && (
                                <div className="mt-4 p-3 bg-green-50 border border-green-200 rounded-md text-xs text-green-800 animate-fade-in-down">
                                    {triggerMessage}
                                </div>
                            )}
                        </div>

                        <div className="mt-6 pt-6 border-t border-gray-100 text-xs text-gray-400">
                            ※投入されたメッセージは、バックグラウンドの BulkQueueWorker によって自動的に一括デキュー・保存処理されます。
                        </div>
                    </div>

                    {/* Column 2: SQS Monitoring */}
                    <div className="lg:col-span-1 bg-white shadow rounded-lg border border-gray-200 p-6 w-full">
                        <h2 className="text-lg font-bold text-gray-900 border-b border-gray-200 pb-3 mb-4 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" className="text-blue-500 mr-2 flex-shrink-0">
                                <polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/>
                                <path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>
                            </svg>
                            SQS キュー監視
                        </h2>
                        <p className="text-sm text-gray-500 mb-6 leading-relaxed">
                            AWS SQS のキュー属性（未処理件数および処理中のメッセージ件数）を動的に監視します。
                        </p>

                        {isLoadingMetrics ? (
                            <div className="flex justify-center items-center py-12">
                                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                            </div>
                        ) : (
                            <div className="space-y-6">
                                {/* Water Level Queue */}
                                <div className="p-4 bg-gray-50 rounded-lg border border-gray-150">
                                    <h3 className="text-sm font-bold text-gray-800 mb-3 flex justify-between items-center">
                                        <span className="flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" className="text-blue-500 mr-1.5 flex-shrink-0">
                                                <path d="M12 22a7 7 0 0 0 7-7c0-4.3-7-11-7-11S5 10.7 5 15a7 7 0 0 0 7 7z"/>
                                            </svg>
                                            水位データキュー
                                        </span>
                                        <span className="text-xs px-2 py-0.5 rounded bg-gray-200 text-gray-700 font-mono">raw-events</span>
                                    </h3>
                                    <div className="space-y-3">
                                        <div>
                                            <div className="flex justify-between text-xs text-gray-600 mb-1">
                                                <span>未処理 (Pending)</span>
                                                <span className="font-semibold text-gray-900">{metrics.water_queue.pending.toLocaleString()} 件</span>
                                            </div>
                                            <div className="w-full bg-gray-200 rounded-full h-2">
                                                <div
                                                    className="bg-indigo-600 h-2 rounded-full transition-all duration-500"
                                                    style={{ width: `${Math.min(100, (metrics.water_queue.pending / 10000) * 100)}%` }}
                                                ></div>
                                            </div>
                                        </div>
                                        <div>
                                            <div className="flex justify-between text-xs text-gray-600 mb-1">
                                                <span>処理中 (In-Flight)</span>
                                                <span className="font-semibold text-gray-900">{metrics.water_queue.in_flight.toLocaleString()} 件</span>
                                            </div>
                                            <div className="w-full bg-gray-200 rounded-full h-2">
                                                <div
                                                    className="bg-blue-400 h-2 rounded-full transition-all duration-500"
                                                    style={{ width: `${Math.min(100, (metrics.water_queue.in_flight / 100) * 100)}%` }}
                                                ></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {/* Weather Queue */}
                                <div className="p-4 bg-gray-50 rounded-lg border border-gray-150">
                                    <h3 className="text-sm font-bold text-gray-800 mb-3 flex justify-between items-center">
                                        <span className="flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" className="text-amber-500 mr-1.5 flex-shrink-0">
                                                <path d="M12 2v2"/>
                                                <path d="m18.4 5.6-1.4 1.4"/>
                                                <path d="M22 12h-2"/>
                                                <path d="m18.4 18.4-1.4-1.4"/>
                                                <path d="M12 22v-2"/>
                                                <path d="m5.6 18.4 1.4-1.4"/>
                                                <path d="M2 12h2"/>
                                                <path d="m5.6 5.6 1.4 1.4"/>
                                                <path d="M16 12a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z"/>
                                            </svg>
                                            気象データキュー
                                        </span>
                                        <span className="text-xs px-2 py-0.5 rounded bg-gray-200 text-gray-700 font-mono">raw-events</span>
                                    </h3>
                                    <div className="space-y-3">
                                        <div>
                                            <div className="flex justify-between text-xs text-gray-600 mb-1">
                                                <span>未処理 (Pending)</span>
                                                <span className="font-semibold text-gray-900">{metrics.weather_queue.pending.toLocaleString()} 件</span>
                                            </div>
                                            <div className="w-full bg-gray-200 rounded-full h-2">
                                                <div
                                                    className="bg-indigo-600 h-2 rounded-full transition-all duration-500"
                                                    style={{ width: `${Math.min(100, (metrics.weather_queue.pending / 10000) * 100)}%` }}
                                                ></div>
                                            </div>
                                        </div>
                                        <div>
                                            <div className="flex justify-between text-xs text-gray-600 mb-1">
                                                <span>処理中 (In-Flight)</span>
                                                <span className="font-semibold text-gray-900">{metrics.weather_queue.in_flight.toLocaleString()} 件</span>
                                            </div>
                                            <div className="w-full bg-gray-200 rounded-full h-2">
                                                <div
                                                    className="bg-blue-400 h-2 rounded-full transition-all duration-500"
                                                    style={{ width: `${Math.min(100, (metrics.weather_queue.in_flight / 100) * 100)}%` }}
                                                ></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {/* DLQ Alert */}
                                <div className={`p-4 rounded-lg border transition ${
                                    metrics.dlq.pending > 0
                                        ? 'bg-red-50 border-red-200 text-red-800'
                                        : 'bg-green-50 border-green-200 text-green-800'
                                }`}>
                                    <div className="flex items-center font-semibold text-sm mb-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" className="text-red-500 mr-2 flex-shrink-0">
                                            <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                                            <line x1="12" y1="9" x2="12" y2="13"/>
                                            <line x1="12" y1="17" x2="12.01" y2="17"/>
                                        </svg>
                                        <span>デッドレターキュー (DLQ)</span>
                                    </div>
                                    <p className="text-xs">
                                        滞留メッセージ: <span className="font-bold font-mono text-sm">{metrics.dlq.pending}</span> 件
                                    </p>
                                    {metrics.dlq.pending > 0 && (
                                        <p className="text-[10px] mt-1 text-red-600">
                                            ※処理に連続で失敗したメッセージがDLQへ退避されています。
                                        </p>
                                    )}
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Column 3: DB Record Metrics */}
                    <div className="lg:col-span-1 bg-white shadow rounded-lg border border-gray-200 p-6 w-full">
                        <h2 className="text-lg font-bold text-gray-900 border-b border-gray-200 pb-3 mb-4 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" className="text-emerald-500 mr-2 flex-shrink-0">
                                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                                <polyline points="17 6 23 6 23 12"/>
                            </svg>
                            DB 書き込みパフォーマンス
                        </h2>
                        <p className="text-sm text-gray-500 mb-6 leading-relaxed">
                            直近 5 分間にデータベースへ登録（バルクインサート）された観測レコードの件数を監視します。
                        </p>

                        {isLoadingMetrics ? (
                            <div className="flex justify-center items-center py-12">
                                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                            </div>
                        ) : (
                            <div className="space-y-6">
                                <div className="p-4 bg-emerald-50 border border-emerald-100 rounded-lg text-emerald-900 flex justify-between items-center">
                                    <div>
                                        <h3 className="text-sm font-bold">水位データ追加件数 (5分間)</h3>
                                        <p className="text-xs text-emerald-700">Water levels recorded in DB</p>
                                    </div>
                                    <span className="text-3xl font-extrabold font-mono text-emerald-600">
                                        {metrics.db_records.water_levels_count_5m.toLocaleString()}
                                    </span>
                                </div>

                                <div className="p-4 bg-teal-50 border border-teal-100 rounded-lg text-teal-900 flex justify-between items-center">
                                    <div>
                                        <h3 className="text-sm font-bold">気象データ追加件数 (5分間)</h3>
                                        <p className="text-xs text-teal-700">Weather records recorded in DB</p>
                                    </div>
                                    <span className="text-3xl font-extrabold font-mono text-teal-600">
                                        {metrics.db_records.weather_records_count_5m.toLocaleString()}
                                    </span>
                                </div>

                                <div className="p-4 bg-gray-50 border border-gray-150 rounded-lg text-gray-700 text-xs leading-relaxed">
                                    <h4 className="font-semibold text-gray-950 mb-1 flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" className="text-amber-500 mr-1.5 flex-shrink-0">
                                            <path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A5 5 0 0 0 8 8c0 1 .3 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5"/>
                                            <path d="M9 18h6"/>
                                            <path d="M10 22h4"/>
                                        </svg>
                                        バルク処理と悲観的ロックの効果
                                    </h4>
                                    SQS から一度にメッセージを取得して一括保存（Bulk Insert）を行うことで、大量イベント受信時における DB I/O のオーバーヘッドを極限まで削減しています。また、並行更新時にはデータベース行の悲観的ロック（Pessimistic Lock）によりデータの整合性を担保しています。
                                </div>
                            </div>
                        )}
                    </div>

                </div>
            </div>
        </div>
    );
}
