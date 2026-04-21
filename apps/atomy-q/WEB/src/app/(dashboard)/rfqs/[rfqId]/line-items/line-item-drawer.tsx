'use client';

import React from 'react';
import { X } from 'lucide-react';

import { Button } from '@/components/ds/Button';
import { TextAreaInput, TextInput } from '@/components/ds/Input';
import { useCreateRfqLineItem } from '@/hooks/use-create-rfq-line-item';

function parseOptionalNumber(value: string): number | undefined {
  const raw = value.trim();
  if (raw === '') return undefined;

  const parsed = Number(raw);
  return Number.isFinite(parsed) ? parsed : undefined;
}

function getFocusableElements(container: HTMLElement): HTMLElement[] {
  return Array.from(
    container.querySelectorAll<HTMLElement>(
      [
        'button:not([disabled])',
        'input:not([disabled])',
        'select:not([disabled])',
        'textarea:not([disabled])',
        'a[href]',
        '[tabindex]:not([tabindex="-1"])',
      ].join(','),
    ),
  );
}

export interface LineItemDrawerProps {
  rfqId: string;
  open: boolean;
  onClose: () => void;
  onCreated?: () => Promise<void> | void;
  isWritable: boolean;
}

export function LineItemDrawer({
  rfqId,
  open,
  onClose,
  onCreated,
  isWritable,
}: LineItemDrawerProps) {
  const createLineItem = useCreateRfqLineItem(rfqId);
  const drawerRef = React.useRef<HTMLDivElement | null>(null);
  const onCloseRef = React.useRef(onClose);
  const previousActiveElementRef = React.useRef<HTMLElement | null>(null);
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

  const focusFirstElement = React.useCallback(() => {
    const drawer = drawerRef.current;
    if (!drawer) return;

    const focusableElements = getFocusableElements(drawer);
    const firstFocusable = focusableElements[0] ?? drawer;
    firstFocusable.focus();
  }, []);

  React.useEffect(() => {
    onCloseRef.current = onClose;
  }, [onClose]);

  React.useEffect(() => {
    if (!open) {
      previousActiveElementRef.current = null;
      return;
    }

    previousActiveElementRef.current = document.activeElement instanceof HTMLElement ? document.activeElement : null;
    focusFirstElement();

    const handleKeyDown = (e: KeyboardEvent) => {
      if (!open) return;

      if (e.key === 'Escape') {
        e.preventDefault();
        onCloseRef.current();
        return;
      }

      if (e.key !== 'Tab') {
        return;
      }

      const drawer = drawerRef.current;
      if (!drawer) {
        return;
      }

      const focusableElements = getFocusableElements(drawer);
      if (focusableElements.length === 0) {
        e.preventDefault();
        drawer.focus();
        return;
      }

      const currentIndex = document.activeElement instanceof HTMLElement
        ? focusableElements.indexOf(document.activeElement)
        : -1;
      const firstFocusable = focusableElements[0];
      const lastFocusable = focusableElements[focusableElements.length - 1];

      if (e.shiftKey) {
        if (currentIndex <= 0) {
          e.preventDefault();
          lastFocusable.focus();
        }
        return;
      }

      if (currentIndex === -1 || currentIndex === focusableElements.length - 1) {
        e.preventDefault();
        firstFocusable.focus();
      }
    };

    document.addEventListener('keydown', handleKeyDown);
    return () => {
      document.removeEventListener('keydown', handleKeyDown);
      const previousActiveElement = previousActiveElementRef.current;
      previousActiveElementRef.current = null;

      if (previousActiveElement && document.contains(previousActiveElement)) {
        previousActiveElement.focus();
      }
    };
  }, [focusFirstElement, open]);

  if (!open) return null;

  const submit = async (event: React.FormEvent) => {
    event.preventDefault();
    setError(null);

    if (!isWritable) {
      setError('Turn off NEXT_PUBLIC_USE_MOCKS to save line items.');
      return;
    }

    const trimmedDesc = description.trim();
    const trimmedUom = uom.trim();
    const trimmedCurrency = currency.trim().toUpperCase();
    const trimmedSpecs = specifications.trim();
    const qty = parseOptionalNumber(quantity);
    const price = parseOptionalNumber(unitPrice);

    if (!trimmedDesc) {
      setError('Description is required.');
      return;
    }
    if (!trimmedUom) {
      setError('UOM is required.');
      return;
    }
    if (!trimmedCurrency) {
      setError('Currency is required.');
      return;
    }
    if (qty === undefined) {
      setError('Quantity is required.');
      return;
    }
    if (!Number.isFinite(qty) || qty <= 0) {
      setError('Quantity must be a positive number.');
      return;
    }
    if (price === undefined) {
      setError('Unit price is required.');
      return;
    }
    if (!Number.isFinite(price) || price < 0) {
      setError('Unit price must be a non-negative number.');
      return;
    }

    try {
      await createLineItem.mutateAsync({
        description: trimmedDesc,
        quantity: qty,
        uom: trimmedUom,
        unit_price: price,
        currency: trimmedCurrency,
        specifications: trimmedSpecs || null,
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
        aria-hidden="true"
        onClick={onClose}
      />
      <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="line-item-drawer-title"
        tabIndex={-1}
        ref={drawerRef}
        className="fixed inset-y-0 right-0 w-full max-w-md bg-white border-l border-slate-200 shadow-lg z-30 flex flex-col outline-none"
      >
        <div className="flex items-center justify-between p-4 border-b border-slate-200">
          <h2 id="line-item-drawer-title" className="text-sm font-semibold text-slate-900">Add line item</h2>
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
            <TextInput id="line-item-quantity" label="Quantity" type="number" min="1" step="1" value={quantity} onChange={(e) => setQuantity(e.target.value)} />
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
