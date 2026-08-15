<header class="contact-header"><p class="eyebrow"><?= e(t('Direct line / Tehran')) ?></p><h1><?= e(t('Let’s begin')) ?><br><em><?= e(t('a conversation.')) ?></em></h1><p><?= e(t('For wholesale, distribution, appointments or collection questions, send a note to the studio.')) ?></p></header>
<section class="contact-layout">
    <aside class="contact-details">
        <p class="eyebrow"><?= e(t('Contact details')) ?></p>
        <dl><div><dt><?= e(t('Email')) ?></dt><dd><a href="mailto:<?= e($config['site']['email']) ?>"><?= e($config['site']['email']) ?></a></dd></div><div><dt><?= e(t('Telephone')) ?></dt><dd><a href="tel:<?= e($config['site']['phone_link']) ?>"><?= e($config['site']['phone_display']) ?></a></dd></div><div><dt><?= e(t('Order mobile / Telegram')) ?></dt><dd><a href="tel:<?= e($config['site']['order_mobile_link']) ?>"><?= e($config['site']['order_mobile_display']) ?></a></dd></div><div><dt><?= e(t('Studio')) ?></dt><dd><?= e($config['site']['address']) ?></dd></div></dl>
        <a class="instagram-panel" href="<?= e($config['site']['instagram']) ?>" target="_blank" rel="noopener"><span><?= e(t('Follow the working collection')) ?></span><b>@raspina.clothing ↗</b></a>
    </aside>
    <div class="form-panel">
        <p class="eyebrow"><?= e(t('Send a message')) ?></p><h2><?= e(t('Tell us what')) ?><br><?= e(t('you have in mind.')) ?></h2>
        <?php if ($form_flash): ?><div class="form-alert <?= $form_flash['ok'] ? 'is-success' : 'is-error' ?>" role="status"><?= e($form_flash['message']) ?></div><?php endif; ?>
        <form class="contact-form" method="post" action="<?= e(url('/contact')) ?>">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <label class="honey" aria-hidden="true">Website<input type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true"></label>
            <div class="field-row"><label><span><?= e(t('Name *')) ?></span><input name="name" required maxlength="100" autocomplete="name"></label><label><span><?= e(t('Email *')) ?></span><input name="email" type="email" required maxlength="190" autocomplete="email"></label></div>
            <div class="field-row"><label><span><?= e(t('Business')) ?></span><input name="business_name" maxlength="120" autocomplete="organization"></label><label><span><?= e(t('Telephone')) ?></span><input name="phone" type="tel" maxlength="60" autocomplete="tel"></label></div>
            <label><span><?= e(t('Your message *')) ?></span><textarea name="message" required minlength="10" maxlength="3000" rows="6" placeholder="<?= e(t('Collection, market, quantities or timing…')) ?>"></textarea></label>
            <button class="button button-dark" type="submit"><?= e(t('Send message ↗')) ?></button><p class="form-privacy"><?= e(t('By sending this form, you ask Raspina to respond to your enquiry. See our')) ?> <a href="<?= e(url('/privacy')) ?>"><?= e(t('privacy notice')) ?></a>.</p>
        </form>
    </div>
</section>
