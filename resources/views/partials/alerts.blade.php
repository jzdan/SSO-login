@if (session('status'))
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 border-0 shadow-sm" role="alert">
        <i class="bi bi-check-circle-fill"></i>
        <div>{{ session('status') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
@endif

@if ($errors->any() && ! ($hideErrorSummary ?? false))
    <div class="alert alert-danger d-flex gap-2 border-0 shadow-sm" role="alert">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div>
            @if ($errors->count() === 1)
                {{ $errors->first() }}
            @else
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@endif
