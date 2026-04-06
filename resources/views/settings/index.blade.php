@extends('traffic-sentinel::layout')

@php
    use Illuminate\Support\Str;
@endphp

@section('content')
    <div class="ts-card">

        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-gear me-2"></i>Settings
            </h5>
        </div>

        <form method="POST" action="{{ route('traffic-sentinel.settings.save') }}">
            @csrf

            {{-- Tabs --}}
            <ul class="nav nav-tabs px-3 pt-3" role="tablist">
                @foreach($schema ?? [] as $tab => $fields)
                    @php $tabId = 'tab-'.Str::slug($tab); @endphp

                    <li class="nav-item">
                        <button type="button"
                                class="nav-link {{ $loop->first ? 'active' : '' }}"
                                data-bs-toggle="tab"
                                data-bs-target="#{{ $tabId }}">
                            {{ $tab }}
                        </button>
                    </li>
                @endforeach
            </ul>

            {{-- Tab Content --}}
            <div class="tab-content p-3">

                @foreach($schema ?? [] as $tab => $fields)
                    @php $tabId = 'tab-'.Str::slug($tab); @endphp

                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                         id="{{ $tabId }}">

                        @foreach($fields as $key => $meta)

                            @php
                                $configKey = str_replace('traffic-sentinel.', '', $key);
                                $value = data_get(config('traffic-sentinel'), $configKey);

                                // ✅ FIXED (no DB query)
                                $isDbOverride = array_key_exists($key, $settingsMap);

                                $label = Str::of($configKey)
                                    ->replace('.', ' ')
                                    ->replace('_', ' ')
                                    ->title();
                            @endphp

                            <div class="mb-3">

                                {{-- Label + Source --}}
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label fw-semibold mb-0">
                                        {{ $label }}
                                    </label>

                                    <span class="badge bg-{{ $isDbOverride ? 'success' : 'secondary' }}">
                                    {{ $isDbOverride ? 'DB' : 'ENV' }}
                                </span>
                                </div>

                                {{-- BOOLEAN --}}
                                @if($meta['type'] === 'boolean')
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="{{ $key }}" value="0"> {{-- 🔥 FIX --}}
                                        <input class="form-check-input"
                                               type="checkbox"
                                               name="{{ $key }}"
                                               value="1"
                                                {{ $value ? 'checked' : '' }}>
                                    </div>

                                    {{-- NUMBER --}}
                                @elseif($meta['type'] === 'number')
                                    <input type="number"
                                           name="{{ $key }}"
                                           value="{{ $value }}"
                                           class="form-control">

                                    {{-- JSON --}}
                                @elseif($meta['type'] === 'json')
                                    <textarea name="{{ $key }}"
                                              class="form-control font-monospace"
                                              rows="4">{{ is_array($value) ? json_encode($value, JSON_PRETTY_PRINT) : '' }}</textarea>

                                    {{-- TEXT --}}
                                @else
                                    <input type="text"
                                           name="{{ $key }}"
                                           value="{{ $value }}"
                                           class="form-control">
                                @endif

                            </div>

                        @endforeach

                    </div>
                @endforeach

            </div>

            {{-- Save --}}
            <div class="p-3 border-top text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i> Save Settings
                </button>
            </div>

        </form>

    </div>
@endsection
