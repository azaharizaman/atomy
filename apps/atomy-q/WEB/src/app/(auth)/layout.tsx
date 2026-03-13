'use client';

import React from 'react';
import Link from 'next/link';

export default function AuthLayout({ children }: { children: React.ReactNode }) {
  return (
    <div className="min-h-screen flex bg-slate-100">
      {/* Left: Form area — light grey background, centered white card */}
      <div className="flex-1 flex flex-col items-center justify-center px-6 py-12 sm:px-8">
        <Link href="/login" className="mb-6 flex items-center gap-2 self-start sm:self-center">
          <div className="h-8 w-8 rounded-lg bg-indigo-600 flex items-center justify-center">
            <span className="text-white text-xs font-bold">AQ</span>
          </div>
          <span className="text-xl font-bold tracking-tight text-slate-900">Atomy-Q</span>
        </Link>
        <div className="w-full max-w-[420px] rounded-2xl border border-slate-200 bg-white p-8 shadow-lg shadow-slate-200/50">
          {children}
        </div>
        <p className="mt-6 text-center text-xs text-slate-500">
          <Link href="/status" className="text-indigo-600 hover:text-indigo-700 font-medium">
            System status
          </Link>
          {' · '}
          Secure workspace access
        </p>
      </div>

      {/* Right: Promo panel — Atomy-Q indigo brand */}
      <div className="relative hidden lg:flex flex-1 flex-col justify-between overflow-hidden bg-gradient-to-br from-indigo-600 via-indigo-700 to-indigo-800 px-12 py-16">
        <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_50%_50%,rgba(255,255,255,0.08),transparent_60%)]" aria-hidden />
        <div className="relative z-10">
          <div className="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1.5 text-xs font-medium text-white/90 uppercase tracking-wider">
            <span className="h-2 w-2 rounded-full bg-emerald-400" />
            Atomy-Q Workspace
          </div>
          <h2 className="mt-12 max-w-md text-3xl font-bold leading-tight text-white">
            A governed procurement cockpit for quote comparison, approvals, and award decisions.
          </h2>
          <p className="mt-4 max-w-sm text-sm leading-relaxed text-indigo-100">
            Consolidate RFQs, normalize vendor quotes, and coordinate approvals in a single operational surface built for speed.
          </p>
        </div>

        <div className="relative z-10 grid gap-4 sm:grid-cols-3 mt-12">
          {[
            { title: 'Two layouts', desc: 'Dashboard and RFQ workspace keep context pinned.' },
            { title: 'Full workflow', desc: 'Intake, comparison, approvals, negotiations, award.' },
            { title: 'Audit-ready', desc: 'Decision trail and evidence bundles stay aligned.' },
          ].map((item) => (
            <div key={item.title} className="rounded-xl border border-white/15 bg-white/10 px-4 py-4 backdrop-blur-sm">
              <p className="text-lg font-semibold text-white">{item.title}</p>
              <p className="mt-1.5 text-xs text-indigo-100">{item.desc}</p>
            </div>
          ))}
        </div>

        <div className="relative z-10 flex justify-center gap-1.5 mt-10">
          {[0, 1, 2].map((i) => (
            <span
              key={i}
              className={`h-2 w-2 rounded-full transition-colors ${i === 0 ? 'bg-white w-6' : 'bg-white/40'}`}
              aria-hidden
            />
          ))}
        </div>
      </div>
    </div>
  );
}
