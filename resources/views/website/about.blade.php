@extends('website.layouts')

@section('content')
<!-- About section one-->
<section class="section mt-6" id="about">
    <div class="container px-4">
        <div class="row justify-content-center align-items-center">
            <div class="col-12">
                <div class="section-title">
                    <h2 class="mb-4">{{ isset($pageData->section_1_heading) ? $pageData->section_1_heading : '' }}</h2>
                    <div class="content pe-0 pe-lg-5">
                        {!! isset($pageData->section_1_content) ? xss_clean($pageData->section_1_content) : '' !!}
                    </div>
                </div>
            </div>
            <div class="col-lg-5 mt-5 mt-lg-0">
                <img src="{{ isset($pageMedia->about_image) ? asset('public/uploads/media/'.$pageMedia->about_image) : asset('public/website/assets/about-us.jpg') }}" alt="" class="rounded w-100">
            </div>
        </div>
    </div>
</section>

<!-- <section class="about-section section bg-sky">
    <div class="container">
        <div class="row justify-content-around">
            <div class="col-lg-5">
                <div class="section-title">
                    <p class="text-white text-uppercase fw-bold mb-3"></p>
                    <h2>{{ isset($pageData->section_2_heading) ? $pageData->section_2_heading : '' }}</h2>
                </div>
                {!! isset($pageData->section_2_content) ? xss_clean($pageData->section_2_content) : '' !!}
            </div>
            <div class="col-lg-5">
                <div class="section-title">
                    <p class="text-white text-uppercase fw-bold mb-3"></p>
                    <h2>{{ isset($pageData->section_3_heading) ? $pageData->section_3_heading : '' }}</h2>
                </div>
                {!! isset($pageData->section_3_content) ? xss_clean($pageData->section_3_content) : '' !!}
            </div>
        </div>
    </div>
</section> -->

<!-- Team members section-->
<section class="section teams mt-6">
    <div class="container px-4">
        <div class="row gx-5 justify-content-center">
            <div class="col-lg-8 col-xl-6">
                <div class="text-center section-header">
                    <h3>{{ _lang('Teams') }}</h3>
                    <h2>{{ isset($pageData->team_heading) ? $pageData->team_heading : '' }}</h2>
                    <p>{{ isset($pageData->team_sub_heading) ? $pageData->team_sub_heading : '' }}</p>
                </div>
            </div>
        </div>
        
        <div class="row gx-5 justify-content-center">
            @foreach($team_members as $team)
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card team rounded shadow-lg h-100">
                    <div class="card-body d-flex align-items-center flex-column justify-content-center text-center p-5">
                        <picture class="avatar">
                            <img class="img-fluid rounded-circle" src="{{ asset('public/uploads/media/'.$team->image) }}" alt="">
                        </picture>
                        <p class="lead fw-bolder mb-0 mt-4 text-dark">{{ $team->translation->name }}</p>
                        <p class="text-primary small fw-bold mb-4">{{ $team->translation->role }}</p>
                        <p>{{ $team->translation->description }}</p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>     
@endsection