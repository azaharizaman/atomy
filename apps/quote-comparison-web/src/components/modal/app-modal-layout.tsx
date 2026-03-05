"use client";

import * as Dialog from "@radix-ui/react-dialog";
import { type ReactNode } from "react";
import { X } from "lucide-react";

import { cn } from "@/lib/cn";

export interface AppModalLayoutProps {
  readonly title: string;
  readonly description?: string;
  readonly trigger: ReactNode;
  readonly children: ReactNode;
}

export function AppModalLayout({ title, description, trigger, children }: AppModalLayoutProps): JSX.Element {
  return (
    <Dialog.Root>
      <Dialog.Trigger asChild>{trigger}</Dialog.Trigger>
      <Dialog.Portal>
        <Dialog.Overlay className="fixed inset-0 z-40 bg-slate-950/50" />
        <Dialog.Content
          className={cn(
            "fixed left-1/2 top-1/2 z-50 w-full max-w-2xl -translate-x-1/2 -translate-y-1/2 rounded-lg",
            "border border-slate-200 bg-white shadow-xl"
          )}
        >
          <div className="flex items-start justify-between border-b border-slate-200 px-4 py-3">
            <div>
              <Dialog.Title className="text-base font-semibold text-slate-900">{title}</Dialog.Title>
              {description ? <Dialog.Description className="mt-1 text-sm text-slate-600">{description}</Dialog.Description> : null}
            </div>
            <Dialog.Close className="rounded-md p-1 text-slate-500 hover:bg-slate-100 hover:text-slate-900">
              <X className="h-4 w-4" />
            </Dialog.Close>
          </div>
          <div className="max-h-[70vh] overflow-auto px-4 py-3">{children}</div>
        </Dialog.Content>
      </Dialog.Portal>
    </Dialog.Root>
  );
}
