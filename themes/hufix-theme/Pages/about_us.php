<?php
/**
 * Frontend: About Us Page (page id = 34) - FIXED VERSION
 * -----------------------------------------------------
 * حط الملف ده في page-about.php أو تمبلت صفحة About Us
 * -----------------------------------------------------
 */

// $about_page_id = 34;
$about_page_id =  get_queried_object_id();
/* Template Name: About Us */ 
get_header();
?>

<!-- ===== Hero ===== -->
<?php
$hero_heading = get_field( 'au_hero_heading', $about_page_id );
$hero_button  = get_field( 'au_hero_button', $about_page_id );
$hero_button_popup = get_field( 'au_hero_button_popup', $about_page_id );
$hero_bg      = get_field( 'au_hero_bg', $about_page_id );
?>

<style>
    .Hero_Section .Hero_Seaction_Data .Box button {
    width: 300px;
    text-align: center;
    padding: 20px;
    border: 1px solid #fff;
    font-size: 20px;
     font-family: sans-serif;
    font-weight: lighter;
    background: none;
    color: #fff;
}
</style>
<section class="Hero_Section About_Hero" <?php if ( $hero_bg ) : ?>style="background-image: url('<?php echo esc_url( $hero_bg ); ?>');"<?php endif; ?>>
    <div class="Hero_Seaction_Data">
        <div class="Box">
            <h1><?php echo nl2br( esc_html( $hero_heading ) ); ?></h1>
            <?php if ( $hero_button_popup ) : ?>
                <button class="hero-btn-popup" data-popup="<?php echo esc_attr( $hero_button_popup ); ?>"><?php echo esc_html( $hero_button ); ?></button>
            <?php else : ?>
                <button><?php echo esc_html( $hero_button ); ?></button>
            <?php endif; ?>
        </div>
    </div>
</section>


<!-- ===== Intro (Where Meest) ===== -->
<?php
$intro_heading   = get_field( 'au_intro_heading', $about_page_id );
$intro_paragraph = get_field( 'au_intro_paragraph', $about_page_id );
?>
<section class="Where_Meest">
    <div class="Where_Meest_Data" style="position: relative; overflow: hidden;">
        <h2 class="wow animate__animated animate__backInLeft"><?php echo esc_html( $intro_heading ); ?></h2>
        <div class="Download_Data wow animate__animated animate__backInRight">
            <?php echo wp_kses_post( $intro_paragraph ); ?>
        </div>
    </div>
</section>


<!-- ===== Team ===== -->
<?php
$team_title    = get_field( 'au_team_title', $about_page_id );
$team_subtitle = get_field( 'au_team_subtitle', $about_page_id );
?>
<section class="team">
    <div class="team_container">

        <div class="team_header">
            <div class="team_header_text">
                <h2 class="team_title"><?php echo esc_html( $team_title ); ?></h2>
                <p class="team_subtitle"><?php echo esc_html( $team_subtitle ); ?></p>
            </div>
            <div class="team_arrows">
                <button class="team_arrow" id="prevBtn"><img src="<?php echo get_template_directory_uri(); ?>/assets/images/next.png" alt=""></button>
                <button class="team_arrow" id="nextBtn"><img src="<?php echo get_template_directory_uri(); ?>/assets/images/prev.png" alt=""></button>
            </div>
        </div>

        <div class="team_slider_wrapper">
            <div class="team_slider" id="teamSlider">

                <?php if ( have_rows( 'au_team_members', $about_page_id ) ) : ?>
                    <?php while ( have_rows( 'au_team_members', $about_page_id ) ) : the_row();
                        $m_image = get_sub_field( 'image' );
                        $m_name  = get_sub_field( 'name' );
                        $m_bio   = get_sub_field( 'bio' );
                        ?>
                        <div class="team_card">
                            <?php if ( $m_image ) : ?>
                                <div class="team_img">
                                    <img src="<?php echo esc_url( $m_image ); ?>" alt="<?php echo esc_attr( $m_name ); ?>">
                                </div>
                            <?php endif; ?>
                            <h3><?php echo esc_html( $m_name ); ?></h3>
                            <?php echo wp_kses_post( $m_bio ); ?>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>

            </div>
        </div>

    </div>
</section>


<!-- ===== Expertise ===== -->
<?php
$expertise_bg    = get_field( 'au_expertise_bg', $about_page_id );
$expertise_label = get_field( 'au_expertise_label', $about_page_id );
$expertise_title = get_field( 'au_expertise_title', $about_page_id );
$expertise_desc  = get_field( 'au_expertise_desc', $about_page_id );
?>
<section class="expertise">

    <div class="expertise_bg" <?php if ( $expertise_bg ) : ?>style="background-image: url('<?php echo esc_url( $expertise_bg ); ?>');"<?php endif; ?>></div>

    <div class="expertise_container">

        <div class="expertise_label"><?php echo esc_html( $expertise_label ); ?></div>

        <h2 class="expertise_title"><?php echo nl2br( esc_html( $expertise_title ) ); ?></h2>

        <div class="expertise_desc"><?php echo wp_kses_post( $expertise_desc ); ?></div>

        <?php if ( have_rows( 'au_expertise_list', $about_page_id ) ) : ?>
            <ul class="expertise_list">
                <?php while ( have_rows( 'au_expertise_list', $about_page_id ) ) : the_row(); ?>
                    <li>
                        <img src="<?php echo get_template_directory_uri(); ?>/assets/images/Margin.png" alt="check">
                        <span><?php echo esc_html( get_sub_field( 'text' ) ); ?></span>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php endif; ?>

    </div>
</section>


<!-- ===== Trust ===== -->
<?php
$trust_title       = get_field( 'au_trust_title', $about_page_id );
$trust_desc        = get_field( 'au_trust_desc', $about_page_id );
$trust_button_text = get_field( 'au_trust_button_text', $about_page_id );
$trust_button_link = get_field( 'au_trust_button_link', $about_page_id );
$trust_image       = get_field( 'au_trust_image', $about_page_id );
?>
<section class="trust">
    <div class="trust_container" style="position: relative; overflow: hidden;">

        <div class="trust_left wow animate__animated animate__fadeInLeft">

            <h2 class="trust_title"><?php echo esc_html( $trust_title ); ?></h2>
            <div class="trust_line"></div>

            <div class="trust_desc"><?php echo wp_kses_post( $trust_desc ); ?></div>

            <?php if ( have_rows( 'au_trust_list', $about_page_id ) ) : ?>
                <ul class="trust_list">
                    <?php while ( have_rows( 'au_trust_list', $about_page_id ) ) : the_row(); ?>
                        <li>
                            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/check.png" alt="">
                            <span><?php echo esc_html( get_sub_field( 'text' ) ); ?></span>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php endif; ?>

            <a href="<?php echo $trust_button_link ? esc_url( $trust_button_link ) : '#'; ?>" class="trust_btn">
                <?php echo esc_html( $trust_button_text ); ?>
            </a>

        </div>

        <div class="trust_right wow animate__animated animate__fadeInRight">
            <?php if ( $trust_image ) : ?>
                <img src="<?php echo esc_url( $trust_image ); ?>" alt="<?php echo esc_attr( $trust_title ); ?>">
            <?php endif; ?>
        </div>

    </div>
</section>


<!-- ===== Accreditation ===== -->
<?php $acc_title = get_field( 'au_acc_title', $about_page_id ); ?>
<section class="accreditation">
    <div class="accreditation_container">

        <h2 class="accreditation_title"><?php echo esc_html( $acc_title ); ?></h2>

        <?php if ( have_rows( 'au_acc_logos', $about_page_id ) ) : ?>
            <div class="accreditation_logos">
                <?php while ( have_rows( 'au_acc_logos', $about_page_id ) ) : the_row();
                    $logo = get_sub_field( 'image' );
                    $alt  = get_sub_field( 'alt' );
                    if ( ! $logo ) continue;
                    ?>
                    <div class="accreditation_logo">
                        <img src="<?php echo esc_url( $logo ); ?>" alt="<?php echo esc_attr( $alt ); ?>">
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>

    </div>
</section>


<!-- ===== Core Focus Areas ===== -->
<?php $focus_title = get_field( 'au_focus_title', $about_page_id ); ?>
<section class="focus">
    <div class="focus_container">

        <h2 class="focus_title"><?php echo esc_html( $focus_title ); ?></h2>

        <?php if ( have_rows( 'au_focus_items', $about_page_id ) ) : ?>
            <div class="focus_list">
                <?php while ( have_rows( 'au_focus_items', $about_page_id ) ) : the_row();
                    $badge_text  = get_sub_field( 'badge_text' );
                    $badge_style = get_sub_field( 'badge_style' );
                    $name        = get_sub_field( 'name' );
                    ?>
                    <div class="focus_item">
                        <span class="focus_badge <?php echo esc_attr( $badge_style ); ?>"><?php echo esc_html( $badge_text ); ?></span>
                        <span class="focus_name"><?php echo esc_html( $name ); ?></span>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>

    </div>
</section>


<!-- ===== Testimonials ===== -->
<?php $testi_title = get_field( 'au_testi_title', $about_page_id ); ?>
<section class="testimonials">
    <div class="testimonials_container">

        <h2 class="testimonials_title"><?php echo esc_html( $testi_title ); ?></h2>

        <div class="testimonials_slider_wrapper">
            <div class="testimonials_slider" id="testimonialsSlider">

                <?php if ( have_rows( 'au_testimonials', $about_page_id ) ) : ?>
                <?php while ( have_rows( 'au_testimonials', $about_page_id ) ) : the_row();
                    $t_image = get_sub_field( 'image' );
                    $t_name  = get_sub_field( 'name' );
                    $t_text  = get_sub_field( 'text' );
                    $t_job   = get_sub_field( 'job' );
                    ?>
                    <div class="testimonial_card">
                        <?php if ( $t_image ) : ?>
                            <div class="testimonial_img">
                                <img src="<?php echo esc_url( $t_image ); ?>" alt="<?php echo esc_attr( $t_name ); ?>">
                            </div>
                        <?php endif; ?>
                        <?php echo wp_kses_post( $t_text ); ?>
                        <h3 style="color: #246A73 !important;"><?php echo esc_html( $t_name ); ?></h3>
                        <?php if ( $t_job ) : ?>
                            <p class="testimonial_job" style="color: #246A73 !important;"><?php echo esc_html( $t_job ); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
                <?php endif; ?>

            </div>
        </div>

        <div class="testimonials_dots" id="testimonialsDots"></div>

    </div>
</section>


<!-- ===== Partners ===== -->
<?php
$partners_title    = get_field( 'au_partners_title', $about_page_id );
$partners_subtitle = get_field( 'au_partners_subtitle', $about_page_id );
$partners_desc     = get_field( 'au_partners_desc', $about_page_id );
?>
<section class="partners">
    <div class="partners_container">

        <div class="partners_header">
            <h2 class="partners_title"><?php echo esc_html( $partners_title ); ?></h2>
            <p class="partners_subtitle"><?php echo esc_html( $partners_subtitle ); ?></p>
        </div>

        <div class="partners_slider_row">

            <button class="partners_arrow" id="partnersPrev">
                <img src="<?php echo get_template_directory_uri(); ?>/assets/images/Vector (4).png" alt="">
            </button>

            <div class="partners_slider_wrapper">
                <div class="partners_slider" id="partnersSlider">

                    <?php if ( have_rows( 'au_partners_logos', $about_page_id ) ) : ?>
                        <?php while ( have_rows( 'au_partners_logos', $about_page_id ) ) : the_row();
                            $p_logo = get_sub_field( 'image' );
                            $p_alt  = get_sub_field( 'alt' );
                            if ( ! $p_logo ) continue;
                            ?>
                            <div class="partner_logo">
                                <img src="<?php echo esc_url( $p_logo ); ?>" alt="<?php echo esc_attr( $p_alt ); ?>">
                            </div>
                        <?php endwhile; ?>
                    <?php endif; ?>

                </div>
            </div>

            <button class="partners_arrow" id="partnersNext">
                <img src="<?php echo get_template_directory_uri(); ?>/assets/images/Vector (3).png" alt="">
            </button>

        </div>

        <div class="partners_desc"><?php echo wp_kses_post( $partners_desc ); ?></div>

    </div>
</section>


<!-- ===== Hufix DNA ===== -->
<?php
$dna_bg    = get_field( 'au_dna_bg', $about_page_id );
$dna_title = get_field( 'au_dna_title', $about_page_id );
?>
<section class="dna">

    <div class="dna_bg" <?php if ( $dna_bg ) : ?>style="background-image: url('<?php echo esc_url( $dna_bg ); ?>');"<?php endif; ?>></div>

    <div class="dna_container" style="position: relative; overflow: hidden;">

        <div class="dna_title_col">
            <h2 class="dna_title"><?php echo esc_html( $dna_title ); ?></h2>
        </div>

        <?php if ( have_rows( 'au_dna_cards', $about_page_id ) ) : ?>
            <?php
            $delay = 0;
            while ( have_rows( 'au_dna_cards', $about_page_id ) ) : the_row();
                $d_icon  = get_sub_field( 'icon' );
                $d_title = get_sub_field( 'title' );
                $d_text  = get_sub_field( 'text' );
                ?>
                <div class="dna_card wow animate__animated animate__bounceInLeft" data-wow-delay="<?php echo esc_attr( $delay ); ?>s">
                    <div class="dna_icon">
                        <?php if ( $d_icon ) : ?>
                            <img src="<?php echo esc_url( $d_icon ); ?>" alt="<?php echo esc_attr( $d_title ); ?>">
                        <?php endif; ?>
                        <h3><?php echo esc_html( $d_title ); ?></h3>
                    </div>
                    <?php echo wp_kses_post( $d_text ); ?>
                </div>
                <?php
                $delay += 0.2;
            endwhile;
            ?>
        <?php endif; ?>

    </div>
</section>

<?php get_footer(); ?>



 <!-- Marquee Animation -->
  <script>
    const track = document.getElementById('track');
    const speed = 1;

    function fillTrack() {
      const originalItems = Array.from(track.children);
      while (track.scrollWidth < window.innerWidth * 2) {
        originalItems.forEach(item => {
          track.appendChild(item.cloneNode(true));
        });
      }
    }

    fillTrack();
    window.addEventListener('resize', fillTrack);

    let x = 0;

    function getOriginalWidth() {
      const originalCount = track.querySelectorAll('.marquee-item').length;
      const half = Math.floor(originalCount / 2);
      let w = 0;
      for (let i = 0; i < half; i++) {
        w += track.children[i].getBoundingClientRect().width;
      }
      return w;
    }

    function animateMarquee() {
      x -= speed;
      if (x <= -getOriginalWidth()) x = 0;
      track.style.transform = `translateX(${x}px)`;
      requestAnimationFrame(animateMarquee);
    }

    animateMarquee();
  </script>


<script>
const searchBtn = document.getElementById("searchBtn");
const searchPopup = document.getElementById("searchPopup");
const closePopup = document.getElementById("closePopup");

searchBtn.addEventListener("click", () => {
    searchPopup.classList.add("active");
});

closePopup.addEventListener("click", () => {
    searchPopup.classList.remove("active");
});

searchPopup.addEventListener("click", (e) => {
    if (e.target === searchPopup) {
        searchPopup.classList.remove("active");
    }
});

// إغلاق بـ ESC
document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
        searchPopup.classList.remove("active");
    }
});



const popup = document.getElementById("trainingPopup");
const openBtn = document.getElementById("openTrainingPopup");
const closeBtn = document.querySelector(".close-popup");

openBtn.addEventListener("click", function(e){
    e.preventDefault();
    popup.classList.add("active");
});

closeBtn.addEventListener("click", function(){
    popup.classList.remove("active");
});

popup.addEventListener("click", function(e){
    if(e.target === popup){
        popup.classList.remove("active");
    }
});

document.addEventListener("keydown", function(e){
    if(e.key === "Escape"){
        popup.classList.remove("active");
    }
});


</script>


<script>
    let lastScrollY = window.scrollY;
    const header = document.querySelector('.header');
    const headerMobile = document.querySelector('.header_Mobile');

    window.addEventListener('scroll', () => {
        const currentScrollY = window.scrollY;

        if (currentScrollY > 150) {
            header.classList.add('scrolled');
            headerMobile.classList.add('scrolled');

            if (currentScrollY > lastScrollY) {
                header.classList.add('hidden');
                headerMobile.classList.add('hidden');
            } else {
                header.classList.remove('hidden');
                headerMobile.classList.remove('hidden');
            }
        } else {
            header.classList.remove('scrolled', 'hidden');
            headerMobile.classList.remove('scrolled', 'hidden');
        }

        lastScrollY = currentScrollY;
    });
</script>


<script>
    const slider = document.getElementById('teamSlider');
const prevBtn = document.getElementById('prevBtn');
const nextBtn = document.getElementById('nextBtn');

// ===== Clone cards for infinite loop =====
const cards = Array.from(slider.querySelectorAll('.team_card'));
const totalOriginal = cards.length;

// Clone all cards and append at end
cards.forEach(card => {
    const clone = card.cloneNode(true);
    clone.classList.add('cloned');
    slider.appendChild(clone);
});

// Clone all cards and prepend at start
cards.forEach(card => {
    const clone = card.cloneNode(true);
    clone.classList.add('cloned');
    slider.insertBefore(clone, slider.firstChild);
});

// ===== State =====
let currentIndex = totalOriginal; // start after prepended clones
let isTransitioning = false;
let autoPlayInterval;

// ===== Helpers =====
function getVisibleCount() {
    if (window.innerWidth <= 580) return 1;
    if (window.innerWidth <= 900) return 2;
    return 3;
}

function getCardWidth() {
    const card = slider.querySelector('.team_card');
    const style = window.getComputedStyle(slider);
    const gap = parseFloat(style.gap) || 30;
    return card.offsetWidth + gap;
}

function goTo(index, animate = true) {
    if (!animate) {
        slider.style.transition = 'none';
    } else {
        slider.style.transition = 'transform 0.5s ease';
    }
    slider.style.transform = `translateX(-${index * getCardWidth()}px)`;
}

// ===== Init position (no animation) =====
goTo(currentIndex, false);

// ===== After transition: jump silently if at clone =====
slider.addEventListener('transitionend', () => {
    const allCards = slider.querySelectorAll('.team_card');
    const totalAll = allCards.length;

    if (currentIndex >= totalOriginal * 2) {
        currentIndex = totalOriginal;
        goTo(currentIndex, false);
    }

    if (currentIndex <= totalOriginal - 1) {
        currentIndex = totalOriginal * 2 - 1;
        goTo(currentIndex, false);
    }

    isTransitioning = false;
});

// ===== Next =====
function goNext() {
    if (isTransitioning) return;
    isTransitioning = true;
    currentIndex++;
    goTo(currentIndex);
}

// ===== Prev =====
function goPrev() {
    if (isTransitioning) return;
    isTransitioning = true;
    currentIndex--;
    goTo(currentIndex);
}

// ===== Buttons =====
nextBtn.addEventListener('click', () => {
    goNext();
    resetAutoPlay();
});

prevBtn.addEventListener('click', () => {
    goPrev();
    resetAutoPlay();
});

// ===== Auto Play =====
function startAutoPlay() {
    autoPlayInterval = setInterval(() => {
        goNext();
    }, 3000);
}

function resetAutoPlay() {
    clearInterval(autoPlayInterval);
    startAutoPlay();
}

startAutoPlay();

// ===== Resize =====
window.addEventListener('resize', () => {
    goTo(currentIndex, false);
});
</script>

<script>
    // ===== Testimonials Slider =====
const testiSlider = document.getElementById('testimonialsSlider');
const testiDots = document.getElementById('testimonialsDots');
const testiWrapper = testiSlider ? testiSlider.closest('.testimonials_slider_wrapper') : null;

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

function normalizeTestiIndex() {
    if (testiIndex >= testiTotal * 2) {
        testiIndex = testiTotal;
        testiGoTo(testiIndex, false);
    }

    if (testiIndex <= testiTotal - 1) {
        testiIndex = testiTotal * 2 - 1;
        testiGoTo(testiIndex, false);
    }

    updateTestiDots();
}

// ===== Init =====
testiGoTo(testiIndex, false);

// ===== Transition End =====
testiSlider.addEventListener('transitionend', () => {
    normalizeTestiIndex();
    testiTransitioning = false;
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

// ===== Mobile / Touch Drag =====
let testiDragStartX = 0;
let testiDragCurrentX = 0;
let testiDragStartY = 0;
let testiIsDragging = false;
let testiIsSwiping = false;
let testiStartTranslate = 0;

function testiGetTranslateX() {
    return -testiIndex * testiCardWidth();
}

function testiStartDrag(clientX, clientY) {
    if (testiTransitioning) return;

    clearInterval(testiAuto);
    testiIsDragging = true;
    testiIsSwiping = false;
    testiDragStartX = clientX;
    testiDragCurrentX = clientX;
    testiDragStartY = clientY;
    testiStartTranslate = testiGetTranslateX();
    testiSlider.style.transition = 'none';
}

function testiMoveDrag(clientX, clientY, event) {
    if (!testiIsDragging) return;

    const diffX = clientX - testiDragStartX;
    const diffY = clientY - testiDragStartY;

    if (!testiIsSwiping && Math.abs(diffX) > 8 && Math.abs(diffX) > Math.abs(diffY)) {
        testiIsSwiping = true;
    }

    if (!testiIsSwiping) return;

    if (event && event.cancelable) {
        event.preventDefault();
    }

    testiDragCurrentX = clientX;
    testiSlider.style.transform = `translateX(${testiStartTranslate + diffX}px)`;
}

function testiEndDrag() {
    if (!testiIsDragging) return;

    const diffX = testiDragCurrentX - testiDragStartX;
    const threshold = Math.min(90, testiCardWidth() * 0.25);

    testiIsDragging = false;

    if (Math.abs(diffX) > threshold) {
        testiTransitioning = true;

        if (diffX < 0) {
            testiIndex++;
        } else {
            testiIndex--;
        }

        testiGoTo(testiIndex, true);
    } else {
        testiGoTo(testiIndex, true);
    }

    resetTestiAuto();
}

if (testiWrapper) {
    testiWrapper.style.touchAction = 'pan-y';
    testiWrapper.style.cursor = 'grab';

    testiWrapper.addEventListener('touchstart', function(e) {
        if (!e.touches || !e.touches.length) return;
        testiStartDrag(e.touches[0].clientX, e.touches[0].clientY);
    }, { passive: true });

    testiWrapper.addEventListener('touchmove', function(e) {
        if (!e.touches || !e.touches.length) return;
        testiMoveDrag(e.touches[0].clientX, e.touches[0].clientY, e);
    }, { passive: false });

    testiWrapper.addEventListener('touchend', testiEndDrag);
    testiWrapper.addEventListener('touchcancel', testiEndDrag);

    testiWrapper.addEventListener('mousedown', function(e) {
        testiWrapper.style.cursor = 'grabbing';
        testiStartDrag(e.clientX, e.clientY);
    });

    window.addEventListener('mousemove', function(e) {
        testiMoveDrag(e.clientX, e.clientY, e);
    });

    window.addEventListener('mouseup', function() {
        testiWrapper.style.cursor = 'grab';
        testiEndDrag();
    });

    testiWrapper.addEventListener('mouseleave', function() {
        if (testiIsDragging) {
            testiWrapper.style.cursor = 'grab';
            testiEndDrag();
        }
    });
}

// ===== Resize =====
window.addEventListener('resize', () => {
    testiGoTo(testiIndex, false);
});
</script>


<script>
    // ===== Partners Slider =====
const partnersSlider = document.getElementById('partnersSlider');
const partnersPrev  = document.getElementById('partnersPrev');
const partnersNext  = document.getElementById('partnersNext');

const partnerCards  = Array.from(partnersSlider.querySelectorAll('.partner_logo'));
const partnerTotal  = partnerCards.length;

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
  document.addEventListener("DOMContentLoaded", function () {
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

    document.querySelectorAll(".training-card").forEach(function (card) {
      card.addEventListener("click", function () {
        document.querySelectorAll(".training-card").forEach(function (item) {
          item.classList.remove("active");
        });
        card.classList.add("active");
      });
    });
  });
</script>



<script>
    const openMenuBtn    = document.querySelector('.open_menu');
    const sideMenu       = document.getElementById('sideMenu');
    const closeMenuBtn   = document.getElementById('closeMenu');
    const servicesToggle = document.getElementById('servicesToggle');
    const menuOverlay    = document.getElementById('overlay');

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

    // الـ toggle بيشتغل بس لو ضغط على السهم أو الـ Services نفسه مش على اللينكات الجوانية
    servicesToggle.querySelector('a').addEventListener('click', function(e) {
        e.preventDefault();
        servicesToggle.classList.toggle('open');
    });
</script>



<script>
    // Allow numbers only in phone fields
    document.querySelectorAll('.numbers-only').forEach(function(input) {
        input.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        input.addEventListener('paste', function(e) {
            e.preventDefault();

            var pastedText = '';
            if (e.clipboardData || window.clipboardData) {
                pastedText = (e.clipboardData || window.clipboardData).getData('text');
            }

            this.value += pastedText.replace(/[^0-9]/g, '');
        });

        input.addEventListener('keypress', function(e) {
            var char = String.fromCharCode(e.which || e.keyCode);

            if (!/[0-9]/.test(char)) {
                e.preventDefault();
            }
        });
    });
</script>

<script>
    const currentPage = window.location.pathname.split('/').pop() || '/';

document.querySelectorAll('.Main_Nav > li').forEach(li => {
    // تحقق من الـ link المباشر
    const directLink = li.querySelector(':scope > a');
    if (directLink?.getAttribute('href') === currentPage) {
        li.classList.add('active');
    }
    
    // تحقق من الـ dropdown links
    const dropdownLinks = li.querySelectorAll('.dropdown a');
    dropdownLinks.forEach(link => {
        if (link.getAttribute('href') === currentPage) {
            li.classList.add('active'); // يلون الـ li الأب (Services)
        }
    });
});
</script>


<script>
// (function () {
//     var hero = document.querySelector('.Hero_Section.About_Hero');
//     if (!hero) return;

//     var baseSize    = 115; /* % - الحجم الطبيعي عند سكرول = 0 (زودتها من 106 لـ 115) */
//     var minSize     = 100; /* % - أصغر حجم توصله بعد الزووم-أوت */
//     var scrollRange = 250;  /* قللتها من 400 لـ 250 عشان يوصل لأقصى تصغير بسرعة أكبر */

//     function updateHeroZoom() {
//         var y = window.scrollY || window.pageYOffset || 0;
//         var progress = Math.min(Math.max(y / scrollRange, 0), 1);
//         var size = baseSize - (progress * (baseSize - minSize));
//         hero.style.backgroundSize = size + '%';
//     }

//     window.addEventListener('scroll', updateHeroZoom, { passive: true });
//     updateHeroZoom();
// })();
</script>