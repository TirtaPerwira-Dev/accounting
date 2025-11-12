<div class="space-y-6">
    <!-- Template Information -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Template Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Code</dt>
                    <dd class="mt-1 text-sm text-gray-900 font-mono bg-gray-100 px-2 py-1 rounded">{{ $template->code }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Name</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $template->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Type</dt>
                    <dd class="mt-1">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            @switch($template->type)
                                @case('asset') bg-green-100 text-green-800 @break
                                @case('liability') bg-yellow-100 text-yellow-800 @break
                                @case('equity') bg-blue-100 text-blue-800 @break
                                @case('revenue') bg-purple-100 text-purple-800 @break
                                @case('expense') bg-red-100 text-red-800 @break
                                @default bg-gray-100 text-gray-800
                            @endswitch">
                            {{ ucfirst($template->type) }}
                        </span>
                    </dd>
                </div>
            </div>
        </div>
    </div>

    <!-- Hierarchy Tree -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Complete Hierarchy</h3>
            <div class="space-y-2">
                @foreach($hierarchy as $node)
                    @include('filament.partials.hierarchy-node', ['node' => $node, 'level' => 0])
                @endforeach
            </div>
        </div>
    </div>
</div>

@push('scripts')
<style>
.hierarchy-node {
    transition: all 0.2s ease;
}
.hierarchy-node:hover {
    background-color: #f9fafb;
}
.current-node {
    background-color: #dbeafe;
    border: 2px solid #3b82f6;
}
</style>
@endpush
