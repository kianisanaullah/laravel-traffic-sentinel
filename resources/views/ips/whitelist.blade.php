@extends('traffic-sentinel::layout')

@section('content')
    <div class="ts-card mb-3">

        <div class="p-3 border-bottom">
            <strong>Add Whitelist IP</strong>
        </div>

        <div class="p-3">

            <form method="POST" action="{{ route('traffic-sentinel.whitelist.store') }}">

                @csrf

                <div class="row g-3">

                    <div class="col-md-3">
                        <input type="text" name="ip" class="form-control" placeholder="IP Address" required>
                    </div>

                    <div class="col-md-3">
                        <input type="text" name="name" class="form-control" placeholder="Name">
                    </div>

                    <div class="col-md-3">
                        <input type="text" name="description" class="form-control" placeholder="Description">
                    </div>

                    <div class="col-md-2">
                        <input type="datetime-local" name="expires_at" class="form-control">
                    </div>

                    <div class="col-md-1">
                        <button class="btn btn-success">Add</button>
                    </div>

                </div>

            </form>

        </div>

    </div>
    <div class="ts-card">

        <div class="p-3 border-bottom">
            <strong>Whitelisted IPs</strong>
        </div>

        <div class="table-responsive">

            <table class="table ts-table">

                <thead>

                <tr>
                    <th>IP</th>
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

                        <td>
                            @include('traffic-sentinel::partials.ip-cell',['ip'=>$ip->ip])
                        </td>

                        <td>{{ $ip->name }}</td>

                        <td class="text-muted">{{ $ip->description }}</td>

                        <td>
                            @if($ip->expires_at)
                                {{ \Carbon\Carbon::parse($ip->expires_at)->format('Y-m-d H:i') }}
                            @else
                                Never
                            @endif
                        </td>

                        <td>
                            @if($ip->active)
                                <span class="ts-badge bg-success">Active</span>
                            @else
                                <span class="ts-badge bg-secondary">Disabled</span>
                            @endif
                        </td>

                        <td>

                            <form method="POST"
                                  action="{{ route('traffic-sentinel.whitelist.delete',$ip->id) }}"
                                  onsubmit="return confirm('Remove this IP?')">

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
