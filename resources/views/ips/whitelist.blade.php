@extends('traffic-sentinel::layout')

@section('content')
    <div class="ts-card mb-3">

        <div class="p-3 border-bottom">
            <strong>Add Whitelist Entry</strong>
        </div>

        <div class="p-3">

            <form method="POST" action="{{ route('whitelist.store') }}">
                @csrf

                <div class="row g-3">

                    {{-- IP / SUBNET --}}
                    <div class="col-md-3">
                        <input type="text"
                               name="ip"
                               class="form-control"
                               placeholder="IP or Subnet (e.g. 66.249.0.0/16)"
                               required>
                    </div>

                    {{-- TYPE --}}
                    <div class="col-md-2">
                        <select name="type" class="form-select">
                            <option value="ip">IP</option>
                            <option value="subnet">Subnet</option>
                        </select>
                    </div>

                    {{-- NAME --}}
                    <div class="col-md-2">
                        <input type="text"
                               name="name"
                               class="form-control"
                               placeholder="Name">
                    </div>

                    {{-- DESCRIPTION --}}
                    <div class="col-md-3">
                        <input type="text"
                               name="description"
                               class="form-control"
                               placeholder="Description">
                    </div>

                    {{-- EXPIRY --}}
                    <div class="col-md-1">
                        <input type="datetime-local"
                               name="expires_at"
                               class="form-control">
                    </div>

                    {{-- BUTTON --}}
                    <div class="col-md-1">
                        <button class="btn btn-success w-100">
                            Add
                        </button>
                    </div>

                </div>

            </form>

        </div>

    </div>

    <div class="ts-card">

        <div class="p-3 border-bottom">
            <strong>Whitelisted Entries</strong>
        </div>

        <div class="table-responsive">

            <table class="table ts-table">

                <thead>
                <tr>
                    <th>IP / Subnet</th>
                    <th>Type</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Expires</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                </thead>

                <tbody>

                @foreach($ips as $ip)

                    <tr>

                        {{-- IP --}}
                        <td>
                            @include('traffic-sentinel::partials.ip-cell',['ip'=>$ip->ip])
                        </td>

                        {{-- TYPE --}}
                        <td>
                            @if(($ip->type ?? 'ip') === 'subnet')
                                <span class="ts-badge bg-info">
                                    <i class="bi bi-diagram-3 me-1"></i>Subnet
                                </span>
                            @else
                                <span class="ts-badge">
                                    <i class="bi bi-geo me-1"></i>IP
                                </span>
                            @endif
                        </td>

                        {{-- NAME --}}
                        <td>{{ $ip->name }}</td>

                        {{-- DESCRIPTION --}}
                        <td class="text-muted">{{ $ip->description }}</td>

                        {{-- EXPIRES --}}
                        <td>
                            @if($ip->expires_at)
                                {{ \Carbon\Carbon::parse($ip->expires_at)->format('Y-m-d H:i') }}
                            @else
                                Never
                            @endif
                        </td>

                        {{-- STATUS --}}
                        <td>
                            @if($ip->active)
                                <span class="ts-badge bg-success">Active</span>
                            @else
                                <span class="ts-badge bg-secondary">Disabled</span>
                            @endif
                        </td>

                        {{-- ACTION --}}
                        <td>
                            <form method="POST"
                                  action="{{ route('whitelist.delete',$ip->id) }}"
                                  onsubmit="return confirm('Remove this entry?')">

                                @csrf
                                @method('DELETE')

                                <button class="btn btn-sm btn-danger">
                                    Remove
                                </button>

                            </form>
                        </td>

                    </tr>

                @endforeach

                </tbody>

            </table>

        </div>

        <div class="p-3">
            {{ $ips->links('pagination::bootstrap-5') }}
        </div>

    </div>
@endsection
