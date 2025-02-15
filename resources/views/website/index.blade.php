@extends('website.layouts')

@section('content')
<!-- hero-->
<section class="wrapper">
    <div class="container pt-5 text-center">
        <div class="row">
            <div class="col-lg-6 mx-auto">
                <h1 class="display-1 mb-4">
                    {{ isset($pageData->hero_heading) ? $pageData->hero_heading : '' }}
                </h1>
                <p class="lead fs-lg px-xl-12 px-xxl-6 mb-7">
                    {{ isset($pageData->hero_sub_heading) ? $pageData->hero_sub_heading : '' }}
                </p>
                <!-- <div class="d-flex justify-content-center">
                    <span><a href="{{ isset($pageData->get_started_link) ? $pageData->get_started_link : '#' }}" class="btn btn-primary rounded mx-1">{{ isset($pageData->get_started_text) ? $pageData->get_started_text : _lang('Get Started') }}</a></span>
                    <span><a href="{{ route('login') }}" class="btn btn-green rounded mx-1">{{ _lang('Sign In') }}</a></span>
                </div> -->
            </div>
            <div class="col-lg-6">
                <div>
                    <img class="img-fluid mx-auto rounded shadow-lg" src="{{ isset($pageMedia->hero_bg_image) ? asset('/uploads/media/'.$pageMedia->hero_bg_image) : asset('/website/assets/hero-bg.jpg') }}" alt="" />
                    <!-- <img class="position-absolute rounded shadow-lg" data-cue="slideInRight" src="/website/asset/img/photos/balance-sheet.png" style="top: 20%; right: -10%; max-width: 30%; height: auto" alt="" /> -->
                    <!-- <img class="position-absolute rounded shadow-lg" data-cue="slideInLeft" src="/website/asset/img/photos/balance-sheet.png" style="top: 10%; left: -10%; max-width: 30%; height: auto" alt="" /> -->
                    <!-- <img class="position-absolute rounded shadow-lg" data-cue="slideInLeft" src="/website/asset/img/photos/invoice.png" style="bottom: 10%; left: -13%; max-width: 30%; height: auto" alt="" /> -->
                </div>
            </div>
            <!-- /column -->
        </div>
        <!-- /.row -->
    </div>
    <!-- /.container -->
</section>

<!-- services -->
<section class="wrapper bg-light">
    <div class="container">
        @if(isset($pageData->features_status) && $pageData->features_status == 1)
        <div class="container">
            <div class="row">
                <div class="col-lg-8 col-xl-7 col-xxl-6">
                    <h2 class="fs-15 text-uppercase text-muted mb-3">
                        Features
                    </h2>
                    <h3 class="display-4 mb-9">
                        {{ isset($pageData->features_heading) ? $pageData->features_heading : '' }}
                    </h3>
                    <p>{{ isset($pageData->features_sub_heading) ? $pageData->features_sub_heading : '' }}</p>
                </div>
                <!-- /column -->
            </div>
            <!-- /.row -->
            <div class="row gx-md-8 gy-8">
                @foreach($features as $feature)
                <div class="col-md-6 col-lg-3">
                    <div class="icon btn btn-block btn-lg btn-primary pe-none mb-5">
                        {!! xss_clean($feature->icon) !!}
                    </div>
                    <h4>{{ $feature->translation->title }}</h4>
                    <p class="mb-3">
                        {{ $feature->translation->content }}
                    </p>
                    <a href="#" class="more hover link-primary">Learn More</a>
                </div>
                <!--/column -->
                @endforeach
            </div>
            <!--/.row -->
        </div>
        @endif
        <!--/.row -->
    </div>
    <!-- /.container -->
</section>

<!-- video -->
<!-- <section class="wrapper bg-soft-primary">
    <div class="container py-14 pt-md-16 pt-lg-0 pb-md-16">
        <div class="row text-center" data-cue="slideInUp">
            <div class="col-lg-10 mx-auto">
                <div class="mt-lg-n20 mt-xl-n22 position-relative">
                    <div class="shape bg-dot red rellax w-16 h-18" data-rellax-speed="1" style="top: 1rem; left: -3.9rem"></div>
                    <div class="shape rounded-circle bg-line primary rellax w-18 h-18" data-rellax-speed="1" style="bottom: 2rem; right: -3rem"></div>
                    <video poster="/website/asset/img/photos/movie.jpg" class="player" playsinline controls preload="none">
                        <source src="/website/asset/media/movie.mp4" type="video/mp4" />
                        <source src="/website/asset/media/movie.webm" type="video/webm" />
                    </video>
                </div>
            </div>
        </div>
        <div class="row text-center mt-12">
            <div class="col-lg-9 mx-auto">
                <h3 class="display-4 mb-0 text-center px-xl-10 px-xxl-15">
                    Find out everything you need to know about creating a business
                    process model
                </h3>
                <div class="row gx-lg-8 gx-xl-12 process-wrapper text-center mt-9" data-cues="slideInUp" data-group="process">
                    <div class="col-md-4">
                        <img src="/website/asset/img/icons/lineal/shield.svg" class="svg-inject icon-svg icon-svg-md text-red mb-3" alt="" />
                        <h4 class="mb-1">1. Secured Transactions</h4>
                        <p>Etiam porta malesuada magna mollis euismod sem.</p>
                    </div>
                    <div class="col-md-4">
                        <img src="/website/asset/img/icons/lineal/savings.svg" class="svg-inject icon-svg icon-svg-md text-green mb-3" alt="" />
                        <h4 class="mb-1">2. Budget Planning</h4>
                        <p>Etiam porta malesuada magna mollis euismod sem.</p>
                    </div>
                    <div class="col-md-4">
                        <img src="/website/asset/img/icons/lineal/loading.svg" class="svg-inject icon-svg icon-svg-md text-yellow mb-3" alt="" />
                        <h4 class="mb-1">3. Up to Date</h4>
                        <p>Etiam porta malesuada magna mollis euismod sem.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section> -->

@if(isset($pageData->pricing_status) && $pageData->pricing_status == 1)
<!-- pricing section -->
<section class="wrapper">
    <div class="container pt-15 pb-6 text-center">
        <div class="row">
            <div class="col-lg-7">
                <div class="pricing-wrapper position-relative">
                    <!-- <div class="d-flex align-items-center justify-content-center">
                @if($packages->where('package_type', 'monthly')->count() > 0)
                <div class="form-check form-switch custom-switch mb-5 me-3">
                    <input class="form-check-input plan_type" type="checkbox" value="monthly" name="plan_type" id="monthy-plans" checked>
                    <label class="form-check-label ms-1 text-primary" for="monthy-plans"><b>{{ _lang('Monthly') }}</b></label>
                </div>
                @endif

                @if($packages->where('package_type', 'yearly')->count() > 0)
                <div class="form-check form-switch custom-switch mb-5 me-3">
                    <input class="form-check-input plan_type" type="checkbox" value="yearly" name="plan_type" id="yearly-plans">
                    <label class="form-check-label ms-1 text-primary" for="yearly-plans"><b>{{ _lang('Yearly') }}</b></label>
                </div>
                @endif

                @if($packages->where('package_type', 'lifetime')->count() > 0)
                <div class="form-check form-switch custom-switch mb-5">
                    <input class="form-check-input plan_type" type="checkbox" value="lifetime" name="plan_type" id="lifetime-plans">
                    <label class="form-check-label ms-1 text-primary" for="lifetime-plans"><b>{{ _lang('Lifetime') }}</b></label>
                </div>
                @endif
            </div> -->
                    <div class="row">
                        @foreach($packages as $package)
                        <div class="{{ $package->package_type }}-plan {{ $package->package_type == 'monthly' ? '' : 'd-none' }}">
                            <div class="card shadow-sm text-center">
                                <div class="card-body">
                                    @if($package->cost != 0)
                                    @if($package->is_popular == 1)
                                    <span>{{ _lang('Most popular') }}</span>
                                    @endif
                                    <h4 class="card-title">{{ $package->name }}</h4>
                                    @if($package->discount > 0)
                                    <p class="d-inline-block">
                                        <small><del>{{ decimalPlace($package->cost, currency_symbol()) }}</del></small>
                                        <span class="bg-primary d-inline-block text-white px-3 py-1 rounded-pill ms-1">{{ $package->discount.'% '._lang('Discount') }}</span>
                                    </p>
                                    <div class="prices text-dark">
                                        <div class="price price-show">
                                            <span class="price-currency">{{ currency_symbol() }}</span><span class="price-value">{{ $package->cost - ($package->discount / 100) * $package->cost }}</span>
                                            <span class="price-duration">{{ ucwords($package->package_type) }}</span>
                                        </div>
                                    </div>
                                    @else
                                    <div class="prices text-dark">
                                        <div class="price price-show">
                                            <span class="price-currency">{{ currency_symbol() }}</span><span class="price-value">{{ $package->cost }}</span>
                                            <span class="price-duration">{{ ucwords($package->package_type) }}</span>
                                        </div>
                                    </div>
                                    @endif
                                    @else
                                    <h4 class="card-title">{{ $package->name }}</h4>
                                    <div class="prices text-dark">
                                        <div class="price price-show">
                                            <span class="price-currency">Tailored solutions to meet your unique needs</span>
                                        </div>
                                        <div>Price: Contact us for a quote</div>
                                    </div>
                                    @endif

                                    <!-- @if($package->trial_days > 0)
                                    <h6 class="mt-2 text-danger">{{ $package->trial_days.' '._lang('Days Free Trial') }}</h6>
                                    @else
                                    <h6 class="mt-2 text-dark">{{ _lang('No Trial Available') }}</h6>
                                    @endif -->
                                    <!--/.prices -->
                                    <div class="d-flex align-items-center justify-content-center">
                                        <ul class="icon-list bullet-bg bullet-soft-primary mt-7 mb-8 text-start">
                                            <!-- <li>
                                    <i class="uil uil-check"></i>
                                    {{ str_replace('-1',_lang('Unlimited'), $package->business_limit).' '._lang('Business Account') }}
                                </li> -->
                                            <li>
                                                <i class="uil uil-check"></i>
                                                {{ str_replace('-1',_lang('Unlimited'), $package->user_limit).' '._lang('System User') }}
                                            </li>
                                            <li>
                                                <i class="uil uil-check"></i>
                                                {{ str_replace('-1',_lang('Unlimited'), $package->invoice_limit).' '._lang('Invoice') }}
                                            </li>
                                            <li>
                                                <i class="uil uil-check"></i>
                                                {{ str_replace('-1',_lang('Unlimited'), $package->quotation_limit).' '._lang('Quotation') }}
                                            </li>
                                            <li>
                                                <i class="uil {{ $package->payroll_module == 0 ? 'uil-times bullet-soft-red' : 'uil-check' }} me-2"></i>
                                                {{ _lang('HR & Payroll Module') }}
                                            </li>
                                            <li>
                                                <i class="uil {{ $package->pos == 0 ? 'uil-times bullet-soft-red' : 'uil-check' }} me-2"></i>
                                                {{ _lang('Point Of Sale') }}
                                            </li>
                                            <!-- <li>
                                    <i class="uil {{ $package->recurring_invoice == 0 ? 'uil-times bullet-soft-red' : 'uil-check' }} me-2"></i>
                                    {{ _lang('Recurring Invoice') }}
                                </li> -->
                                            <li>
                                                <i class="uil uil-check"></i>
                                                {{ str_replace('-1',_lang('Unlimited'), $package->customer_limit).' '._lang('Customer Account') }}
                                            </li>
                                            <!-- <li>
                                    <i class="uil {{ $package->invoice_builder == 0 ? 'uil-times bullet-soft-red' : 'uil-check' }} me-2"></i>
                                    {{ _lang('Invoice Template Builder') }}
                                </li> -->
                                            <li>
                                                <i class="uil {{ $package->online_invoice_payment == 0 ? 'uil-times bullet-soft-red' : 'uil-check' }} me-2"></i>
                                                {{ _lang('Accept Online Payment') }}
                                            </li>
                                            <li>
                                                <i class="uil {{ $package->cost != 0 ? 'uil-times bullet-soft-red' : 'uil-check' }} me-2"></i>
                                                {{ _lang('Custom Development') }}
                                            </li>
                                        </ul>
                                    </div>
                                    @if($package->cost != 0)
                                    <a href="{{ route('register') }}?package_id={{ $package->id }}" class="btn btn-primary rounded">Choose Plan</a>
                                    @else
                                    <a href="/contact" class="btn btn-primary rounded">Contact Us</a>
                                    @endif
                                </div>
                                <!--/.card-body -->
                            </div>
                            <!--/.pricing -->
                        </div>
                        @endforeach
                        <!--/column -->
                    </div>
                    <!--/.row -->
                </div>
            </div>
            <div class="col-lg-5 d-flex align-items-center justify-content-center mt-6">
                <div>
                    <h3 class="display-4 px-lg-10">
                        {{ isset($pageData->pricing_heading) ? $pageData->pricing_heading : '' }}
                    </h3>
                    <p>{{ isset($pageData->pricing_sub_heading) ? $pageData->pricing_sub_heading : '' }}</p>
                </div>
            </div>
            <!--/column -->
        </div>
        <!--/.row -->
    </div>
    <!-- /.container -->
</section>
@endif

@if(isset($pageData->blog_status) && $pageData->blog_status == 1)
<!-- Blog preview section-->
<!-- <section id="snippet-1" class="wrapper bg-light wrapper-border">
    <div class="container pt-15 pt-md-17 pb-13 pb-md-15">
        <div class="text-center section-header">
            <h2 class="display-4 mb-3 text-center">Blogs</h2>
            <p class="lead fs-lg mb-10 text-center px-md-16 px-lg-21 px-xl-0">
                {{ isset($pageData->blog_heading) ? $pageData->blog_heading : '' }}
            </p>
            <p>{{ isset($pageData->blog_sub_heading) ? $pageData->blog_sub_heading : '' }}</p>
        </div>
        <div class="swiper-container blog grid-view mb-6" data-margin="30" data-dots="true" data-items-xl="3" data-items-md="2" data-items-xs="1">
            <div class="swiper">
                <div class="swiper-wrapper">
                    @foreach($blog_posts as $post)
                    <div class="swiper-slide">
                        <article>
                            <figure class="overlay overlay-1 hover-scale rounded mb-5">
                                <a href="#">
                                    <img src="{{ asset('/uploads/media/'.$post->image) }}" alt="" /></a>
                                <figcaption>
                                    <a href="{{ url('/blogs/'.$post->slug) }}">
                                        <h5 class="from-top mb-0">Read More</h5>
                                    </a>
                                </figcaption>
                            </figure>
                            <div class="post-header">
                                <h2 class="post-title h3 mt-1 mb-3">
                                    <a class="link-dark" href="{{ url('/blogs/'.$post->slug) }}">{{ $post->translation->title }}</a>
                                </h2>
                            </div>
                            <div class="post-footer">
                                <ul class="post-meta">
                                    <li class="post-date">
                                        <i class="uil uil-calendar-alt"></i><span>{{ $post->created_at }}</span>
                                    </li>
                                </ul>
                            </div>
                        </article>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section> -->
@endif


@if(isset($pageData->testimonials_status) && $pageData->testimonials_status == 1)
<!-- testimonials -->
<!-- <section id="testimonial">
    <div class="container pt-10 pt-md-17 pb-13 pb-md-15">
        <div class="text-center section-header mb-5">
            <h3>{{ _lang('Testimonials') }}</h3>
            <h2>{{ isset($pageData->testimonials_heading) ? $pageData->testimonials_heading : '' }}</h2>
            <p>{{ isset($pageData->testimonials_sub_heading) ? $pageData->testimonials_sub_heading : '' }}</p>
        </div>
        <div class="position-relative">
            <div class="shape rounded-circle bg-soft-yellow rellax w-16 h-16" data-rellax-speed="1" style="bottom: 0.5rem; right: -1.7rem"></div>
            <div class="shape bg-dot primary rellax w-16 h-16" data-rellax-speed="1" style="top: -1rem; left: -1.7rem"></div>
            <div class="swiper-container dots-closer mb-6" data-margin="0" data-dots="true" data-items-xl="3" data-items-md="2" data-items-xs="1">
                <div class="swiper">
                    <div class="swiper-wrapper">
                        @foreach($testimonials as $testimonial)
                        <div class="swiper-slide">
                            <div class="item-inner">
                                <div class="card">
                                    <div class="card-body">
                                        <blockquote class="icon mb-0">
                                            <p>
                                                “{{ $testimonial->translation->testimonial }}.”
                                            </p>
                                            <div class="blockquote-details">
                                                <img class="rounded-circle w-12" src="{{ asset('/uploads/media/'.$testimonial->image) }}" alt="{{ $testimonial->translation->name }}" />
                                                <div class="info">
                                                    <p class="mb-0">{{ $testimonial->translation->name }}</p>
                                                </div>
                                            </div>
                                        </blockquote>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section> -->
@endif


@if(isset($pageData->newsletter_status) && $pageData->newsletter_status == 1)
<!-- <section id="newsletter" style="background-image: url({{ isset($pageMedia->newsletter_bg_image) ? '/uploads/media/'.$pageMedia->newsletter_bg_image : '/website/assets/call-to-action.jpg' }})">
    <div class="container px-4 py-10 text-center">
        <h4 class="widget-title text-white mb-3">Our Newsletter</h4>
        <h2 class="text-white">{{ isset($pageData->newsletter_heading) ? $pageData->newsletter_heading : '' }}</h2>
        <p class="text-white">{{ isset($pageData->newsletter_sub_heading) ? $pageData->newsletter_sub_heading : '' }}</p>
        <div class="newsletter-wrapper">
            <div id="mc_embed_signup2">
                <form action="{{ url('/email_subscription') }}" id="email_subscription" method="post">
                    @csrf
                    <div id="mc_embed_signup_scroll2">
                        <div class="mc-field-group input-group form-floating">
                            <input type="email" value="" name="email_address" class="required email form-control" placeholder="Email Address" id="mce-EMAIL2" />
                            <label for="mce-EMAIL2">Email Address</label>
                            <input type="submit" value="Join" name="subscribe" id="mc-embedded-subscribe2" class="btn btn-primary" />
                        </div>
                        <div id="mce-responses2" class="clear">
                            <div class="response" id="mce-error-response2" style="display: none"></div>
                            <div class="response" id="mce-success-response2" style="display: none"></div>
                        </div>
                        <div style="position: absolute; left: -5000px" aria-hidden="true">
                            <input type="text" name="b_ddc180777a163e0f9f66ee014_4b1bcfa0bc" tabindex="-1" value="" />
                        </div>
                        <div class="clear"></div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section> -->
@endif
@endsection