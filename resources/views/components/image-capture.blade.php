@props(['name', 'label', 'value' => null])

<div class="mt-4">
    <x-input-label :for="$name" :value="$label" />
    
    <div class="mt-2">
        <!-- Hidden file input -->
        <input 
            type="file" 
            id="{{ $name }}_input" 
            name="{{ $name }}" 
            accept="image/*" 
            capture="environment"
            class="hidden"
        />
        
        <!-- Preview and capture button container -->
        <div class="flex flex-col items-center gap-4">
            <!-- Image preview -->
            <div id="{{ $name }}_preview_container" class="{{ $value ? '' : 'hidden' }} w-full max-w-md">
                <img 
                    id="{{ $name }}_preview" 
                    src="{{ $value ? asset('storage/' . $value) : '' }}" 
                    alt="Preview" 
                    class="w-full h-auto rounded-lg border-2 border-gray-300 object-cover max-h-64"
                />
            </div>
            
            <!-- Buttons -->
            <div class="flex gap-2 w-full max-w-md">
                <button 
                    type="button" 
                    data-action="camera"
                    data-field="{{ $name }}"
                    class="image-capture-btn flex-1 px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="inline-block w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Capture Photo
                </button>
                
                <button 
                    type="button" 
                    data-action="select"
                    data-field="{{ $name }}"
                    class="image-capture-btn flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="inline-block w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    Choose File
                </button>
                
                <button 
                    type="button" 
                    id="{{ $name }}_remove_btn"
                    data-action="remove"
                    data-field="{{ $name }}"
                    class="image-capture-btn {{ $value ? '' : 'hidden' }} px-4 py-2 text-sm font-medium text-red-700 bg-white border border-red-300 rounded-md hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    <svg class="inline-block w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    
    <x-input-error :messages="$errors->get($name)" class="mt-2" />
</div>

