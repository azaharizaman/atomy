import Link from "next/link";
import { Nav } from "./Nav";

export function Header() {
  return (
    <header className="sticky top-0 z-50 border-b border-[var(--border)] bg-[var(--background)]/95 backdrop-blur supports-[backdrop-filter]:bg-[var(--background)]/80">
      <div className="mx-auto flex h-14 max-w-6xl items-center justify-between px-4 sm:px-6">
        <Link
          href="/"
          className="flex items-center gap-2 font-semibold tracking-tight text-[var(--foreground)]"
        >
          <span className="text-[var(--accent)]">Atomy</span>
          <span className="text-zinc-500">/</span>
          <span className="text-zinc-400">Canary</span>
        </Link>
        <Nav />
      </div>
    </header>
  );
}
