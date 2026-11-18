<style>
    .employees-pagination-toolbar {
        display: flex;
        justify-content: flex-end;
        padding: 14px 16px;
        border-bottom: 1px solid var(--c-border);
        background: var(--c-surface-2);
        transition: background-color var(--transition), border-color var(--transition);
    }
    .employees-pagination-toolbar label {
        display: flex;
        align-items: center;
        gap: 10px;
        color: var(--c-text-secondary);
        font-size: 12px;
        font-weight: 700;
    }
    .employees-pagination-toolbar select {
        width: 92px;
        min-height: 36px;
        padding: 0 8px;
        border: 1px solid var(--c-border-strong);
        border-radius: 8px;
        background: var(--c-surface);
        color: var(--c-text);
        font: inherit;
        transition: background-color var(--transition), color var(--transition), border-color var(--transition);
    }
    .employees-pagination {
        display: flex;
        justify-content: center;
        padding: 12px 16px 16px;
    }
    .employees-pagination nav { margin: 0; }
    .employees-pagination ul.pagination {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 6px;
        margin: 0;
        padding: 0;
        list-style: none;
    }
    .employees-pagination .pagination li { display: flex; }
    .employees-pagination .pagination a,
    .employees-pagination .pagination li > span {
        display: inline-flex;
        min-width: 36px;
        min-height: 36px;
        align-items: center;
        justify-content: center;
        margin: 0;
        padding: 6px 10px;
        border: 1px solid var(--c-border-strong);
        border-radius: 8px;
        background: var(--c-surface);
        color: var(--c-text-secondary);
        font-size: 13px;
        font-weight: 700;
        line-height: 1;
        text-decoration: none;
        transition: background-color var(--transition), color var(--transition), border-color var(--transition);
    }
    .employees-pagination .pagination a:hover,
    .employees-pagination .pagination li.active > span {
        border-color: var(--c-primary);
        background: var(--c-primary);
        color: #fff;
    }
    .employees-pagination .pagination li.disabled > span {
        background: var(--c-surface-2);
        color: var(--c-text-muted);
    }
    .employees-pagination .pagination a:focus-visible {
        outline: 2px solid var(--c-primary);
        outline-offset: 2px;
    }
</style>

<div class="card" style="padding:0;overflow:hidden;">
    <form method="GET" action="{{ route('admin.employees.index') }}" class="employees-pagination-toolbar">
        <label for="employees-per-page">
            Rows per page
            <select id="employees-per-page" name="per_page" aria-label="Rows per page">
                @foreach ([5, 10, 15, 20, 25, 50, 75, 100] as $option)
                    <option value="{{ $option }}" {{ $perPage === $option ? 'selected' : '' }}>{{ $option }}</option>
                @endforeach
            </select>
        </label>
    </form>
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>
                        <input id="select-all-employees" type="checkbox" aria-label="Select all employees on this page">
                    </th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Position</th>
                    <th>Contact</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @foreach($employees as $employee)
                <tr>
                    <td>
                        <input class="employee-row-checkbox" type="checkbox" form="bulk-delete-employees-form"
                               name="employee_ids[]" value="{{ $employee->id }}"
                               @disabled(!$employee->isEmployee())
                               aria-label="Select {{ $employee->name }}">
                    </td>
                    <td>
                        <a class="link-primary" href="{{ route('admin.employees.attendances', $employee) }}">
                            {{ $employee->name }}
                        </a>
                    </td>
                    <td style="color:var(--c-text-secondary);">{{ $employee->email }}</td>
                    <td>
                        <span class="badge badge-blue">{{ $employee->role }}</span>
                    </td>
                    <td style="color:var(--c-text-secondary);">{{ $employee->position }}</td>
                    <td style="font-size:13px;color:var(--c-text-secondary);">{{ $employee->contact_number }}</td>
                    <td>
                        <div style="display:flex;gap:6px;align-items:center;">
                            <a class="btn btn-sm" href="{{ route('admin.employees.edit', $employee) }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                                </svg>
                                Edit
                            </a>
                            <form class="inline" method="POST" data-no-ajax action="{{ route('admin.employees.destroy', $employee) }}">
                                @csrf @method('DELETE')
                                <input type="hidden" name="confirm_name" value="{{ $employee->name }}">
                                <button class="btn btn-sm btn-danger" type="submit"
                                        onclick="return confirm('Delete {{ addslashes($employee->name) }}? This cannot be undone.')">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

@if ($employees->hasPages())
    <div class="employees-pagination">{{ $employees->links() }}</div>
@endif
