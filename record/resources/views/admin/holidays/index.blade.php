@extends('layouts.admin', ['title' => 'Holidays'])

@section('content')
    <form method="POST" action="{{ route('admin.holidays.store') }}" class="card" style="margin-bottom:16px;">
        @csrf
        <div class="card-title">Add a holiday</div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <input class="form-control" type="date" name="holiday_date" required>
            <input class="form-control" name="name" placeholder="Holiday name" required>
            <select class="form-control" name="kind">
                <option value="regular">Regular (200% if worked)</option>
                <option value="special">Special (130% if worked)</option>
            </select>
            <button class="btn btn-primary" type="submit">Save</button>
        </div>
    </form>
    <div class="card" style="padding:0;">
        <table class="table">
            <thead><tr><th>Date</th><th>Name</th><th>Kind</th><th></th></tr></thead>
            <tbody>
            @forelse($holidays as $holiday)
                <tr>
                    <td>{{ $holiday->holiday_date->format('Y-m-d') }}</td>
                    <td>{{ $holiday->name }}</td>
                    <td>{{ $holiday->kind }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.holidays.destroy', $holiday) }}">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="table-empty">No holidays yet. Worked regular holidays pay the extra premium on top of the normal day.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
