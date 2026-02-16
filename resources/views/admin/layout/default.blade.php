<!DOCTYPE html>
<html lang="en">
@include('admin.layout.head')
<body>
<a href="#main-content" class="skip-link">
    {{ t('Skip to main content') }}
</a>
<header>
    <nav class="header-admin navbar navbar-expand-md box-shadow">
        <a href="/"><img src="/images/page/biospex_logo.svg" alt="BIOSPEX"
                         class="my-0 mr-md-auto top-logo-admin font-weight-normal"/></a>
        @include('common.nav')
    </nav>
</header>
<main id="main-content" class="container mb-4">
    @include('common.notices')
    @yield('content')
    @include('common.wedigbio-progress-modal')
    @include('common.wedigbio-rate-modal')
    @include('common.process-modal')
    @include('common.ocr-main-modal')
    @include('common.modal')
</main>
@include('admin.layout.foot')
</body>
</html>