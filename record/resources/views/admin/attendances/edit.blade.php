@extends('layouts.admin', ['title' => 'Edit Attendance'])
@section('content')
    @include('admin.attendances.partials.form', ['action' => route('admin.attendances.update', $attendance), 'method' => 'PUT', 'attendance' => $attendance])
@endsection
