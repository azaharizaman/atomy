"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";

import { api } from "@/lib/api";
import { isObject, toText, unwrapResponse } from "@/hooks/normalize-utils";
import { useAiStatus } from "@/hooks/use-ai-status";

export interface AiNarrativeSummary {
    featureKey: string;
    available: boolean;
    headline: string | null;
    summary: string | null;
    bullets: string[];
    provenance: Record<string, unknown> | null;
    raw: Record<string, unknown> | null;
}

export interface UseAiNarrativeSummaryResult {
    summary: AiNarrativeSummary | null;
    isLoading: boolean;
    isError: boolean;
    error: Error | null;
    isHidden: boolean;
    shouldShowUnavailableMessage: boolean;
    messageKey: string | null;
    generate: (() => void) | null;
    isGenerating: boolean;
    generateError: Error | null;
    canGenerate: boolean;
}

function normalizeStringList(value: unknown): string[] {
    if (!Array.isArray(value)) {
        return [];
    }

    return value
        .map((item) => toText(item))
        .filter((item): item is string => item !== null);
}

function normalizeProvenance(value: unknown): Record<string, unknown> | null {
    return isObject(value) ? value : null;
}

function pickNarrativeContainer(
    value: Record<string, unknown>,
): Record<string, unknown> {
    const candidate =
        value.ai_narrative ??
        value.aiNarrative ??
        value.ai_summary ??
        value.aiSummary ??
        value.ai_insights ??
        value.aiInsights ??
        value;
    return isObject(candidate) ? candidate : value;
}

export function normalizeAiNarrativePayload(
    payload: unknown,
): AiNarrativeSummary {
    const raw = unwrapResponse(payload);
    if (!isObject(raw)) {
        throw new Error("AI narrative payload must be an object.");
    }

    const artifact = pickNarrativeContainer(raw);
    const narrativePayload = isObject(artifact.payload) ? artifact.payload : artifact;
    const featureKey = toText(
        artifact.feature_key ??
            artifact.featureKey ??
            narrativePayload.feature_key ??
            narrativePayload.featureKey,
    );
    if (featureKey === null) {
        throw new Error("AI narrative payload is missing feature_key.");
    }

    const provenance = normalizeProvenance(
        artifact.provenance ?? narrativePayload.provenance ?? raw.provenance,
    );
    const headline = toText(
        narrativePayload.headline ?? narrativePayload.title ?? narrativePayload.message,
    );
    const summary = toText(narrativePayload.summary ?? narrativePayload.description);
    const bullets = normalizeStringList(
        narrativePayload.bullets ??
            narrativePayload.key_points ??
            narrativePayload.keyPoints ??
            narrativePayload.highlights ??
            narrativePayload.rationale,
    );

    return {
        featureKey,
        available: artifact.available === true || narrativePayload.available === true,
        headline,
        summary,
        bullets,
        provenance,
        raw: artifact,
    };
}

export function useAiNarrativeSummary(
    path: string,
    featureKey: string,
    options?: {
        enabled?: boolean;
        queryKey?: readonly unknown[];
        generatePath?: string;
        generatePayload?: unknown;
    },
): UseAiNarrativeSummaryResult {
    const aiStatus = useAiStatus();
    const queryClient = useQueryClient();
    const isFeatureAvailable = aiStatus.isFeatureAvailable(featureKey);
    const shouldHideAiControls = aiStatus.shouldHideAiControls(featureKey);
    const shouldShowUnavailableMessage =
        aiStatus.shouldShowUnavailableMessage(featureKey);
    const messageKey = aiStatus.messageKeyForFeature(featureKey);
    const enabled =
        Boolean(path) && options?.enabled !== false && isFeatureAvailable;

    const queryKey = options?.queryKey ?? [
        "ai-narrative-summary",
        featureKey,
        path,
    ];

    const query = useQuery({
        queryKey,
        enabled,
        queryFn: async (): Promise<AiNarrativeSummary> => {
            const response = await api.get(path);

            if (response.data == null) {
                throw new Error(
                    `AI narrative "${featureKey}" is unavailable from the live API.`,
                );
            }

            return normalizeAiNarrativePayload(response.data);
        },
        retry: false,
        staleTime: 60 * 1000,
        refetchOnReconnect: false,
        refetchOnWindowFocus: false,
    });
    const mutation = useMutation({
        mutationFn: async (): Promise<AiNarrativeSummary> => {
            if (!options?.generatePath) {
                throw new Error(
                    `AI narrative "${featureKey}" does not expose a generate endpoint.`,
                );
            }

            const response = await api.post(
                options.generatePath,
                options.generatePayload ?? {},
            );

            if (response.data == null) {
                throw new Error(
                    `AI narrative "${featureKey}" generation returned no data.`,
                );
            }

            return normalizeAiNarrativePayload(response.data);
        },
        onSuccess: (summary) => {
            queryClient.setQueryData(queryKey, summary);
        },
    });
    const canGenerate =
        Boolean(options?.generatePath) &&
        isFeatureAvailable &&
        !shouldHideAiControls &&
        enabled;

    return {
        summary: query.data ?? null,
        isLoading: query.isLoading,
        isError: query.isError,
        error: query.error ?? null,
        isHidden: shouldHideAiControls,
        shouldShowUnavailableMessage,
        messageKey,
        generate: canGenerate ? () => mutation.mutate() : null,
        isGenerating: mutation.isPending,
        generateError: mutation.error ?? null,
        canGenerate,
    };
}
