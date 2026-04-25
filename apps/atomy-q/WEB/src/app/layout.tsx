import type { Metadata } from "next";
import "./globals.css";
import QueryProvider from "@/providers/query-provider";
import AuthProvider from "@/providers/auth-provider";
import { AiProvider } from "@/providers/ai-provider";
import { Toaster } from "sonner";

export const metadata: Metadata = {
  title: "Atomy-Q",
  description: "Quote Comparison & Procurement",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="en">
      <body className="antialiased">
        <QueryProvider>
          <AiProvider>
            <AuthProvider>
              {children}
              <Toaster />
            </AuthProvider>
          </AiProvider>
        </QueryProvider>
      </body>
    </html>
  );
}
