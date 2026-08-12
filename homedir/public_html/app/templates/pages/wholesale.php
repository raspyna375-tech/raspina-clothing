<header class="wholesale-hero"><div><p class="eyebrow">Wholesale / International</p><h1>Your market.<br><em>Our collection.</em></h1><p>A direct enquiry process for boutique owners, distributors and apparel buyers.</p></div><div class="wholesale-hero-image"><img src="<?= e(asset('images/editorial/shop_04.webp')) ?>" alt="Raspina Clothing wholesale collection" width="800" height="1050"><span>Enquiries / 2026</span></div></header>

<section class="wholesale-process section"><div class="section-heading"><p class="eyebrow">A clear process</p><h2>From edit to quotation</h2></div><ol class="wholesale-steps"><li><span>01</span><h3>Build your shortlist</h3><p>Save products as you browse. Your list stays privately in this browser until you send it.</p><a href="<?= e(url('/shop')) ?>">Explore the archive ↗</a></li><li><span>02</span><h3>Share the context</h3><p>Tell us your country, business, preferred quantities, sizing and timing.</p></li><li><span>03</span><h3>Receive a current quote</h3><p>We confirm availability, wholesale pricing, production options and delivery details.</p></li></ol></section>

<section class="wholesale-form-section" id="enquiry">
    <div class="wholesale-form-intro"><p class="eyebrow">Start the enquiry</p><h2>Tell us about<br>your business.</h2><p>No payment is taken here. This form starts a direct wholesale conversation with the Raspina team.</p><div class="shortlist-summary"><span>Inquiry shortlist</span><b><span data-shortlist-count>0</span> saved pieces</b><a href="<?= e(url('/wishlist')) ?>">Review list ↗</a></div></div>
    <div class="form-panel form-panel-dark">
        <?php if ($requested_product): ?><div class="requested-product"><span>Starting with</span><strong><?= e($requested_product['name']) ?></strong><small>SKU <?= e($requested_product['sku']) ?></small></div><?php endif; ?>
        <?php if ($form_flash): ?><div class="form-alert <?= $form_flash['ok'] ? 'is-success' : 'is-error' ?>" role="status"><?= e($form_flash['message']) ?></div><?php endif; ?>
        <form class="contact-form" method="post" action="<?= e(url('/wholesale')) ?>" data-inquiry-form>
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="product_slugs" value="<?= e($requested_product ? $requested_product['slug'] : '') ?>" data-product-slugs>
            <label class="honey" aria-hidden="true">Website<input type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true"></label>
            <div class="field-row"><label><span>Name *</span><input name="name" required maxlength="100" autocomplete="name"></label><label><span>Business name</span><input name="business_name" maxlength="120" autocomplete="organization"></label></div>
            <div class="field-row"><label><span>Business email *</span><input name="email" type="email" required maxlength="190" autocomplete="email"></label><label><span>Phone / WhatsApp</span><input name="phone" type="tel" maxlength="60" autocomplete="tel"></label></div>
            <label><span>Country / market</span><input name="country" maxlength="100" autocomplete="country-name"></label>
            <label><span>What do you need? *</span><textarea name="message" required minlength="10" maxlength="3000" rows="7" placeholder="Tell us about quantities, categories, sizes, timing and destination…"></textarea></label>
            <button class="button button-light" type="submit">Send wholesale request ↗</button><p class="form-privacy">We use these details only to respond to this enquiry. See <a href="<?= e(url('/privacy')) ?>">privacy</a>.</p>
        </form>
    </div>
</section>
