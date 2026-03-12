'use client';

import { useState } from 'react';

export default function AdminNotificationsPage() {
  const [triggering, setTriggering] = useState(false);
  const [result, setResult] = useState('');

  async function triggerNotifications() {
    setTriggering(true);
    setResult('');
    try {
      const res = await fetch('/api/notifications', { method: 'POST' });
      if (res.ok) {
        setResult('Notification job completed successfully. Check client portals for new notifications.');
      } else {
        setResult('Failed to trigger notifications.');
      }
    } catch {
      setResult('Error triggering notifications.');
    } finally {
      setTriggering(false);
    }
  }

  return (
    <div className="max-w-3xl mx-auto space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Notification Center</h1>
        <p className="text-gray-500 mt-0.5">Manage automatic client notifications</p>
      </div>

      <div className="bg-white rounded-2xl border border-gray-200 p-6 space-y-4">
        <h2 className="font-semibold text-gray-900">Automatic Notifications</h2>
        <p className="text-sm text-gray-600">
          The system automatically sends notifications daily at 9:00 AM for:
        </p>
        <ul className="space-y-2 text-sm text-gray-600">
          {[
            '💳 Payment due within 7 days',
            '📄 Documents pending submission within 7 days',
            '✈️ Trip starting in 7 days',
            '✈️ Trip starting in 1 day (same-day reminder)',
          ].map((item) => (
            <li key={item} className="flex items-center gap-2">
              <span className="w-1.5 h-1.5 bg-brand-500 rounded-full flex-shrink-0" />
              {item}
            </li>
          ))}
        </ul>
      </div>

      <div className="bg-white rounded-2xl border border-gray-200 p-6 space-y-4">
        <h2 className="font-semibold text-gray-900">Manual Trigger</h2>
        <p className="text-sm text-gray-600">
          Run the notification job now to send any pending notifications immediately.
        </p>
        <button
          onClick={triggerNotifications}
          disabled={triggering}
          className="flex items-center gap-2 bg-brand-600 hover:bg-brand-700 disabled:bg-brand-400 text-white font-semibold px-6 py-3 rounded-xl transition"
        >
          {triggering ? (
            <>
              <svg className="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
              </svg>
              Running...
            </>
          ) : (
            <>
              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
              </svg>
              Run Notification Job
            </>
          )}
        </button>
        {result && (
          <div className="p-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-700">
            {result}
          </div>
        )}
      </div>
    </div>
  );
}
