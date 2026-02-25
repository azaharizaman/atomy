<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tenant Onboarding - Laravel Canary</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-2xl mx-auto bg-white p-8 rounded shadow-md">
        <h1 class="text-3xl font-bold mb-6 text-indigo-700">Tenant Onboarding</h1>

        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('tenants.onboard') }}" method="POST" class="space-y-4">
            @csrf
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Code</label>
                    <input type="text" name="code" value="{{ old('code') }}" class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-2 border" placeholder="ACME">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-2 border" placeholder="Acme Corp">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Domain</label>
                <input type="text" name="domain" value="{{ old('domain') }}" class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-2 border" placeholder="acme.test">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Admin Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-2 border" placeholder="admin@acme.test">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Admin Password</label>
                    <input type="password" name="password" class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-2 border" placeholder="********">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Plan</label>
                <select name="plan" class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-2 border">
                    <option value="starter" {{ old('plan') == 'starter' ? 'selected' : '' }}>Starter</option>
                    <option value="professional" {{ old('plan') == 'professional' ? 'selected' : '' }}>Professional</option>
                    <option value="enterprise" {{ old('plan') == 'enterprise' ? 'selected' : '' }}>Enterprise</option>
                </select>
            </div>

            <div class="pt-4 flex items-center justify-between">
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded hover:bg-indigo-700 font-bold">Onboard Tenant</button>
                <a href="{{ route('tenants.status') }}" class="text-indigo-600 hover:underline">Check Readiness Status</a>
            </div>
        </form>
    </div>
</body>
</html>
