<div class="account-hierarchy-node p-3 rounded-lg border {{ $node['is_current'] ? 'current-account-node' : 'border-gray-200' }}"
     style="margin-left: {{ $level * 20 }}px;">

    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <!-- Level indicator -->
            <div class="shrink-0">
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    L{{ $node['level'] + 1 }}
                </span>
            </div>

            <!-- Account info -->
            <div>
                <div class="flex items-center space-x-2">
                    <span class="font-mono text-sm font-medium text-gray-900">{{ $node['code'] }}</span>
                    <span class="text-sm text-gray-900">{{ $node['name'] }}</span>

                    @if($node['is_current'])
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            Current
                        </span>
                    @endif

                    @if(!$node['is_active'])
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            Inactive
                        </span>
                    @endif

                    @if($node['template_code'])
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            Template: {{ $node['template_code'] }}
                        </span>
                    @endif
                </div>

                <div class="flex items-center space-x-4 mt-1">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        @switch($node['type'])
                            @case('asset') bg-green-100 text-green-800 @break
                            @case('liability') bg-yellow-100 text-yellow-800 @break
                            @case('equity') bg-blue-100 text-blue-800 @break
                            @case('revenue') bg-purple-100 text-purple-800 @break
                            @case('expense') bg-red-100 text-red-800 @break
                            @default bg-gray-100 text-gray-800
                        @endswitch">
                        {{ ucfirst($node['type']) }}
                    </span>

                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        @if($node['normal_balance'] === 'debit') bg-green-100 text-green-800 @else bg-blue-100 text-blue-800 @endif">
                        {{ ucfirst($node['normal_balance']) }}
                    </span>

                    @if($node['current_balance'] != 0)
                        <span class="text-xs {{ $node['current_balance'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                            Balance: Rp {{ number_format(abs($node['current_balance']), 2) }}
                            {{ $node['current_balance'] > 0 ? '(Debit)' : '(Credit)' }}
                        </span>
                    @endif

                    @if($node['children_count'] > 0)
                        <span class="text-xs text-gray-500">
                            {{ $node['children_count'] }} sub account{{ $node['children_count'] > 1 ? 's' : '' }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Render children -->
@if(count($node['children']) > 0)
    @foreach($node['children'] as $child)
        @include('filament.partials.account-hierarchy-node', ['node' => $child, 'level' => $level + 1])
    @endforeach
@endif
