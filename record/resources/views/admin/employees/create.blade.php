@extends('layouts.admin', ['title' => 'Add Employee'])

@section('content')
    @include('admin.employees.partials.form', ['action' => route('admin.employees.store'), 'method' => 'POST', 'employee' => null])
@endsection
