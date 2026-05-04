import { describe, expect, it } from "vitest";

import {
    formatCompactCount,
    formatCompactCurrency,
    formatCompactMetricValue,
    formatCompactPercent,
} from "@/lib/format-compact-metric";

describe("compact metric formatting", () => {
    it("truncates dashboard currency into compact units", () => {
        expect(formatCompactCurrency(998_778.09, "USD")).toBe("$0.9M");
        expect(formatCompactCurrency(1_250_000, "USD")).toBe("$1.2M");
        expect(formatCompactCurrency(43_082_28.53, "USD")).toBe("$4.3M");
    });

    it("keeps small currency readable", () => {
        expect(formatCompactCurrency(950, "USD")).toBe("$950");
        expect(formatCompactCurrency(12_500, "USD")).toBe("$12.5K");
        expect(formatCompactCurrency("$1.2M", "USD")).toBe("$1.2M");
    });

    it("rounds percentages for glance cards", () => {
        expect(formatCompactPercent(15.04)).toBe("15%");
        expect(formatCompactPercent(15.5)).toBe("16%");
    });

    it("abbreviates only large counts", () => {
        expect(formatCompactCount(44)).toBe("44");
        expect(formatCompactCount(12_450)).toBe("12.4K");
        expect(formatCompactCount(1_250_000)).toBe("1.2M");
    });

    it("infers compact formatting for generic KPI values", () => {
        expect(formatCompactMetricValue("$998,778.09")).toBe("$0.9M");
        expect(formatCompactMetricValue("15.04%")).toBe("15%");
        expect(formatCompactMetricValue("12450")).toBe("12.4K");
        expect(formatCompactMetricValue("14 days")).toBe("14 days");
    });
});
