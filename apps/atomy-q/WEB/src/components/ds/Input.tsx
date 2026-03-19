'use client';

import React from 'react';
import { ChevronDown, Eye, EyeOff, Search, X } from 'lucide-react';

const inputBase =
  'w-full rounded-md border border-slate-200 bg-white text-slate-900 text-sm placeholder:text-slate-400 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-400 disabled:opacity-50 disabled:cursor-not-allowed';

interface LabelProps {
  htmlFor?: string;
  required?: boolean;
  children: React.ReactNode;
  className?: string;
}

export function FieldLabel({ htmlFor, required, children, className = '' }: LabelProps) {
  return (
    <label htmlFor={htmlFor} className={['block text-xs font-medium text-slate-600 mb-1', className].join(' ')}>
      {children}
      {required && <span className="text-red-500 ml-0.5">*</span>}
    </label>
  );
}

export function FieldHint({ children }: { children: React.ReactNode }) {
  return <p className="mt-1 text-xs text-slate-500">{children}</p>;
}

export function FieldError({ children }: { children: React.ReactNode }) {
  return <p className="mt-1 text-xs text-red-600">{children}</p>;
}

interface TextInputProps extends React.InputHTMLAttributes<HTMLInputElement> {
  label?: string;
  hint?: string;
  error?: string;
  prefixIcon?: React.ReactNode;
  suffixIcon?: React.ReactNode;
  containerClassName?: string;
}

export function TextInput({
  label,
  hint,
  error,
  prefixIcon,
  suffixIcon,
  containerClassName = '',
  className = '',
  id,
  required,
  ...props
}: TextInputProps) {
  const inputId = id ?? (label ? label.toLowerCase().replace(/\s+/g, '-') : undefined);

  return (
    <div className={['flex flex-col', containerClassName].join(' ')}>
      {label && (
        <FieldLabel htmlFor={inputId} required={required}>
          {label}
        </FieldLabel>
      )}
      <div className="relative">
        {prefixIcon && <span className="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400">{prefixIcon}</span>}
        <input
          id={inputId}
          required={required}
          className={[
            inputBase,
            'h-8 px-3',
            prefixIcon ? 'pl-8' : '',
            suffixIcon ? 'pr-8' : '',
            error ? 'border-red-400 focus:ring-red-400 focus:border-red-400' : '',
            className,
          ].join(' ')}
          {...props}
        />
        {suffixIcon && <span className="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400">{suffixIcon}</span>}
      </div>
      {error && <FieldError>{error}</FieldError>}
      {hint && !error && <FieldHint>{hint}</FieldHint>}
    </div>
  );
}

type PasswordInputProps = Omit<TextInputProps, 'type' | 'suffixIcon'>;

export function PasswordInput(props: PasswordInputProps) {
  const [visible, setVisible] = React.useState(false);
  return (
    <TextInput
      {...props}
      type={visible ? 'text' : 'password'}
      suffixIcon={
        <button type="button" onClick={() => setVisible((v) => !v)} className="text-slate-400 hover:text-slate-600 p-0.5">
          {visible ? <EyeOff size={14} /> : <Eye size={14} />}
        </button>
      }
    />
  );
}

interface SearchInputProps extends Omit<React.InputHTMLAttributes<HTMLInputElement>, 'type'> {
  shortcut?: string;
  onClear?: () => void;
  containerClassName?: string;
  compact?: boolean;
}

export function SearchInput({ shortcut, onClear, containerClassName = '', compact = false, value, className = '', ...props }: SearchInputProps) {
  return (
    <div className={['relative', containerClassName].join(' ')}>
      <Search size={13} className="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
      <input
        type="search"
        value={value}
        className={[
          inputBase,
          compact ? 'h-7 text-xs' : 'h-8 text-sm',
          'pl-7',
          shortcut ? 'pr-14' : onClear && value ? 'pr-7' : 'pr-3',
          className,
        ].join(' ')}
        {...props}
      />
      {shortcut && (
        <kbd className="absolute right-2.5 top-1/2 -translate-y-1/2 inline-flex items-center px-1 rounded text-[10px] font-mono text-slate-400 bg-slate-100 border border-slate-200">
          {shortcut}
        </kbd>
      )}
      {onClear && value && !shortcut && (
        <button type="button" onClick={onClear} className="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
          <X size={12} />
        </button>
      )}
    </div>
  );
}

interface SelectInputProps extends React.SelectHTMLAttributes<HTMLSelectElement> {
  label?: string;
  hint?: string;
  error?: string;
  options: { value: string; label: string; disabled?: boolean }[];
  placeholder?: string;
  containerClassName?: string;
  compact?: boolean;
}

export function SelectInput({
  label,
  hint,
  error,
  options,
  placeholder,
  containerClassName = '',
  compact = false,
  className = '',
  id,
  required,
  ...props
}: SelectInputProps) {
  const inputId = id ?? (label ? label.toLowerCase().replace(/\s+/g, '-') : undefined);

  return (
    <div className={['flex flex-col', containerClassName].join(' ')}>
      {label && (
        <FieldLabel htmlFor={inputId} required={required}>
          {label}
        </FieldLabel>
      )}
      <div className="relative">
        <select
          id={inputId}
          required={required}
          className={[
            inputBase,
            compact ? 'h-7 text-xs' : 'h-8 text-sm',
            'pl-3 pr-8 appearance-none cursor-pointer',
            error ? 'border-red-400 focus:ring-red-400' : '',
            className,
          ].join(' ')}
          {...props}
        >
          {placeholder && <option value="">{placeholder}</option>}
          {options.map((opt) => (
            <option key={opt.value} value={opt.value} disabled={opt.disabled}>
              {opt.label}
            </option>
          ))}
        </select>
        <ChevronDown size={13} className="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
      </div>
      {error && <FieldError>{error}</FieldError>}
      {hint && !error && <FieldHint>{hint}</FieldHint>}
    </div>
  );
}

interface CheckboxProps extends Omit<React.InputHTMLAttributes<HTMLInputElement>, 'type'> {
  label?: string;
  indeterminate?: boolean;
}

export function Checkbox({ label, indeterminate, className = '', ...props }: CheckboxProps) {
  const ref = React.useRef<HTMLInputElement>(null);

  React.useEffect(() => {
    if (ref.current) ref.current.indeterminate = !!indeterminate;
  }, [indeterminate]);

  const input = (
    <input
      ref={ref}
      type="checkbox"
      className={[
        'w-3.5 h-3.5 rounded border border-slate-300 text-indigo-600 cursor-pointer',
        'focus:ring-indigo-500 focus:ring-2 focus:ring-offset-0',
        'accent-indigo-600',
        className,
      ].join(' ')}
      {...props}
    />
  );

  if (!label) return input;

  return (
    <label className="inline-flex items-center gap-2 cursor-pointer select-none">
      {input}
      <span className="text-sm text-slate-700">{label}</span>
    </label>
  );
}

interface ToggleSwitchProps {
  checked: boolean;
  onChange: (checked: boolean) => void;
  label?: string;
  size?: 'sm' | 'md';
  disabled?: boolean;
}

export function ToggleSwitch({ checked, onChange, label, size = 'md', disabled = false }: ToggleSwitchProps) {
  const sizes = {
    sm: { track: 'w-7 h-4', thumb: 'w-3 h-3', translate: 'translate-x-3.5' },
    md: { track: 'w-9 h-5', thumb: 'w-3.5 h-3.5', translate: 'translate-x-4.5' },
  };
  const s = sizes[size];

  return (
    <label className={['inline-flex items-center gap-2 select-none', disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'].join(' ')}>
      <button
        role="switch"
        type="button"
        aria-checked={checked}
        disabled={disabled}
        onClick={() => !disabled && onChange(!checked)}
        className={[
          'relative inline-flex items-center rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1',
          s.track,
          checked ? 'bg-indigo-600' : 'bg-slate-300',
        ].join(' ')}
      >
        <span
          className={[
            'absolute left-0.5 rounded-full bg-white shadow transition-transform duration-200',
            s.thumb,
            checked ? s.translate : 'translate-x-0',
          ].join(' ')}
        />
      </button>
      {label && <span className="text-sm text-slate-700">{label}</span>}
    </label>
  );
}

interface FilterChipProps {
  label: string;
  onRemove?: () => void;
  className?: string;
}

export function FilterChip({ label, onRemove, className = '' }: FilterChipProps) {
  return (
    <span className={['inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-200', className].join(' ')}>
      {label}
      {onRemove && (
        <button type="button" onClick={onRemove} className="ml-0.5 rounded-full hover:bg-indigo-200 p-0.5 transition-colors">
          <X size={9} />
        </button>
      )}
    </span>
  );
}

