<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tenant Domain Status - Laravel Canary</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-3xl mx-auto bg-white p-8 rounded shadow-md">
        <h1 class="text-3xl font-bold mb-6 text-indigo-700">Tenant Domain Status</h1>

        <div class="mb-8">
            <h2 class="text-xl font-semibold mb-4 border-b pb-2">Orchestrator Readiness</h2>
            @if ($ready)
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                    <p class="font-bold">System Ready</p>
                    <p>All required adapters are correctly wired in the Laravel Service Container.</p>
                </div>
            @else
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                    <p class="font-bold">System Not Ready</p>
                    <p>One or more required adapters are missing from the Service Container.</p>
                </div>

                <div class="mt-4">
                    <h3 class="font-bold text-red-800">Issues:</h3>
                    <ul class="list-disc list-inside text-red-700">
                        @foreach ($issues as $issue)
                            <li>{{ $issue }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <div class="mb-8">
            <h2 class="text-xl font-semibold mb-4 border-b pb-2">Adapter Details</h2>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Interface</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @php
                        $interfaces = [
                            'TenantCreatorAdapterInterface',
                            'AdminCreatorAdapterInterface',
                            'CompanyCreatorAdapterInterface',
                            'AuditLoggerAdapterInterface',
                            'SettingsInitializerAdapterInterface',
                            'FeatureConfiguratorAdapterInterface',
                        ];
                    @endphp
                    @foreach ($interfaces as $interface)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $interface }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @php
                                    $isMissing = collect($issues)->contains(fn($issue) => str_contains($issue, $interface));
                                @endphp
                                @if ($isMissing)
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">MISSING</span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">WIRED</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="pt-4 flex items-center justify-between">
            <a href="{{ route('tenants.onboard.form') }}" class="bg-indigo-600 text-white px-6 py-2 rounded hover:bg-indigo-700 font-bold">Go to Onboarding</a>
            <span class="text-gray-500 text-sm">Laravel v{{ Illuminate\Foundation\Application::VERSION }} (PHP v{{ PHP_VERSION }})</span>
        </div>
    </div>
</body>
</html>
