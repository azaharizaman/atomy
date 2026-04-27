'use client';

type ErrorWithStatus = Error & {
  status?: number;
};

function getErrorStatus(error: unknown): number | undefined {
  if (error === null || error === undefined || typeof error !== 'object') {
    return undefined;
  }

  const candidate = error as Partial<ErrorWithStatus> & { response?: { status?: number } };
  if (typeof candidate.status === 'number') {
    return candidate.status;
  }

  if (typeof candidate.response?.status === 'number') {
    return candidate.response.status;
  }

  return undefined;
}

function getErrorMessage(error: unknown): string {
  return error instanceof Error ? error.message : '';
}

function isAccessFailure(status: number | undefined): boolean {
  return status === 403 || status === 404;
}

function isInvalidStatusMessage(message: string): boolean {
  return /invalid status/i.test(message);
}

function isIncompletePayloadMessage(message: string): boolean {
  return /payload|overview|record|context|shell/i.test(message) && /missing|incomplete|unavailable|failed/i.test(message);
}

export function getRfqRecordErrorMessage(error: unknown): string {
  const status = getErrorStatus(error);
  const message = getErrorMessage(error);

  if (isAccessFailure(status)) {
    return 'This RFQ is not available in your workspace.';
  }

  if (isInvalidStatusMessage(message)) {
    return 'We found this RFQ, but its status data is incomplete.';
  }

  if (isIncompletePayloadMessage(message)) {
    return 'We found this RFQ, but its data is incomplete.';
  }

  return 'We could not load this RFQ right now. Please try again in a moment.';
}

export function getRfqOverviewErrorMessage(error: unknown): string {
  const status = getErrorStatus(error);
  const message = getErrorMessage(error);

  if (isAccessFailure(status)) {
    return 'This RFQ is not available in your workspace, so the overview cannot be shown.';
  }

  if (isInvalidStatusMessage(message) || isIncompletePayloadMessage(message)) {
    return 'We found this RFQ, but its overview data is incomplete.';
  }

  return 'We could not load the overview for this RFQ right now. Please try again in a moment.';
}

export function getRfqInsightsErrorMessage(error: unknown): string {
  const status = getErrorStatus(error);
  const message = getErrorMessage(error);

  if (isAccessFailure(status)) {
    return 'This RFQ is not available in your workspace, so insights cannot be shown.';
  }

  if (isInvalidStatusMessage(message) || isIncompletePayloadMessage(message)) {
    return 'We found this RFQ, but the data needed for insights is incomplete.';
  }

  return 'We could not load insights for this RFQ right now. Please try again in a moment.';
}
