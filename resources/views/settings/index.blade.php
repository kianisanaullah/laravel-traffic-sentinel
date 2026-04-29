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

            {{-- SUCCESS --}}
            @if(session('success'))
                <div class="alert alert-success m-3">
                    {{ session('success') }}
                </div>
            @endif

            {{-- ERROR --}}
            @if ($errors->any())
                <div class="alert alert-danger m-3">
                    Please fix the errors below.
                </div>
            @endif

            {{-- Tabs --}}
            <ul class="nav nav-tabs px-3 pt-3" role="tablist">
                @foreach($schema ?? [] as $tab => $fields)
                    @php
                        $tabId = 'tab-'.Str::slug($tab);
                        $hasError = collect($fields)->keys()->some(fn($k) => $errors->has($k));
                    @endphp

                    <li class="nav-item">
                        <button type="button"
                                class="nav-link {{ $loop->first || $hasError ? 'active' : '' }}"
                                data-bs-toggle="tab"
                                data-bs-target="#{{ $tabId }}">
                            {{ $tab }}

                            @if($hasError)
                                <span class="text-danger ms-1">●</span>
                            @endif
                        </button>
                    </li>
                @endforeach
            </ul>

            {{-- Tab Content --}}
            <div class="tab-content p-3">

                @foreach($schema ?? [] as $tab => $fields)
                    @php
                        $tabId = 'tab-'.Str::slug($tab);
                        $hasError = collect($fields)->keys()->some(fn($k) => $errors->has($k));
                    @endphp

                    <div class="tab-pane fade {{ $loop->first || $hasError ? 'show active' : '' }}"
                         id="{{ $tabId }}">

                        @foreach($fields as $key => $meta)

                            @php
                                // 🔥 SAFE INPUT NAME (CRITICAL FIX)
                                $inputName = str_replace(['.', '-'], ['__', '_'], $key);

                                $configKey = str_replace('traffic-sentinel.', '', $key);

                                $value = old(
                                    $inputName,
                                    $settingsMap[$key] ?? data_get(config('traffic-sentinel'), $configKey)
                                );

                                $isDbOverride = array_key_exists($key, $settingsMap);

                                $label = Str::of($configKey)
                                    ->replace('.', ' ')
                                    ->replace('_', ' ')
                                    ->title();

                                $hasFieldError = $errors->has($inputName);
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
                                        <input type="hidden" name="{{ $inputName }}" value="0">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               name="{{ $inputName }}"
                                               value="1"
                                                {{ (string)$value === '1' ? 'checked' : '' }}>
                                    </div>

                                    {{-- NUMBER --}}
                                @elseif($meta['type'] === 'number')
                                    <input type="number"
                                           name="{{ $inputName }}"
                                           value="{{ $value }}"
                                           class="form-control {{ $hasFieldError ? 'is-invalid' : '' }}">

                                    {{-- JSON --}}
                                @elseif($meta['type'] === 'json')
                                    <textarea name="{{ $inputName }}"
                                              class="form-control font-monospace {{ $hasFieldError ? 'is-invalid' : '' }}"
                                              rows="4">{{ is_array($value) ? json_encode($value, JSON_PRETTY_PRINT) : $value }}</textarea>
                                    <small class="text-muted">
                                        Enter valid JSON array. Example:
                                        <code>["1", "2"]</code>
                                    </small>
                                    {{-- TEXT --}}
                                @else
                                    <input type="text"
                                           name="{{ $inputName }}"
                                           value="{{ $value }}"
                                           class="form-control {{ $hasFieldError ? 'is-invalid' : '' }}">
                                @endif

                                {{-- ERROR --}}
                                @if($hasFieldError)
                                    <div class="invalid-feedback d-block">
                                        {{ $errors->first($inputName) }}
                                    </div>
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
