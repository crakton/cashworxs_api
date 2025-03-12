<!--begin::Sidebar-->
<div id="kt_app_sidebar" class="app-sidebar flex-column" data-kt-drawer="true" data-kt-drawer-name="app-sidebar"
    data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true" data-kt-drawer-width="250px"
    data-kt-drawer-direction="start" data-kt-drawer-toggle="#kt_app_sidebar_mobile_toggle">
    <!--begin::Main-->
    <div class="d-flex flex-column justify-content-between h-100 hover-scroll-overlay-y my-2 d-flex flex-column"
        id="kt_app_sidebar_main" data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-height="auto"
        data-kt-scroll-dependencies="#kt_app_header" data-kt-scroll-wrappers="#kt_app_main" data-kt-scroll-offset="5px">
        <!--begin::Sidebar menu-->
        <div id="#kt_app_sidebar_menu" data-kt-menu="true" data-kt-menu-expand="false"
            class="flex-column-fluid menu menu-sub-indention menu-column menu-rounded menu-active-bg mb-7">
            <!-- All the sidebar menu items here, like: -->
            <!--begin:Menu item-->
            <div data-kt-menu-trigger="click" class="menu-item here show menu-accordion">
                <!--begin:Menu link-->
                <span class="menu-link">
                    <span class="menu-icon">
                        <i class="ki-duotone ki-element-11 fs-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                            <span class="path4"></span>
                        </i>
                    </span>
                    <span class="menu-title">Dashboards</span>
                    <span class="menu-arrow"></span>
                </span>
                <!--end:Menu link-->
                <!--begin:Menu sub-->
                <div class="menu-sub menu-sub-accordion">
                    <!--begin:Menu item-->
                    <div class="menu-item">
                        <!--begin:Menu link-->
                        <a class="menu-link active" href="../dist/index.html">
                            <span class="menu-bullet">
                                <span class="bullet bullet-dot"></span>
                            </span>
                            <span class="menu-title">Default</span>
                        </a>
                        <!--end:Menu link-->
                    </div>
                    <!--end:Menu item-->
                    <!-- ... Rest of your sidebar menu items ... -->
                </div>
                <!--end:Menu sub-->
            </div>
            <!--end:Menu item-->
            <!-- ... Rest of your sidebar menu items ... -->
        </div>
        <!--end::Sidebar menu-->
        <!--begin::Footer-->
        <div class="app-sidebar-project-default app-sidebar-project-minimize text-center min-h-lg-400px flex-column-auto d-flex flex-column justify-content-end"
            id="kt_app_sidebar_footer">
            <!--begin::Title-->
            <h2 class="fw-bold text-gray-800">Welcome to Saul</h2>
            <!--end::Title-->
            <!--begin::Description-->
            <div class="fw-semibold text-gray-700 fs-7 lh-2 px-7 mb-1">Join the movement make a difference.</div>
            <!--end::Description-->
            <!--begin::Illustration-->
            <img class="mx-auto h-150px h-lg-175px mb-4" src="assets/media/misc/saul-welcome.png" alt="" />
            <!--end::Illustration-->
            <div class="text-center mb-lg-5 pb-lg-3">
                <a href="#" class="btn btn-sm btn-dark" data-bs-toggle="modal"
                    data-bs-target="#kt_modal_create_account">Get Started</a>
            </div>
        </div>
        <!--end::Footer-->
    </div>
    <!--end::Main-->
</div>
<!--end::Sidebar-->
