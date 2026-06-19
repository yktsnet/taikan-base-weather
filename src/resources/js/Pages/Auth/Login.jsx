import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Login() {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/login', {
            onFinish: () => reset('password'),
        });
    };

    return (
        <div className="min-h-screen flex flex-col justify-center py-12 sm:px-6 lg:px-8 bg-gray-100">
            <Head title="管理者ログイン" />

            <div className="sm:mx-auto sm:w-full sm:max-w-md">
                <div className="flex justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-indigo-600 animate-pulse">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                </div>
                <h2 className="mt-6 text-center text-3xl font-extrabold text-gray-900 tracking-tight">
                    kawa-watch
                </h2>
                <p className="mt-2 text-center text-sm text-gray-600">
                    システム管理者用ログインパネル
                </p>
            </div>

            <div className="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
                <div className="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10 border border-gray-200">
                    <form className="space-y-6" onSubmit={handleSubmit}>
                        <div>
                            <label htmlFor="email" className="block text-sm font-medium text-gray-700">
                                メールアドレス
                            </label>
                            <div className="mt-1">
                                <input
                                    id="email"
                                    name="email"
                                    type="email"
                                    autoComplete="email"
                                    required
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    className="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 bg-white text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                />
                            </div>
                            {errors.email && (
                                <p className="mt-2 text-sm text-red-600 font-medium">
                                    {errors.email}
                                </p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="password" className="block text-sm font-medium text-gray-700">
                                パスワード
                            </label>
                            <div className="mt-1">
                                <input
                                    id="password"
                                    name="password"
                                    type="password"
                                    autoComplete="current-password"
                                    required
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    className="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 bg-white text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                />
                            </div>
                            {errors.password && (
                                <p className="mt-2 text-sm text-red-600 font-medium">
                                    {errors.password}
                                </p>
                            )}
                        </div>

                        <div className="flex items-center justify-between">
                            <div className="flex items-center">
                                <input
                                    id="remember"
                                    name="remember"
                                    type="checkbox"
                                    checked={data.remember}
                                    onChange={(e) => setData('remember', e.target.checked)}
                                    className="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded bg-white cursor-pointer"
                                />
                                <label htmlFor="remember" className="ml-2 block text-sm text-gray-700 cursor-pointer">
                                    ログイン状態を維持
                                </label>
                            </div>
                        </div>

                        <div>
                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                            >
                                {processing ? 'ログイン中...' : 'ログイン'}
                            </button>
                        </div>
                    </form>

                    {/* Demo Account Notice */}
                    <div className="mt-6 p-4 bg-blue-50 border border-blue-100 rounded-md text-xs text-blue-800">
                        <p className="font-semibold text-indigo-700 mb-2 flex items-center">
                            <span className="mr-1">💡</span> デモ用管理者アカウント
                        </p>
                        <p className="mb-1">
                            メールアドレス: <code className="bg-blue-100 px-1.5 py-0.5 rounded text-blue-900 select-all font-mono">admin@example.com</code>
                        </p>
                        <p>
                            パスワード: <code className="bg-blue-100 px-1.5 py-0.5 rounded text-blue-900 select-all font-mono">password</code>
                        </p>
                    </div>

                    <div className="mt-6 flex justify-center">
                        <Link href="/" className="text-sm font-medium text-gray-500 hover:text-gray-700 transition">
                            ← 一般ダッシュボードに戻る
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    );
}
