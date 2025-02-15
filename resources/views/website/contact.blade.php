@extends('website.layouts')

@section('content')
<!--Contact Us Section-->
<section class="contact-us mt-6">
    <div class="container px-4">
        <div class="contact-head">
            <div class="row">
                <div class="col-lg-8 mb-lg-0 mb-5 col-12">
                    @if(Session::has('success'))
                    <div class="alert alert-success">
                        <strong>{{ session('success') }}</strong>
                    </div>
                    @endif

                    @if(Session::has('error'))
                    <div class="alert alert-danger">
                        <strong>{{ session('error') }}</strong>
                    </div>
                    @endif

                    <div class="title">
                        <h2 class="text-dark fw-bold mb-3">{{ isset($pageData->contact_form_heading) ? $pageData->contact_form_heading : '' }}</h2>
                        <p>{{ isset($pageData->contact_form_sub_heading) ? $pageData->contact_form_sub_heading : '' }}</p>
                    </div>
                </div>
                <div class="col-xl-10 mx-auto">
                    <div class="row gy-10 gx-lg-8 gx-xl-12">
                        <div class="col-lg-12">
                            <form class="form" method="post" autocomplete="off" action="{{ url('/send_message') }}">
                                @csrf
                                <div class="messages"></div>
                                <div class="row gx-4">
                                    <div class="col-md-6">
                                        <div class="form-floating mb-4">
                                            <input id="form_name" type="text" name="name" class="form-control" placeholder="Jane" required="">
                                            <label for="form_name">Name *</label>
                                            <div class="valid-feedback"> Looks good! </div>
                                            <div class="invalid-feedback"> Please enter your name. </div>
                                        </div>
                                    </div>
                                    <!-- /column -->
                                    <div class="col-md-6">
                                        <div class="form-floating mb-4">
                                            <input id="form_email" type="email" name="email" class="form-control" placeholder="jane.doe@example.com" required="">
                                            <label for="form_email">Email *</label>
                                            <div class="valid-feedback"> Looks good! </div>
                                            <div class="invalid-feedback"> Please provide a valid email address. </div>
                                        </div>
                                    </div>
                                    <!-- /column -->
                                    <div class="col-md-6">
                                        <div class="form-select-wrapper mb-4">
                                            <select class="form-select" id="form-select" name="subject" required="">
                                                <option value="">Select a department</option>
                                                <option value="Sales">Sales</option>
                                                <option value="Marketing">Marketing</option>
                                                <option value="Customer Support">Customer Support</option>
                                            </select>
                                            <div class="valid-feedback"> Looks good! </div>
                                            <div class="invalid-feedback"> Please select a department. </div>
                                        </div>
                                    </div>
                                    <!-- /column -->
                                    <div class="col-12">
                                        <div class="form-floating mb-4"><grammarly-extension data-grammarly-shadow-root="true" style="position: absolute; top: 0px; left: 0px; pointer-events: none;" class="dnXmp"></grammarly-extension><grammarly-extension data-grammarly-shadow-root="true" style="position: absolute; top: 0px; left: 0px; pointer-events: none;" class="dnXmp"></grammarly-extension>
                                            <textarea id="form_message" name="message" class="form-control" placeholder="Your message" style="height: 150px" required="" spellcheck="false"></textarea>
                                            <label for="form_message">Message *</label>
                                            <div class="valid-feedback"> Looks good! </div>
                                            <div class="invalid-feedback"> Please enter your messsage. </div>
                                        </div>
                                    </div>
                                    <!-- /column -->
                                    <div class="col-12">
                                        <div class="g-recaptcha" data-sitekey="{{ get_option('recaptcha_site_key') }}"></div>
                                        <br>
                                    </div>
                                    <!-- /column -->
                                    <div class="col-12">
                                        <input type="submit" class="btn btn-primary rounded-pill btn-send mb-3" value="Send message">
                                        <p class="text-muted"><strong>*</strong> These fields are required.</p>
                                    </div>
                                    <!-- /column -->
                                </div>
                                <!-- /.row -->
                            </form>
                            <!-- /form -->
                        </div>
                        <!--/column -->
                        <!-- <div class="col-lg-4">
                            @if(isset($pageData->contact_info_heading))
                            @foreach($pageData->contact_info_heading as $contact_info_heading)
                            <div class="d-flex flex-row">
                                <div>
                                    <h5 class="mb-1">{{ $contact_info_heading }}</h5>
                                    <div class="content">{!! isset($pageData->contact_info_content[$loop->index]) ? xss_clean($pageData->contact_info_content[$loop->index]) : '' !!}</div>
                                </div>
                            </div>
                            @endforeach
                            @endif

                            <div class="single-info">
                                <h4 class="title text-dark fw-bold">{{ _lang('Social Links') }}</h4>
                                <div class="content">
                                    <ul class="list-unstyled d-flex">
                                        <li class="me-2" data-wow-delay=".2s"><a href="{{ isset($pageData->facebook_link) ? $pageData->facebook_link : '' }}" class="text-decoration-none"><i class="bi bi-facebook ri-2x"></i></a></li>
                                        <li class="me-2" data-wow-delay=".4s"><a href="{{ isset($pageData->linkedin_link) ? $pageData->linkedin_link : '' }}" class="text-decoration-none"><i class="bi bi-linkedin ri-2x"></i></a></li>
                                        <li class="me-2" data-wow-delay=".6s"><a href="{{ isset($pageData->twitter_link) ? $pageData->twitter_link : '' }}" class="text-decoration-none"><i class="bi bi-twitter ri-2x"></i></a></li>
                                        <li class="me-2" data-wow-delay=".8s"><a href="{{ isset($pageData->youtube_link) ? $pageData->youtube_link : '' }}" class="text-decoration-none"><i class="bi bi-youtube ri-2x"></i></a></li>
                                    </ul>
                                </div>
                            </div>
                        </div> -->
                        <!--/column -->
                    </div>
                    <!--/.row -->
                </div>
                <!-- /column -->
            </div>
        </div>
    </div>
</section>
@endsection