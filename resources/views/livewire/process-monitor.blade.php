<div wire:poll.visible.5s="refreshData">
    <div>
        <p class="text-center color-action small mb-4">
            {{ t('Stats update every 5 seconds.') }}
        </p>

        <div class="space-y-5 pl-3 pr-3">
            {{-- OCR Section --}}
            <div class="text-center">
                <h4 class="mb-3">{{ t('OCR Processes') }}</h4>
            </div>
            <div id="ocr-html">
                @forelse($ocrQueues as $queue)
                    @php
                        $title = $queue->expedition?->title ?? $queue->project?->title ?? '—';
                        $key = "ocr-{$queue->id}-{$queue->stage->value}-{$queue->processed_files}";
                    @endphp

                    <div wire:key="{{ $key }}">
                        @if($loop->first)
                            @include('common.process-active', [
                               'title' => $title,
                               'stage' => $queue->stage->getLabel(),
                               'processedCount' => $queue->processed_files,
                               'totalCount' => $queue->total
                           ])
                        @else
                            @include('common.process-queued', [
                                'title' => $title,
                                'stage' => $queue->stage->getLabel(),
                                'queuePosition' => $loop->index === 1
                                    ? __('Next in queue.')
                                    : trans_choice(':count processes remain...', $loop->index)
                            ])
                        @endif
                    </div>
                @empty
                    <p class="text-muted text-center fst-italic">{{ t('No OCR processes running') }}</p>
                @endforelse
            </div>

            {{-- Export Section --}}
            <div class="text-center">
                <h4 class="mb-3">{{ t('Export Processes') }}</h4>
            </div>
            <div id="export-html">
                @forelse($exportQueues as $queue)
                    @php
                        $title = $queue->expedition?->title ?? '—';
                        $key = "export-{$queue->id}-{$queue->stage->value}-{$queue->processed_files}-{$queue->queued}";
                    @endphp

                    <div wire:key="{{ $key }}">
                        @if(! $queue->queued)
                            @include('common.process-queued', [
                                'title' => $title,
                                'stage' => \App\Enums\ExportQueueStage::Waiting->getLabel(),
                                'queuePosition' => $loop->index === 0
                                    ? __('Next in queue.')
                                    : trans_choice(':count exports in queue...', $loop->index)
                            ])
                        @else
                            @include('common.process-active', [
                                   'title' => $title,
                                   'stage' => $queue->stage->getLabel(),
                                   'processedCount' => $queue->processed_files,
                                   'totalCount' => $queue->total
                               ])
                        @endif
                    </div>
                @empty
                    <p class="text-muted text-center fst-italic">{{ t('No exports running') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</div>