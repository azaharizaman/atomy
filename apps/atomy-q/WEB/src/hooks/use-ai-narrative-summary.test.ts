import { describe, expect, it } from "vitest";

import { normalizeAiNarrativePayload } from "@/hooks/use-ai-narrative-summary";

describe("normalizeAiNarrativePayload", () => {
    it("reads RFQ insight narratives from generated artifact payloads", () => {
        const summary = normalizeAiNarrativePayload({
            data: {
                rfq_id: "01KQPWACR4FK17A78FMQDNGGYM",
                ai_insights: {
                    feature_key: "rfq_ai_insights",
                    available: true,
                    status: "available",
                    payload: {
                        headline: "Supplier risk needs review",
                        summary: "Three supplier findings are open.",
                        bullets: ["Review financial watch finding"],
                    },
                    provenance: {
                        provider_name: "openrouter",
                        endpoint_group: "insight",
                    },
                },
            },
        });

        expect(summary.featureKey).toBe("rfq_ai_insights");
        expect(summary.available).toBe(true);
        expect(summary.headline).toBe("Supplier risk needs review");
        expect(summary.summary).toBe("Three supplier findings are open.");
        expect(summary.bullets).toEqual(["Review financial watch finding"]);
        expect(summary.provenance?.provider_name).toBe("openrouter");
    });
});
