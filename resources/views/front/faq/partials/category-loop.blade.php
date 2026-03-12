<div class="col-sm-12 mb-5">
    <h2 class="mb-4 text-center content-header">{{ $category->name }}</h2>
    <div id="accordion{{ $category->id }}">
        @foreach($category->faqs as $key => $faq)
            <div class="card faq">
                <div class="card-header" id="heading{{ $faq->id }}">
                    <button
                            id="faq-button-{{ $faq->id }}"
                            class="faq btn text-left d-block w-100"
                            type="button"
                            data-toggle="collapse"
                            data-target="#collapse{{ $faq->id }}"
                            aria-expanded="false"
                            aria-controls="collapse{{ $faq->id }}"
                    >
                        {{ $faq->question }}
                    </button>
                </div>

                <div
                        id="collapse{{ $faq->id }}"
                        class="collapse"
                        role="region"
                        aria-labelledby="faq-button-{{ $faq->id }}"
                        data-parent="#accordion{{ $category->id }}"
                >
                    <div class="card-body">
                        {!! $faq->answer !!}
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>