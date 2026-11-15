{{--
    Event date and time: a date field plus a list of half-hour times, sent as {name}_date and {name}_time and
    combined into {name} ("Y-m-d H:i") by EventFormRequest. A saved time that isn't on the half hour stays in
    the list, so an existing event shows exactly the time it has.

    @param string $name  start_date or end_date
    @param string $label
    @param \Carbon\Carbon|null $value  the saved date in the event's time zone
--}}
@php
    $dateValue = old($name.'_date', $value?->format('Y-m-d'));
    $timeValue = old($name.'_time', $value?->format('H:i'));
    $times = collect(range(0, 45))->map(fn (int $slot) => sprintf('%02d:%02d', intdiv($slot, 2), ($slot % 2) * 30));
    if (filled($timeValue) && ! $times->contains($timeValue)) {
        $times = $times->push($timeValue)->sort()->values();
    }
    $invalid = $errors->has($name) ? 'is-invalid' : '';
@endphp
<label for="{{ $name }}" class="col-form-label required">{{ $label }}:</label>
<input type="date"
       class="form-control {{ $invalid }} a11y-form-control"
       id="{{ $name }}" name="{{ $name }}_date"
       value="{{ $dateValue }}"
       required>
<select id="{{ $name }}_time" name="{{ $name }}_time"
        class="form-control custom-select mt-2 {{ $invalid }} a11y-form-control"
        aria-label="{{ $label }} {{ t('Time') }}"
        required>
    <option value="" disabled hidden {{ blank($timeValue) ? 'selected' : '' }}>{{ t('Time') }}</option>
    @foreach($times as $time)
        <option value="{{ $time }}" {{ $time === $timeValue ? 'selected' : '' }}>{{ $time }}</option>
    @endforeach
</select>
<span class="invalid-feedback">{{ $errors->first($name) }}</span>
