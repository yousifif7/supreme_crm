<?php

namespace App\DataTables;

use App\Models\User;
use App\Models\ShiftDate;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

class ShiftsDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder<ShiftDate> $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', function ($shiftDate) {
                return view('security_boards.shifts.action', compact('shiftDate'));
            })
            ->addColumn('checkbox', function ($shiftDate) {
                return '<input type="checkbox" class="dT-row-checkbox" value="' . $shiftDate->id . '">';
            })
            ->addColumn('number', function ($shiftDate) {
                return '';
            })
            ->editColumn('client_name', function ($shiftDate) {
                $client= User::role('client')->where('id',$shiftDate->shift->client_id)->first();
                return $client->name ?? 'N/A';
            })
            ->editColumn('site_name', function ($shiftDate) {
                return $shiftDate->shift->site->site_name ?? 'N/A';
            })
            ->editColumn('staff_name', function ($shiftDate) {
                if ($shiftDate->staff) {
                    return $shiftDate->staff->first_name . ' ' . $shiftDate->staff->last_name;
                }
                return 'Unassigned';
            })
            ->addColumn('subcontractor_name', function ($shiftDate) {
                // Resolve to the SAME subcontractor the filter matches on: the
                // shift_date's own when it has one, otherwise the parent shift's.
                // Treat 0 / '' as "no own subcontractor" (not just null) so the
                // display and the filter agree on precedence.
                $ownId = $shiftDate->subcontractor_id;
                $hasOwn = !empty($ownId);

                $sub = $hasOwn
                    ? $shiftDate->subcontractor
                    : optional($shiftDate->shift)->subcontractor;
                if ($sub) {
                    $name = trim($sub->company_name ?? '') ?: trim($sub->contact_person ?? '');
                    if (!$name && $sub->user) {
                        $name = trim(($sub->user->first_name ?? '') . ' ' . ($sub->user->last_name ?? ''));
                    }
                    if ($name !== '') {
                        return $name;
                    }
                }

                // Fallback: much of the data stores the subcontractor USER id directly
                // in subcontractor_id (no sub_contractors row), so the relation above is
                // null. Resolve the name from that user instead — same precedence.
                $storedId = $hasOwn ? $ownId : optional($shiftDate->shift)->subcontractor_id;
                if ($storedId) {
                    $user = \App\Models\User::find($storedId);
                    if ($user) {
                        return trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
                    }
                }

                return '';
            })
            // ->editColumn('created_at', function ($user) {
            //     return $user->created_at?->format('Y-m-d');
            // })
            ->editColumn('shift_date', function ($shiftDate) {
                return format_date($shiftDate->shift_date);
            })
            ->addColumn('shift_time', function ($shiftDate) {
                $start = \Carbon\Carbon::createFromFormat('H:i:s', $shiftDate->start_time)->format('H:i');
                $end = \Carbon\Carbon::createFromFormat('H:i:s', $shiftDate->end_time)->format('H:i');
                return $start . ' - ' . $end;
            })
            ->editColumn('total_hours', function ($shiftDate) {
                return number_format($shiftDate->total_hours, 2) . ' hrs';
            })
            ->addColumn('status', function ($shiftDate) {
    return ShiftDate::getStatusBadge($shiftDate->is_assign);
})->filterColumn('client_name', function($query, $keyword) {
                $query->whereHas('shift.client', function($q) use ($keyword) {
                    $q->where('name', 'like', "%$keyword%");
                });
            })
            ->filterColumn('site_name', function($query, $keyword) {
                $query->whereHas('shift.site', function($q) use ($keyword) {
                    $q->where('site_name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('staff_name', function($query, $keyword) {
                $query->whereHas('staff', function($q) use ($keyword) {
                    $q->where('first_name', 'like', "%{$keyword}%")
                      ->orWhere('last_name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('subcontractor_name', function($query, $keyword) {
                // Match against company name / contact person on the Subcontractor model,
                // OR the linked user's first/last name / email. Look at both the
                // shift_date.subcontractor_id and the parent shifts.subcontractor_id.
                $query->where(function ($q) use ($keyword) {
                    $like = "%{$keyword}%";

                    $matchSub = function ($sq) use ($like) {
                        $sq->where('company_name', 'like', $like)
                           ->orWhere('contact_person', 'like', $like)
                           ->orWhereHas('user', function ($uq) use ($like) {
                               $uq->where('first_name', 'like', $like)
                                  ->orWhere('last_name', 'like', $like)
                                  ->orWhere('email', 'like', $like);
                           });
                    };

                    // shift_date-level subcontractor
                    $q->whereHas('subcontractor', $matchSub)
                      // OR parent shift-level subcontractor
                      ->orWhereHas('shift.subcontractor', $matchSub);

                    // Much of the data stores the subcontractor USER id directly in
                    // subcontractor_id (no sub_contractors row), so the relations
                    // above can't match. Also match by users whose name/email hits
                    // and whose id equals the stored subcontractor_id (shift_date or
                    // parent shift).
                    $userIds = \App\Models\User::where(function ($uq) use ($like) {
                            $uq->where('first_name', 'like', $like)
                               ->orWhere('last_name', 'like', $like)
                               ->orWhere('email', 'like', $like);
                        })
                        ->pluck('id')
                        ->all();

                    if (!empty($userIds)) {
                        $q->orWhereIn('shift_dates.subcontractor_id', $userIds)
                          ->orWhereHas('shift', function ($sq) use ($userIds) {
                              $sq->whereIn('subcontractor_id', $userIds);
                          });
                    }
                });
            })
            ->filterColumn('shift_date', function($query, $keyword) {
                $query->where('shift_date', 'like', "%{$keyword}%");
            })
            ->filterColumn('total_hours', function($query, $keyword) {
                $query->where('total_hours', 'like', "%{$keyword}%");
            })
            ->rawColumns(['action', 'checkbox', 'number', 'status'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<ShiftDate>
     */
    public function query(ShiftDate $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->with([
                'shift.client',
                'shift.site',
                'shift.staff',
                'shift.subcontractor.user',
                'staff',
                'subcontractor.user',
            ])
            ->select('shift_dates.*');

        $staffId = request()->get('staff');
        $clientId = request()->get('client_id');
        $siteId = request()->get('site');
        $subcontractorId = request()->get('subcontractor');
        $fromShift = request()->get('from_shift');
        $toShift = request()->get('to_shift');

        if ($staffId !== null && $staffId !== '') {
            $query->where('shift_dates.staff_id', $staffId);
        }

        // The Subcontractor filter sends a USER id (the dropdown lists subcontractor
        // users). A shift can carry a subcontractor at the shift_date level OR on the
        // parent shift, and the stored *.subcontractor_id may be either the linked
        // user_id directly (most data) or a sub_contractors.id. Mirror the scheduling
        // board's resolution: accept the user id itself plus any sub_contractors.id
        // rows that point at that user.
        //
        // IMPORTANT: match the SAME subcontractor the table column displays. The
        // column shows the shift_date-level subcontractor and only falls back to the
        // parent shift's when the shift_date has none. The filter must use the exact
        // same precedence — otherwise a row whose shift_date sub is A but whose parent
        // shift sub is B would pass a filter for B while visibly showing A (the bug
        // where filtering "PROTECTO K9" still showed "SUPREME PROTECTION LTD").
        if ($subcontractorId !== null && $subcontractorId !== '') {
            $acceptableIds = \App\Models\Subcontractor::where('user_id', $subcontractorId)
                ->pluck('id')
                ->push($subcontractorId)
                ->map(fn ($v) => (string) $v)
                ->unique()
                ->values()
                ->all();

            $query->where(function ($q) use ($acceptableIds) {
                // Displayed subcontractor is the shift_date's own one.
                $q->whereIn('shift_dates.subcontractor_id', $acceptableIds)
                  // OR the shift_date has none (null / 0 / empty), so the displayed
                  // one is the parent shift's.
                  ->orWhere(function ($sq) use ($acceptableIds) {
                      $sq->where(function ($emptyQ) {
                              $emptyQ->whereNull('shift_dates.subcontractor_id')
                                     ->orWhere('shift_dates.subcontractor_id', 0)
                                     ->orWhere('shift_dates.subcontractor_id', '');
                          })
                         ->whereHas('shift', function ($shiftQ) use ($acceptableIds) {
                             $shiftQ->whereIn('subcontractor_id', $acceptableIds);
                         });
                  });
            });
        }

        if ($siteId !== null && $siteId !== '') {
            $query->whereHas('shift', function ($q) use ($siteId) {
                $q->where('site_id', $siteId);
            });
        }

        if ($clientId !== null && $clientId !== '') {
            $query->whereHas('shift', function ($q) use ($clientId) {
                $q->where('client_id', $clientId);
            });
        }

        if (!empty($fromShift)) {
            $query->whereDate('shift_dates.shift_date', '>=', $fromShift);
        }

        if (!empty($toShift)) {
            $query->whereDate('shift_dates.shift_date', '<=', $toShift);
        }

        // Status priority: modal filter (status) > quick status (shift_status / shiftStatus)
        $status = request()->get('ShiftStatus', request()->get('shift_status', request()->get('shiftStatus')));

        if ($status !== null && $status !== '') {
            // Filter by the is_assign field which is used as the status indicator
            $query->where('is_assign', $status);
        }

        return $query;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('shifts-table')
            ->setTableAttribute('class', 'table table-row-bordered table-row-dashed gy-4 align-middle fw-bold')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->addAction(['width' => '80px'])
            ->dom(
                't
                <"d-flex justify-content-between mt-2"
                  <"col-sm-12 col-md-5 align-self-center ps-3"i>
                  <"d-flex justify-content-between" p>
                >'
            )
            ->orderBy([6, 'DESC'])
            ->parameters([
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
            Column::make('client_name')->addClass('ps-0')->orderable(false),
            Column::make('site_name')->orderable(false),
            Column::make('staff_name')->orderable(false),
            Column::make('subcontractor_name')->title('Subcontractor')->orderable(false),
            Column::make('shift_date')->orderable(true)->searchable(false),
            Column::make('shift_time')->orderable(false)->searchable(false),
            Column::make('break_time')->title('Break Time')->orderable(false),
            Column::make('total_hours')->title('Total Hours')->orderable(false),
            Column::make('status')->title('Status')->orderable(false)->searchable(false),
            // Column::make('created_at')->title('Created at'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Shifts_' . date('YmdHis');
    }
}
