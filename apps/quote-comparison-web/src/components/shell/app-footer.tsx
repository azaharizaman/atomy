export function AppFooter(): JSX.Element {
  return (
    <footer className="flex flex-wrap items-center justify-between gap-2 border-t border-slate-200 bg-white px-6 py-3 text-xs text-slate-600">
      <p>Atomy-Q v0.1.0</p>
      <p className="rounded bg-slate-100 px-2 py-0.5 text-slate-700">Environment: Staging</p>
      <div className="flex items-center gap-4">
        <a className="hover:text-slate-900" href="#">System Status</a>
        <a className="hover:text-slate-900" href="#">API Docs</a>
        <a className="hover:text-slate-900" href="#">Privacy</a>
      </div>
    </footer>
  );
}
