'use client';

import React from 'react';
import { Loader2 } from 'lucide-react';

import type { ButtonSize, ButtonVariant } from './tokens';

interface ButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: ButtonVariant;
  size?: ButtonSize;
  loading?: boolean;
  icon?: React.ReactNode;
  iconPosition?: 'left' | 'right';
  fullWidth?: boolean;
}

const variantStyles: Record<ButtonVariant, string> = {
  primary:
    'bg-indigo-600 text-white border border-indigo-600 hover:bg-indigo-700 hover:border-indigo-700 active:bg-indigo-800 disabled:bg-indigo-300 disabled:border-indigo-300 disabled:cursor-not-allowed',
  secondary:
    'bg-slate-100 text-slate-700 border border-slate-200 hover:bg-slate-200 hover:border-slate-300 active:bg-slate-300 disabled:opacity-50 disabled:cursor-not-allowed',
  ghost:
    'bg-transparent text-slate-600 border border-transparent hover:bg-slate-100 hover:text-slate-800 active:bg-slate-200 disabled:opacity-50 disabled:cursor-not-allowed',
  outline:
    'bg-white text-slate-700 border border-slate-300 hover:bg-slate-50 hover:border-slate-400 active:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed',
  destructive:
    'bg-white text-red-600 border border-red-300 hover:bg-red-50 hover:border-red-400 active:bg-red-100 disabled:opacity-50 disabled:cursor-not-allowed',
};

const sizeStyles: Record<ButtonSize, string> = {
  xs: 'h-6 px-2 text-xs gap-1 rounded',
  sm: 'h-7 px-3 text-xs gap-1.5 rounded',
  md: 'h-8 px-3.5 text-sm gap-2 rounded-md',
  lg: 'h-9 px-4 text-sm gap-2 rounded-md',
};

export function Button({
  variant = 'primary',
  size = 'md',
  loading = false,
  icon,
  iconPosition = 'left',
  fullWidth = false,
  children,
  disabled,
  className = '',
  ...props
}: ButtonProps) {
  const base =
    'inline-flex min-w-fit items-center justify-center font-medium transition-colors duration-150 select-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-1 whitespace-nowrap';

  return (
    <button
      type="button"
      className={[base, variantStyles[variant], sizeStyles[size], fullWidth ? 'w-full' : '', className].join(' ')}
      disabled={disabled || loading}
      {...props}
    >
      {loading ? (
        <Loader2 size={12} className="animate-spin shrink-0" />
      ) : (
        icon && iconPosition === 'left' && <span className="shrink-0">{icon}</span>
      )}
      {children && <span>{children}</span>}
      {!loading && icon && iconPosition === 'right' && <span className="shrink-0">{icon}</span>}
    </button>
  );
}

interface IconButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: ButtonVariant;
  size?: ButtonSize;
  label: string;
  children: React.ReactNode;
}

export function IconButton({ variant = 'ghost', size = 'md', label, children, className = '', ...props }: IconButtonProps) {
  const base =
    'inline-flex items-center justify-center rounded-md transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500';

  const sizeMap: Record<ButtonSize, string> = {
    xs: 'w-5 h-5',
    sm: 'w-6 h-6',
    md: 'w-8 h-8',
    lg: 'w-9 h-9',
  };

  return (
    <button
      type="button"
      aria-label={label}
      title={label}
      className={[base, variantStyles[variant], sizeMap[size], className].join(' ')}
      {...props}
    >
      {children}
    </button>
  );
}
