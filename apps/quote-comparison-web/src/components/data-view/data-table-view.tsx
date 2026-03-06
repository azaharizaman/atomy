"use client";

import {
  flexRender,
  getCoreRowModel,
  useReactTable,
  type ColumnDef
} from "@tanstack/react-table";

import { cn } from "@/lib/cn";

interface DataTableViewProps<TData extends object> {
  readonly columns: readonly ColumnDef<TData>[];
  readonly data: readonly TData[];
  readonly emptyLabel?: string;
}

export function DataTableView<TData extends object>({ columns, data, emptyLabel = "No records found." }: DataTableViewProps<TData>): JSX.Element {
  const table = useReactTable({
    data: [...data],
    columns: [...columns],
    getCoreRowModel: getCoreRowModel()
  });

  if (data.length === 0) {
    return <p className="rounded-md border border-slate-200 bg-white px-4 py-8 text-center text-sm text-slate-500">{emptyLabel}</p>;
  }

  return (
    <div className="overflow-auto rounded-lg border border-slate-200 bg-white">
      <table className="min-w-full border-collapse text-sm">
        <thead className="bg-slate-50">
          {table.getHeaderGroups().map((headerGroup) => (
            <tr key={headerGroup.id}>
              {headerGroup.headers.map((header) => (
                <th className="border-b border-slate-200 px-3 py-2 text-left font-medium text-slate-600" key={header.id}>
                  {header.isPlaceholder ? null : flexRender(header.column.columnDef.header, header.getContext())}
                </th>
              ))}
            </tr>
          ))}
        </thead>
        <tbody>
          {table.getRowModel().rows.map((row) => (
            <tr className="hover:bg-slate-50" key={row.id}>
              {row.getVisibleCells().map((cell) => (
                <td className={cn("border-b border-slate-100 px-3 py-2 text-slate-900", "last:border-b-0")} key={cell.id}>
                  {flexRender(cell.column.columnDef.cell, cell.getContext())}
                </td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
