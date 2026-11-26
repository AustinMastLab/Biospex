<div class="col-sm-12 mb-5">
    <h2 class="mb-4 text-center content-header">{{ $category->name }}</h2>
    {{-- One answer open at a time. --}}
    <div id="accordion{{ $category->id }}" x-data="{ openFaq: null }">
        @foreach($category->faqs as $key => $faq)
            <div class="card faq">
                <div class="card-header" id="heading{{ $faq->id }}">
                    <button
                            id="faq-button-{{ $faq->id }}"
                            class="faq btn text-left d-block w-100"
                            type="button"
                            @click="openFaq = openFaq === {{ $faq->id }} ? null : {{ $faq->id }}"
                            :aria-expanded="(openFaq === {{ $faq->id }}).toString()"
                            aria-expanded="false"
                            aria-controls="collapse{{ $faq->id }}"
                    >
                        {{ $faq->question }}
                    </button>
                </div>

                <div
                        id="collapse{{ $faq->id }}"
                        role="region"
                        aria-labelledby="faq-button-{{ $faq->id }}"
                        x-show="openFaq === {{ $faq->id }}"
                        x-collapse
                        x-cloak
                >
                    <div class="card-body">
                        {!! $faq->answer !!}
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>