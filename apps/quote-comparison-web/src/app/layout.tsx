import type { Metadata } from "next";
import { type ReactNode } from "react";

import "./globals.css";

export const metadata: Metadata = {
  title: "Atomy-Q | Agentic Quote Comparison",
  description: "Frontend app for quotation comparison workflows"
};

export default function RootLayout({ children }: { readonly children: ReactNode }): JSX.Element {
  return (
    <html lang="en">
      <body>{children}</body>
    </html>
  );
}
