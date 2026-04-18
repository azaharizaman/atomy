'use client';

import React from 'react';
import { AlertTriangle, RefreshCw, Mail, Home } from 'lucide-react';
import { Button } from '@/components/ds/Button';
import { Card } from '@/components/ds/Card';

type GlobalErrorProps = Readonly<{
  error: Error & { digest?: string };
  reset: () => void;
}>;

const SUPPORT_EMAIL = 'support@atomy-q.nexusnv.net';

export default function GlobalError({ error, reset }: GlobalErrorProps) {
  const reference = typeof error.digest === 'string' && error.digest.trim() !== '' ? error.digest.trim() : null;

  return (
    <html lang="en">
      <body className="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(79,70,229,0.12),_transparent_40%),linear-gradient(180deg,_#f8fafc_0%,_#eef2ff_100%)] text-slate-900">
        <main className="min-h-screen flex items-center justify-center px-4 py-10">
          <Card className="w-full max-w-2xl overflow-hidden" padding="none">
            <div className="border-b border-slate-100 bg-slate-50/80 px-6 py-5">
              <div className="flex items-start gap-4">
                <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-red-50 text-red-600">
                  <AlertTriangle size={22} aria-hidden />
                </div>
                <div className="space-y-1">
                  <h1 className="text-xl font-semibold tracking-tight text-slate-900">Something went wrong</h1>
                  <p className="text-sm leading-6 text-slate-600">
                    An unexpected application error interrupted the page. Your data should still be intact.
                  </p>
                </div>
              </div>
            </div>

            <div className="grid gap-6 px-6 py-6 md:grid-cols-[1.4fr_0.9fr]">
              <section className="space-y-4">
                <div className="space-y-2">
                  <p className="text-sm font-medium text-slate-800">What you can do next</p>
                  <ul className="space-y-2 text-sm leading-6 text-slate-600">
                    <li className="flex gap-2">
                      <span className="mt-2 h-1.5 w-1.5 rounded-full bg-indigo-500" aria-hidden />
                      Try the page again using the retry button below.
                    </li>
                    <li className="flex gap-2">
                      <span className="mt-2 h-1.5 w-1.5 rounded-full bg-indigo-500" aria-hidden />
                      If the error keeps happening, contact support with the reference shown on this screen.
                    </li>
                    <li className="flex gap-2">
                      <span className="mt-2 h-1.5 w-1.5 rounded-full bg-indigo-500" aria-hidden />
                      If you just need to keep working, go back to the dashboard and reopen the task.
                    </li>
                  </ul>
                </div>

                <div className="flex flex-wrap gap-3">
                  <Button
                    onClick={reset}
                    icon={<RefreshCw size={14} aria-hidden />}
                    iconPosition="left"
                  >
                    Try again
                  </Button>
                  <Button
                    variant="outline"
                    onClick={() => {
                      window.location.href = '/';
                    }}
                    icon={<Home size={14} aria-hidden />}
                    iconPosition="left"
                  >
                    Go to dashboard
                  </Button>
                </div>
              </section>

              <aside className="space-y-4 rounded-xl border border-slate-200 bg-white p-4 shadow-[0_1px_3px_0_rgba(15,23,42,0.06)]">
                <div className="space-y-1">
                  <p className="text-sm font-semibold text-slate-900">Support</p>
                  <p className="text-xs leading-5 text-slate-500">
                    Send the reference code to support if you need help investigating this failure.
                  </p>
                </div>

                <a
                  href={`mailto:${SUPPORT_EMAIL}`}
                  className="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-indigo-700 transition-colors hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-800"
                >
                  <Mail size={14} aria-hidden />
                  {SUPPORT_EMAIL}
                </a>

                {reference && (
                  <div className="space-y-1">
                    <p className="text-xs font-medium uppercase tracking-wide text-slate-400">Reference</p>
                    <p className="break-all rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 font-mono text-xs text-slate-700">
                      {reference}
                    </p>
                  </div>
                )}

                <p className="text-xs leading-5 text-slate-500">
                  The error was unexpected, so no technical details are shown here. Support can use the reference
                  code to help trace the incident.
                </p>
              </aside>
            </div>
          </Card>
        </main>
      </body>
    </html>
  );
}
