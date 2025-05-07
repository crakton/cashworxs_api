<!DOCTYPE html>
<html lang="en">
<!--begin::Head-->

<head>
    <base href="{{ route('app') }}" />
    <title>CASHWORX::ADMIN</title>
    <meta charset="utf-8" />
    <meta name="description" content="CASHWORX::ADMIN" />
    <meta name="keywords" content="CASHWORX, admin, dashboard" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico" />
    <!--begin::Fonts(mandatory for all pages)-->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
    <!--end::Fonts-->
    <!--begin::Global Stylesheets Bundle(mandatory for all pages)-->
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css" />
    <!--end::Global Stylesheets Bundle-->
</head>
<!--end::Head-->
<!--begin::Body-->

<body id="kt_body" class="app-blank">
    <!--begin::Theme mode setup on page load-->
    <script>
        var defaultThemeMode = "light";
        var themeMode;
        if (document.documentElement) {
            if (document.documentElement.hasAttribute("data-bs-theme-mode")) {
                themeMode = document.documentElement.getAttribute("data-bs-theme-mode");
            } else {
                if (localStorage.getItem("data-bs-theme") !== null) {
                    themeMode = localStorage.getItem("data-bs-theme");
                } else {
                    themeMode = defaultThemeMode;
                }
            }
            if (themeMode === "system") {
                themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
            }
            document.documentElement.setAttribute("data-bs-theme", themeMode);
        }
    </script>
    <!--end::Theme mode setup on page load-->
    <!--begin::Root-->
    <div class="d-flex flex-column flex-root" id="kt_app_root">
        <!--begin::Authentication - Sign-in -->
        <div class="d-flex flex-column flex-lg-row flex-column-fluid">
            <!--begin::Aside-->
            <div class="d-flex flex-column flex-lg-row-auto bg-primary w-xl-600px positon-xl-relative">
                <div class="d-flex flex-column position-xl-fixed top-0 bottom-0 w-xl-600px scroll-y">
                    <div class="d-flex flex-row-fluid flex-column text-center p-5 p-lg-10 pt-lg-20">
                        <a href="{{ route('app') }}" class="py-2 py-lg-20">
                            <img alt="Logo" src="assets/media/logos/mail.svg" class="h-40px h-lg-50px" />
                        </a>
                        <h1 class="d-none d-lg-block fw-bold text-white fs-2qx pb-5 pb-md-10">Welcome to CASHWORX
                        </h1>
                        <p class="d-none d-lg-block fw-semibold fs-2 text-white">
                            Manage your finances with ease.
                        </p>
                    </div>
                    <div class="d-none d-lg-block d-flex flex-row-auto bgi-no-repeat bgi-position-x-center bgi-size-contain bgi-position-y-bottom min-h-100px min-h-lg-350px"
                        style="background-image: url(assets/media/illustrations/sketchy-1/17.png)">
                    </div>
                </div>
            </div>
            <!--begin::Aside-->
            <!--begin::Body-->
            <div class="d-flex flex-column flex-lg-row-fluid py-10">
                <!--begin::Content-->
                <div class="d-flex flex-center flex-column flex-column-fluid">
                    <!--begin::Wrapper-->
                    <div class="w-lg-500px p-10 p-lg-15 mx-auto">
                        <form class="form w-100" novalidate="novalidate" id="kt_sign_in_form">
                            <div class="text-center mb-10">
                                <h1 class="text-dark mb-3">Sign In to CASHWORX</h1>
                                <div class="text-gray-400 fw-semibold fs-4">New Here?
                                    <a href="{{ route('signup') }}" class="link-primary fw-bold">Create an Account</a>
                                </div>
                            </div>
                            <div class="fv-row mb-10">
                                <label class="form-label fs-6 fw-bold text-dark">Phone Number</label>
                                <input class="form-control form-control-lg form-control-solid" type="text"
                                    name="phone_number" id="phone_number" autocomplete="off" />
                            </div>
                            <div class="fv-row mb-10">
                                <div class="d-flex flex-stack mb-2">
                                    <label class="form-label fw-bold text-dark fs-6 mb-0">Password</label>
                                    <a href="#" class="link-primary fs-6 fw-bold">Forgot Password ?</a>
                                </div>
                                <input class="form-control form-control-lg form-control-solid" type="password"
                                    name="password" id="password" autocomplete="off" />
                            </div>
                            <div class="text-center">
                                <button type="button" id="kt_sign_in_submit" class="btn btn-lg btn-primary w-100 mb-5">
                                    <span class="indicator-label">Sign In</span>
                                    <span class="indicator-progress">Please wait...
                                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- Error message display area -->
                <div id="error_message" class="text-center text-danger" style="display: none;"></div>
            </div>
        </div>
    </div>
    <script>
        var hostUrl = "assets/";
    </script>
    <!--begin::Global Javascript Bundle(mandatory for all pages)-->
    <script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>

    <!-- Login Form Handler -->
    <script>
        $(document).ready(function() {
            // Setup CSRF token for AJAX requests
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // Handle form submission
            $('#kt_sign_in_submit').on('click', function(e) {
                e.preventDefault();

                var btn = $(this);
                var form = $('#kt_sign_in_form');

                // Show loading state
                btn.attr('data-kt-indicator', 'on');
                btn.prop('disabled', true);

                // Clear previous errors
                $('#error_message').hide();

                // Get form data
                var phoneNumber = $('#phone_number').val();
                var password = $('#password').val();

                // Validate inputs
                if (!phoneNumber || !password) {
                    $('#error_message').text('Please enter both phone number and password').show();
                    btn.removeAttr('data-kt-indicator');
                    btn.prop('disabled', false);
                    return;
                }

                // Make API call
                $.ajax({
                    url: '/api/auth/login',
                    type: 'POST',
                    data: {
                        phone_number: phoneNumber,
                        password: password
                    },
                    success: function(response) {
                        // Store token in localStorage

                        if (response.data.user.is_admin) {

                            localStorage.setItem('cashworx_token', response.data.token);
                            localStorage.setItem('cashworx_user', JSON.stringify(response.data
                                .user));

                            // Redirect to dashboard
                            window.location.href = "{{ route('dashboard') }}";
                        } else {
                            $('#error_message').text('Unauthorized user').show();
                            btn.removeAttr('data-kt-indicator');
                        }

                    },
                    error: function(xhr) {
                        // Handle errors
                        btn.removeAttr('data-kt-indicator');
                        btn.prop('disabled', false);

                        var errorMessage = 'Login failed. Please try again.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }

                        $('#error_message').text(errorMessage).show();
                    }
                });
            });
        });
    </script>
</body>
<!--end::Body-->

</html>
