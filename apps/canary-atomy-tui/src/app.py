from textual.app import App, ComposeResult
from textual.widgets import Header, Footer, Static, ListItem, ListView, Label, DataTable, Input, Button
from textual.containers import Container, Horizontal, Vertical, Grid
from textual.screen import Screen
from textual.binding import Binding
from textual.message import Message
from api_client import atomy_client

class Sidebar(Vertical):
    def __init__(self, *args, active_id: str = None, **kwargs):
        self.active_id = active_id
        super().__init__(*args, **kwargs)

    def compose(self) -> ComposeResult:
        yield Label("NEXUS CANARY", id="app-title")
        yield ListView(
            ListItem(Label("Tenants"), id="nav-tenants"),
            ListItem(Label("Users"), id="nav-users"),
            ListItem(Label("Features"), id="nav-features"),
            ListItem(Label("Modules"), id="nav-modules"),
            ListItem(Label("Feature Flags"), id="nav-flags"),
            id="nav-list"
        )

    def on_mount(self) -> None:
        if self.active_id:
            try:
                nav_list = self.query_one("#nav-list")
                for i, item in enumerate(nav_list.query("ListItem")):
                    if item.id == self.active_id:
                        nav_list.index = i
                        break
            except:
                pass

class LoginScreen(Screen):
    def compose(self) -> ComposeResult:
        yield Grid(
            Vertical(
                Label("Atomy Admin Login", id="login-title"),
                Input(placeholder="Email", id="email", value="admin@atomy.dev"),
                Input(placeholder="Password", password=True, id="password", value="secret"),
                Button("Login", variant="primary", id="login-btn"),
                id="login-form"
            ),
            id="login-container"
        )

    async def on_button_pressed(self, event: Button.Pressed) -> None:
        if event.button.id == "login-btn":
            email = self.query_one("#email").value
            password = self.query_one("#password").value
            
            result = await atomy_client.login(email, password)
            if result:
                self.app.pop_screen()
                self.app.notify("Login successful")
                self.app.post_message(self.app.Navigated("nav-tenants"))
            else:
                self.app.notify("Login failed", severity="error")

class BaseScreen(Screen):
    ACTIVE_NAV = None
    TITLE = ""
    def compose(self) -> ComposeResult:
        yield Header()
        yield Horizontal(
            Sidebar(id="sidebar", active_id=self.ACTIVE_NAV),
            Vertical(
                Label(self.TITLE, id="screen-title"),
                self.get_content(),
                id="main-content"
            )
        )
        yield Footer()

    def get_content(self) -> ComposeResult:
        raise NotImplementedError()

class TenantsScreen(BaseScreen):
    TITLE = "TENANT MANAGEMENT"
    ACTIVE_NAV = "nav-tenants"
    BINDINGS = [
        Binding("s", "suspend", "Suspend"),
        Binding("a", "activate", "Activate"),
        Binding("x", "archive", "Archive"),
        Binding("r", "refresh", "Refresh"),
    ]

    def get_content(self) -> ComposeResult:
        yield DataTable(id="tenants-table")

    async def on_mount(self) -> None:
        table = self.query_one("#tenants-table")
        table.cursor_type = "row"
        table.add_columns("ID", "Name", "Status", "Created At")
        await self.refresh_tenants()

    async def refresh_tenants(self):
        table = self.query_one("#tenants-table")
        table.clear()
        tenants = await atomy_client.get_tenants()
        for t in tenants:
            table.add_row(t.get("id"), t.get("name"), t.get("status"), t.get("createdAt"), key=t.get("id"))

    async def action_suspend(self):
        table = self.query_one("#tenants-table")
        if table.cursor_row is not None:
            tenant_id = table.get_row_at(table.cursor_row).key.value
            if await atomy_client.suspend_tenant(tenant_id):
                self.app.notify(f"Tenant {tenant_id} suspended")
                await self.refresh_tenants()

    async def action_activate(self):
        table = self.query_one("#tenants-table")
        if table.cursor_row is not None:
            tenant_id = table.get_row_at(table.cursor_row).key.value
            if await atomy_client.activate_tenant(tenant_id):
                self.app.notify(f"Tenant {tenant_id} activated")
                await self.refresh_tenants()

    async def action_archive(self):
        table = self.query_one("#tenants-table")
        if table.cursor_row is not None:
            tenant_id = table.get_row_at(table.cursor_row).key.value
            if await atomy_client.archive_tenant(tenant_id):
                self.app.notify(f"Tenant {tenant_id} archived")
                await self.refresh_tenants()

    async def action_refresh(self):
        await self.refresh_tenants()

class UsersScreen(BaseScreen):
    TITLE = "USER MANAGEMENT"
    ACTIVE_NAV = "nav-users"
    BINDINGS = [
        Binding("r", "refresh", "Refresh"),
    ]
    def get_content(self) -> ComposeResult:
        yield DataTable(id="users-table")

    async def on_mount(self) -> None:
        table = self.query_one("#users-table")
        table.cursor_type = "row"
        table.add_columns("ID", "Email", "Roles")
        await self.refresh_users()

    async def refresh_users(self):
        table = self.query_one("#users-table")
        table.clear()
        users = await atomy_client.get_users()
        for u in users:
            table.add_row(u.get("id"), u.get("email"), ", ".join(u.get("roles", [])), key=u.get("id"))

    async def action_refresh(self):
        await self.refresh_users()

class FeaturesScreen(BaseScreen):
    TITLE = "SYSTEM FEATURES"
    ACTIVE_NAV = "nav-features"
    def get_content(self) -> ComposeResult:
        yield Label("Feature management coming soon...")

class ModulesScreen(BaseScreen):
    TITLE = "MODULE INSTALLER"
    ACTIVE_NAV = "nav-modules"
    BINDINGS = [
        Binding("r", "refresh", "Refresh"),
    ]
    def get_content(self) -> ComposeResult:
        yield DataTable(id="modules-table")
        
    async def on_mount(self) -> None:
        table = self.query_one("#modules-table")
        table.cursor_type = "row"
        table.add_columns("Module ID", "Status", "Source Path")
        await self.refresh_modules()

    async def action_refresh(self):
        await self.refresh_modules()

    async def refresh_modules(self):
        import os
        table = self.query_one("#modules-table")
        table.clear()
        
        # 1. Get remote installed modules from API
        installed_modules = await atomy_client.get_modules()
        installed_ids = {m.get("id") for m in installed_modules}
        
        # 2. Scan local orchestrators/ folder
        # app.py is in apps/canary-atomy-tui/src/app.py
        # orchestrators is in orchestrators/
        app_dir = os.path.dirname(os.path.abspath(__file__))
        monorepo_root = os.path.abspath(os.path.join(app_dir, "..", "..", ".."))
        orchestrators_path = os.path.join(monorepo_root, "orchestrators")
        
        local_ids = set()
        if os.path.exists(orchestrators_path):
            for entry in os.scandir(orchestrators_path):
                if entry.is_dir() and not entry.name.startswith("."):
                    module_id = entry.name
                    local_ids.add(module_id)
                    status = "Installed" if module_id in installed_ids else "Available (Local)"
                    table.add_row(module_id, status, entry.path, key=module_id)
        
        # 3. Add remote-only modules if any
        for m in installed_modules:
            mid = m.get("id")
            if mid not in local_ids:
                table.add_row(mid, "Installed (Remote)", "N/A", key=mid)

    async def on_data_table_row_selected(self, event: DataTable.RowSelected) -> None:
        module_id = event.row_key.value
        # Check if already installed
        row = self.query_one("#modules-table").get_row(event.row_key)
        if row[1] == "Installed":
            self.app.notify(f"Module {module_id} is already installed", severity="warning")
            return
            
        success = await atomy_client.install_module(module_id)
        if success:
            self.app.notify(f"Module {module_id} installed successfully")
            await self.refresh_modules()
        else:
            self.app.notify(f"Failed to install module {module_id}", severity="error")

class FeatureFlagsScreen(BaseScreen):
    TITLE = "FEATURE FLAGS"
    ACTIVE_NAV = "nav-flags"
    BINDINGS = [
        Binding("r", "refresh", "Refresh"),
    ]
    def get_content(self) -> ComposeResult:
        yield DataTable(id="flags-table")

    async def on_mount(self) -> None:
        table = self.query_one("#flags-table")
        table.cursor_type = "row"
        table.add_columns("Name", "Enabled", "Strategy")
        await self.refresh_flags()

    async def refresh_flags(self):
        table = self.query_one("#flags-table")
        table.clear()
        flags = await atomy_client.get_feature_flags()
        for f in flags:
            table.add_row(f.get("name"), str(f.get("enabled")), f.get("strategy", "default"), key=f.get("name"))

    async def action_refresh(self):
        await self.refresh_flags()

class AtomyTUI(App):
    CSS = """
    $primary: #e6b800; /* Canary Yellow */
    $surface: #1e1e1e;
    
    #app-title {
        padding: 1;
        background: $primary;
        color: black;
        text-style: bold;
        text-align: center;
        margin-bottom: 1;
    }
    #sidebar {
        width: 25;
        background: $surface;
        border-right: solid $primary;
    }
    #nav-list {
        background: transparent;
    }
    #screen-title {
        padding: 1;
        text-style: bold;
        color: $primary;
    }
    #main-content {
        padding: 1;
    }
    DataTable {
        height: 100%;
    }
    #login-container {
        align: center middle;
    }
    #login-form {
        width: 40;
        height: auto;
        border: thick $primary;
        padding: 1;
        background: $surface;
    }
    #login-title {
        text-align: center;
        text-style: bold;
        margin-bottom: 1;
    }
    Input {
        margin-bottom: 1;
    }
    """

    BINDINGS = [
        Binding("q", "quit", "Quit"),
        Binding("l", "login", "Login"),
        Binding("ctrl+s", "focus_sidebar", "Focus Sidebar"),
    ]

    class Navigated(Message):
        def __init__(self, target_id: str) -> None:
            self.target_id = target_id
            super().__init__()

    def on_mount(self) -> None:
        self.push_screen(LoginScreen())

    async def on_list_view_selected(self, event: ListView.Selected) -> None:
        if event.item.id == "nav-tenants":
            self.switch_screen(TenantsScreen())
        elif event.item.id == "nav-users":
            self.switch_screen(UsersScreen())
        elif event.item.id == "nav-features":
            self.switch_screen(FeaturesScreen())
        elif event.item.id == "nav-modules":
            self.switch_screen(ModulesScreen())
        elif event.item.id == "nav-flags":
            self.switch_screen(FeatureFlagsScreen())

    def on_atomy_tui_navigated(self, message: Navigated) -> None:
        if message.target_id == "nav-tenants":
            self.switch_screen(TenantsScreen())

    def action_focus_sidebar(self) -> None:
        self.query_one("#nav-list").focus()

if __name__ == "__main__":
    app = AtomyTUI()
    app.run()
