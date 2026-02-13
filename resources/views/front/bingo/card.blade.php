<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="{{ t('FSU Department of Biological Science') }}">
    <meta name="csrf-param" content="_token">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="google-site-verification" content="DRVQlYZQo5OkUlUhNG8Re-CgYEB7ELA0I_3qJJlzb0U"/>
    <title>
        {{ t('BIOSPEX | Bingo') }}
    </title>
    @include('common.favicon')
    <link href="https://fonts.googleapis.com/css?family=Raleway:400,700|Work+Sans:400,700" rel="stylesheet">
    <script src="https://kit.fontawesome.com/c840411e54.js" crossorigin="anonymous" SameSite="none Secure"></script>
    <link href="{{ mix('/css/front.css') }}" rel="stylesheet" type="text/css"/>
    @production
        @include('common.google-analytics')
    @endproduction
</head>
<body>
<div class="container">
    <canvas id="bingo-conffeti" class="collapse" style="z-index: -1; position:fixed; top:0;left:0"></canvas>
    <div class="row mb-0 mt-3">
        <h1 class="page-title m-auto">{{ $bingo->title }}</h1>
        <p class="text-center">{{ $bingo->directions }}</p>
    </div>
    <div class="row mb-0 mt-3">
        <div id="bingo-rows" class="bingo m-auto">

        </div>
    </div>
    <div id="bingodiv" class="d-flex" style="width:100%; height: 500px"></div>
    @include('front.bingo.partials.bingo-modal')
</div>
@include('common.php-vars-javascript')
<script src="{{ mix('/js/manifest.js') }}"></script>
<script src="{{ mix('/js/vendor.js') }}"></script>
<script src="{{ mix('/js/front.js') }}"></script>
@include('common.amchart')
<script src="{{ asset('js/amChartBingo.min.js')}}"></script>
<script>
    let bingoConfetti = new ConfettiGenerator({target: 'bingo-conffeti'});
    bingoConfetti.render();
</script>
</body>
</html>