export interface QuoteRunRecord {
  readonly runId: string;
  readonly rfqId: string;
  readonly status: "pending_approval" | "auto_approved" | "approved" | "rejected";
  readonly topVendor: string;
  readonly totalValue: number;
  readonly riskLevel: "low" | "medium" | "high";
  readonly createdAt: string;
}

export const demoQuoteRuns: readonly QuoteRunRecord[] = [
  {
    runId: "RUN-2026-0001",
    rfqId: "RFQ-2026-011",
    status: "pending_approval",
    topVendor: "VertexCloud",
    totalValue: 384500,
    riskLevel: "high",
    createdAt: "2026-03-06T02:20:00Z"
  },
  {
    runId: "RUN-2026-0002",
    rfqId: "RFQ-2026-012",
    status: "auto_approved",
    topVendor: "Altis Systems",
    totalValue: 121900,
    riskLevel: "low",
    createdAt: "2026-03-06T01:10:00Z"
  },
  {
    runId: "RUN-2026-0003",
    rfqId: "RFQ-2026-013",
    status: "approved",
    topVendor: "BlueSentry",
    totalValue: 241300,
    riskLevel: "medium",
    createdAt: "2026-03-05T22:45:00Z"
  }
] as const;
