<x-dynamic-component :component="$getFieldWrapperView()" :id="$getId()" :label="$getLabel()" :label-sr-only="$isLabelHidden()" :helper-text="$getHelperText()"
    :hint="$getHint()" :hint-icon="$getHintIcon()" :required="$isRequired()" :state-path="$getStatePath()" :field="$field">

    <div
        class="relative flex items-center justify-start pe-6 overflow-hidden transition duration-75 bg-white rounded-lg shadow-sm fi-input-wrp ring-1 focus-within:ring-2 dark:bg-white/5 ring-gray-950/10 focus-within:ring-primary-600 dark:ring-white/20 dark:focus-within:ring-primary-500 fi-fo-text-input">


        <input {{ $isDisabled() ? 'disabled' : '' }} type="time" x-ref="timePicker"
           x-data="mdtimepicker($refs.timePicker, {
            okLabel: '{{ $getOkLabel() }}',
            cancelLabel: '{{ $getCancelLabel() }}',
            format: 'HH:mm',
            timeFormat: 'HH:mm',
            theme: 'material', // atau default, tergantung library
            hourPadding: true, // opsional
            events: {
                timeChanged: function(data, timepicker) {
                    @this.set('{!! $getStatePath() !!}', data.time);
                },
            }
        })"



            {{ $applyStateBindingModifiers('wire:model') }}="{{ $getStatePath() }}" @class([
                'time-input-picker fi-input block w-full border-none bg-transparent py-1.5 text-base text-gray-950 outline-none transition duration-75 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.400)] dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] dark:disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.500)] sm:text-sm sm:leading-6',

            ])>
        <span
            class="absolute inset-y-0 right-0 flex items-center mx-2 pointer-events-none rtl:right-auto rtl:left-0">
            <svg @class([
                'w-5 h-5 text-gray-400',
                'dark:text-gray-400' => config('forms.dark_mode'),
            ]) xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
        </span>
    </div>
</x-dynamic-component>
