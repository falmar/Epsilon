<?php

defined("EPSILON_EXEC") or die();

use Epsilon\Factory;
use Epsilon\User\SystemMessage;

?>
<!doctype html>
<html class="no-js" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?php echo Factory::getDocument()->get('SubTitle'); ?> - <?php echo Factory::getDocument()->get('Title'); ?></title>
</head>
<body>

<div class="row">
    <div class="large-12 column">
        <?php if ($this->getVar('SystemMessages')) { ?>
            <br>
            <?php
            /** @var SystemMessage $Message */
            foreach ($this->getVar('SystemMessages') as $Message) {
                ?>
                <div class="alert callout <?php echo $Message->get("Type") ?>" data-closable>
                    <?php echo $Message->get("Message") ?>
                    <?php $Message->setViewed(1) ?>
                    <button class="close-button" aria-label="Dismiss alert" type="button" data-close>
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php } ?>
        <?php } ?>
    </div>
</div>

<div class="row">
    <div class="large-12 column">
        <?php

        if (Factory::getDocument()->countByPosition("Component")) {
            foreach (Factory::getDocument()->getByPosition("Component") as $Content) {
                echo $Content;
            }
        }

        ?>
    </div>
</div>

<?php
foreach (Factory::getDocument()->get("StyleSheets") as $CSS) {
    echo "<link rel='stylesheet' href='$CSS' />", PHP_EOL;
}

foreach (Factory::getDocument()->get("JavaScripts") as $JS) {
    echo "<script src='$JS' ></script>", PHP_EOL;
}
?>

<script type="text/javascript">
    function getURL(URL) {
        return "<?php echo Factory::getRouter()->getURL()?>" + URL;
    }
</script>

</body>
</html>
