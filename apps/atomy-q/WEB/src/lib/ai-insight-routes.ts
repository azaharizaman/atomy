'use client';

export type SmartAiInsightRoute = {
  featureKey: string;
  queryPath: string;
  queryKey: readonly unknown[];
  generatePath: string;
  generatePayload?: unknown;
};

function rfqInsightRoute(pathname: string): SmartAiInsightRoute | null {
  const segments = pathname.split('/').filter(Boolean);
  if (segments[0] !== 'rfqs' || !segments[1] || segments[1] === 'new') {
    return null;
  }

  const section = segments[2] ?? 'overview';
  if (!['overview', 'risk'].includes(section)) {
    return null;
  }

  const rfqId = segments[1];

  return {
    featureKey: 'rfq_ai_insights',
    queryPath: `/risk-items?rfqId=${encodeURIComponent(rfqId)}`,
    queryKey: ['rfqs', rfqId, 'ai-summary'],
    generatePath: '/risk-items/generate',
    generatePayload: { rfq_id: rfqId },
  };
}

export function resolveSmartAiInsightRoute(pathname: string): SmartAiInsightRoute | null {
  if (pathname === '/') {
    return {
      featureKey: 'dashboard_ai_summary',
      queryPath: '/dashboard/kpis',
      queryKey: ['dashboard', 'ai-summary'],
      generatePath: '/dashboard/kpis/generate',
    };
  }

  if (pathname === '/reporting') {
    return {
      featureKey: 'reporting_ai_summary',
      queryPath: '/reports/kpis',
      queryKey: ['reporting', 'ai-summary'],
      generatePath: '/reports/kpis/generate',
    };
  }

  return rfqInsightRoute(pathname);
}
