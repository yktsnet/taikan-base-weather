import React from 'react';
import { Head, Link } from '@inertiajs/react';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  LineController,
  BarController,
  Title,
  Tooltip,
  Legend,
} from 'chart.js';
import { Chart } from 'react-chartjs-2';

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  LineController,
  BarController,
  Title,
  Tooltip,
  Legend
);

export default function StationDetail({ station, water_levels, weather_records }) {
    // Sort data from oldest to newest for chronological display
    const sortedWaterLevels = [...water_levels].reverse();
    // Assuming weather records correspond in time; if not perfectly aligned, we might need to merge by time
    // For simplicity, we assume they align by observed_at or we just match the timestamps

    // Create unified time labels from water levels (or merge both)
    const labels = sortedWaterLevels.map(wl => {
        // format date optionally
        return new Date(wl.observed_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    });

    // Match weather records to the water level labels
    // We reverse weather_records to match chronology
    const sortedWeatherRecords = [...weather_records].reverse();
    const precipData = labels.map((label, index) => {
        // If they perfectly align:
        const wr = sortedWeatherRecords[index];
        return wr ? wr.precipitation_mm : 0;
    });

    const waterLevelData = sortedWaterLevels.map(wl => wl.level_m);

    // Prepare chart data
    const chartData = {
        labels,
        datasets: [
            {
                type: 'line',
                label: 'Water Level (m)',
                data: waterLevelData,
                borderColor: 'rgb(53, 162, 235)',
                backgroundColor: 'rgba(53, 162, 235, 0.5)',
                yAxisID: 'y',
                tension: 0.1,
            },
            {
                type: 'bar',
                label: 'Precipitation (mm)',
                data: precipData,
                backgroundColor: 'rgba(75, 192, 192, 0.5)',
                yAxisID: 'y1',
            },
            // Warning Level Line (Dataset trick since chartjs-plugin-annotation is not installed)
            {
                type: 'line',
                label: 'Warning Level',
                data: Array(labels.length).fill(station.warning_level),
                borderColor: 'orange',
                borderWidth: 2,
                borderDash: [5, 5],
                pointRadius: 0,
                yAxisID: 'y',
            },
            // Danger Level Line
            {
                type: 'line',
                label: 'Danger Level',
                data: Array(labels.length).fill(station.danger_level),
                borderColor: 'red',
                borderWidth: 2,
                borderDash: [5, 5],
                pointRadius: 0,
                yAxisID: 'y',
            }
        ],
    };

    const chartOptions = {
        responsive: true,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        stacked: false,
        plugins: {
            title: {
                display: true,
                text: 'Water Level and Precipitation History',
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) {
                            label += ': ';
                        }
                        if (context.parsed.y !== null) {
                            label += context.parsed.y;
                            if (context.dataset.yAxisID === 'y') label += ' m';
                            else if (context.dataset.yAxisID === 'y1') label += ' mm';
                        }
                        return label;
                    }
                }
            }
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                title: {
                    display: true,
                    text: 'Water Level (m)'
                },
                suggestedMin: 0,
                // Ensure warning/danger levels fit
                suggestedMax: Math.max(...waterLevelData, station.danger_level) + 1,
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                title: {
                    display: true,
                    text: 'Precipitation (mm)'
                },
                grid: {
                    drawOnChartArea: false, // only want the grid lines for one axis to show up
                },
                suggestedMin: 0,
            },
        },
    };

    return (
        <div className="min-h-screen bg-gray-100 py-8">
            <Head title={`Station Detail - ${station.name}`} />
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="mb-6">
                    <Link
                        href="/"
                        className="text-indigo-600 hover:text-indigo-900 font-medium"
                    >
                        &larr; Back to Dashboard
                    </Link>
                </div>

                {/* Chart Section */}
                <div className="bg-white shadow overflow-hidden sm:rounded-lg mb-8 p-4">
                    <div className="h-96 w-full">
                        <Chart type='bar' data={chartData} options={chartOptions} />
                    </div>
                </div>

                <div className="bg-white shadow overflow-hidden sm:rounded-lg mb-8">
                    <div className="px-4 py-5 sm:px-6">
                        <h3 className="text-lg leading-6 font-medium text-gray-900">
                            Station Information
                        </h3>
                        <p className="mt-1 max-w-2xl text-sm text-gray-500">
                            Details and historical data for {station.name}.
                        </p>
                    </div>
                    <div className="border-t border-gray-200 px-4 py-5 sm:p-0">
                        <dl className="sm:divide-y sm:divide-gray-200">
                            <div className="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt className="text-sm font-medium text-gray-500">River Name</dt>
                                <dd className="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                    {station.river_name}
                                </dd>
                            </div>
                            <div className="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt className="text-sm font-medium text-gray-500">Prefecture</dt>
                                <dd className="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                    {station.prefecture}
                                </dd>
                            </div>
                            <div className="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt className="text-sm font-medium text-gray-500">Warning / Danger Level</dt>
                                <dd className="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                    {station.warning_level} m / {station.danger_level} m
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    {/* Water Levels Table */}
                    <div className="bg-white shadow overflow-hidden sm:rounded-lg">
                        <div className="px-4 py-5 sm:px-6 border-b border-gray-200">
                            <h3 className="text-lg leading-6 font-medium text-gray-900">Recent Water Levels</h3>
                        </div>
                        <ul className="divide-y divide-gray-200 overflow-y-auto max-h-96">
                            {water_levels.map((wl) => (
                                <li key={wl.id} className="px-4 py-4 sm:px-6 flex justify-between items-center">
                                    <div className="text-sm text-gray-900">{wl.observed_at}</div>
                                    <div className="text-sm font-medium text-gray-900">{wl.level_m} m</div>
                                    <div className={`text-sm ${wl.alert_status !== 'normal' ? 'text-red-600 font-bold' : 'text-gray-500'}`}>
                                        {wl.alert_status}
                                    </div>
                                </li>
                            ))}
                            {water_levels.length === 0 && (
                                <li className="px-4 py-4 sm:px-6 text-sm text-gray-500 text-center">No recent water level data.</li>
                            )}
                        </ul>
                    </div>

                    {/* Weather Records Table */}
                    <div className="bg-white shadow overflow-hidden sm:rounded-lg">
                        <div className="px-4 py-5 sm:px-6 border-b border-gray-200">
                            <h3 className="text-lg leading-6 font-medium text-gray-900">Recent Weather Records</h3>
                        </div>
                        <ul className="divide-y divide-gray-200 overflow-y-auto max-h-96">
                            {weather_records.map((wr) => (
                                <li key={wr.id} className="px-4 py-4 sm:px-6 flex justify-between items-center">
                                    <div className="text-sm text-gray-900">{wr.observed_at}</div>
                                    <div className="text-sm font-medium text-gray-900">{wr.precipitation_mm} mm</div>
                                    <div className="text-sm text-gray-500">{wr.temperature_c} °C</div>
                                </li>
                            ))}
                            {weather_records.length === 0 && (
                                <li className="px-4 py-4 sm:px-6 text-sm text-gray-500 text-center">No recent weather data.</li>
                            )}
                        </ul>
                    </div>
                </div>

            </div>
        </div>
    );
}
