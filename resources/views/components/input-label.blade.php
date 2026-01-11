@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-bold text-md text-gray-700']) }}>
    @if($value)
        @php
            $labelText = e($value);
            // Replace ' *' with space + red asterisk
            $labelText = str_replace(' *', ' <span class="text-red-500">*</span>', $labelText);
        @endphp
        {!! $labelText !!}
    @else
        {{ $slot }}
    @endif
</label>
