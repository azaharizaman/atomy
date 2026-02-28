"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";

const navItems = [
  { href: "/", label: "Dashboard" },
  { href: "/modules", label: "Modules" },
  { href: "/users", label: "Users" },
  { href: "/feature-flags", label: "Feature Flags" },
];

export function Nav() {
  const pathname = usePathname();
  return (
    <nav className="flex items-center gap-6">
      {navItems.map(({ href, label }) => {
        const isActive = href === "/" ? pathname === "/" : pathname.startsWith(href);
        return (
          <Link
            key={href}
            href={href}
            className={`text-sm font-medium transition-colors hover:text-[var(--accent)] ${
              isActive ? "text-[var(--accent)]" : "text-zinc-400"
            }`}
          >
            {label}
          </Link>
        );
      })}
    </nav>
  );
}
