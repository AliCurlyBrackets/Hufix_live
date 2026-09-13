<div class="search-popup" id="searchPopup">
    <div class="popup-content">
        <button class="close-btn" id="closePopup">&times;</button>
        <?php $cs_is_ar = (strpos($_SERVER['REQUEST_URI'], '/ar') !== false); ?>
        <h3><?php echo $cs_is_ar ? 'بحث' : 'Search'; ?></h3>
        <form method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
            <input
                type="text"
                name="s"
                placeholder="<?php echo esc_attr( $cs_is_ar ? 'بحث...' : 'Search...' ); ?>"
                value="<?php echo esc_attr( get_search_query() ); ?>"
                autofocus>
            <button type="submit">
                <img src="<?php echo esc_url( get_stylesheet_directory_uri() ); ?>/assets/images/search-alt-2-svgrepo-com.png">
            </button>
        </form>
    </div>
</div>


<div class="training-popup" id="trainingPopup">
    <div class="popup-box">
        <button class="close-popup">&times;</button>
        <?php $cs_is_ar = (strpos($_SERVER['REQUEST_URI'], '/ar') !== false); ?>
        <h2><?php echo $cs_is_ar ? 'طلب تدريب مخصص' : 'Request Custom Training'; ?></h2>
        <form>
            <div class="form-row">
                <div class="form-group">
                    <label><?php echo $cs_is_ar ? 'الاسم بالكامل' : 'Full Name'; ?></label>
                    <input type="text" placeholder="<?php echo esc_attr( $cs_is_ar ? 'محمد أحمد' : 'John Doe' ); ?>">
                </div>
                <div class="form-group">
                    <label><?php echo $cs_is_ar ? 'البريد الإلكتروني' : 'Email Address'; ?></label>
                    <input type="email" placeholder="<?php echo esc_attr( $cs_is_ar ? 'name@company.com' : 'john@company.com' ); ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label><?php echo $cs_is_ar ? 'اسم الدورة' : 'Course Name'; ?></label>
                    <input type="text" placeholder="<?php echo esc_attr( $cs_is_ar ? 'اسم الدورة' : 'Course Name' ); ?>">
                </div>
                <div class="form-group">
                    <label><?php echo $cs_is_ar ? 'الدولة' : 'Country'; ?></label>
                    <input type="text" placeholder="<?php echo esc_attr( $cs_is_ar ? 'الدولة' : 'Country' ); ?>">
                </div>
            </div>
            <div class="form-group">
                <label><?php echo $cs_is_ar ? 'رقم الهاتف' : 'Phone Number'; ?></label>
                <input
                    type="tel"
                    class="numbers-only"
                    placeholder="966xxxxxxxxx"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    autocomplete="tel">
            </div>
            <div class="form-group">
                <label><?php echo $cs_is_ar ? 'الشركة' : 'Company'; ?></label>
                <input type="text" placeholder="<?php echo esc_attr( $cs_is_ar ? 'اسم الشركة' : 'Company Name' ); ?>">
            </div>
            <div class="form-group">
                <label><?php echo $cs_is_ar ? 'الرسالة / الاستفسار' : 'Message / Inquiry'; ?></label>
                <textarea rows="5" placeholder="<?php echo esc_attr( $cs_is_ar ? 'إزاي نقدر نساعدك؟' : 'How can we help you?' ); ?>"></textarea>
            </div>
            <button type="submit" class="submit-btn">
                <?php echo $cs_is_ar ? 'إرسال الرسالة' : 'Send Message'; ?>
            </button>
        </form>
    </div>
</div>
<!-- Overlay -->
<div class="overlay" id="overlay"></div>

<!-- Side Menu -->
<div class="side-menu" id="sideMenu">

    <div class="side-menu-top">
        <a href="index.html">
            <img src="<?php echo get_template_directory_uri() . '/assets/images/header/Mask_group.png'  ?>" alt="Logo">
        </a>
        <button class="close-menu" id="closeMenu">&#x2715;</button>
    </div>

    <nav class="side-menu-nav">
        <nav class="side-menu-nav">
            <?php
            wp_nav_menu(array(
                'theme_location' => 'main-menu',
                'container'      => false,
                'menu_class'     => '',
                'fallback_cb'    => false,
            ));
            ?>
        </nav>
    </nav>

    <div class="side-menu-search">
        <form class="side-menu-search"
            method="get"
            action="<?php echo esc_url(home_url('/')); ?>">

            <img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/images/header/search.png">

            <input
                type="text"
                name="s"
                placeholder="Search..."
                value="<?php echo get_search_query(); ?>">

        </form>
    </div>

</div>

<?php

/**
 * Frontend: Footer (كامل)
 * -----------------------------------------------------
 * ضيف الكود ده في footer.php بدل الجزء الحالي بتاع الفوتر
 * (من ***** Footer ***** لحد نهاية </footer>)
 *
 * الفرق عن اللي عندك: كارت الـ CTA بقى بيتشيك الأول لو
 * الصفحة الحالية عاملة "Override Global CTA" (من صفحة
 * التحرير بتاعتها)، ولو لأ بياخد من "Footer Setting" العامة
 * زي ما هو حاصل دلوقتي بالظبط. باقي الفوتر زي ما هو.
 * -----------------------------------------------------
 */

// ----- CTA: يا إما من الصفحة (Override) يا إما من الإعدادات العامة -----
$cta_icon = get_field('footer_cta_icon', 'option'); // الأيقونة دايماً عامة

$page_override = is_singular('page') && get_field('page_cta_override');

if ($page_override) {
    $cta_title   = get_field('page_cta_title');
    $cta_text    = get_field('page_cta_text');
    $cta_buttons = get_field('page_cta_buttons'); // array عادية
} else {
    $cta_title   = get_field('footer_cta_title', 'option');
    $cta_text    = get_field('footer_cta_text', 'option');
    $cta_buttons = get_field('footer_cta_buttons', 'option');
}

// ----- باقي بيانات الفوتر: زي ما هي، دايماً عامة -----
$col1_title  = get_field('footer_col1_title', 'option');
$col2_title  = get_field('footer_col2_title', 'option');

$contact_title   = get_field('footer_contact_title', 'option');
$footer_address  = get_field('footer_address', 'option');
$footer_email    = get_field('footer_email', 'option');
$footer_phone    = get_field('footer_phone', 'option');
$whatsapp_on     = get_field('footer_whatsapp_enabled', 'option');
$whatsapp_link   = get_field('footer_whatsapp_link', 'option');

$payment_title   = get_field('footer_payment_title', 'option');
$copyright_text  = get_field('footer_copyright', 'option');
?>


<!-- ===== CTA + Footer ===== -->
<footer class="footer">

    <!-- CTA Card -->
    <div class="cta_wrapper">
        <div class="cta_card">
            <div class="cta_top">
                <?php if ($cta_icon) : ?>
                    <div class="cta_icon">
                        <img src="<?php echo esc_url($cta_icon); ?>" alt="Mail">
                    </div>
                <?php endif; ?>
                <h3><?php echo esc_html($cta_title); ?></h3>
            </div>
            <p><?php echo esc_html($cta_text); ?></p>

            <?php if (! empty($cta_buttons)) : ?>
                <div class="cta_buttons">
                    <?php foreach ($cta_buttons as $btn) :
                        $btn_text  = $btn['text']  ?? '';
                        $btn_style = $btn['style'] ?? 'outline';
                        $btn_class = 'cta_btn ' . ($btn_style === 'filled' ? 'cta_btn--filled' : 'cta_btn--outline');

                        // 'action' مش موجود في الأزرار العامة القديمة -> بيتعامل زي link تلقائي
                        $action = $btn['action'] ?? 'link';
                    ?>

                        <?php if ($action === 'popup') :
                            $popup_key = $btn['popup'] ?? '';
                        ?>
                            <a href="#"
                                class="<?php echo esc_attr($btn_class); ?>"
                                data-popup="<?php echo esc_attr($popup_key); ?>">
                                <?php echo esc_html($btn_text); ?>
                            </a>

                        <?php else :
                            $btn_link = $btn['link'] ?? '#';
                            $btn_id   = $btn['element_id'] ?? ''; // توافق مع أزرار الأوبشنز القديمة
                        ?>
                            <a href="<?php echo $btn_link ? esc_url($btn_link) : '#'; ?>"
                                class="<?php echo esc_attr($btn_class); ?>"
                                <?php if ($btn_id) : ?>id="<?php echo esc_attr($btn_id); ?>" <?php endif; ?>
                                <?php if ($btn_link && preg_match('/\.(pdf|docx?|zip)$/i', $btn_link)) : ?>download<?php endif; ?>>
                                <?php echo esc_html($btn_text); ?>
                            </a>
                        <?php endif; ?>

                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer Links -->
    <div class="footer_main">
        <div class="footer_container">

            <!-- Col 1: Footer 1 Menu -->
            <div class="footer_col">
                <h4><?php echo esc_html($col1_title); ?></h4>
                <?php
                wp_nav_menu(array(
                    'theme_location' => 'footer-menu-1',
                    'container'      => false,
                    'items_wrap'     => '<ul>%3$s</ul>',
                    'fallback_cb'    => false,
                ));
                ?>
            </div>

            <!-- Col 2: Footer 2 Menu -->
            <div class="footer_col">
                <h4><?php echo esc_html($col2_title); ?></h4>
                <?php
                wp_nav_menu(array(
                    'theme_location' => 'footer-menu-2',
                    'container'      => false,
                    'items_wrap'     => '<ul>%3$s</ul>',
                    'fallback_cb'    => false,
                ));
                ?>
            </div>

            <!-- Col 3: Contact -->
            <div class="footer_col">
                <h4><?php echo esc_html($contact_title); ?></h4>
                <ul class="footer_contact">
                    <?php if ($footer_address) : ?>
                        <li>
                            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/footer/SVG.png" alt="Location">
                            <span><?php echo esc_html($footer_address); ?></span>
                        </li>
                    <?php endif; ?>
                    <?php if ($footer_email) : ?>
                        <li>
                            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/footer/SVG (1).png" alt="Email">
                            <span><?php echo esc_html($footer_email); ?></span>
                        </li>
                    <?php endif; ?>
                    <?php if ($footer_phone) : ?>
                        <li>
                            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/footer/SVG (3).png" alt="Phone">
                            <span><?php echo esc_html($footer_phone); ?></span>
                        </li>
                    <?php endif; ?>
                </ul>

                <?php if ($whatsapp_on && $whatsapp_link) : ?>
                    <a href="<?php echo esc_url($whatsapp_link); ?>" class="whatsapp_btn">
                        <img src="<?php echo get_template_directory_uri(); ?>/assets/images/footer/SVG (2).png" alt="WhatsApp">
                        WhatsApp Support
                    </a>
                <?php endif; ?>
            </div>

            <!-- Col 4: Payment -->
            <div class="footer_col">
                <h4><?php echo esc_html($payment_title); ?></h4>
                <?php if (have_rows('footer_payment_badges', 'option')) : ?>
                    <div class="payment_grid">
                        <?php while (have_rows('footer_payment_badges', 'option')) : the_row(); ?>
                            <span class="payment_badge"><?php echo esc_html(get_sub_field('text')); ?></span>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- Footer Bottom -->
    <div class="footer_bottom">
        <div class="footer_container footer_bottom_inner">

            <p class="footer_copy"><?php echo esc_html($copyright_text); ?></p>

            <?php if (have_rows('footer_socials', 'option')) : ?>
                <div class="footer_socials">
                    <?php while (have_rows('footer_socials', 'option')) : the_row();
                        $s_icon = get_sub_field('icon');
                        $s_link = get_sub_field('link');
                        if (! $s_icon) continue;
                    ?>
                        <a href="<?php echo $s_link ? esc_url($s_link) : '#'; ?>">
                            <img src="<?php echo esc_url($s_icon); ?>" alt="">
                        </a>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>

</footer>


<style>
    .contact_icon {
        background-color: rgb(66, 219, 135);
        color: rgb(255, 255, 255);
        width: 60px;
        height: 60px;
        font-size: 30px;
        text-align: center;
        display: flex;
        align-items: center;
        justify-content: center;
        transform: translateY(0px);
        box-shadow: rgb(66, 219, 135) 0px 0px 0px 0px;
        font-weight: normal;
        font-family: sans-serif;
        border-radius: 50px;
        animation: 1.25s cubic-bezier(0.66, 0, 0, 1) 0s infinite normal none running pulsing;
        transition: 300ms ease-in-out;
        text-decoration: none !important;
    }


    .floating_btn {
        position: fixed;
        bottom: 20px;
        right: 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        margin-inline-start: -200px;
        opacity: 0;
        transition: 0.3s;
    }


    .floating_btn.fly-icon {
        margin-inline-start: 0px;
        opacity: 1;
    }

    .icon_whatssapp {
        position: fixed;
        bottom: 50px;
        right: 50px;
    }

    @keyframes pulsing {
        100% {
            box-shadow: rgba(232, 76, 61, 0) 0px 0px 0px 30px;
        }

    }
</style>

<a class="icon_whatssapp" target="_blank" href="https://wa.me/" aria-label="Chat with us on WhatsApp" rel="noopener noreferrer">
    <div class="contact_icon">
        <img src="https://hadarah-center.com/wp-content/uploads/2026/04/whatsapp-svgrepo-com.png" width="30" />
    </div>
</a>


<?php
/**
 * البوب أبات (Popups) الأربعة اللي محتاجهم أزرار الـ CTA
 * -----------------------------------------------------
 * ضيف الكود ده في footer.php بدل الـ <div class="training-popup" id="trainingPopup">
 * الحالي (استبدله بالكامل بالكتلة دي اللي فيها الأربع بوب أبات).
 *
 * ازاي بيشتغل الربط؟
 * الاختيار اللي بتحدده في "Which Popup?" في ACF (Book a Service / Request Training /
 * Request HR Consultation / Request Free Consultation) بيحفظ قيمة زي:
 *   service | training | hr | consultation
 * وده هو نفسه اللي بيتكتب تلقائي في data-popup="..." على الزرار (شوف كود الفوتر
 * اللي عندك بالفعل: data-popup="<?php echo esc_attr($popup_key); ?>").
 *
 * السكريبت "GENERIC POPUP OPENER" اللي موجود عندك بالفعل في آخر footer.php
 * بيدور على عنصر عليه data-popup-id="نفس القيمة" ويفتحه. يعني كل اللي محتاجه
 * إنك تحط على كل div بوب أب data-popup-id يطابق قيمة الـ select. بس كده.
 * -----------------------------------------------------
 */
?>

<?php $is_ar = (strpos($_SERVER['REQUEST_URI'], '/ar') !== false); ?>

<!-- ================= Popup: Book a Service ================= -->
<div class="training-popup" data-popup-id="service">
    <div class="popup-box">
        <button class="close-popup" aria-label="Close">&times;</button>
        <h2><?php echo $is_ar ? 'حجز خدمة' : 'Book a Service'; ?></h2>
        

        <form class="site-popup-form" data-form-type="service">
            <div class="form-row">
                <div class="form-group">
                    <label><?php echo $is_ar ? 'الاسم الكامل' : 'Full Name'; ?> <span class="req">*</span></label>
                    <input type="text" name="full_name" placeholder="<?php echo $is_ar ? 'جون دو' : 'John Doe'; ?>" required>
                </div>
                <div class="form-group">
                    <label><?php echo $is_ar ? 'اسم الشركة' : 'Company Name'; ?></label>
                    <input type="text" name="company_name" placeholder="<?php echo $is_ar ? 'اسم الشركة' : 'Company Name'; ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label><?php echo $is_ar ? 'المسمى الوظيفي' : 'Job Title'; ?></label>
                    <input type="text" name="job_title" placeholder="<?php echo $is_ar ? 'المسمى الوظيفي' : 'Job Title'; ?>">
                </div>
                <div class="form-group">
                    <label><?php echo $is_ar ? 'البريد الإلكتروني' : 'Email Address'; ?> <span class="req">*</span></label>
                    <input type="email" name="email" placeholder="john@company.com" required>
                </div>
            </div>

            <div class="form-rows">
                <div class="form-group">
                    <label><?php echo $is_ar ? 'رقم الهاتف' : 'Phone Number'; ?></label>
                    <input type="tel" class="numbers-only" name="phone" placeholder="966xxxxxxxxx"
                        inputmode="numeric" pattern="[0-9]*" autocomplete="tel">
                </div>
                <!--<div class="form-group">-->
                <!--    <label>Preferred Contact Method</label>-->
                <!--    <select name="preferred_contact">-->
                <!--        <option value="">-- Select --</option>-->
                <!--        <option value="email">Email</option>-->
                <!--        <option value="phone">Phone</option>-->
                <!--        <option value="video">Video Call</option>-->
                <!--    </select>-->
                <!--</div>-->
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label><?php echo $is_ar ? 'الدولة' : 'Country'; ?></label>
                    <input type="text" name="country" placeholder="<?php echo $is_ar ? 'الدولة' : 'Country'; ?>">
                </div>
                <div class="form-group">
                    <label><?php echo $is_ar ? 'الخدمة المطلوبة' : 'Service Required'; ?> <span class="req">*</span></label>
                    <select name="service_required" required>
                        <option value=""><?php echo $is_ar ? '-- اختر خدمة --' : '-- Select a Service --'; ?></option>
                        <!-- عدّل القائمة دي بالخدمات الحقيقية عندك -->
                        <option value="business_consulting"><?php echo $is_ar ? 'الاستشارات الإدارية' : 'Business Consulting'; ?></option>
                        <option value="hr_infrastructure"><?php echo $is_ar ? 'البنية التحتية للموارد البشرية' : 'HR Infrastructure'; ?></option>
                        <option value="hr_outsourcing"><?php echo $is_ar ? 'الاستعانة بمصادر خارجية للموارد البشرية والاستشارات' : 'HR Outsourcing & Advisory'; ?></option>
                        <option value="recruitment"><?php echo $is_ar ? 'التوظيف النخبوي' : 'Elite Recruitment'; ?></option>
                        <option value="training"><?php echo $is_ar ? 'التدريب' : 'Training'; ?></option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label><?php echo $is_ar ? 'أخبرنا عن احتياجاتك' : 'Tell Us About Your Needs'; ?></label>
                <textarea rows="4" name="message" placeholder="<?php echo $is_ar ? 'كيف يمكننا مساعدتك؟' : 'How can we help you?'; ?>"></textarea>
            </div>

            <p class="popup-privacy-note">
                <?php echo $is_ar
                    ? 'سيتم استخدام بياناتك فقط للرد على طلبك، ويتم التعامل معها بأمان وفقًا لسياسة الخصوصية الخاصة بنا.'
                    : 'Your information will only be used to respond to your request and handled securely in accordance with our Privacy Policy.'; ?>
            </p>

            <button type="submit" class="submit-btn"><?php echo $is_ar ? 'إرسال الطلب' : 'Send Request'; ?></button>
        </form>
    </div>
</div>


<!-- ================= Popup: Request Training ================= -->
<div class="training-popup" id="trainingPopup" data-popup-id="training">
    <div class="popup-box">
        <button class="close-popup" aria-label="Close">&times;</button>
        <h2><?php echo $is_ar ? 'اطلب تدريب' : 'Request Training'; ?></h2>
        

        <form class="site-popup-form" data-form-type="training">
            <div class="form-row">
                <div class="form-group">
                    <label><?php echo $is_ar ? 'الاسم الكامل' : 'Full Name'; ?> <span class="req">*</span></label>
                    <input type="text" name="full_name" placeholder="<?php echo $is_ar ? 'جون دو' : 'John Doe'; ?>" required>
                </div>
                <div class="form-group">
                    <label><?php echo $is_ar ? 'اسم الشركة' : 'Company Name'; ?></label>
                    <input type="text" name="company_name" placeholder="<?php echo $is_ar ? 'اسم الشركة' : 'Company Name'; ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label><?php echo $is_ar ? 'المسمى الوظيفي' : 'Job Title'; ?></label>
                    <input type="text" name="job_title" placeholder="<?php echo $is_ar ? 'المسمى الوظيفي' : 'Job Title'; ?>">
                </div>
                <div class="form-group">
                    <label><?php echo $is_ar ? 'البريد الإلكتروني' : 'Email Address'; ?> <span class="req">*</span></label>
                    <input type="email" name="email" placeholder="john@company.com" required>
                </div>
            </div>

            <div class="form-rows">
                <div class="form-group">
                    <label><?php echo $is_ar ? 'رقم الهاتف' : 'Phone Number'; ?></label>
                    <input type="tel" class="numbers-only" name="phone" placeholder="966xxxxxxxxx"
                        inputmode="numeric" pattern="[0-9]*" autocomplete="tel">
                </div>
                <div class="form-group">
                    <label><?php echo $is_ar ? 'الدولة' : 'Country'; ?></label>
                    <input type="text" name="country" placeholder="<?php echo $is_ar ? 'الدولة' : 'Country'; ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label><?php echo $is_ar ? 'اسم الدورة' : 'Course Name'; ?></label>
                    <input type="text" name="course_name" placeholder="<?php echo $is_ar ? 'اسم الدورة' : 'Course Name'; ?>">
                </div>
                <div class="form-group">
                    <label><?php echo $is_ar ? 'عدد المشاركين' : 'Number of Participants'; ?></label>
                    <input type="number" min="1" name="participants" placeholder="<?php echo $is_ar ? 'مثال: 10' : 'e.g. 10'; ?>">
                </div>
            </div>

            <div class="form-group">
                <label><?php echo $is_ar ? 'الموعد المفضل للبدء' : 'Preferred Starting Time'; ?></label>
                <input type="date" name="preferred_start">
            </div>

            <div class="form-group">
                <label><?php echo $is_ar ? 'أخبرنا عن احتياجاتك التدريبية' : 'Tell Us About Your Training Needs'; ?></label>
                <textarea rows="4" name="message" placeholder="<?php echo $is_ar ? 'كيف يمكننا مساعدتك؟' : 'How can we help you?'; ?>"></textarea>
            </div>

            <p class="popup-privacy-note">
                <?php echo $is_ar
                    ? 'سيتم استخدام بياناتك فقط للرد على طلبك، ويتم التعامل معها بأمان وفقًا لسياسة الخصوصية الخاصة بنا.'
                    : 'Your information will only be used to respond to your request and handled securely in accordance with our Privacy Policy.'; ?>
            </p>

            <button type="submit" class="submit-btn"><?php echo $is_ar ? 'إرسال الطلب' : 'Send Request'; ?></button>
        </form>
    </div>
</div>


<!-- ================= Popup: Request HR Consultation ================= -->
<div class="training-popup" data-popup-id="hr">
    <div class="popup-box">
        <button class="close-popup" aria-label="Close">&times;</button>
        <h2><?php echo $is_ar ? 'اطلب استشارة موارد بشرية' : 'Request HR Consultation'; ?></h2>
        <p class="popup-subtitle"><?php echo $is_ar ? 'اطلب استشارة موارد بشرية مجانية' : 'Request Free HR Consultation'; ?></p>

        <form class="site-popup-form" data-form-type="hr">
            <div class="form-row">
                <div class="form-group">
                    <label><?php echo $is_ar ? 'الاسم الكامل' : 'Full Name'; ?> <span class="req">*</span></label>
                    <input type="text" name="full_name" placeholder="<?php echo $is_ar ? 'جون دو' : 'John Doe'; ?>" required>
                </div>
                <div class="form-group">
                    <label><?php echo $is_ar ? 'اسم الشركة' : 'Company Name'; ?></label>
                    <input type="text" name="company_name" placeholder="<?php echo $is_ar ? 'اسم الشركة' : 'Company Name'; ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label><?php echo $is_ar ? 'المسمى الوظيفي' : 'Job Title'; ?></label>
                    <input type="text" name="job_title" placeholder="<?php echo $is_ar ? 'المسمى الوظيفي' : 'Job Title'; ?>">
                </div>
                <div class="form-group">
                    <label><?php echo $is_ar ? 'البريد الإلكتروني' : 'Email Address'; ?> <span class="req">*</span></label>
                    <input type="email" name="email" placeholder="john@company.com" required>
                </div>
            </div>

            <div class="form-rows">
                <div class="form-group">
                    <label><?php echo $is_ar ? 'رقم الهاتف' : 'Phone Number'; ?></label>
                    <input type="tel" class="numbers-only" name="phone" placeholder="966xxxxxxxxx"
                        inputmode="numeric" pattern="[0-9]*" autocomplete="tel">
                </div>
                <!--<div class="form-group">-->
                <!--    <label>Preferred Contact Method</label>-->
                <!--    <select name="preferred_contact">-->
                <!--        <option value="">-- Select --</option>-->
                <!--        <option value="email">Email</option>-->
                <!--        <option value="phone">Phone</option>-->
                <!--        <option value="video">Video Call</option>-->
                <!--    </select>-->
                <!--</div>-->
            </div>

            <div class="form-group">
                <label><?php echo $is_ar ? 'الدولة' : 'Country'; ?></label>
                <input type="text" name="country" placeholder="<?php echo $is_ar ? 'الدولة' : 'Country'; ?>">
            </div>

            <div class="form-group">
                <label><?php echo $is_ar ? 'أخبرنا عن احتياجاتك' : 'Tell Us About Your Needs'; ?></label>
                <textarea rows="4" name="message" placeholder="<?php echo $is_ar ? 'كيف يمكننا مساعدتك؟' : 'How can we help you?'; ?>"></textarea>
            </div>

            <p class="popup-privacy-note">
                <?php echo $is_ar
                    ? 'سيتم استخدام بياناتك فقط للرد على طلبك، ويتم التعامل معها بأمان وفقًا لسياسة الخصوصية الخاصة بنا.'
                    : 'Your information will only be used to respond to your request and handled securely in accordance with our Privacy Policy.'; ?>
            </p>

            <button type="submit" class="submit-btn"><?php echo $is_ar ? 'إرسال الطلب' : 'Send Request'; ?></button>
        </form>
    </div>
</div>


<!-- ================= Popup: Request Free Consultation (General) ================= -->
<div class="training-popup" data-popup-id="consultation">
    <div class="popup-box">
        <button class="close-popup" aria-label="Close">&times;</button>
        <h2><?php echo $is_ar ? 'اطلب استشارة مجانية' : 'Request Free Consultation'; ?></h2>

        <form class="site-popup-form" data-form-type="consultation">
            <div class="form-row">
                <div class="form-group">
                    <label><?php echo $is_ar ? 'الاسم الكامل' : 'Full Name'; ?> <span class="req">*</span></label>
                    <input type="text" name="full_name" placeholder="<?php echo $is_ar ? 'جون دو' : 'John Doe'; ?>" required>
                </div>
                <div class="form-group">
                    <label><?php echo $is_ar ? 'اسم الشركة' : 'Company Name'; ?></label>
                    <input type="text" name="company_name" placeholder="<?php echo $is_ar ? 'اسم الشركة' : 'Company Name'; ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label><?php echo $is_ar ? 'المسمى الوظيفي' : 'Job Title'; ?></label>
                    <input type="text" name="job_title" placeholder="<?php echo $is_ar ? 'المسمى الوظيفي' : 'Job Title'; ?>">
                </div>
                <div class="form-group">
                    <label><?php echo $is_ar ? 'البريد الإلكتروني' : 'Email Address'; ?> <span class="req">*</span></label>
                    <input type="email" name="email" placeholder="john@company.com" required>
                </div>
            </div>

            <div class="form-rows">
                <div class="form-group">
                    <label><?php echo $is_ar ? 'رقم الهاتف' : 'Phone Number'; ?></label>
                    <input type="tel" class="numbers-only" name="phone" placeholder="966xxxxxxxxx"
                        inputmode="numeric" pattern="[0-9]*" autocomplete="tel">
                </div>
                <!--<div class="form-group">-->
                <!--    <label>Preferred Contact Method</label>-->
                <!--    <select name="preferred_contact">-->
                <!--        <option value="">-- Select --</option>-->
                <!--        <option value="email">Email</option>-->
                <!--        <option value="phone">Phone</option>-->
                <!--        <option value="video">Video Call</option>-->
                <!--    </select>-->
                <!--</div>-->
            </div>

            <div class="form-group">
                <label><?php echo $is_ar ? 'الدولة' : 'Country'; ?></label>
                <input type="text" name="country" placeholder="<?php echo $is_ar ? 'الدولة' : 'Country'; ?>">
            </div>

            <div class="form-group">
                <label><?php echo $is_ar ? 'أخبرنا عن احتياجاتك' : 'Tell Us About Your Needs'; ?></label>
                <textarea rows="4" name="message" placeholder="<?php echo $is_ar ? 'كيف يمكننا مساعدتك؟' : 'How can we help you?'; ?>"></textarea>
            </div>

            <p class="popup-privacy-note">
                <?php echo $is_ar
                    ? 'سيتم استخدام بياناتك فقط للرد على طلبك، ويتم التعامل معها بأمان وفقًا لسياسة الخصوصية الخاصة بنا.'
                    : 'Your information will only be used to respond to your request and handled securely in accordance with our Privacy Policy.'; ?>
            </p>

            <button type="submit" class="submit-btn"><?php echo $is_ar ? 'إرسال الطلب' : 'Send Request'; ?></button>
        </form>
    </div>
</div>





<script>
    // ======= MARQUEE ORIGINAL WITH SAFE LOOP =======
    const track = document.getElementById('track');
    const speed = 1;
    let x = 0;

    function getOriginalWidth() {
        const originalItems = track.querySelectorAll('.marquee-item');
        let w = 0;
        originalItems.forEach(item => w += item.getBoundingClientRect().width);
        return w;
    }

    function fillTrack() {
        const originalItems = Array.from(track.children);
        let safety = 0; // حماية من infinite loop
        const maxSafety = 12;
        while (track.scrollWidth < window.innerWidth * 2 && safety < maxSafety) {
            originalItems.forEach(item => track.appendChild(item.cloneNode(true)));
            safety++;
        }
    }

    function animateMarquee() {
        x -= speed;
        if (x <= -getOriginalWidth()) x = 0;
        track.style.transform = `translateX(${x}px)`;
        requestAnimationFrame(animateMarquee);
    }

    window.addEventListener('load', function() {
        fillTrack();
        animateMarquee();
    });

    window.addEventListener('resize', fillTrack);
</script>

<script>
    // ======= HEADER SCROLL ORIGINAL =======
    (function() {
        let lastScrollY = window.scrollY;
        const header = document.querySelector('.header');
        const headerMobile = document.querySelector('.header_Mobile');
        let ticking = false;

        if (!header && !headerMobile) return;

        function updateHeaderOnScroll() {
            const currentScrollY = window.scrollY;
            const isDown = currentScrollY > lastScrollY;

            [header, headerMobile].forEach(function(el) {
                if (!el) return;
                if (currentScrollY > 150) {
                    el.classList.add('scrolled');
                    el.classList.toggle('hidden', isDown);
                } else {
                    el.classList.remove('scrolled', 'hidden');
                }
            });

            lastScrollY = currentScrollY;
            ticking = false;
        }

        window.addEventListener('scroll', function() {
            if (!ticking) {
                window.requestAnimationFrame(updateHeaderOnScroll);
                ticking = true;
            }
        }, {
            passive: true
        });
    })();
</script>

<script>
    // ======= SEARCH POPUP ORIGINAL =======
    (function() {
        const searchBtn = document.getElementById("searchBtn");
        const searchPopup = document.getElementById("searchPopup");
        const closePopup = document.getElementById("closePopup");

        if (searchBtn && searchPopup && closePopup) {
            searchBtn.addEventListener("click", function() {
                searchPopup.classList.add("active");
            });

            closePopup.addEventListener("click", function() {
                searchPopup.classList.remove("active");
            });

            searchPopup.addEventListener("click", function(e) {
                if (e.target === searchPopup) {
                    searchPopup.classList.remove("active");
                }
            });
        }

    // ===== GENERIC POPUP OPENER =====
    // Any element with data-popup="xxx" opens [data-popup-id="xxx"]
    document.querySelectorAll('[data-popup]').forEach(btn => {
        btn.addEventListener('click', e => {
            e.preventDefault();
            const key = btn.getAttribute('data-popup');
            document.querySelectorAll('[data-popup-id="' + key + '"]').forEach(p => p.classList.add('active'));
        });
    });
    document.querySelectorAll('[data-popup-id]').forEach(popup => {
        const closeBtn = popup.querySelector('.close-popup, .close-btn');
        popup.addEventListener('click', e => { if (e.target === popup) popup.classList.remove('active'); });
        closeBtn && closeBtn.addEventListener('click', () => popup.classList.remove('active'));
    });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') document.querySelectorAll('[data-popup-id].active').forEach(p => p.classList.remove('active'));
    });

    })();
</script>

<script>
    // ======= SIDE MENU ORIGINAL =======
    (function() {
        const openMenuBtn = document.querySelector('.open_menu');
        const sideMenu = document.getElementById('sideMenu');
        const closeMenuBtn = document.getElementById('closeMenu');
        const menuOverlay = document.getElementById('overlay');

        if (!openMenuBtn || !sideMenu || !closeMenuBtn || !menuOverlay) return;

        function openSideMenu() {
            sideMenu.classList.add('open');
            menuOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeSideMenu() {
            sideMenu.classList.remove('open');
            menuOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }

        openMenuBtn.addEventListener('click', openSideMenu);
        closeMenuBtn.addEventListener('click', closeSideMenu);

        menuOverlay.addEventListener('click', function() {
            if (sideMenu.classList.contains('open')) {
                closeSideMenu();
            }
        });

        document.querySelectorAll('.side-menu-nav .menu-item-has-children > a').forEach(function(item) {

            item.addEventListener('click', function(e) {

                e.preventDefault();

                this.parentElement.classList.toggle('open');

            });

        });
    })();
</script>

<script>
    // ======= PHONE INPUT VALIDATION =======
    (function() {
        document.querySelectorAll('.numbers-only').forEach(function(input) {
            input.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
            });

            input.addEventListener('paste', function(e) {
                e.preventDefault();
                var pastedText = (e.clipboardData || window.clipboardData).getData('text');
                var cleanText = pastedText.replace(/[^0-9]/g, '');
                var start = this.selectionStart || this.value.length;
                var end = this.selectionEnd || this.value.length;
                this.value = this.value.slice(0, start) + cleanText + this.value.slice(end);
                this.dispatchEvent(new Event('input'));
            });

            input.addEventListener('keydown', function(e) {
                var allowedKeys = ['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'Home', 'End'];
                if (allowedKeys.indexOf(e.key) !== -1 || e.ctrlKey || e.metaKey) return;
                if (!/^[0-9]$/.test(e.key)) e.preventDefault();
            });
        });
    })();
</script>

<script>
    // ======= ACTIVE NAV HIGHLIGHT =======
    const currentPage = window.location.pathname.split('/').pop() || 'index.html';
    document.querySelectorAll('.Main_Nav > li').forEach(li => {
        const directLink = li.querySelector(':scope > a');
        if (directLink?.getAttribute('href') === currentPage) li.classList.add('active');
        const dropdownLinks = li.querySelectorAll('.dropdown a');
        dropdownLinks.forEach(link => {
            if (link.getAttribute('href') === currentPage) li.classList.add('active');
        });
    });
</script>



<script>
    // ===== Testimonials Slider =====
    const testiSlider = document.getElementById('testimonialsSlider');
    const testiDots = document.getElementById('testimonialsDots');

    const testiCards = Array.from(testiSlider.querySelectorAll('.testimonial_card'));
    const testiTotal = testiCards.length;

    let testiIndex = testiTotal;
    let testiAuto;
    let testiTransitioning = false;

    // ===== Clone for infinite loop =====
    testiCards.forEach(card => {
        const clone = card.cloneNode(true);
        testiSlider.appendChild(clone);
    });

    testiCards.forEach(card => {
        const clone = card.cloneNode(true);
        testiSlider.insertBefore(clone, testiSlider.firstChild);
    });

    // ===== Helpers =====
    function testiVisible() {
        if (window.innerWidth <= 580) return 1;
        if (window.innerWidth <= 900) return 2;
        return 3;
    }

    function testiCardWidth() {
        const card = testiSlider.querySelector('.testimonial_card');
        const gap = 24;
        return card.offsetWidth + gap;
    }

    function testiGoTo(index, animate = true) {
        testiSlider.style.transition = animate ? 'transform 0.5s ease' : 'none';
        testiSlider.style.transform = `translateX(-${index * testiCardWidth()}px)`;
    }

    // ===== Init =====
    testiGoTo(testiIndex, false);

    // ===== Transition End =====
    testiSlider.addEventListener('transitionend', () => {
        if (testiIndex >= testiTotal * 2) {
            testiIndex = testiTotal;
            testiGoTo(testiIndex, false);
        }
        if (testiIndex <= testiTotal - 1) {
            testiIndex = testiTotal * 2 - 1;
            testiGoTo(testiIndex, false);
        }
        testiTransitioning = false;
        updateTestiDots();
    });

    // ===== Next / Prev =====
    function testiNext() {
        if (testiTransitioning) return;
        testiTransitioning = true;
        testiIndex++;
        testiGoTo(testiIndex);
    }

    function testiPrev() {
        if (testiTransitioning) return;
        testiTransitioning = true;
        testiIndex--;
        testiGoTo(testiIndex);
    }

    // ===== Dots =====
    function buildTestiDots() {
        testiDots.innerHTML = '';
        for (let i = 0; i < testiTotal; i++) {
            const dot = document.createElement('button');
            dot.classList.add('dot');
            if (i === 0) dot.classList.add('active');
            dot.addEventListener('click', () => {
                testiIndex = testiTotal + i;
                testiGoTo(testiIndex);
                resetTestiAuto();
            });
            testiDots.appendChild(dot);
        }
    }

    function updateTestiDots() {
        const dots = testiDots.querySelectorAll('.dot');
        const realIndex = (testiIndex - testiTotal + testiTotal) % testiTotal;
        dots.forEach((dot, i) => {
            dot.classList.toggle('active', i === realIndex);
        });
    }

    buildTestiDots();

    // ===== Auto Play =====
    function startTestiAuto() {
        testiAuto = setInterval(testiNext, 3000);
    }

    function resetTestiAuto() {
        clearInterval(testiAuto);
        startTestiAuto();
    }

    startTestiAuto();

    // ===== Resize =====
    window.addEventListener('resize', () => {
        testiGoTo(testiIndex, false);
    });
</script>


<script>
    // ===== Partners Slider =====
    const partnersSlider = document.getElementById('partnersSlider');
    const partnersPrev = document.getElementById('partnersPrev');
    const partnersNext = document.getElementById('partnersNext');

    const partnerCards = Array.from(partnersSlider.querySelectorAll('.partner_logo'));
    const partnerTotal = partnerCards.length;

    let partnerIndex = partnerTotal;
    let partnerAuto;
    let partnerTransitioning = false;

    // ===== Clone for infinite loop =====
    partnerCards.forEach(card => {
        partnersSlider.appendChild(card.cloneNode(true));
    });
    partnerCards.forEach(card => {
        partnersSlider.insertBefore(card.cloneNode(true), partnersSlider.firstChild);
    });

    // ===== Helpers =====
    function partnerVisible() {
        if (window.innerWidth <= 580) return 2;
        if (window.innerWidth <= 900) return 4;
        return 6;
    }

    function partnerCardWidth() {
        const card = partnersSlider.querySelector('.partner_logo');
        const gap = 20;
        return card.offsetWidth + gap;
    }

    function partnerGoTo(index, animate = true) {
        partnersSlider.style.transition = animate ? 'transform 0.5s ease' : 'none';
        partnersSlider.style.transform = `translateX(-${index * partnerCardWidth()}px)`;
    }

    // ===== Init =====
    partnerGoTo(partnerIndex, false);

    // ===== Transition End =====
    partnersSlider.addEventListener('transitionend', () => {
        if (partnerIndex >= partnerTotal * 2) {
            partnerIndex = partnerTotal;
            partnerGoTo(partnerIndex, false);
        }
        if (partnerIndex <= partnerTotal - 1) {
            partnerIndex = partnerTotal * 2 - 1;
            partnerGoTo(partnerIndex, false);
        }
        partnerTransitioning = false;
    });

    // ===== Next / Prev =====
    function partnerNext() {
        if (partnerTransitioning) return;
        partnerTransitioning = true;
        partnerIndex++;
        partnerGoTo(partnerIndex);
    }

    function partnerPrev() {
        if (partnerTransitioning) return;
        partnerTransitioning = true;
        partnerIndex--;
        partnerGoTo(partnerIndex);
    }

    // ===== Buttons =====
    partnersNext.addEventListener('click', () => {
        partnerNext();
        resetPartnerAuto();
    });

    partnersPrev.addEventListener('click', () => {
        partnerPrev();
        resetPartnerAuto();
    });

    // ===== Auto Play =====
    function startPartnerAuto() {
        partnerAuto = setInterval(partnerNext, 2500);
    }

    function resetPartnerAuto() {
        clearInterval(partnerAuto);
        startPartnerAuto();
    }

    startPartnerAuto();

    // ===== Resize =====
    window.addEventListener('resize', () => {
        partnerGoTo(partnerIndex, false);
    });
</script>


<script>
    document.addEventListener("DOMContentLoaded", function() {
        const trainingSwiper = new Swiper(".trainingSwiper", {
            slidesPerView: 4,
            grid: {
                rows: 2,
                fill: "row"
            },
            spaceBetween: 28,
            speed: 900,
            loop: true,

            autoplay: {
                delay: 2200,
                disableOnInteraction: false,
                pauseOnMouseEnter: true,
                reverseDirection: false
            },

            navigation: {
                nextEl: ".training-next",
                prevEl: ".training-prev"
            },

            breakpoints: {
                0: {
                    slidesPerView: 1,
                    grid: {
                        rows: 2,
                        fill: "row"
                    }
                },
                576: {
                    slidesPerView: 2,
                    grid: {
                        rows: 2,
                        fill: "row"
                    }
                },
                768: {
                    slidesPerView: 3,
                    grid: {
                        rows: 2,
                        fill: "row"
                    }
                },
                1200: {
                    slidesPerView: 4,
                    grid: {
                        rows: 2,
                        fill: "row"
                    }
                }
            }
        });

        document.querySelectorAll(".training-card").forEach(function(card) {
            card.addEventListener("click", function() {
                document.querySelectorAll(".training-card").forEach(function(item) {
                    item.classList.remove("active");
                });
                card.classList.add("active");
            });
        });
    });
</script>


<script>
    // ======= GENERIC POPUP OPENER (data-popup) =======
// ضيف السكريبت ده بدل / جنب سكريبت "SEARCH POPUP ORIGINAL" اللي عندك.
// أي زرار طالع من الـ CTA وله data-popup="xxx" هيفتح
// العنصر اللي عليه data-popup-id="xxx" تلقائي.
//
// المهم: كل بوب أب (Book a Service / Request Training / HR Consultation /
// Request Free Consultation) لازم يبقى ليه div زي الموجود دلوقتي
// لـ #trainingPopup بالظبط، بس تحط عليه:
//   data-popup-id="training"   (أو service / hr / consultation)
// بدل id="trainingPopup"
//
// مثال العنصر:
// <div class="training-popup" data-popup-id="training"> ... </div>

(function () {
    document.querySelectorAll('[data-popup]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var key = this.getAttribute('data-popup');
            var popup = document.querySelector('[data-popup-id="' + key + '"]');
            if (popup) popup.classList.add('active');
        });
    });

    document.querySelectorAll('[data-popup-id]').forEach(function (popup) {
        var closeBtn = popup.querySelector('.close-popup, .close-btn');
        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                popup.classList.remove('active');
            });
        }
        popup.addEventListener('click', function (e) {
            if (e.target === popup) popup.classList.remove('active');
        });
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('[data-popup-id].active').forEach(function (p) {
                p.classList.remove('active');
            });
        }
    });
})();
</script>


<?php wp_footer(); ?>
</body>

</html>