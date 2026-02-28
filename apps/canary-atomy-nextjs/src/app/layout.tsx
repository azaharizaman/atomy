import type { Metadata } from "next";
import { Geist, Geist_Mono } from "next/font/google";
import "./globals.css";
import { Sidebar } from "@/components/Sidebar";
import { AuthProviderWrapper } from "@/components/AuthProviderWrapper";

const geistSans = Geist({
  variable: "--font-geist-sans",
  subsets: ["latin"],
});

const geistMono = Geist_Mono({
  variable: "--font-geist-mono",
  subsets: ["latin"],
});

export const metadata: Metadata = {
  title: "Atomy Canary | Nexus ERP",
  description: "Canary frontend for the Atomy Nexus enterprise management system",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="en">
      <body
        className={`${geistSans.variable} ${geistMono.variable} flex min-h-screen antialiased`}
      >
        <AuthProviderWrapper>
          <Sidebar />
          <main className="flex-1 overflow-auto bg-[var(--background)]">{children}</main>
        </AuthProviderWrapper>
      </body>
    </html>
  );
}
