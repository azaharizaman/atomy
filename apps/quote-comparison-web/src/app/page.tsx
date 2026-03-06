"use client";

import { useMemo, useState } from "react";
import { type ColumnDef } from "@tanstack/react-table";

import { AppModalLayout } from "@/components/modal/app-modal-layout";
import { RecordCollectionView } from "@/components/data-view/record-collection-view";
import { RecordFinder, type FinderFilter } from "@/components/record-finder/record-finder";
import { AppShell } from "@/components/shell/app-shell";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { navigationSections } from "@/config/navigation";
import { demoQuoteRuns, type QuoteRunRecord } from "@/features/quotes/demo-data";
import { type QuickAction } from "@/types/navigation";

const currencyFormatter = new Intl.NumberFormat("en-US", {
  style: "currency",
  currency: "USD",
  maximumFractionDigits: 0
});

const finderFilters: readonly FinderFilter[] = [
  {
    id: "status",
    label: "Status",
    options: [
      { value: "pending_approval", label: "Pending Approval" },
      { value: "auto_approved", label: "Auto Approved" },
      { value: "approved", label: "Approved" },
      { value: "rejected", label: "Rejected" }
    ]
  },
  {
    id: "riskLevel",
    label: "Risk",
    options: [
      { value: "low", label: "Low" },
      { value: "medium", label: "Medium" },
      { value: "high", label: "High" }
    ]
  }
] as const;

function statusTone(status: QuoteRunRecord["status"]): "neutral" | "success" | "warning" | "danger" {
  if (status === "approved" || status === "auto_approved") {
    return "success";
  }

  if (status === "pending_approval") {
    return "warning";
  }

  return "danger";
}

export default function HomePage(): JSX.Element {
  const [searchQuery, setSearchQuery] = useState("");
  const [selectedFilters, setSelectedFilters] = useState<Record<string, string>>({});

  const quickActions = useMemo<readonly QuickAction[]>(
    () => [
      { label: "New RFQ", onClick: () => undefined },
      { label: "Run Comparison", onClick: () => undefined },
      { label: "Request Approval", onClick: () => undefined }
    ],
    []
  );

  const columns = useMemo<readonly ColumnDef<QuoteRunRecord>[]>(
    () => [
      {
        accessorKey: "runId",
        header: "Run ID"
      },
      {
        accessorKey: "rfqId",
        header: "RFQ ID"
      },
      {
        accessorKey: "topVendor",
        header: "Top Vendor"
      },
      {
        accessorKey: "totalValue",
        header: "Total Value",
        cell: (context) => currencyFormatter.format(context.row.original.totalValue)
      },
      {
        accessorKey: "status",
        header: "Status",
        cell: (context) => (
          <Badge tone={statusTone(context.row.original.status)}>
            {context.row.original.status.replace("_", " ")}
          </Badge>
        )
      },
      {
        accessorKey: "riskLevel",
        header: "Risk",
        cell: (context) => (
          <Badge tone={context.row.original.riskLevel === "high" ? "danger" : context.row.original.riskLevel === "medium" ? "warning" : "success"}>
            {context.row.original.riskLevel}
          </Badge>
        )
      }
    ],
    []
  );

  const filteredRuns = useMemo(() => {
    return demoQuoteRuns.filter((record) => {
      const query = searchQuery.toLowerCase();
      const queryMatch =
        query.length === 0 ||
        record.runId.toLowerCase().includes(query) ||
        record.rfqId.toLowerCase().includes(query) ||
        record.topVendor.toLowerCase().includes(query);

      const statusMatch =
        !selectedFilters.status ||
        record.status === selectedFilters.status;

      const riskMatch =
        !selectedFilters.riskLevel ||
        record.riskLevel === selectedFilters.riskLevel;

      return queryMatch && statusMatch && riskMatch;
    });
  }, [searchQuery, selectedFilters]);

  return (
    <AppShell
      activeSidebarLabel="Dashboard"
      quickActions={quickActions}
      sections={navigationSections}
      subtitle="Configurable component foundations for agentic quotation workflows"
      title="Quotation Comparison Console"
    >
      <div className="space-y-4">
        <Card>
          <CardHeader className="flex items-center justify-between">
            <p className="text-sm font-medium text-slate-900">Record Finder (Reusable)</p>
            <AppModalLayout
              description="Example reusable modal layout component."
              title="Save Finder Preset"
              trigger={<Button type="button">Open Modal</Button>}
            >
              <form className="space-y-3">
                <label className="block text-sm text-slate-700">
                  Preset Name
                  <Input className="mt-1" placeholder="Q1 high-risk approvals" />
                </label>
                <label className="block text-sm text-slate-700">
                  Description
                  <Input className="mt-1" placeholder="Optional context" />
                </label>
                <div className="flex justify-end">
                  <Button type="button">Save</Button>
                </div>
              </form>
            </AppModalLayout>
          </CardHeader>
          <CardContent>
            <RecordFinder
              filters={finderFilters}
              onSearch={(query, filters) => {
                setSearchQuery(query);
                setSelectedFilters({ ...filters });
              }}
              placeholder="Find runs by RFQ, vendor, or run ID"
            />
          </CardContent>
        </Card>

        <RecordCollectionView
          columns={columns}
          defaultMode="table"
          emptyLabel="No quote comparison runs match your filters."
          records={filteredRuns}
          renderCard={(record) => (
            <Card key={record.runId}>
              <CardHeader className="flex items-center justify-between">
                <span className="font-medium text-slate-900">{record.runId}</span>
                <Badge tone={statusTone(record.status)}>{record.status.replace("_", " ")}</Badge>
              </CardHeader>
              <CardContent className="space-y-1 text-sm text-slate-700">
                <p>RFQ: {record.rfqId}</p>
                <p>Top Vendor: {record.topVendor}</p>
                <p>Total Value: {currencyFormatter.format(record.totalValue)}</p>
                <p className="capitalize">Risk: {record.riskLevel}</p>
              </CardContent>
            </Card>
          )}
        />
      </div>
    </AppShell>
  );
}
