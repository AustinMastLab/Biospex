<!-- Footer -->
<footer id="footer" class="page-footer font-small blue-grey lighten-5">
    <!-- Copyright -->
    <div class="text-center py-3" style="color: #e1e1e1;">{{ t('© 2014–%s Copyright', \Carbon\Carbon::now()->year) }}
        <a href="https://www.bio.fsu.edu/"> {{ t('FSU Department of Biological Science') }}</a>
    </div>
    <!-- Copyright -->
</footer>
@include('common.php-vars-javascript')
<script src="{{ mix('/js/manifest.js') }}"></script>
<script src="{{ mix('/js/vendor.js') }}"></script>
<script src="{{ mix('/js/admin.js') }}"></script>
@include('common.amchart')
@livewireScripts
@stack('scripts')