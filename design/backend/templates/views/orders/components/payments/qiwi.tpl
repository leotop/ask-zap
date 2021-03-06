{script src="js/lib/maskedinput/jquery.maskedinput.min.js"}

{script src="js/lib/inputmask/jquery.inputmask.min.js"}
{script src="js/lib/jquery-bind-first/jquery.bind-first-0.2.3.js"}
{script src="js/lib/inputmask-multi/jquery.inputmask-multi.js"}

<script type="text/javascript">
    (function(_, $) {
        _.qiwi_phone_masks_list = {$qiwi_phone_mask_codes nofilter};
        {if $addons.qiwi_rest.phone_mask}
        _.qiwi_phone_mask = '{$addons.rus_payments.phone_mask}'
        {/if}
    }(Tygh, Tygh.$));
</script>

{script src="js/addons/rus_payments/input_mask.js"}

<div class="control-group">
    <label for="qiwi_phone_number" class="control-label cm-required">{__("phone")}</label>
    <div class="controls">
        <input id="qiwi_phone_number" size="35" type="text" name="payment_info[phone]" value="{$phone_normalize}" class="input-big cm-mask" />
    </div>
</div>


