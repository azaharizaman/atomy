'use client';

import React from 'react';

export interface OwnerCellProps {
  name: string;
}

export function OwnerCell({ name }: OwnerCellProps) {
  const initials = name
    .split(' ')
    .filter(Boolean)
    .map((p) => p[0])
    .join('')
    .slice(0, 2)
    .toUpperCase();
  return (
    <div className="flex items-center gap-2">
      <div className="w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center text-xs font-medium text-slate-600">
        {initials}
      </div>
      <span className="text-sm text-slate-700">{name}</span>
    </div>
  );
}
