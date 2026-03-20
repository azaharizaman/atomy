import { redirect } from 'next/navigation';

export default async function RfqScopedApprovalRedirectPage({
  params,
}: {
  params: Promise<{ approvalId: string }>;
}) {
  const { approvalId } = await params;
  redirect(`/approvals/${encodeURIComponent(approvalId)}`);
}
