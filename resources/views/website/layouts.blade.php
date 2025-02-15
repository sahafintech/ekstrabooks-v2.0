<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>{{ !isset($page_title) ? get_option('site_title', config('app.name')) : $page_title }}</title>
    <!-- Favicon-->
    <link rel="icon" type="image/png" href="{{ get_favicon() }}" />

    <!-- Bootstrap icons-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.4/font/bootstrap-icons.css" rel="stylesheet">

    <!--Google Font-->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Core theme CSS (includes Bootstrap)-->
    <link rel="stylesheet" href="{{ asset('/website/asset/css/plugins.css') }}" />
    <link rel="stylesheet" href="{{ asset('/website/asset/css/style.css') }}" />
    <link href="{{ asset('/backend/plugins/jquery-toast-plugin/jquery.toast.min.css') }}" rel="stylesheet" />
    @php $header_footer_settings = json_decode(get_trans_option('header_footer_page')); @endphp
    @php $header_footer_media = json_decode(get_trans_option('header_footer_page_media')); @endphp

    <script src="https://www.google.com/recaptcha/api.js" async defer></script>

    @include('website.custom-css')
</head>

<body>
    <div class="content-wrapper">
        <header class="wrapper bg-white">
            <nav class="navbar navbar-expand-lg center-nav transparent navbar-light">
                <div class="container flex-lg-row flex-nowrap align-items-center">
                    <div class="navbar-brand w-100">
                        <a class="navbar-brand" href="/">
                            <img src="{{ get_logo() }}" style="width: 120px;" />
                        </a>
                    </div>
                    <div class="navbar-collapse offcanvas offcanvas-nav offcanvas-start">
                        <div class="offcanvas-header d-lg-none">
                            <h3 class="text-white fs-30 mb-0">Ekstrabooks</h3>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                        </div>
                        <div class="offcanvas-body ms-lg-auto d-flex flex-column h-100">
                            <ul class="navbar-nav">
                                <li class="nav-item">
                                    <a class="nav-link {{ url()->current() == url('/') ? 'active' : '' }}" href="{{ url('/') }}">Home</a>
                                </li>
                                <!-- @if(count(\App\Models\Page::active()->get()) > 0)
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Pages</a>
                                    <ul class="dropdown-menu">
                                        @foreach(\App\Models\Page::active()->get() as $d_page)
                                        <li class="nav-item">
                                            <a class="dropdown-item" href="{{ url('/'.$d_page->slug) }}">{{ $d_page->translation->title }}</a>
                                        </li>
                                        @endforeach
                                    </ul>
                                </li>
                                @endif -->
                                <!-- <li class="nav-item dropdown">
                                    <a class="nav-link {{ url()->current() == url('/about') ? 'active' : '' }}" href="{{ url('about') }}">About</a>
                                </li> -->
                                <!-- <li class="nav-item">
                                    <a class="nav-link {{ url()->current() == url('/pricing') ? 'active' : '' }}" href="{{ url('pricing') }}">Pricing</a>
                                </li> -->
                                <!-- <li class="nav-item">
                                    <a class="nav-link {{ url()->current() == url('/features') ? 'active' : '' }}" href="{{ url('/features') }}">Features</a>
                                </li> -->
                                <!-- <li class="nav-item">
                                    <a class="nav-link {{ url()->current() == url('/blogs') ? 'active' : '' }}" href="{{ url('blogs') }}">Blogs</a>
                                </li> -->
                                <li class="nav-item">
                                    <a class="nav-link {{ url()->current() == url('/faq') ? 'active' : '' }}" href="{{ url('/faq') }}">FAQ</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ url()->current() == url('/contact') ? 'active' : '' }}" href="{{ url('/contact') }}">Contact</a>
                                </li>
                            </ul>
                            <!-- /.navbar-nav -->
                            <div class="offcanvas-footer d-lg-none">
                                <div>
                                    <!-- <a href="mailto:info@sahafintech.com" class="link-inverse">info@sahafintech.com</a> -->
                                    <br />
                                    <!-- +252 63 805 9999 <br /> -->
                                    <nav class="nav social social-white mt-4">
                                        <a href="#"><i class="uil uil-twitter"></i></a>
                                        <a href="#"><i class="uil uil-facebook-f"></i></a>
                                        <a href="#"><i class="uil uil-dribbble"></i></a>
                                        <a href="#"><i class="uil uil-instagram"></i></a>
                                        <a href="#"><i class="uil uil-youtube"></i></a>
                                    </nav>
                                    <!-- /.social -->
                                </div>
                            </div>
                            <!-- /.offcanvas-footer -->
                        </div>
                        <!-- /.offcanvas-body -->
                    </div>
                    <!-- /.navbar-collapse -->
                    <div class="navbar-other w-100 d-flex ms-auto">
                        @guest
                        <ul class="navbar-nav flex-row align-items-center ms-auto">
                            <li class="nav-item">
                                <a href="{{ route('login') }}" class="nav-link">Sign In</a>
                            </li>
                            <li class="nav-item d-none d-md-block">
                                <a href="{{ route('register') }}" class="btn btn-sm btn-primary rounded">Sign Up</a>
                            </li>
                            <li class="nav-item d-lg-none">
                                <button class="hamburger offcanvas-nav-btn">
                                    <span></span>
                                </button>
                            </li>
                        </ul>
                        <!-- /.navbar-nav -->
                        @endguest

                        @auth
                        <ul class="navbar-nav flex-row align-items-center ms-auto">
                            <li class="nav-item d-none d-md-block">
                                <a href="{{ route('dashboard.index') }}" class="btn btn-sm btn-primary rounded">Dashboard</a>
                            </li>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <li class="nav-item">
                                    <button type="submit" class="nav-link">Logout</button>
                                </li>
                            </form>
                            <li class="nav-item d-lg-none">
                                <button class="hamburger offcanvas-nav-btn">
                                    <span></span>
                                </button>
                            </li>
                        </ul>
                        @endauth
                    </div>
                    <!-- /.navbar-other -->
                </div>
                <!-- /.container -->
            </nav>
            <!-- /.navbar -->
        </header>

        @yield('content')

        <!-- @php $gdpr_cookie_consent = json_decode(get_trans_option('gdpr_cookie_consent_page')) @endphp

        @if(isset($gdpr_cookie_consent->cookie_consent_status) && $gdpr_cookie_consent->cookie_consent_status == 1)
        @include('cookie-consent::index')
        @endif -->
    </div>

    <footer class="bg-gray mt-5 mt-md-16">
        <div class="container py-13 py-md-15">
            <div class="row gy-6 gy-lg-0">
                <div class="col-md-4 col-lg-3">
                    <div class="widget">
                        <img class="mb-4" src="{{ get_logo() }}" style="width: 120px;" />
                        <p class="mb-4">
                            © {{ now()->format('Y') }} Sahafintech. <br class="d-none d-lg-block" />All rights
                            reserved.
                        </p>
                        <nav class="nav social social-dark">
                            <a href="#"><i class="uil uil-twitter"></i></a>
                            <a href="#"><i class="uil uil-facebook-f"></i></a>
                            <a href="#"><i class="uil uil-dribbble"></i></a>
                            <a href="#"><i class="uil uil-instagram"></i></a>
                            <a href="#"><i class="uil uil-youtube"></i></a>
                        </nav>
                        <!-- /.social -->
                    </div>
                    <!-- /.widget -->
                </div>
                <!-- /column -->
                <div class="col-md-4 col-lg-3">
                    <div class="widget">
                        <h4 class="widget-title text-dark mb-3">Get in Touch</h4>
                        <address class="pe-xl-15 pe-xxl-17">
                            Jigjiga Yar Qaalib Building Floor 1, Hargeisa Somaliland
                        </address>
                        <!-- <a href="mailto:info@sahafintech.com" class="text-dark">info@sahafintech.com</a><br /> -->
                        <!-- +252 63 805 9999 -->
                    </div>
                    <!-- /.widget -->
                </div>
                <!-- /column -->
                <div class="col-md-4 col-lg-3">
                    <div class="widget">
                        <h4 class="widget-title text-dark mb-3">{{ isset($header_footer_settings->widget_2_heading) ? $header_footer_settings->widget_2_heading : '' }}</h4>
                        <ul class="list-unstyled mb-0">
                            @if(isset($header_footer_settings->widget_2_menus))
                            @foreach($header_footer_settings->widget_2_menus as $widget_2_menu)
                            <li><a href="{{ url('/'.$widget_2_menu) }}" class="text-dark">{{ get_page_title($widget_2_menu) }}</a></li>
                            @endforeach
                            @endif
                        </ul>
                    </div>
                    <!-- /.widget -->
                </div>
                <!-- <div class="col-md-4 col-lg-3">
                    <div class="widget">
                        <h4 class="widget-title text-dark mb-3">{{ isset($header_footer_settings->widget_3_heading) ? $header_footer_settings->widget_3_heading : '' }}</h4>
                        <ul class="list-unstyled mb-0">
                            @if(isset($header_footer_settings->widget_3_menus))
                            @foreach($header_footer_settings->widget_3_menus as $widget_3_menu)
                            <li><a href="{{ url('/'.$widget_3_menu) }}" class="text-dark">{{ get_page_title($widget_3_menu) }}</a></li>
                            @endforeach
                            @endif
                        </ul>
                    </div>
                </div> -->
                <!-- /column -->
                <div class="col-md-6">
                    <div class="widget">
                        <p class="text-dark mb-3">{{ isset($header_footer_settings->widget_2_content) ? $header_footer_settings->widget_2_content : '' }}</p>
                    </div>
                    <!-- /.widget -->
                </div>
                <!-- /column -->
            </div>
            <!--/.row -->
        </div>
        <!-- /.container -->
    </footer>

    <script src="{{ asset('/website/asset/js/plugins.js') }}"></script>
    <script src="{{ asset('/website/asset/js/theme.js') }}"></script>

    <script src="/assets/js/jquery-3.7.0.js"></script>

    <!-- Core theme JS-->
    <script src="{{ asset('/website/js/scripts.js') }}"></script>
    @include('website.custom-js')
</body>

</html>