<?php

namespace App\DataTables;

use App\Models\User;
use App\Models\Employee;
use Carbon\Carbon;
use Yajra\DataTables\Html\Column;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

class EmployeesDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder<Employee> $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', function ($employee) {
                return view('employees.action', compact('employee'));
            })
            ->addColumn('checkbox', function ($employee) {
                return '<input type="checkbox" class="dT-row-checkbox" value="' . $employee->id . '">';
            })
            ->addColumn('number', function ($employee) {
                return '';
            })
            ->editColumn('name', function ($employee) {
                return view('employees.name_column', ['employee' => $employee]);
            })
            ->editColumn('email', function ($employee) {
                return $employee->email;
            })
            // ->editColumn('sia_licence', function ($employee) {
            //     return '<p class="mb-0 fw-semibold">' . $employee->sia_licence . '</p>
            //             <span class="text-primary fw-bold">Active</span>';
            // })
            ->editColumn('sia_licence', function ($employee) {
                // Determine SIA status strictly from expiry date: Active if expiry in future, otherwise Inactive
                $displayStatus = 'Inactive';
                $expiryRaw = $employee->sia_expiry ?? null;

                if (!empty($expiryRaw)) {
                    $parsed = null;
                    $formats = ['Y-m-d H:i:s', 'Y-m-d', 'd/m/Y', 'm/d/Y'];
                    foreach ($formats as $fmt) {
                        try {
                            $dt = Carbon::createFromFormat($fmt, $expiryRaw);
                            if ($dt && $dt->format($fmt) === $dt->format($fmt)) {
                                $parsed = $dt;
                                break;
                            }
                        } catch (\Exception $_) {
                            // try next format
                        }
                    }

                    // Last resort: let Carbon try to parse generally
                    if (!$parsed) {
                        try { $parsed = Carbon::parse($expiryRaw); } catch (\Exception $_) { $parsed = null; }
                    }

                    if ($parsed) {
                        $displayStatus = $parsed->isFuture() ? 'Active' : 'Inactive';
                    }
                }

                // CSS class based on normalized display status
                $class = $displayStatus === 'Active' ? 'text-primary' : 'text-danger';

                return '<p class="mb-0 fw-semibold">' . e($employee->sia_licence) . '</p>'
                    . '<span class="' . $class . ' fw-bold">' . e($displayStatus) . '</span>';
            })
            ->editColumn('sia_expiry', function ($employee) {
                if (empty($employee->sia_expiry)) return '';
                try { return Carbon::parse($employee->sia_expiry)->format('m/d/Y'); }
                catch (\Exception $e) { return $employee->sia_expiry; }
            })
            ->editColumn('visa_expiry', function ($employee) {
                if (empty($employee->visa_expiry)) return '';
                try { return Carbon::parse($employee->visa_expiry)->format('m/d/Y'); }
                catch (\Exception $e) { return $employee->visa_expiry; }
            })
            ->editColumn('visa_type', function ($employee) {
                return $employee->visa_type;
            })
            ->editColumn('contact', function ($employee) {
                return $employee->contact;
            })
            ->editColumn('created_at', function ($user) {
                return $user->created_at?->format('m/d/Y');
            })
            ->editColumn('subcontractor', function ($employee) {
                $subcontractor = User::role('subcontractor')->where('id', $employee->subcontractor)->first();
                return $subcontractor->name ?? 'N/A';
            })
            ->filterColumn('name', function ($query, $keyword) {
                $query->where('fore_name', 'like', "%{$keyword}%")
                    ->orWhere('sur_name', 'like', "%{$keyword}%");
            })
            ->filterColumn('sia_licence', function ($query, $keyword) {
                $query->where('sia_licence', 'like', "%{$keyword}%");
            })
            ->rawColumns(['action', 'checkbox', 'number', 'name', 'sia_licence'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<Employee>
     */
    public function query(Employee $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->select('employees.*')
            ->orderBy('fore_name', 'asc')   // 👈 First name
            ->orderBy('sur_name', 'asc');  // 👈 Then last name

        // Apply SIA status filter if provided via request (e.g. 'Active' or 'Inactive').
        // Status is derived strictly from the SIA expiry date (NOT the sia_status column),
        // to match the Active/Inactive label rendered in the table:
        //   - Active   => sia_expiry is set and in the future
        //   - Inactive => sia_expiry is missing or on/before today (expired)
        $siaStatus = request('sia_status');
        if ($siaStatus) {
            $s = strtolower($siaStatus);
            if ($s === 'active') {
                $query->whereNotNull('sia_expiry')->whereDate('sia_expiry', '>', now());
            } elseif ($s === 'inactive') {
                $query->where(function ($q) {
                    $q->whereNull('sia_expiry')
                      ->orWhereDate('sia_expiry', '<=', now());
                });
            }
        }

        // Apply general employee status filter (e.g. 'Active', 'Terminated', 'Need Approval')
        $empStatus = request('status');
        if ($empStatus) {

            try {
                $s = preg_replace('/\s+/', ' ', trim(strtolower(preg_replace('/[^a-z0-9]+/i', ' ', $empStatus))));
            } catch (\Exception $e) {
                // fallback to a simple normalize if preg_replace fails for some reason
                $s = strtolower(str_replace('_', ' ', trim($empStatus)));
            }

            $query->whereRaw(
                "LOWER(TRIM(REPLACE(REPLACE(REPLACE(COALESCE(status, ''), '_', ' '), '-', ' '), '\\t', ' '))) = ?",
                [$s]
            );
        }

        if ($this->filter === 'archived') {
            $query = $model->onlyTrashed()
                ->select('employees.*')
                ->orderBy('fore_name', 'asc')
                ->orderBy('sur_name', 'asc');
        }

        return $query;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('employees-table')
            ->setTableAttribute('class', 'table table-row-bordered table-row-dashed gy-4 align-middle fw-bold')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom(
                't
                <"d-flex justify-content-between mt-2"
                  <"col-sm-12 col-md-5 align-self-center ps-3"i>
                  <"d-flex justify-content-between" p>
                >'
            )
            ->addAction(['width' => '120px'])
            ->orderBy([9, 'DESC'])
            ->parameters([
                // send selected SIA status from the UI select (#siaStatusFilter)
                'ajax' => [
                    // include UI filters and log to browser console for debugging
                    'data' => "function(d) { d.sia_status = $('#siaStatusFilter').val(); d.status = $('#empStatusFilter').val(); console.log('EmployeesDataTable ajax data', {sia_status: d.sia_status, status: d.status}); }",
                ],
                "scrollX" => true,
                "pageLength" => 25,
                "drawCallback" => "function(settings) {
                    feather.replace();
                    var api = this.api();
                    var start = api.page.info().start;
                    api.column(1, {page: 'current'}).nodes().each(function(cell, i) {
                        cell.innerHTML = start + i + 1;
                    });
                }",
            ]);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::computed('checkbox')->title('<input type="checkbox" id="selectAll">')->exportable(false)->printable(false)->width(20)->addClass('text-center px-2')->orderable(false)->searchable(false),
            Column::computed('number')->title('#')->width(30)->addClass('px-2')->orderable(false)->searchable(false),
            Column::make('name')->title('Name')->addClass('ps-0')->orderable(false),
            Column::make('email')->title('Email')->addClass('ps-0')->orderable(false),
            Column::make('sia_licence')->title('SIA'),
            Column::make('sia_expiry')->title('EXPIRY'),
            Column::make('visa_expiry')->title('VISA EXPIRY'),
            Column::make('visa_type')->title('IMMIGRATION STATUS'),
            Column::make('contact')->title('CONTACT NO'),
            Column::make('subcontractor')->title('SUBCONTRACTOR'),
            Column::make('created_at')->title('Created at'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Employees_' . date('YmdHis');
    }
}
