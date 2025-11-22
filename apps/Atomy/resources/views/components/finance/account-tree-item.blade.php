@props(['account', 'widget', 'level' => 0])

<div class="account-item" style="margin-left: {{ $level * 20 }}px;">
    <div class="flex items-center justify-between py-2 px-3 hover:bg-gray-50 dark:hover:bg-gray-700 rounded">
        <div class="flex items-center space-x-3">
            @if($account['is_header'])
                <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                </svg>
            @else
                <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            @endif
            
            <span class="text-sm font-mono text-gray-600 dark:text-gray-400">{{ $account['code'] }}</span>
            <span class="text-sm {{ $account['is_header'] ? 'font-semibold' : '' }} text-gray-900 dark:text-white">
                {{ $account['name'] }}
            </span>
            
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-{{ $widget->getTypeBadgeColor($account['type']) }}-100 text-{{ $widget->getTypeBadgeColor($account['type']) }}-800 dark:bg-{{ $widget->getTypeBadgeColor($account['type']) }}-900 dark:text-{{ $widget->getTypeBadgeColor($account['type']) }}-200">
                {{ $account['type'] }}
            </span>
        </div>
    </div>
    
    @if(isset($account['children']) && count($account['children']) > 0)
        @foreach($account['children'] as $child)
            <x-finance::account-tree-item :account="$child" :widget="$widget" :level="$level + 1" />
        @endforeach
    @endif
</div>
