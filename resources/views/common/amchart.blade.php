{{-- amCharts 4 for pages that draw charts. Include it before the page's chart bundle; pass ['maps' => true] for map charts. --}}
<script src="//www.amcharts.com/lib/4/core.js"></script>
<script src="//www.amcharts.com/lib/4/charts.js"></script>
<script src="//www.amcharts.com/lib/4/themes/animated.js"></script>
@if($maps ?? false)
    <script src="//www.amcharts.com/lib/4/maps.js"></script>
    <script src="https://www.amcharts.com/lib/4/geodata/worldLow.js"></script>
    <script src="https://www.amcharts.com/lib/4/geodata/usaLow.js"></script>
@endif
