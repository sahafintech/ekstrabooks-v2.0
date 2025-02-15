@extends('website.layouts')

@section('content')
<!-- Page Content-->
<section id="faq">
    <div class="container my-3">
        <div class="row gx-5 justify-content-center">
            <div class="col-lg-8 col-xl-6">
                <div class="text-center section-header">
                    <h3>{{ _lang('FAQ') }}</h3>
                    <h2>{{ isset($pageData->faq_heading) ? $pageData->faq_heading : '' }}</h2>
                    <p>{{ isset($pageData->faq_sub_heading) ? $pageData->faq_sub_heading : '' }}</p>
                </div>
            </div>
        </div>

        <div class="row mt-6">
            <div class="col-12">
                <div id="accordion-2" class="accordion-wrapper row">
                    @foreach($faqs as $faq)
                    <div class="col-lg-6">
                        <div class="card accordion-item">
                            <div class="card-header" id="accordion-heading-2-{{ $faq->id }}">
                                <button class="collapsed" data-bs-toggle="collapse" data-bs-target="#accordion-collapse-2-{{ $faq->id }}" aria-expanded="false" aria-controls="accordion-collapse-2-{{ $faq->id }}">{{ $faq->translation->question }}</button>
                            </div>
                            <!-- /.card-header -->
                            <div id="accordion-collapse-2-{{ $faq->id }}" class="collapse" aria-labelledby="accordion-heading-2-{{ $faq->id }}" data-bs-target="#accordion-{{ $faq->id }}">
                                <div class="card-body">
                                    <p>{{ $faq->translation->answer }}</p>
                                </div>
                                <!-- /.card-body -->
                            </div>
                            <!-- /.collapse -->
                        </div>
                    </div>
                    <!-- /.card -->
                    @endforeach
                </div>
                <!-- /.accordion-wrapper -->
            </div>
            <!--/column -->
        </div>
    </div>
</section>
@endsection