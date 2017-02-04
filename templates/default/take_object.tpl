<form action="take_object.php" method="post" id="form_take_object">
    <input type="hidden" name="object_id" value="{$object->object_id}">
    <div class="bigtable">
        <fieldset class="cssform">
            <legend class="ui-state-active ui-corner-top">{_T string="Object"}</legend>
            <div>
                <p>

                    <img src="picture.php?object_id={$object->object_id}&amp;rand={$time}&amp;thumb=1"
                        class="picture fright"
                        width="{$object->picture->getOptimalThumbWidth()}"
                        height="{$object->picture->getOptimalThumbHeight()}"
                        alt="{_T string="Object's photo"}"/>
                    <span class="bline">{_T string="Name:"}</span>
                    {$object->name}
                </p>
            </div>
    {if $lendsprefs.VIEW_DESCRIPTION}
            <div>
                <p>
                    <span class="bline">{_T string="Description:"}</span>
                    {$object->description}
                </p>
            </div>
    {/if}
    {if $lendsprefs.VIEW_SERIAL}
            <div>
                <p>
                    <span class="bline">{_T string="Serial number:"}</span>
                    {$object->serial_number}
                </p>
            </div>
    {/if}
    {if $lendsprefs.VIEW_PRICE}
            <div>
                <p>
                    <span class="bline">{_T string="Price:"}</span>
                    {$object->price}
                </p>
            </div>
    {/if}
            <div>
                <p>
                    <span class="bline">{_T string="Borrow price (%currency):" pattern="/%currency/" replace=$object->currency}</span>
                    {if $login->isAdmin() || $login->isStaff()}
                        <input type="text" name="rent_price" id="rent_price" value="{$object->rent_price}" size="10" style="text-align: right">
                    {else}
                        <input type="hidden" name="rent_price" id="rent_price" value="{$object->rent_price}">
                        <span id="rent_price_label">{$object->rent_price}</span>
                    {/if}
                </p>
            </div>
    {if $lendsprefs.VIEW_DIMENSION}
            <div>
                <p>
                    <span class="bline">{_T string="Dimensions:"}</span>
                    {$object->dimension}
                </p>
            </div>
    {/if}
    {if $lendsprefs.VIEW_WEIGHT}
            <div>
                <p>
                    <span class="bline">{_T string="Weight:"}</span>
                    {$object->weight}
                </p>
            </div>
    {/if}
            {if $login->isAdmin() || $login->isStaff()}
                <div>
                    <p>
                        <span class="bline">{_T string="Member:"}</span>
                        <select name="id_adh" id="id_adh">
                            <option value="null">{_T string="--- Select a member ---"}</option>
                            {foreach from=$members item=mmbr}
                                <option value="{$mmbr->id_adh}"{if $login->id eq $mmbr->id_adh} selected="selected"{/if}>{$mmbr->nom_adh} {$mmbr->prenom_adh}{if $mmbr->pseudo_adh != ''} ({$mmbr->pseudo_adh}){/if}</option>
                            {/foreach}
                        </select>
                    </p>
                </div>
            {/if}
            <div>
                <p>
                    <span class="bline">{_T string="Status:"}</span>
                    <select name="status" id="status">
                        <option value="null">{_T string="--- Select a status ---"}</option>
                        {foreach from=$statuses item=sta}
                            <option value="{$sta->status_id}/{$sta->rent_day_number}">
                                {$sta->status_text}
                                {if $sta->rent_day_number ne ''}
                                    ({_T string="%days days" pattern="/%days/" replace=$sta->rent_day_number})
                                {/if}
                            </option>
                        {/foreach}
                    </select>
                </p>
            </div>
            <div>
                <p>
                    <span class="bline">{_T string="Expected return:"}</span>
                    <input type="text" id="expected_return" name="expected_return" size="8">
                </p>
            </div>
            {if $lendsprefs.AUTO_GENERATE_CONTRIBUTION}
                <div>
                    <p>
                        <span class="bline">{_T string="TAKE OBJECT.PAYMENT TYPE"}</span>
                        <select name="payment_type" id="payment_type">
                            <option value="null">{_T string="TAKE OBJECT.SELECT PAYMENT TYPE"}</option>
                            <option value="{php}echo Galette\Entity\Contribution::PAYMENT_CASH;{/php}">{_T string="Cash"}</option>
                            <option value="{php}echo Galette\Entity\Contribution::PAYMENT_CREDITCARD;{/php}">{_T string="Credit card"}</option>
                            <option value="{php}echo Galette\Entity\Contribution::PAYMENT_CHECK;{/php}">{_T string="Check"}</option>
                            <option value="{php}echo Galette\Entity\Contribution::PAYMENT_TRANSFER;{/php}">{_T string="Transfer"}</option>
                            <option value="{php}echo Galette\Entity\Contribution::PAYMENT_PAYPAL;{/php}">{_T string="Paypal"}</option>
                            <option value="{php}echo Galette\Entity\Contribution::PAYMENT_OTHER;{/php}">{_T string="Other"}</option>
                        </select>
                    </p>
                </div>
            {/if}
        </fieldset>
    </div>
    <div class="disclaimer">
        {_T string="The items offered for rent are in good condition and verification rental contradictory to their status is at the time of withdrawal. No claims will be accepted after the release of the object. Writing by the store a list of reservation does not exempt the customer checking his retrait. The payment of rent entitles the purchaser to make normal use of the loaned object. If the object is rendered in a degraded state, the seller reserves the right to collect all or part of the security deposit. In case of deterioration of the rented beyond the standard object, a financial contribution will be required for additional cleaning caused. In case of damage, loss or theft of the rented property, the deposit will not be refunded automatically to 'the company as damages pursuant to Article 1152 of the Civil Code and without that it need for any other judicial or extra-judicial formality. In some other cases not listed above and at the discretion of the seller, the deposit check may also be collected in whole or party."}
    </div>
    <div class="button-container" id="button_container">
        <input type="submit" id="btnsave" name="yes" value="{_T string="Take away"}">
        <a href="objects_list.php" class="button" id="btncancel">{_T string="Cancel"}</a>
    </div>
</form>
<script>
    var _init_takeobject_js = function() {
        $('#btnsave').button('disable');
        $('#expected_return').datepicker({
            changeMonth: true,
            changeYear: true,
            showOn: 'both',
            buttonImage: '{$template_subdir}images/calendar.png',
            buttonImageOnly: true,
            minDate: 0,
            selectOtherMonths: true,
            showOtherMonths: false,
            showWeek: true,
        });

        $('#id_adh, #status, #payment_type').on('change',function() {
            validStatus()
        });
    }

    {if not $ajax}
    $(function () {
        _init_takeobject_js();
    });
    {/if}

    function completeZero(n) {
        return n < 10 ? '0' + n : n;
    }

    function validStatus() {
        var _disabled = false;
        var visibility = 'visible';
        if ($('#status').val() === 'null') {
            _disabled = true;
        }
        if ($('#id_adh').val() === 'null') {
            _disabled = true;
        }
        if ($('#payment_type').val() === 'null') {
            _disabled = true;
        }

        var _lyes = $('#btnsave');
        if (_disabled) {
            _lyes.button('disable');
        } else {
            _lyes.button('enable');
        }

        var id_days = $('#status').val();
        if (id_days === 'null') {
            return;
        }

        var nb_days = id_days.split('/');
        if (nb_days[1].length === 0) {
            var text = "{$object->rent_price}";
            $('#rent_price').val(text);
            $('#rent_price_label').html(text);
            return;
        }

        var tomorrow = new Date({$year}, {$month} - 1, {$day} + parseInt(nb_days[1]));
        $('#expected_return').val(completeZero(tomorrow.getDate()) + '/' + completeZero(tomorrow.getMonth() + 1) + '/' + tomorrow.getFullYear());

        if ('1' === '{$object->price_per_day}') {
            var price_per_day = {$rent_price} * parseInt(nb_days[1]);
            var text = price_per_day.toFixed(2).replace(".", ",");
            $('#rent_price').val(text);
            $('#rent_price_label').html(text);
        }
    }
</script>
