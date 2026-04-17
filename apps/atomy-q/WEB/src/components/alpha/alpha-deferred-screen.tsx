'use client';

import { Clock3 } from 'lucide-react';

import { ALPHA_DEFERRED_COPY } from '@/lib/alpha-mode';

import { EmptyState, SectionCard } from '@/components/ds/Card';
import { PageHeader } from '@/components/ds/FilterBar';

interface AlphaDeferredScreenProps {
  title: string;
  subtitle?: string;
}

export function AlphaDeferredScreen({ title, subtitle }: AlphaDeferredScreenProps) {
  return (
    <div className="space-y-6">
      <PageHeader title={title} subtitle={subtitle} />
      <SectionCard title="Deferred in alpha" subtitle={title}>
        <EmptyState
          icon={<Clock3 size={20} />}
          title="Deferred in alpha"
          description={ALPHA_DEFERRED_COPY}
        />
      </SectionCard>
    </div>
  );
}
