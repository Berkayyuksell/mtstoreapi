<x-filament-panels::page>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

        {{-- ===================== SOL PANEL: Kontroller ===================== --}}
        <div class="flex flex-col gap-4">
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="fi-section-header px-6 py-4 border-b border-gray-200 dark:border-white/10">
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white flex items-center gap-2">
                        <x-heroicon-o-cog-6-tooth class="w-5 h-5 text-primary-500" />
                        İstek Ayarları
                    </h2>
                </div>

                <div class="p-6 space-y-5">

                    {{-- Kullanıcı Seç --}}
                    <div class="space-y-1.5">
                        <label class="fi-fo-field-wrp-label block text-sm font-medium leading-6 text-gray-950 dark:text-white">
                            Kullanıcı
                        </label>
                        <select
                            wire:model.live="userId"
                            class="fi-select-input block w-full rounded-lg border-0 py-1.5 text-gray-950 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-primary-600 dark:bg-white/5 dark:text-white dark:ring-white/20 text-sm"
                        >
                            <option value="">— Kullanıcı seçin —</option>
                            @foreach($this->getUsers() as $id => $label)
                                <option value="{{ $id }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Endpoint Seç --}}
                    <div class="space-y-1.5">
                        <label class="fi-fo-field-wrp-label block text-sm font-medium leading-6 text-gray-950 dark:text-white">
                            Endpoint
                        </label>
                        <select
                            wire:model.live="endpointKey"
                            class="fi-select-input block w-full rounded-lg border-0 py-1.5 text-gray-950 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-primary-600 dark:bg-white/5 dark:text-white dark:ring-white/20 text-sm"
                        >
                            <option value="">— Endpoint seçin —</option>
                            @foreach($this->getEndpointGroups() as $group => $endpoints)
                                <optgroup label="{{ $group }}">
                                    @foreach($endpoints as $key => $config)
                                        <option value="{{ $key }}">
                                            {{ $config['method'] }} — {{ $config['label'] }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>

                    {{-- Dinamik Parametreler --}}
                    @php $endpointConfig = $this->getSelectedEndpointConfig(); @endphp

                    @if($endpointConfig && count($endpointConfig['params']) > 0)
                        <div class="rounded-lg bg-gray-50 dark:bg-white/5 p-4 space-y-3">
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Parametreler
                            </p>
                            @foreach($endpointConfig['params'] as $param)
                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ $param['label'] }}
                                        @if($param['required'])
                                            <span class="text-danger-500">*</span>
                                        @endif
                                    </label>
                                    <input
                                        type="text"
                                        wire:model="paramValues.{{ $param['key'] }}"
                                        placeholder="{{ $param['required'] ? 'Zorunlu' : 'İsteğe bağlı' }}"
                                        class="block w-full rounded-lg border-0 py-1.5 text-sm text-gray-950 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-white/5 dark:text-white dark:ring-white/20 dark:placeholder:text-gray-500"
                                    />
                                </div>
                            @endforeach
                        </div>
                    @elseif($endpointConfig)
                        <div class="rounded-lg bg-gray-50 dark:bg-white/5 px-4 py-3">
                            <p class="text-sm text-gray-500 dark:text-gray-400 italic">Bu endpoint parametre gerektirmiyor.</p>
                        </div>
                    @endif

                    {{-- Hata Mesajı --}}
                    @if($errorMessage)
                        <div class="rounded-lg bg-danger-50 dark:bg-danger-500/10 px-4 py-3 ring-1 ring-danger-200 dark:ring-danger-500/20">
                            <p class="text-sm text-danger-600 dark:text-danger-400 flex items-center gap-2">
                                <x-heroicon-o-exclamation-circle class="w-4 h-4 flex-shrink-0" />
                                {{ $errorMessage }}
                            </p>
                        </div>
                    @endif

                    {{-- Gönder Butonu --}}
                    <button
                        wire:click="sendRequest"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-60 cursor-not-allowed"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 disabled:opacity-60 disabled:cursor-not-allowed transition"
                    >
                        <span wire:loading.remove wire:target="sendRequest">
                            <x-heroicon-o-paper-airplane class="w-4 h-4" />
                        </span>
                        <span wire:loading wire:target="sendRequest">
                            <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </span>
                        <span wire:loading.remove wire:target="sendRequest">İstek Gönder</span>
                        <span wire:loading wire:target="sendRequest">Gönderiliyor...</span>
                    </button>

                </div>
            </div>
        </div>

        {{-- ===================== SAĞ PANEL: Yanıt ===================== --}}
        <div class="flex flex-col gap-4">
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 h-full">
                <div class="fi-section-header px-6 py-4 border-b border-gray-200 dark:border-white/10 flex items-center justify-between">
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white flex items-center gap-2">
                        <x-heroicon-o-code-bracket class="w-5 h-5 text-primary-500" />
                        Yanıt
                    </h2>

                    @if($responseStatus !== null)
                        <div class="flex items-center gap-3">
                            {{-- Status Badge --}}
                            <span @class([
                                'inline-flex items-center rounded-md px-2 py-1 text-xs font-bold ring-1 ring-inset',
                                'bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-500/10 dark:text-success-400 dark:ring-success-500/20' => $responseStatus >= 200 && $responseStatus < 300,
                                'bg-warning-50 text-warning-700 ring-warning-600/20 dark:bg-warning-500/10 dark:text-warning-400 dark:ring-warning-500/20' => $responseStatus >= 400 && $responseStatus < 500,
                                'bg-danger-50 text-danger-700 ring-danger-600/20 dark:bg-danger-500/10 dark:text-danger-400 dark:ring-danger-500/20'   => $responseStatus >= 500,
                            ])>
                                HTTP {{ $responseStatus }}
                            </span>

                            {{-- Response Time --}}
                            @if($responseTime !== null)
                                <span class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                    <x-heroicon-o-clock class="w-3.5 h-3.5" />
                                    {{ $responseTime }} ms
                                </span>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="p-4">
                    @if($responseJson !== null)
                        {{-- Copy Button + JSON --}}
                        <div class="relative">
                            <button
                                onclick="navigator.clipboard.writeText(document.getElementById('response-json').innerText).then(() => { this.innerText = 'Kopyalandı!'; setTimeout(() => this.innerText = 'Kopyala', 2000); })"
                                class="absolute top-2 right-2 z-10 rounded-md bg-gray-100 dark:bg-white/10 px-2 py-1 text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-white/20 transition"
                            >
                                Kopyala
                            </button>
                            <pre
                                id="response-json"
                                class="overflow-auto max-h-[600px] rounded-lg bg-gray-950 dark:bg-gray-800 p-4 text-xs text-green-400 leading-relaxed font-mono whitespace-pre-wrap break-words"
                            >{{ $responseJson }}</pre>
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-20 text-center">
                            <x-heroicon-o-signal class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" />
                            <p class="text-sm text-gray-500 dark:text-gray-400">Henüz istek gönderilmedi.</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Kullanıcı ve endpoint seçip "İstek Gönder"e basın.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>

</x-filament-panels::page>
