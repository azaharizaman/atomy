'use client';

import React from 'react';
import { X } from 'lucide-react';

import { Button } from '@/components/ds/Button';
import { TextAreaInput, TextInput } from '@/components/ds/Input';
import { useCreateRfqLineItem } from '@/hooks/use-create-rfq-line-item';

export function LineItemDrawer({
  rfqId,
  open,
  onClose,
  onCreated,
  isWritable,
}: {
  rfqId: string;
  open: boolean;
  onClose: () => void;
  onCreated?: () => Promise<void> | void;
  isWritable: boolean;
}) {
  const createLineItem = useCreateRfqLineItem(rfqId);
  const [description, setDescription] = React.useState('');
  const [quantity, setQuantity] = React.useState('1');
  const [uom, setUom] = React.useState('');
  const [unitPrice, setUnitPrice] = React.useState('0');
  const [currency, setCurrency] = React.useState('USD');
  const [specifications, setSpecifications] = React.useState('');
  const [error, setError] = React.useState<string | null>(null);

  React.useEffect(() => {
    if (open) {
      setDescription('');
      setQuantity('1');
      setUom('');
      setUnitPrice('0');
      setCurrency('USD');
      setSpecifications('');
      setError(null);
    }
  }, [open]);

  if (!open) return null;

  const submit = async (event: React.FormEvent) => {
    event.preventDefault();
    setError(null);

    if (!isWritable) {
      setError('Turn off NEXT_PUBLIC_USE_MOCKS to save line items.');
      return;
    }

    try {
      await createLineItem.mutateAsync({
        description: description.trim(),
        quantity: Number(quantity),
        uom: uom.trim(),
        unit_price: Number(unitPrice),
        currency: currency.trim().toUpperCase(),
        specifications: specifications.trim() ? specifications.trim() : null,
      });
      await onCreated?.();
      onClose();
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Could not create line item.');
    }
  };

  return (
    <>
      <div
        className="fixed inset-0 bg-black/20 z-20"
        role="button"
        tabIndex={0}
        aria-label="Close line item drawer"
        onClick={onClose}
        onKeyDown={(e) => {
          if (e.key === 'Enter' || e.key === ' ' || e.key === 'Escape') {
            e.preventDefault();
            onClose();
          }
        }}
      />
      <div className="fixed inset-y-0 right-0 w-full max-w-md bg-white border-l border-slate-200 shadow-lg z-30 flex flex-col">
        <div className="flex items-center justify-between p-4 border-b border-slate-200">
          <h2 className="text-sm font-semibold text-slate-900">Add line item</h2>
          <Button size="sm" variant="secondary" onClick={onClose}>
            <X size={14} className="mr-1.5" />
            Close
          </Button>
        </div>
        <form onSubmit={submit} className="flex-1 overflow-y-auto p-4 space-y-4">
          {!isWritable && (
            <div className="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
              Mock mode is read-only for line-item creation. Turn off NEXT_PUBLIC_USE_MOCKS to save.
            </div>
          )}
          {error && <div className="text-sm text-red-600">{error}</div>}
          <TextAreaInput id="line-item-description" label="Description" value={description} onChange={(e) => setDescription(e.target.value)} />
          <div className="grid grid-cols-2 gap-3">
            <TextInput id="line-item-quantity" label="Quantity" type="number" min="0" step="1" value={quantity} onChange={(e) => setQuantity(e.target.value)} />
            <TextInput id="line-item-uom" label="UOM" value={uom} onChange={(e) => setUom(e.target.value)} />
            <TextInput id="line-item-unit-price" label="Unit price" type="number" min="0" step="0.01" value={unitPrice} onChange={(e) => setUnitPrice(e.target.value)} />
            <TextInput id="line-item-currency" label="Currency" value={currency} onChange={(e) => setCurrency(e.target.value)} />
          </div>
          <TextAreaInput id="line-item-specifications" label="Specifications" value={specifications} onChange={(e) => setSpecifications(e.target.value)} />
          <div className="flex items-center justify-end gap-2 pt-2">
            <Button type="button" variant="ghost" onClick={onClose} disabled={createLineItem.isPending}>
              Cancel
            </Button>
            <Button type="submit" variant="primary" loading={createLineItem.isPending} disabled={!isWritable}>
              Save line item
            </Button>
          </div>
        </form>
      </div>
    </>
  );
}
