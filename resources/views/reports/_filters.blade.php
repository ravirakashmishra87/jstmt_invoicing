<form class="row g-2 align-items-end mb-3" method="GET">
    @if(isset($from))
        <div class="col-auto"><label class="form-label">From</label><input type="date" name="from" class="form-control" value="{{ $from }}"></div>
        <div class="col-auto"><label class="form-label">To</label><input type="date" name="to" class="form-control" value="{{ $to }}"></div>
        <div class="col-auto"><button class="btn btn-primary">Run</button></div>
    @endif
    @if(auth()->user()->hasPermission('reports.export'))
        <div class="col-auto"><a class="btn btn-outline-secondary" href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}">Export CSV</a></div>
    @endif
</form>
