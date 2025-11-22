<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <form wire:submit.prevent="generateReport">
                {{ $this->form }}
                
                <div class="mt-4">
                    <x-filament::button type="submit">
                        Generate Report
                    </x-filament::button>
                </div>
            </form>
        </div>

        @php
            $data = $this->getTrialBalanceData();
        @endphp

        @if($data)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Trial Balance
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        As of {{ \Carbon\Carbon::parse($asOfDate)->format('F d, Y') }}
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Account Code
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Account Name
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Debit
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Credit
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($data['accounts'] as $account)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900 dark:text-white">
                                        {{ $account['code'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $account['name'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">
                                        @if($account['debit'] > 0)
                                            RM {{ number_format($account['debit'], 2) }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">
                                        @if($account['credit'] > 0)
                                            RM {{ number_format($account['credit'], 2) }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-gray-900 font-semibold">
                            <tr>
                                <td colspan="2" class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    Total
                                </td>
                                <td class="px-6 py-4 text-sm text-right text-gray-900 dark:text-white">
                                    RM {{ number_format($data['total_debit'], 2) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-right text-gray-900 dark:text-white">
                                    RM {{ number_format($data['total_credit'], 2) }}
                                </td>
                            </tr>
                            <tr class="{{ $data['balanced'] ? 'bg-green-50 dark:bg-green-900' : 'bg-red-50 dark:bg-red-900' }}">
                                <td colspan="4" class="px-6 py-4 text-sm text-center {{ $data['balanced'] ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200' }}">
                                    @if($data['balanced'])
                                        ✓ Trial Balance is Balanced
                                    @else
                                        ✗ Trial Balance is Out of Balance (Difference: RM {{ number_format(abs($data['total_debit'] - $data['total_credit']), 2) }})
                                    @endif
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
