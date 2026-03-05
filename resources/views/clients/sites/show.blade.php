@extends('layouts.app')
@section('title', 'Site Details')
@section('contents')
<div class="page-wrapper">
    <div class="content">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h3>{{ $site->site_name }}</h3>
                        <small class="text-muted">Code: {{ $site->site_code ?? '-' }}</small>
                    </div>
                    <div>
                        <a href="{{ route('client.sites.index') }}" class="btn btn-secondary">&larr; Go back</a>
                    </div>
                </div>

                <div class="mb-2"><strong>Address:</strong> {{ $site->address ?? '-' }}</div>
                <div class="mb-2"><strong>Post Code:</strong> {{ $site->post_code ?? '-' }}</div>
                <div class="mb-2"><strong>Radius (meters):</strong> {{ $site->radius ?? '-' }}</div>
                <div class="mb-2"><strong>Contact Person:</strong> {{ $site->contact_person ?? '-' }}</div>
                <div class="mb-2"><strong>Contact Number:</strong> {{ $site->contact_number ?? '-' }}</div>
                <div class="mb-2"><strong>Guard Names:</strong> {{ $site->guard_names ?? '-' }}</div>
                <div class="mb-2"><strong>Note:</strong> {{ $site->note ?? '-' }}</div>
                <div class="mb-2"><strong>Manager 1:</strong> {{ optional($site->manager1)->name ?? $site->manager_1_id ?? '-' }}</div>
                <div class="mb-2"><strong>Manager 2:</strong> {{ optional($site->manager2)->name ?? $site->manager_2_id ?? '-' }}</div>
                <div class="mb-2"><strong>Start Time:</strong> {{ $site->start_time ?? '-' }}</div>
                <div class="mb-2"><strong>End Time:</strong> {{ $site->end_time ?? '-' }}</div>
                <div class="mb-2"><strong>Break Time (mins):</strong> {{ $site->break_time ?? '-' }}</div>
                <div class="mb-2"><strong>Guard Rate:</strong> {{ $site->guard_rate ?? '-' }}</div>
                <div class="mb-2"><strong>Office Rate:</strong> {{ $site->office_rate ?? '-' }}</div>
                <div class="mb-2"><strong>Billable Rate:</strong> {{ $site->billable_rate ?? '-' }}</div>
                <div class="mb-2"><strong>Payable Rate:</strong> {{ $site->payable_rate ?? '-' }}</div>
                <div class="mb-2"><strong>Has QR:</strong> {{ $site->has_qr ? 'Yes' : 'No' }}</div>
                @php $qrRoute = route('sites.qr', $site->id); @endphp
                @if(File::exists(public_path('qrForSites/site_' . $site->id . '.png')) || File::exists(storage_path('app/qrForSites/site_' . $site->id . '.png')))
                    <div class="mb-3">
                        <strong>QR Code:</strong>
                        <div class="mt-2">
                            <a href="{{ $qrRoute }}" target="_blank" title="Open QR in new tab">
                                <img src="{{ $qrRoute }}" alt="Site QR" style="max-width:200px;cursor:pointer;border:1px solid #ddd;padding:4px;background:#fff">
                            </a>
                        </div>
                        <div class="mt-2">
                            <a href="{{ $qrRoute }}" class="btn btn-sm btn-primary" target="_blank">Open</a>
                            <a href="{{ $qrRoute }}" class="btn btn-sm btn-secondary" download>Download</a>
                            <button id="qr-open-print" class="btn btn-sm btn-info">Open & Print</button>
                        </div>
                    </div>
                @endif

                {{-- NFC tags for checkpoints --}}
                @if($site->checkpoints && $site->checkpoints->whereNotNull('nfc_tag')->count() > 0)
                    <div class="mb-3">
                        <strong>NFC Tags:</strong>
                        <div class="mt-2 d-flex flex-column gap-2">
                            @foreach($site->checkpoints as $cp)
                                @if($cp->nfc_tag)
                                    <div>
                                        <strong>{{ $cp->name }}:</strong>
                                        <code class="me-2">{{ $cp->nfc_tag }}</code>
                                        <button class="btn btn-sm btn-outline-secondary copy-nfc" data-tag="{{ $cp->nfc_tag }}">Copy</button>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function(){
    var btn = document.getElementById('qr-open-print');
    if(!btn) return;
    btn.addEventListener('click', function(e){
        e.preventDefault();
        var url = '{{ route('sites.qr', $site->id) }}';
        var win = window.open(url, '_blank');
        if(win){
            var timer = setInterval(function(){
                try {
                    if(win.document.readyState === 'complete'){
                        clearInterval(timer);
                        win.focus();
                        win.print();
                    }
                } catch(err){
                    clearInterval(timer);
                    alert('Unable to auto-print. Use Open or Download options.');
                }
            }, 200);
        } else {
            alert('Popup blocked. Use Open or Download options.');
        }
    });
    // Copy NFC handlers
    document.querySelectorAll('.copy-nfc').forEach(function(btn){
        btn.addEventListener('click', function(){
            var tag = this.dataset.tag;
            navigator.clipboard?.writeText(tag).then(function(){
                // simple feedback
                alert('NFC tag copied');
            }).catch(function(){ alert('Copy failed'); });
        });
    });
});
</script>
@endsection
