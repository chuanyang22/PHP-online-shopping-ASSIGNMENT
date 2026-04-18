<?php
// footer.php
// $lang is already loaded by header.php — no need to load again
?>
    <footer class="site-footer">
        <h3>📍 <?= $lang['store_location'] ?></h3>
        <p><?= $lang['visit_us'] ?></p>
        <div class="flex-center mt-15">
            <iframe
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3984.0!2d101.5183!3d3.0738!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31cc4f5a94eff5e1%3A0x8a98d3d0a78cf6e8!2sShah%20Alam%2C%20Selangor!5e0!3m2!1sen!2smy!4v1"
                width="640" height="250"
                class="map-iframe"
                allowfullscreen="" loading="lazy"
                referrerpolicy="no-referrer-when-downgrade">
            </iframe>
        </div>
        <p class="mt-15"><?= $lang['footer_copy'] ?></p>
    </footer>

</body>
</html>