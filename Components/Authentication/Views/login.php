<?php

defined("EPSILON_EXEC") or die();

use Epsilon\Factory;

/** @var \Epsilon\MVC\View $this */

$eLanguage = Factory::getLanguage();
$eRouter   = Factory::getRouter();

?>
<div style="margin-top: 50px;"></div>
<div class="row align-center">
    <div class="medium-6 large-5 column">

        <div class="callout">
            <h3 class="text-center"><?php echo $eLanguage->_("COM_LOGIN-TITLE") ?></h3>
            <form action="<?php echo $eRouter->getURL("Authentication/Authenticate") ?>" method="post">
                <div class="row">
                    <div class="column">
                        <label for="fEmail"><?php echo $eLanguage->_("COM_LOGIN-EMAIL"), ' / ', $eLanguage->_("COM_LOGIN-USERNAME") ?></label>
                        <input name="Login[Email]" id="fEmail" type="text" value="" required>
                    </div>
                </div>
                <div class="row">
                    <div class="column">
                        <label for="fPassword"><?php echo $eLanguage->_("COM_LOGIN-PASSWORD") ?></label>
                        <input name="Login[Password]" id="fPassword" type="password" required value="">
                    </div>
                </div>
                <div class="row align-center">
                    <div class="shrink column">
                        <button class="button" type="submit">Login</button>
                    </div>
                    <div class="shrink column">
                        <a class="button secondary" href="<?php echo $eRouter->getURL(); ?>">Cancel</a>
                    </div>
                </div>
            </form>
        </div>

    </div>
</div>