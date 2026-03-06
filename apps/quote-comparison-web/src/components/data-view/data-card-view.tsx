import { type ReactNode } from "react";

interface DataCardViewProps<TData> {
  readonly data: readonly TData[];
  readonly renderCard: (record: TData) => ReactNode;
  readonly emptyLabel?: string;
}

export function DataCardView<TData>({ data, renderCard, emptyLabel = "No records found." }: DataCardViewProps<TData>): JSX.Element {
  if (data.length === 0) {
    return <p className="rounded-md border border-slate-200 bg-white px-4 py-8 text-center text-sm text-slate-500">{emptyLabel}</p>;
  }

  return <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">{data.map((record) => renderCard(record))}</div>;
}
