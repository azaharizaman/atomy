'use client';

import type { AiCapabilityStatus, AiStatusSnapshot } from './ai-status';

function syntheticCapability(
  snapshot: AiStatusSnapshot,
  featureKey: string,
): AiCapabilityStatus {
  if (snapshot.mode === 'off') {
    return {
      key: featureKey,
      status: 'off',
      available: false,
      fallbackUiMode: 'message',
      messageKey: 'ai.status.off',
      operatorCritical: false,
      reasonCodes: snapshot.reasonCodes,
      diagnostics: null,
    };
  }

  if (snapshot.requestState === 'error') {
    return {
      key: featureKey,
      status: 'unknown',
      available: false,
      fallbackUiMode: 'message',
      messageKey: 'ai.status.unavailable',
      operatorCritical: false,
      reasonCodes: snapshot.reasonCodes,
      diagnostics: null,
    };
  }

  if (snapshot.mode === 'deterministic') {
    return {
      key: featureKey,
      status: 'deterministic',
      available: false,
      fallbackUiMode: 'message',
      messageKey: snapshot.reasonCodes.includes('AI_STATUS_MOCK_MODE') ? 'ai.status.mock_mode' : 'ai.status.degraded',
      operatorCritical: false,
      reasonCodes: snapshot.reasonCodes,
      diagnostics: null,
    };
  }

  if (snapshot.globalHealth === 'degraded') {
    return {
      key: featureKey,
      status: 'degraded',
      available: false,
      fallbackUiMode: 'message',
      messageKey: 'ai.status.degraded',
      operatorCritical: false,
      reasonCodes: snapshot.reasonCodes,
      diagnostics: null,
    };
  }

  return {
    key: featureKey,
    status: 'unknown',
    available: false,
    fallbackUiMode: 'hide',
    messageKey: 'ai.feature.unavailable',
    operatorCritical: false,
    reasonCodes: snapshot.reasonCodes,
    diagnostics: null,
  };
}

export function capabilityForFeature(
  snapshot: AiStatusSnapshot,
  featureKey: string,
): AiCapabilityStatus {
  return snapshot.capabilityStatuses[featureKey] ?? syntheticCapability(snapshot, featureKey);
}

export function isFeatureAvailable(snapshot: AiStatusSnapshot, featureKey: string): boolean {
  return capabilityForFeature(snapshot, featureKey).available;
}

export function shouldHideAiControls(snapshot: AiStatusSnapshot, featureKey: string): boolean {
  if (snapshot.mode === 'off' || snapshot.requestState === 'error') {
    return true;
  }

  return capabilityForFeature(snapshot, featureKey).fallbackUiMode === 'hide';
}

export function shouldShowUnavailableMessage(snapshot: AiStatusSnapshot, featureKey: string): boolean {
  if (isFeatureAvailable(snapshot, featureKey)) {
    return false;
  }

  if (snapshot.mode === 'off' || snapshot.requestState === 'error') {
    return true;
  }

  const capability = capabilityForFeature(snapshot, featureKey);

  return capability.fallbackUiMode === 'message' || capability.fallbackUiMode === 'unknown';
}

export function messageKeyForFeature(snapshot: AiStatusSnapshot, featureKey: string): string | null {
  return capabilityForFeature(snapshot, featureKey).messageKey;
}
