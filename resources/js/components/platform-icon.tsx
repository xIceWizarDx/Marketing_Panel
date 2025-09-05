import { cn } from '@/lib/utils';
import * as React from 'react';

export default function PlatformIcon({ platform, className = '' }: { platform: string; className?: string }) {
    const p = platform.toLowerCase();
    const wrap = (bg: string, node: React.ReactNode) => (
        <div className={cn('inline-flex size-8 items-center justify-center rounded-md', bg, className)}>{node}</div>
    );
    if (p.includes('instagram'))
        return wrap(
            'bg-gradient-to-br from-pink-500 to-purple-500 text-white',
            <svg viewBox="0 0 24 24" className="h-5 w-5">
                <rect x="3" y="3" width="18" height="18" rx="5" fill="currentColor" opacity="0.2" />
                <circle cx="12" cy="12" r="4.5" stroke="currentColor" strokeWidth="2" fill="none" />
                <circle cx="17" cy="7" r="1.2" fill="currentColor" />
            </svg>,
        );
    if (p.includes('facebook') || p === 'meta')
        return wrap(
            'bg-blue-600 text-white',
            <svg viewBox="0 0 24 24" className="h-5 w-5">
                <path d="M14 8h2V5h-2c-2.2 0-4 1.8-4 4v2H8v3h2v5h3v-5h2.1l.9-3H13V9c0-.6.4-1 1-1Z" fill="currentColor" />
            </svg>,
        );
    if (p.includes('youtube'))
        return wrap(
            'bg-red-600 text-white',
            <svg viewBox="0 0 24 24" className="h-5 w-5">
                <path d="M22 12c0-2-.2-3.3-.6-4-.3-.6-.8-1-1.4-1.2C18.7 6.4 12 6.4 12 6.4s-6.7 0-8 .4c-.6.2-1.1.6-1.4 1.2C2.2 8.7 2 10 2 12s.2 3.3.6 4c.3.6.8 1 1.4 1.2 1.3.4 8 .4 8 .4s6.7 0 8-.4c.6-.2 1.1-.6 1.4-1.2.4-.7.6-2 .6-4Z" fill="currentColor" />
                <path d="M10 9.75v4.5L14.5 12 10 9.75Z" fill="#fff" />
            </svg>,
        );
    if (p.includes('tiktok'))
        return wrap(
            'bg-black text-white',
            <svg viewBox="0 0 24 24" className="h-5 w-5">
                <path d="M16 5.5c.7.9 1.7 1.5 2.8 1.8v3.1a6.6 6.6 0 0 1-3.3-1v5.1a5.4 5.4 0 1 1-5.4-5.4c.3 0 .7 0 1 .1v3a2.4 2.4 0 1 0 2.4 2.4V3h2.5v2.5Z" fill="currentColor" />
            </svg>,
        );
    if (p.includes('twitter') || p === 'x')
        return wrap(
            'bg-neutral-900 text-white',
            <svg viewBox="0 0 24 24" className="h-5 w-5">
                <path d="M4 4h3.2l5 6.5L16.7 4H20l-6.3 8.2L20 20h-3.3l-5-6.5L7.3 20H4l6.5-8.1L4 4Z" fill="currentColor" />
            </svg>,
        );
    if (p.includes('linkedin'))
        return wrap(
            'bg-sky-700 text-white',
            <svg viewBox="0 0 24 24" className="h-5 w-5">
                <rect x="4" y="9" width="3.5" height="11" fill="currentColor" />
                <circle cx="5.75" cy="5.75" r="1.75" fill="currentColor" />
                <path d="M11 9h3v1.5c.7-1 1.7-1.8 3.4-1.8 2.4 0 3.6 1.2 3.6 3.9V20h-3.5v-6c0-1.3-.5-2-1.6-2-1.1 0-1.9.8-1.9 2V20H11V9Z" fill="currentColor" />
            </svg>,
        );
    if (p.includes('pinterest'))
        return wrap(
            'bg-red-700 text-white',
            <svg viewBox="0 0 24 24" className="h-5 w-5">
                <path d="M12 3.2a8.8 8.8 0 0 0-3 17.1l.8-3.1c-.2-.6-.4-1.5-.2-2.1.1-.7.8-2.9.8-2.9s-.2-.5-.2-1.2c0-1.1.6-1.9 1.4-1.9.7 0 1 .5 1 1.1 0 .7-.5 1.8-.7 2.8-.2.8.4 1.4 1.2 1.4 1.5 0 2.6-1.6 2.6-3.8 0-2-1.4-3.4-3.6-3.4-2.4 0-3.8 1.8-3.8 3.6 0 .7.3 1.4.7 1.7.1.1.1.2.1.3l-.3 1.1c0 .2-.2.3-.4.2-1.2-.5-1.8-1.8-1.8-3.2 0-2.5 2.1-5.6 6.2-5.6 3.3 0 5.5 2.4 5.5 5.1 0 3.4-1.9 5.9-4.7 5.9-1 0-1.9-.5-2.2-1.1l-.6 2.1c-.2.8-.8 1.8-1.3 2.4 1 .3 2 .5 3.1.5 4.9 0 8.8-3.9 8.8-8.8S16.9 3.2 12 3.2Z" fill="currentColor" />
            </svg>,
        );
    if (p.includes('google'))
        return wrap(
            'bg-indigo-600 text-white',
            <svg viewBox="0 0 24 24" className="h-5 w-5">
                <path d="M9 4c-.8 0-1.5.7-1.5 1.5v13c0 .8.7 1.5 1.5 1.5h.3l6.7-13.4C16.4 5.2 15.8 4 14.7 4H9Z" fill="currentColor" opacity=".9" />
                <circle cx="17.5" cy="17.5" r="2.5" fill="#FDE047" />
            </svg>,
        );
    return wrap('bg-neutral-400 text-white', <span className="text-xs">•</span>);
}


