@extends('layouts.app')
@section('title', brand_title('SIA Licence Reports'))
@section('contents')
    <div class="page-wrapper">
        <div class="content">
            <div class="alert-box-container"></div>

            <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
                <div class="my-auto mb-2">
                    <h2 class="mb-1">SIA Licence Check Reports</h2>
                    <p class="text-muted mb-0">One row per check run. Click a run to see per-employee detail.</p>
                </div>
            </div>

            <div class="card">
                <div class="card-body p-0">
                    @if ($runs->isEmpty())
                        <div class="alert alert-info m-3">No SIA check reports yet. Reports are generated automatically each
                            time the SIA checker runs.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Run Date / Time</th>
                                        <th class="text-center">Total Scanned</th>
                                        <th class="text-center text-success">Active</th>
                                        <th class="text-center text-danger">Inactive</th>
                                        <th class="text-center">Revoked</th>
                                        <th class="text-center text-warning">Errors</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($runs as $run)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($run->run_date)->format('d M Y, H:i') }}</td>
                                            <td class="text-center">{{ $run->total_scanned }}</td>
                                            <td class="text-center">
                                                @if ($run->active > 0)
                                                    <span class="badge bg-success">{{ $run->active }}</span>
                                                @else
                                                    <span class="text-muted">0</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if ($run->inactive > 0)
                                                    <span class="badge bg-danger">{{ $run->inactive }}</span>
                                                @else
                                                    <span class="text-muted">0</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if ($run->revoked > 0)
                                                    <span class="badge bg-secondary">{{ $run->revoked }}</span>
                                                @else
                                                    <span class="text-muted">0</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if ($run->errors > 0)
                                                    <span class="badge bg-warning text-dark">{{ $run->errors }}</span>
                                                @else
                                                    <span class="text-muted">0</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ route('reports.sia.show', $run->run_id) }}"
                                                    class="btn btn-sm btn-outline-primary">
                                                    <i class="ti ti-eye"></i>
                                                </a>
                                                <a href="{{ route('reports.sia.csv', $run->run_id) }}"
                                                    class="btn btn-sm btn-outline-success">
                                                    <i class="ti ti-download"></i>
                                                </a>
                                                <form method="POST" action="{{ route('reports.sia.delete', $run->run_id) }}"
                                                    onsubmit="return false;"
                                                    class="d-inline ms-2 sia-delete-form"
                                                    data-run-id="{{ $run->run_id }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                                        <i class="ti ti-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="p-3">
                            {!! $runs->onEachSide(1)->links('pagination::bootstrap-5') !!}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

    @push('scripts')
    <style>
        /* Styled toast container and items for SIA reports page */
        #sia-toast-container {
            position: fixed;
            top: 72px; /* leave room for the app header */
            right: 1rem;
            z-index: 2100;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            pointer-events: none; /* clicks pass through unless on a toast */
        }
        #sia-toast-container .alert {
            pointer-events: auto;
            min-width: 260px;
            max-width: 420px;
            padding: .5rem .85rem;
            border-radius: .5rem;
            box-shadow: 0 8px 20px rgba(20,20,20,0.08);
            display: flex;
            align-items: center;
            gap: .5rem;
            font-size: .95rem;
            margin: 0;
            opacity: 0;
            transform: translateY(-6px);
            transition: opacity .18s ease, transform .18s ease;
        }
        #sia-toast-container .alert.show {
            opacity: 1;
            transform: translateY(0);
        }
        #sia-toast-container .alert .toast-icon {
            width: 20px;
            height: 20px;
            display:inline-block;
            border-radius: 50%;
            flex: 0 0 20px;
        }
        #sia-toast-container .alert-success .toast-icon { background: #198754; }
        #sia-toast-container .alert-danger .toast-icon { background: #dc3545; }
        #sia-toast-container .alert-info .toast-icon { background: #0dcaf0; }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        function showToast(message, type = 'success') {
            // type: success | error | info
            const containerId = 'sia-toast-container';
            let container = document.getElementById(containerId);
            if (!container) {
                container = document.createElement('div');
                container.id = containerId;
                container.style.position = 'fixed';
                container.style.top = '1rem';
                container.style.right = '1rem';
                container.style.zIndex = 2000;
                document.body.appendChild(container);
            }

            const toast = document.createElement('div');
            toast.className = 'alert';
            if (type === 'success') toast.classList.add('alert-success');
            else if (type === 'error') toast.classList.add('alert-danger');
            else toast.classList.add('alert-info');

            const icon = document.createElement('span');
            icon.className = 'toast-icon';
            toast.appendChild(icon);

            const text = document.createElement('div');
            text.innerText = message;
            toast.appendChild(text);
            container.appendChild(toast);

            // animate in
            requestAnimationFrame(() => { toast.classList.add('show'); });

            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 4200);
        }

        const forms = document.querySelectorAll('.sia-delete-form');
        if (!forms.length) return;

        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : null;

        forms.forEach(form => {
            form.addEventListener('submit', function (e) {
                e.preventDefault();

                if (!confirm('Delete all entries for this run? This cannot be undone.')) return;

                const action = form.getAttribute('action');
                const runId = form.dataset.runId || '';
                const tr = form.closest('tr');

                fetch(action, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken || ''
                    },
                    credentials: 'same-origin'
                }).then(async (res) => {
                    if (res.ok) {
                        // try parse json
                        let data = null;
                        try { data = await res.json(); } catch (err) { data = null; }

                        const deleted = data && data.deleted !== undefined ? data.deleted : null;
                        if (deleted !== null) {
                            showToast(`Deleted ${deleted} reports for run ${runId}`, 'success');
                        } else {
                            showToast('Run deleted', 'success');
                        }

                        // remove row from table to reflect change
                        if (tr) tr.remove();
                    } else {
                        let errText = `Delete failed (${res.status})`;
                        try { const errJson = await res.json(); if (errJson.message) errText = errJson.message; } catch(_) {}
                        showToast(errText, 'error');
                    }
                }).catch((err) => {
                    showToast('Network error: ' + (err.message || err), 'error');
                });
            });
        });
    });
    </script>
    @endpush
