<div class="col-md-10 offset-2 mx-auto">
    <h3 class="text-center pt-4">{{ t('Bingo Words') }}</h3>
    <hr>
    <table id="words-tbl" class="table table-striped table-bordered data-table">
        <thead>
        <tr>
            <th scope="col">{{ t('Words') }}</th>
            <th scope="col">{{ t('Definitions') }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach($bingo->words->sortBy('word', SORT_NATURAL | SORT_FLAG_CASE) as $word)
            <tr>
                <td>{{ $word->word }}</td>
                <td>{{ $word->definition }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
