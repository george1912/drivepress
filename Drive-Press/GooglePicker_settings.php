<?php




$saved = false;
if(isset($_POST['googlepicker_hidden']) && $_POST['googlepicker_hidden'] == 'Y') {
    $googlepicker_public = $_POST['googlepicker_public'];
    update_option('googlepicker_public', $googlepicker_public);
    $saved = true;
} else {
    $googlepicker_public = get_option('googlepicker_public');
}
?>

<?php if ($saved): ?>
<div class="updated"><p><strong><?php _e('Options saved.' ); ?></strong></p></div>
<?php endif; ?>

<div class="wrap">
    <?php echo "<h2>" . __( 'DrivePress', 'googlepicker_settings' ) . "</h2>"; ?>
    <form name="oscimp_form" method="post" action="<?php echo str_replace('%7E', '~', $_SERVER['REQUEST_URI']); ?>">
        <input type="hidden" name="googlepicker_hidden" value="Y">
        <h3>ClientID:<a href="https://console.developers.google.com">[?]</a></h3>
        <p>
            <?php _e('Client ID: '); ?>
            <input type="text" name="googlepicker_public" value="<?php echo $googlepicker_public; ?>" size="20">
            <?php _e('ex: 337216448469-lgra99340938eig4tcr30isgtsopl00j'); ?>
        </p>

        <p class="submit">
        <?php submit_button(); ?>
        </p>
    </form>
    <div>
    <ul>
        <li>You can get your own clientID <a href="https://console.developers.google.com">here</a>.</li>
    </ul>
    </div>
</div>
