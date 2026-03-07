import * as React from 'react';
import { cn } from '@/lib/utils';
import { Upload, FileText } from 'lucide-react';

export interface AtomyQUploadZoneAltProps {
  onFilesSelected?: (files: File[]) => void;
  accept?: string;
  multiple?: boolean;
  maxSizeMB?: number;
  title?: string;
  description?: string;
  className?: string;
}

function AtomyQUploadZoneAlt({
  onFilesSelected,
  accept,
  multiple = true,
  maxSizeMB = 50,
  title = 'Drop files here or click to browse',
  description,
  className,
}: AtomyQUploadZoneAltProps) {
  const [dragActive, setDragActive] = React.useState(false);
  const inputRef = React.useRef<HTMLInputElement>(null);

  const handleDrag = (e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    setDragActive(e.type === 'dragenter' || e.type === 'dragover');
  };

  const handleDrop = (e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    setDragActive(false);
    const files = Array.from(e.dataTransfer.files);
    if (files.length > 0) onFilesSelected?.(files);
  };

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const files = Array.from(e.target.files ?? []);
    if (files.length > 0) onFilesSelected?.(files);
  };

  return (
    <div
      onDragEnter={handleDrag}
      onDragOver={handleDrag}
      onDragLeave={handleDrag}
      onDrop={handleDrop}
      onClick={() => inputRef.current?.click()}
      className={cn(
        'flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed p-10 text-center transition-colors',
        dragActive
          ? 'border-[var(--aq-brand-500)] bg-[var(--aq-brand-tint-6)]'
          : 'border-[var(--aq-border-strong)] bg-[var(--aq-bg-surface)] hover:border-[var(--aq-text-subtle)]',
        className,
      )}
      role="button"
      tabIndex={0}
      aria-label={title}
    >
      <input
        ref={inputRef}
        type="file"
        accept={accept}
        multiple={multiple}
        onChange={handleChange}
        className="hidden"
      />
      <div
        className={cn(
          'mb-3 flex size-12 items-center justify-center rounded-xl',
          dragActive
            ? 'bg-[var(--aq-brand-tint-12)]'
            : 'bg-[var(--aq-bg-elevated)]',
        )}
      >
        {dragActive ? (
          <FileText className="size-5 text-[var(--aq-brand-500)]" />
        ) : (
          <Upload className="size-5 text-[var(--aq-text-muted)]" />
        )}
      </div>
      <p className="text-[13px] font-medium text-[var(--aq-text-primary)]">
        {title}
      </p>
      <p className="mt-1 text-[11px] text-[var(--aq-text-muted)]">
        {description ?? `Max ${maxSizeMB}MB per file`}
      </p>
    </div>
  );
}

export { AtomyQUploadZoneAlt };
