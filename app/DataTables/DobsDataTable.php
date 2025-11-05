<?php

namespace App\DataTables;

use App\Models\User;
use App\Models\DobEntry;
use App\Models\ShiftDate;
use Carbon\Carbon;
use Yajra\DataTables\Html\Column;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;

class DobsDataTable extends DataTable
{
    public function dataTable($query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('user', function ($row) {
                $user = User::find($row->user_id);
                return $user ? $user->first_name . ' ' . $user->last_name : 'Unknown';
            })
            ->addIndexColumn()
            ->addColumn('actions', function ($row) {
                return '
        <div class="action-icon d-inline-flex">
            <a href="javascript:void(0)" class="me-2" onclick="showDob(' . $row->id . ')">
                <i class="ti ti-eye"></i>
            </a>
            <a href="javascript:void(0)" class="me-2" onclick="editDob(' . $row->id . ')">
                <i class="ti ti-pencil text-success"></i>
            </a>
            <a href="javascript:void(0)" onclick="deleteDob(' . $row->id . ')">
                <i class="ti ti-trash text-danger"></i>
            </a>
        </div>';
            })
            ->addColumn('timestamp', function ($row) {
                if (empty($row->timestamp)) return '';
                try {
                    return Carbon::parse($row->timestamp)->format('m/d/Y H:i');
                } catch (\Exception $e) {
                    return $row->timestamp;
                }
            })
            ->addColumn('address', function ($row) {
                $shiftdate = ShiftDate::find($row->shift_id);
                return $shiftdate ? $shiftdate->shift->site->address : 'Unknown';
            })
            ->addColumn('files', function ($row) {
                $html = '';
                foreach ($row->media as $media) {
                    $html .= '<a href="' . asset($media->file_url) . '" target="_blank" class="d-block mb-1 btn btn-sm btn-primary">'
                        . 'View' . '</a>';
                }
                return $html;
            })
            ->addColumn('checkbox', function ($dob) {
                return '<input type="checkbox" class="dob-checkbox" value="' . $dob->id . '">';
            })
            ->rawColumns(['actions', 'files', 'address', 'checkbox'])

            // Global search across dob_entries columns and related user / site fields
            ->filter(function ($query, $keyword) {
                if (empty($keyword)) return;

                $kw = "%{$keyword}%";

                $query->where(function ($q) use ($kw) {
                    $q->where('title', 'like', $kw)
                        ->orWhere('entry_type', 'like', $kw)
                        ->orWhere('timestamp', 'like', $kw)
                        ->orWhere('location', 'like', $kw)
                        ->orWhere('description', 'like', $kw)

                        // Match against user first/last/full name
                        ->orWhereExists(function ($sub) use ($kw) {
                            $sub->select(DB::raw(1))
                                ->from('users')
                                ->whereRaw('users.id = dob_entries.user_id')
                                ->where(function ($u) use ($kw) {
                                    $u->where('first_name', 'like', $kw)
                                      ->orWhere('last_name', 'like', $kw)
                                      ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', $kw);
                                });
                        })

                        // Match against site name or address via shift_dates -> shifts -> sites
                        ->orWhereExists(function ($sub) use ($kw) {
                            $sub->select(DB::raw(1))
                                ->from('shift_dates')
                                ->join('shifts', 'shift_dates.shift_id', '=', 'shifts.id')
                                ->join('sites', 'shifts.site_id', '=', 'sites.id')
                                ->whereRaw('shift_dates.id = dob_entries.shift_id')
                                ->where(function ($u) use ($kw) {
                                    $u->where('sites.site_name', 'like', $kw)
                                      ->orWhere('sites.address', 'like', $kw);
                                });
                        });
                });
            }, true);
    }
    public function query(DobEntry $model)
    {
        return $model->with(['media'])->newQuery()->select('dob_entries.*');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('dobs-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(0);
    }

    protected function getColumns(): array
    {
        return [
            Column::computed('checkbox')
                ->title('<input type="checkbox" id="selectAll">')
                ->exportable(false)
                ->printable(false)
                ->width(20)
                ->addClass('text-center px-2')
                ->orderable(false)
                ->searchable(false),
            Column::computed('DT_RowIndex')->title('#')->width(30)->addClass('px-2')->orderable(false)->searchable(false),
            Column::computed('user')->title('Guard'),
            Column::make('title')->title('Title'),
            Column::make('entry_type')->title('Type'),
            Column::make('timestamp')->title('Timestamp'),
            Column::computed('address')->title('Location'),
            Column::computed('files')->title('Files')->orderable(false)->searchable(false),
            Column::computed('actions')->title('Actions')->exportable(false)->printable(false),
        ];
    }

    protected function filename(): string
    {
        return 'DobEntries_' . date('YmdHis');
    }
}
