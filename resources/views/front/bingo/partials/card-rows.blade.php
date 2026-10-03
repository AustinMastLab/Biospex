@foreach($rows as $row)
    <div class="row">
        @foreach($row as $cellId => $values)
            @if ($cellId === "c3")
                <div class="square logo d-inline float-left d-flex text-center justify-content-center align-items-center">
                    <img src="{{ $project->present()->show_logo }}" onerror="this.onerror=null;this.src='{{ config('config.missing_project_logo') }}'" class="img-fluid"
                         alt="{{ $project->title }} logo"></div>
                @continue
            @endif
            <div class="square d-inline float-left d-flex text-center justify-content-center align-items-center"
                 @if(!empty($values[1]))
                 data-hover="tooltip"
                 title="{{ $values[1] }}"
                 @endif
                         id="{{ $cellId }}">{{ $values[0] }}</div>
    @endforeach
    </div>
@endforeach
