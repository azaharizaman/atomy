"use client";

import { FileText, MoreVertical } from "lucide-react";
import Link from "next/link";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";

interface ContentCardProps {
  title: string;
  subtitle?: string;
  href?: string;
  count?: string | number;
  editedAt?: string;
}

export function ContentCard({ title, subtitle, href, count, editedAt }: ContentCardProps) {
  const content = (
    <CardContent className="p-5">
      <h3 className="font-medium text-foreground line-clamp-2">{title}</h3>
      {subtitle && <p className="mt-0.5 text-sm text-muted-foreground line-clamp-1">{subtitle}</p>}
      <div className="mt-3 flex items-center justify-between">
        <span className="flex items-center gap-1.5 text-sm text-muted-foreground">
          <FileText className="h-4 w-4 shrink-0" />
          {count ?? "—"}
        </span>
        {editedAt && (
          <span className="text-xs text-muted-foreground/70">Edited {editedAt}</span>
        )}
      </div>
      <Button
        variant="ghost"
        size="icon"
        className="absolute right-2 top-2 h-8 w-8 text-muted-foreground hover:text-foreground"
        aria-label="More options"
      >
        <MoreVertical className="h-4 w-4" />
      </Button>
    </CardContent>
  );

  const className =
    "group relative block rounded-xl border bg-card shadow-sm transition-all hover:border-primary/30 hover:shadow-md";

  if (href) {
    return (
      <Link href={href} className={className}>
        <Card className="border-0 shadow-none bg-transparent">
          {content}
        </Card>
      </Link>
    );
  }

  return (
    <Card className={className}>
      {content}
    </Card>
  );
}

