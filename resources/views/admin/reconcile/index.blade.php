@extends('admin.layout.default')
{{-- Web site Title --}}
@section('title')
    {{ t('Expert Review') }}
@stop

@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
@endpush

@section('content')
    {{-- Zoom, page selects and copying answers: the Alpine "expertReview" component (resources/js/expertReview.js). --}}
    <div x-data="expertReview">

    <div class="row mt-5 justify-content-center">
        {{ $reconciles->links() }}
        <select name="pagination" id="pagination" class="form-control custom-select ml-2 a11y-form-control" style="width: 100px"
                aria-label="{{ t('Page') }}" @change="goToPage($event.target.value)" required>
            @foreach($reconciles->getUrlRange(1, $reconciles->total()) as $page => $url)
                <option value="{{ $url }}" {{ $reconciles->url($reconciles->currentPage()) === $url ? ' selected=selected' : '' }}>{{ $page }}</option>
            @endforeach
        </select>
    </div>
    <div class="row">
        <div class="col-6">
            <div class="buttons text-center">
                <button type="button" id="btnZoomIn" class="btn btn-primary p-2 m-1" @click="zoomIn">Zoom in</button>
                <button type="button" id="btnZoomOut" class="btn btn-primary p-2 m-1" @click="zoomOut">Zoom out</button>
                <button type="button" id="btnZoomReset" class="btn btn-primary p-2 m-1" @click="resetZoom">Reset</button>
                <label>(or Mouse Wheel)</label>
            </div>
            <div>
                <div id="panzoom" x-ref="panzoom">
                    <div class="loader mx-auto" x-show="loading"></div>
                    <img src="{{ $imgUrl }}" class="img-fluid lazy" alt="Image" x-ref="image" @load="loading = false">
                </div>
            </div>
        </div>
        <div class="col-6">
            <form method="post" id="frmReconcile"
                  action="{{ route('admin.reconciles.update', [$expedition]) }}" role="form">
                {!! method_field('put') !!}
                @csrf
                <input type="hidden" name="_id" value="{{ $reconciles->first()->_id }}">
                <input type="hidden" name="page"
                       value="{{ $reconciles->hasMorePages() ? $reconciles->nextPageUrl() : $reconciles->url($reconciles->currentPage()) }}">
                <div class="row">
                    <h2 id="output"></h2>
                </div>
                @foreach($columns as $encodedColumn)
                    @php
                        $decodedColumn = TranscriptionMapHelper::decodeTranscriptionField($encodedColumn);
                    @endphp
                    <div class="row">
                        <div class="input-group mt-5">
                            <div class="col-7">
                                <label for="{{ $encodedColumn }}"
                                       class="col-form-label">{{ t('Your expert opinion of') }}
                                    <br> {{ $decodedColumn }}:</label>
                                <textarea class="form-control a11y-form-control" rows="3"
                                          id="{{ $encodedColumn }}"
                                          name="{{ $encodedColumn }}">{{ $reconciles->first()->getAttributes()[$encodedColumn] ?? '' }}</textarea>
                            </div>
                            <div class="col-5">
                                <label class="col-form-label">{{ t('Participants entered for') }} {{ $decodedColumn }}
                                    :</label>
                                @foreach($reconciles->first()->transcriptions as $transcription)
                                    @php
                                        $count = CountHelper::getTranscriptionCountForTranscriber($transcription->subject_projectId, $transcription->user_name);
                                        // A participant's transcription has no field for a column they left out.
                                        $answer = (string) ($transcription->getAttributes()[$encodedColumn] ?? '');
                                        $answerId = 'answer-'.$encodedColumn.'-'.$transcription->_id;
                                    @endphp
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="radio"
                                               id="{{ $answerId }}"
                                               data-column="{{ $encodedColumn }}"
                                               @change="useAnswer($event.target)"
                                               value="{{ $answer }}">
                                        <label class="form-check-label" for="{{ $answerId }}">
                                            <i class="fa fa-flag {{ $count < 500 ? 'fa-flag-grey' : 'fa-flag-green' }}"
                                                data-hover="tooltip"
                                               title="{{ $transcription->user_name }} has {{ $count }} transcriptions"></i>
                                            @if($answer !== '')
                                                {{ $answer }}
                                            @else
                                                <i>{{ t('participant left blank') }}</i>
                                            @endif
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <hr class="header mx-auto mt-5" style="width:300px;">
                    </div>
                @endforeach
                <div class="row">
                    <div class="text-center m-auto col-3">
                        {!! $reconciles->first()->present()->reconcile_reviewed !!}
                    </div>
                </div>
                <div class="row">
                    <div class="form-group m-auto">
                        <button type="submit"
                                class="btn btn-primary text-uppercase mt-5">{{ t('Save') }}</button>
                    </div>
                </div>
            </form>
            @if(!$reconciles->hasMorePages())
                <div class="row mt-5">
                    <div class="col-12 m-auto justify-content-center text-center">
                        <a href="{{ route('admin.reconciles.publish', [$expedition]) }}"
                           class="btn btn-primary p-2 m-1 prevent-default text-uppercase"
                           data-method="post"
                           data-confirm="confirmation"
                           data-title="{{ t('Publish Reconciled File') }}"
                           data-content="{{ t('This will publish a new reconciled_with_expert_opinion.csv file containing your edits in the Expedition downloads section.') }}">
                            {{t('Publish Expert Review')}}</a>
                    </div>
                </div>
            @else
                <div class="row mt-5">
                    <div class="col-12 m-auto justify-content-center text-center">
                        <p>{{ t('Once you have submitted your expert opinion for all pages, go to the last page and click "Publish Reconciled."') }}</p>
                    </div>
                </div>
            @endif
            @if(! empty($comments))
                <div class="row" x-data="{ talk: false }">
                    <div class="text-center mx-auto my-4">
                        <button type="button" class="btn btn-primary pl-4 pr-4 text-uppercase"
                                @click="talk = ! talk"
                                :aria-expanded="talk.toString()" aria-expanded="false" aria-controls="talk"
                        >{{ t('toggle talk comments') }}</button>
                    </div>
                    <div id="talk" class="col-sm-12" x-show="talk" x-collapse x-cloak>
                        <div id="comments" class="row col-sm-12 mx-auto justify-content-center">
                            @each('admin.reconcile.partials.comments-loop', $comments, 'comment')
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
    <div class="row mt-5 justify-content-center">
        {{ $reconciles->links() }}
        <select name="pagination" id="pagination-bottom" class="form-control custom-select ml-2 a11y-form-control" style="width: 100px"
                aria-label="{{ t('Page') }}" @change="goToPage($event.target.value)" required>
            @foreach($reconciles->getUrlRange(1, $reconciles->total()) as $page => $url)
                <option value="{{ $url }}" {{ $reconciles->url($reconciles->currentPage()) === $url ? ' selected=selected' : '' }}>{{ $page }}</option>
            @endforeach
        </select>
    </div>
    </div>
@stop

@push('scripts')
    <script src="https://unpkg.com/@panzoom/panzoom@4.6.0/dist/panzoom.min.js"></script>
    @vite('resources/js/expertReview.js')
@endpush