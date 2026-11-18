@extends('layouts.employee', ['title' => 'My Attendance'])

@section('content')

@php
    $attendanceSyncUrl = route('employee.attendances.live-sync', [
        'per_page' => $perPage,
        'attendance_page' => $attendancePage->currentPage(),
    ]);
@endphp

<div
    id="employee-attendances-live"
    data-live-sync-url="{{ $attendanceSyncUrl }}"
    data-live-signature="{{ $liveSignature }}"
    data-attendance-per-page="{{ $perPage }}"
    data-attendance-page="{{ $attendancePage->currentPage() }}"
    class="content-stack"
>
    @include('employee.attendances.partials.live-region')
</div>

@endsection
