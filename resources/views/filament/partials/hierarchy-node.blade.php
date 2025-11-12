<div class="hierarchy-node p-3 rounded-lg border {{ $node['is_current'] ? 'current-node' : 'border-gray-200' }}"
     style="margin-left: {{ $level * 20 }}px;">

    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <!-- Level indicator -->
            <div class="flex-shrink-0">
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    L{{ $node['level'] + 1 }}
                </span>
            </div>

            <!-- Template info -->
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

                    @if($node['children_count'] > 0)
                        <span class="text-xs text-gray-500">
                            {{ $node['children_count'] }} child{{ $node['children_count'] > 1 ? 'ren' : '' }}
                        </span>
                    @endif

                    @if($node['accounts_count'] > 0)
                        <span class="text-xs text-gray-500">
                            {{ $node['accounts_count'] }} account{{ $node['accounts_count'] > 1 ? 's' : '' }}
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
        @include('filament.partials.hierarchy-node', ['node' => $child, 'level' => $level + 1])
    @endforeach
@endif
