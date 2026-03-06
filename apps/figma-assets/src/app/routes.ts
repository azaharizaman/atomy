import { createBrowserRouter } from "react-router";
import { AppShell } from "./components/layout/AppShell";
import { SignIn } from "./screens/SignIn";
import { Dashboard } from "./screens/Dashboard";
import { RFQList } from "./screens/RFQList";
import { CreateRFQ } from "./screens/CreateRFQ";
import { RFQDetail } from "./screens/RFQDetail";
import { VendorInvitation } from "./screens/VendorInvitation";
import { QuoteIntake } from "./screens/QuoteIntake";
import { QuoteComparison } from "./screens/QuoteComparison";

export const router = createBrowserRouter([
  {
    path: "/",
    Component: SignIn,
  },
  {
    Component: AppShell,
    children: [
      { path: "/dashboard", Component: Dashboard },
      { path: "/rfqs", Component: RFQList },
      { path: "/rfqs/create", Component: CreateRFQ },
      { path: "/rfqs/:id", Component: RFQDetail },
      { path: "/rfqs/:id/vendors", Component: VendorInvitation },
      { path: "/quote-intake", Component: QuoteIntake },
      // Placeholder routes for nav items
      { path: "/comparison/:id?", Component: QuoteComparison },
      { path: "/approvals", Component: Dashboard },
      { path: "/reports", Component: Dashboard },
      { path: "/notifications", Component: Dashboard },
      { path: "/tasks", Component: Dashboard },
      { path: "/mentions", Component: Dashboard },
      { path: "/risk", Component: Dashboard },
      { path: "/decision-trail", Component: Dashboard },
      { path: "/evidence", Component: Dashboard },
      { path: "/users", Component: Dashboard },
      { path: "/scoring", Component: Dashboard },
      { path: "/templates", Component: Dashboard },
      { path: "/integrations", Component: Dashboard },
      { path: "/flags", Component: Dashboard },
      { path: "/profile", Component: Dashboard },
      { path: "/preferences", Component: Dashboard },
      { path: "/help", Component: Dashboard },
    ],
  },
]);
