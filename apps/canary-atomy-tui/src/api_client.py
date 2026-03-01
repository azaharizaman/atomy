import httpx
import os
from dotenv import load_dotenv

# Calculate path relative to this file
_BASE_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
_ENV_PATH = os.path.join(_BASE_DIR, ".env")
load_dotenv(_ENV_PATH)

class AtomyClient:
    def __init__(self):
        self.base_url = os.getenv("API_BASE_URL", "http://localhost:8000")
        self.access_token = None
        self.tenant_id = os.getenv("API_TENANT_ID")

    async def login(self, email=None, password=None, tenant_id=None):
        email = email or os.getenv("API_EMAIL")
        password = password or os.getenv("API_PASSWORD")
        tenant_id = tenant_id or self.tenant_id or ""

        async with httpx.AsyncClient() as client:
            json_data = {"email": email, "password": password}
            if tenant_id:
                json_data["tenantId"] = tenant_id

            response = await client.post(
                f"{self.base_url}/auth/login",
                json=json_data
            )
            if response.status_code == 200:
                data = response.json()
                self.access_token = data.get("accessToken")
                return data
            return None

    async def refresh(self, refresh_token, tenant_id=None):
        tenant_id = tenant_id or self.tenant_id or ""
        async with httpx.AsyncClient() as client:
            json_data = {"refreshToken": refresh_token}
            if tenant_id:
                json_data["tenantId"] = tenant_id
            response = await client.post(
                f"{self.base_url}/auth/refresh",
                json=json_data
            )
            if response.status_code == 200:
                data = response.json()
                self.access_token = data.get("accessToken")
                return data
            return None

    async def logout(self, user_id, session_id=None, tenant_id=None):
        tenant_id = tenant_id or self.tenant_id or ""
        async with httpx.AsyncClient() as client:
            json_data = {"userId": user_id, "sessionId": session_id}
            if tenant_id:
                json_data["tenantId"] = tenant_id
            response = await client.post(
                f"{self.base_url}/auth/logout",
                json=json_data
            )
            return response.status_code == 200

    def _get_headers(self):
        headers = {"Accept": "application/json"}
        if self.access_token:
            headers["Authorization"] = f"Bearer {self.access_token}"
        return headers

    async def get_tenants(self):
        async with httpx.AsyncClient() as client:
            response = await client.get(
                f"{self.base_url}/api/tenants",
                headers=self._get_headers()
            )
            if response.status_code == 200:
                return response.json()
            return []

    async def get_users(self):
        async with httpx.AsyncClient() as client:
            response = await client.get(
                f"{self.base_url}/api/users",
                headers=self._get_headers()
            )
            if response.status_code == 200:
                return response.json()
            return []

    async def get_feature_flags(self):
        async with httpx.AsyncClient() as client:
            response = await client.get(
                f"{self.base_url}/api/feature-flags",
                headers=self._get_headers()
            )
            if response.status_code == 200:
                return response.json()
            return []

    async def get_modules(self):
        async with httpx.AsyncClient() as client:
            response = await client.get(
                f"{self.base_url}/api/modules",
                headers=self._get_headers()
            )
            if response.status_code == 200:
                return response.json()
            return []

    async def install_module(self, module_id):
        async with httpx.AsyncClient() as client:
            response = await client.post(
                f"{self.base_url}/api/modules/{module_id}/install",
                headers=self._get_headers()
            )
            return response.status_code == 200 or response.status_code == 201

    async def suspend_tenant(self, tenant_id):
        async with httpx.AsyncClient() as client:
            response = await client.post(f"{self.base_url}/api/tenants/{tenant_id}/suspend", headers=self._get_headers())
            return response.status_code == 200

    async def activate_tenant(self, tenant_id):
        async with httpx.AsyncClient() as client:
            response = await client.post(f"{self.base_url}/api/tenants/{tenant_id}/activate", headers=self._get_headers())
            return response.status_code == 200

    async def archive_tenant(self, tenant_id):
        async with httpx.AsyncClient() as client:
            response = await client.post(f"{self.base_url}/api/tenants/{tenant_id}/archive", headers=self._get_headers())
            return response.status_code == 200

atomy_client = AtomyClient()
