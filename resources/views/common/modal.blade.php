{{-- Global admin modal: its title, size and body come from the trigger (data-title, data-size, data-url); see resources/js/admin-modals.js. --}}
<x-modal id="global-modal" class="global-modal" :title="t('Dialog')" title-id="modal-title" size="" body-class="text-center" :fade="false"/>
