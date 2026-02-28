"use client";

import { FileText, MoreVertical } from "lucide-react";
import Link from "next/link";

interface ContentCardProps {
  title: string;
  subtitle?: string;
  href?: string;
  count?: string | number;
  editedAt?: string;
}

export function ContentCard({ title, subtitle, href, count, editedAt }: ContentCardProps) {
  const content = (
    <>
      <h3 className="font-medium text-[var(--foreground)] line-clamp-2">{title}</h3>
      {subtitle && <p className="mt-0.5 text-sm text-[var(--text-muted)] line-clamp-1">{subtitle}</p>}
      <div className="mt-3 flex items-center justify-between">
        <span className="flex items-center gap-1.5 text-sm text-[var(--text-muted)]">
          <FileText className="h-4 w-4 shrink-0" />
          {count ?? "—"}
        </span>
        {editedAt && (
          <span className="text-xs text-[var(--text-muted-light)]">Edited {editedAt}</span>
        )}
      </div>
      <button
        type="button"
        className="absolute right-3 top-3 rounded p-1 text-[var(--text-muted)] transition-colors hover:bg-[var(--surface)] hover:text-[var(--foreground)]"
        aria-label="More options"
      >
        <MoreVertical className="h-4 w-4" />
      </button>
    </>
  );

  const className =
    "group relative block rounded-xl border border-[var(--border)] bg-white p-5 shadow-sm transition-all hover:border-[var(--accent)]/30 hover:shadow-md";

  if (href) {
    return (
      <Link href={href} className={className}>
        {content}
      </Link>
    );
  }

  return <div className={className}>{content}</div>;
}
