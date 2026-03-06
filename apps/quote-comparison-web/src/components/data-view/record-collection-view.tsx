"use client";

import { useMemo, useState, type ReactNode } from "react";
import { LayoutGrid, Table } from "lucide-react";
import { type ColumnDef } from "@tanstack/react-table";

import { Button } from "@/components/ui/button";

import { DataCardView } from "./data-card-view";
import { DataTableView } from "./data-table-view";

export type CollectionViewMode = "table" | "cards";

interface RecordCollectionViewProps<TData extends object> {
  readonly records: readonly TData[];
  readonly columns: readonly ColumnDef<TData>[];
  readonly renderCard: (record: TData) => ReactNode;
  readonly defaultMode?: CollectionViewMode;
  readonly emptyLabel?: string;
}

export function RecordCollectionView<TData extends object>({
  records,
  columns,
  renderCard,
  defaultMode = "table",
  emptyLabel
}: RecordCollectionViewProps<TData>): JSX.Element {
  const [mode, setMode] = useState<CollectionViewMode>(defaultMode);

  const modeLabel = useMemo(() => (mode === "table" ? "Table View" : "Card View"), [mode]);

  return (
    <section className="space-y-3">
      <div className="flex items-center justify-between">
        <p className="text-sm text-slate-600">{modeLabel}</p>
        <div className="inline-flex rounded-md border border-slate-200 bg-white p-1">
          <Button
            className="h-8 px-2"
            onClick={() => setMode("table")}
            type="button"
            variant={mode === "table" ? "secondary" : "ghost"}
          >
            <Table className="h-4 w-4" />
          </Button>
          <Button
            className="h-8 px-2"
            onClick={() => setMode("cards")}
            type="button"
            variant={mode === "cards" ? "secondary" : "ghost"}
          >
            <LayoutGrid className="h-4 w-4" />
          </Button>
        </div>
      </div>
      {mode === "table" ? (
        <DataTableView columns={columns} data={records} emptyLabel={emptyLabel} />
      ) : (
        <DataCardView data={records} emptyLabel={emptyLabel} renderCard={renderCard} />
      )}
    </section>
  );
}
