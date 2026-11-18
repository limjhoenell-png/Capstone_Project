<form method="POST" action="{{ $action }}">
    @csrf
    @if($method !== 'POST') @method($method) @endif

    <div class="card" style="max-width:560px;">
        <div class="card-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="color:var(--c-primary)">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
            </svg>
            Attendance Record
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="form-group" style="grid-column:span 2">
                <label class="form-label">Employee</label>
                <select class="form-control" name="user_id" required>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}"
                                @selected((string) old('user_id', $attendance?->user_id) === (string) $employee->id)>
                            {{ $employee->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group" style="grid-column:span 2">
                <label class="form-label">Attendance Date</label>
                <input class="form-control" type="date" name="attendance_date"
                       value="{{ old('attendance_date', optional($attendance?->attendance_date)->format('Y-m-d')) }}"
                       required>
            </div>

            <div class="form-group">
                <label class="form-label">Time In</label>
                <input class="form-control" type="datetime-local" name="time_in"
                       value="{{ old('time_in', optional($attendance?->time_in)->format('Y-m-d\TH:i')) }}">
                <div style="color:var(--c-text-muted);font-size:12px;">Valid Time-In is 05:00 AM to 04:59 PM. Time-Out may be any time.</div>
            </div>

            <div class="form-group">
                <label class="form-label">Time Out</label>
                <input class="form-control" type="datetime-local" name="time_out"
                       value="{{ old('time_out', optional($attendance?->time_out)->format('Y-m-d\TH:i')) }}">
            </div>

            <div class="form-group" style="grid-column:span 2">
                <label class="form-label">Remarks</label>
                <input class="form-control" name="remarks"
                       value="{{ old('remarks', $attendance?->remarks) }}"
                       placeholder="Optional remarks">
            </div>
        </div>

        <div style="display:flex;gap:10px;margin-top:4px;">
            <button class="btn btn-primary" type="submit">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
                Save Record
            </button>
            <a class="btn btn-danger" href="{{ route('admin.attendances.index') }}">Cancel</a>
        </div>
    </div>
</form>
