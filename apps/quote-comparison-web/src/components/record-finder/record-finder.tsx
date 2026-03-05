"use client";

import { useState } from "react";
import { Search } from "lucide-react";

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";

export interface FinderFilterOption {
  readonly value: string;
  readonly label: string;
}

export interface FinderFilter {
  readonly id: string;
  readonly label: string;
  readonly options: readonly FinderFilterOption[];
}

interface RecordFinderProps {
  readonly placeholder?: string;
  readonly filters?: readonly FinderFilter[];
  readonly onSearch: (query: string, selectedFilters: Readonly<Record<string, string>>) => void;
}

export function RecordFinder({ placeholder = "Find records", filters = [], onSearch }: RecordFinderProps): JSX.Element {
  const [query, setQuery] = useState("");
  const [selectedFilters, setSelectedFilters] = useState<Record<string, string>>({});

  return (
    <section className="space-y-3 rounded-lg border border-slate-200 bg-white p-4">
      <div className="flex flex-wrap items-center gap-2">
        <label className="relative min-w-72 flex-1">
          <Search className="pointer-events-none absolute left-2 top-2.5 h-4 w-4 text-slate-400" />
          <Input
            className="pl-8"
            onChange={(event) => setQuery(event.currentTarget.value)}
            placeholder={placeholder}
            value={query}
          />
        </label>
        <Button onClick={() => onSearch(query, selectedFilters)} type="button">
          Search
        </Button>
      </div>
      <div className="flex flex-wrap gap-2">
        {filters.map((filter) => (
          <label className="inline-flex items-center gap-2 text-sm text-slate-700" key={filter.id}>
            <span>{filter.label}</span>
            <select
              className="h-9 rounded-md border border-slate-300 bg-white px-2"
              onChange={(event) =>
                setSelectedFilters((current) => ({
                  ...current,
                  [filter.id]: event.currentTarget.value
                }))
              }
              value={selectedFilters[filter.id] ?? ""}
            >
              <option value="">All</option>
              {filter.options.map((option) => (
                <option key={option.value} value={option.value}>
                  {option.label}
                </option>
              ))}
            </select>
          </label>
        ))}
      </div>
    </section>
  );
}
