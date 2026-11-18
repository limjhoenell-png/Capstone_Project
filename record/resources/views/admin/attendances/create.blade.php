@extends('layouts.admin', ['title' => 'Add Attendance'])
@section('content')
    @include('admin.attendances.partials.form', ['action' => route('admin.attendances.store'), 'method' => 'POST', 'attendance' => null])
@endsection
