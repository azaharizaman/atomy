<div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            Chart of Accounts Hierarchy
        </h3>
        
        @php
            $accountTree = $this->getAccountTree();
        @endphp
        
        @if(empty($accountTree))
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                <svg class="mx-auto h-12 w-12 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p>No accounts found. Create your first account to get started.</p>
            </div>
        @else
            <div class="space-y-2">
                @foreach($accountTree as $account)
                    <x-finance::account-tree-item :account="$account" :widget="$this" />
                @endforeach
            </div>
        @endif
    </div>
</div>
