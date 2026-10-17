{{--
    Confirmation dialog for links and buttons marked data-confirm="confirmation" (opened by resources/js/confirm-dialog.js).
    The title and message are set with x-text, so they're always shown as text, never as HTML.
--}}
<dialog id="confirm-dialog" class="confirm-dialog" aria-labelledby="confirm-dialog-title" aria-describedby="confirm-dialog-message"
        x-data="{ title: '', message: '' }"
        x-on:confirm-dialog-open.window="title = $event.detail.title; message = $event.detail.message">
    <form method="dialog">
        <h2 id="confirm-dialog-title" class="confirm-dialog-title" x-show="title" x-text="title"></h2>
        <p id="confirm-dialog-message" class="confirm-dialog-message" x-text="message"></p>
        <div class="confirm-dialog-buttons">
            <button type="submit" value="cancel" class="btn btn-primary">
                <i class="fas fa-times-circle" aria-hidden="true"></i> {{ t('Cancel') }}
            </button>
            <button type="submit" value="confirm" class="btn btn-primary">
                <i class="fas fa-check-circle" aria-hidden="true"></i> {{ t('Confirm') }}
            </button>
        </div>
    </form>
</dialog>
