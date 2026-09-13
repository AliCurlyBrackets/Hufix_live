<?php
/**
 * Frontend: Training Page (page id = 141)
 * -----------------------------------------------------
 * سيكشن "Training category" (السلايدر) اتسيب ثابت زي ما هو
 * بالظبط بناء على طلبك - من غير أي ACF
 * -----------------------------------------------------
 */

/* Template Name: Traning */

$tr_page_id = get_queried_object_id();
get_header();
?>

<!-- ===== Intro ===== -->
<?php
$intro_title       = get_field( 'tr_intro_title', $tr_page_id );
$intro_desc        = get_field( 'tr_intro_desc', $tr_page_id );
$intro_button_text = get_field( 'tr_intro_button_text', $tr_page_id );
$intro_button_link = get_field( 'tr_intro_button_link', $tr_page_id );
$intro_button_popup = get_field( 'tr_intro_button_popup', $tr_page_id );
$intro_image       = get_field( 'tr_intro_image', $tr_page_id );
?>
<section class="training">
    <div class="training_container" style="position: relative; overflow: hidden;">

        <div class="training_left wow animate__animated animate__bounceInLeft">
            <h2 class="training_title"><?php echo esc_html( $intro_title ); ?></h2>

            <div class="training_desc"><?php echo wp_kses_post( $intro_desc ); ?></div>

            <?php if ( $intro_button_popup ) : ?>
                <a href="#" class="training_btn hero-btn-popup" data-popup="<?php echo esc_attr( $intro_button_popup ); ?>">
                    <?php echo esc_html( $intro_button_text ); ?>
                </a>
            <?php else : ?>
                <a href="<?php echo $intro_button_link ? esc_url( $intro_button_link ) : '#'; ?>" class="training_btn">
                    <?php echo esc_html( $intro_button_text ); ?>
                </a>
            <?php endif; ?>
        </div>

        <div class="training_right wow animate__animated animate__bounceInRight">
            <?php if ( $intro_image ) : ?>
                <img src="<?php echo esc_url( $intro_image ); ?>" alt="<?php echo esc_attr( $intro_title ); ?>">
            <?php endif; ?>
        </div>

    </div>
</section>


<!-- ============================================================
     ===== Training category — سيكشن ثابت (Static) بالكامل =====
     ده مش متصل بـ ACF خالص بناء على طلبك، سيبناه زي ما هو
     ============================================================ -->
<?php
// هات كل الكاتيجوريز بتاعة بلجن الكورسات (مع حماية لو البلجن مش شغال).
$cs_terms = ( defined( 'CS_TAX' ) )
	? get_terms( array(
		'taxonomy'   => CS_TAX,
		'hide_empty' => false,
		'orderby'    => 'name',
	) )
	: array();
?>
<section class="training-section">
  <div class="training-container">

    <h2 class="section-title"><?php cs_e( 'Training category', 'categories_heading' ); ?></h2>
    <p class="section-subtitle"><?php cs_e( 'Our top-requested training themes include:', 'categories_subheading' ); ?></p>

    <div class="training-slider-wrapper">

      <div class="swiper trainingSwiper">
        <div class="swiper-wrapper">

          <?php if ( ! empty( $cs_terms ) && ! is_wp_error( $cs_terms ) ) : ?>
            <?php foreach ( $cs_terms as $cs_term ) :
              $cs_img  = class_exists( 'CS_Taxonomy' ) ? CS_Taxonomy::image_url( $cs_term->term_id, 'medium' ) : '';
              $cs_link = get_term_link( $cs_term );
            ?>
          <div class="swiper-slide">
            <a href="<?php echo esc_url( $cs_link ); ?>" style="display:block;color:inherit;text-decoration:none;">
              <div class="training-card">
                <div class="card-icon">
                  <?php if ( $cs_img ) : ?>
                  <img src="<?php echo esc_url( $cs_img ); ?>" alt="<?php echo esc_attr( $cs_term->name ); ?>">
                  <?php endif; ?>
                </div>
                <div class="card-title"><?php echo esc_html( $cs_term->name ); ?></div>
              </div>
            </a>
          </div>
            <?php endforeach; ?>
          <?php endif; ?>

        </div>
      </div>

      <div class="swiper-button-prev training-arrow training-prev"></div>
      <div class="swiper-button-next training-arrow training-next"></div>

    </div>

         <?php
    $view_all_button_text = get_field('tr_view_all_button_text', $tr_page_id);
    $view_all_button_link = get_field('tr_view_all_button_link', $tr_page_id);
    $view_all_button_popup = get_field('tr_view_all_button_popup', $tr_page_id);
    $is_ar = (strpos($_SERVER['REQUEST_URI'], '/ar') !== false);
    if ( empty($view_all_button_text) ) $view_all_button_text = $is_ar ? 'عرض جميع الدورات التدريبية' : 'View All Training';
    ?>

    <div class="view-btn-wrap">

     <?php if ( $view_all_button_popup ) : ?>
        <button class="view-btn hero-btn-popup" data-popup="<?php echo esc_attr($view_all_button_popup); ?>">
            <?php echo esc_html($view_all_button_text); ?>
        </button>
     <?php elseif ( $view_all_button_link ) : ?>
        <a href="<?php echo esc_url($view_all_button_link); ?>" class="view-btn">
            <?php echo esc_html($view_all_button_text); ?>
        </a>
     <?php else : ?>
        <button class="view-btn">
            <?php echo esc_html($view_all_button_text); ?>
        </button>
     <?php endif; ?>
    </div>

  </div>
</section>

<!-- ===== نهاية سيكشن Training category الثابت ===== -->


<!-- ===== Tailored Sections ===== -->
<?php if ( have_rows( 'tr_tailored_sections', $tr_page_id ) ) : ?>
    <?php while ( have_rows( 'tr_tailored_sections', $tr_page_id ) ) : the_row();

        $t_title         = get_sub_field( 'title' );
        $t_desc          = get_sub_field( 'description' );
        $t_image         = get_sub_field( 'image' );
        $t_image_position = get_sub_field( 'image_position' );
        $t_check_icon    = get_sub_field( 'check_icon' );
        $t_footer        = get_sub_field( 'footer' );
        ?>

        <section class="tailored-section">
            <div class="tailored-container" style="position: relative; overflow: hidden;">

                <?php if ( $t_image_position === 'left' ) : ?>
                    <div class="tailored-image-wrapper wow animate__animated animate__fadeInLeft">
                        <?php if ( $t_image ) : ?>
                            <img src="<?php echo esc_url( $t_image ); ?>" alt="<?php echo esc_attr( $t_title ); ?>" class="tailored-image">
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="tailored-content wow animate__animated animate__fadeInRight">
                    <h2 class="tailored-title"><?php echo esc_html( $t_title ); ?></h2>
                    <p class="tailored-desc"><?php echo esc_html( $t_desc ); ?></p>

                    <?php if ( have_rows( 'list' ) ) : ?>
                        <ul class="tailored-list">
                            <?php while ( have_rows( 'list' ) ) : the_row(); ?>
                                <li class="tailored-item">
                                    <span class="tailored-icon-wrap">
                                        <?php if ( $t_check_icon ) : ?>
                                            <img src="<?php echo esc_url( $t_check_icon ); ?>" alt="check" class="tailored-icon">
                                        <?php endif; ?>
                                    </span>
                                    <span><?php echo esc_html( get_sub_field( 'text' ) ); ?></span>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php endif; ?>

                    <?php if ( $t_footer ) : ?>
                        <p class="tailored-footer"><?php echo esc_html( $t_footer ); ?></p>
                    <?php endif; ?>
                </div>

                <?php if ( $t_image_position === 'right' ) : ?>
                    <div class="tailored-image-wrapper wow animate__animated animate__fadeInRight">
                        <?php if ( $t_image ) : ?>
                            <img src="<?php echo esc_url( $t_image ); ?>" alt="<?php echo esc_attr( $t_title ); ?>" class="tailored-image">
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            </div>
        </section>

    <?php endwhile; ?>
<?php endif; ?>


<!-- ===== Trainer Network ===== -->
<?php
$trainer_title          = get_field( 'tr_trainer_title', $tr_page_id );
$trainer_subtitle       = get_field( 'tr_trainer_subtitle', $tr_page_id );
$trainer_desc           = get_field( 'tr_trainer_desc', $tr_page_id );
$trainer_programs_label = get_field( 'tr_trainer_programs_label', $tr_page_id );
$trainer_check_icon     = get_field( 'tr_trainer_check_icon', $tr_page_id );
$trainer_footer         = get_field( 'tr_trainer_footer', $tr_page_id );
?>
<section class="trainer-section">
    <div class="trainer-container">

        <h2 class="trainer-title"><?php echo esc_html( $trainer_title ); ?></h2>

        <p class="trainer-subtitle"><?php echo esc_html( $trainer_subtitle ); ?></p>

        <div class="trainer-desc"><?php echo wp_kses_post( $trainer_desc ); ?></div>

        <?php if ( $trainer_programs_label ) : ?>
            <p class="trainer-programs-label"><?php echo esc_html( $trainer_programs_label ); ?></p>
        <?php endif; ?>

        <?php if ( have_rows( 'tr_trainer_list', $tr_page_id ) ) : ?>
            <ul class="trainer-list">
                <?php while ( have_rows( 'tr_trainer_list', $tr_page_id ) ) : the_row(); ?>
                    <li class="trainer-item">
                        <span class="trainer-icon-wrap">
                            <?php if ( $trainer_check_icon ) : ?>
                                <img src="<?php echo esc_url( $trainer_check_icon ); ?>" alt="check" class="trainer-icon">
                            <?php endif; ?>
                        </span>
                        <span><?php echo esc_html( get_sub_field( 'text' ) ); ?></span>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php endif; ?>

        <?php if ( $trainer_footer ) : ?>
            <p class="trainer-footer"><?php echo esc_html( $trainer_footer ); ?></p>
        <?php endif; ?>

    </div>
</section>


<!-- ===== Why Choose ===== -->
<?php $why_title = get_field( 'tr_why_title', $tr_page_id ); ?>
<section class="why-section">
    <div class="why-container">

        <h2 class="why-title"><?php echo esc_html( $why_title ); ?></h2>

        <div class="why-grid" style="position: relative; overflow: hidden;">

            <?php if ( have_rows( 'tr_why_cards', $tr_page_id ) ) : ?>
                <?php
                $index = 0;
                while ( have_rows( 'tr_why_cards', $tr_page_id ) ) : the_row();
                    $w_icon = get_sub_field( 'icon' );
                    $w_text = get_sub_field( 'text' );
                    $anim   = ( $index % 2 === 0 ) ? 'animate__fadeInLeft' : 'animate__fadeInRight';
                    ?>
                    <div class="why-card wow animate__animated <?php echo esc_attr( $anim ); ?>">
                        <?php if ( $w_icon ) : ?>
                            <div class="why-icon-wrap">
                                <img src="<?php echo esc_url( $w_icon ); ?>" alt="<?php echo esc_attr( $w_text ); ?>" class="why-icon">
                            </div>
                        <?php endif; ?>
                        <p class="why-text"><?php echo esc_html( $w_text ); ?></p>
                    </div>
                    <?php
                    $index++;
                endwhile;
                ?>
            <?php endif; ?>

        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>




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
  const trainingSwiperEl = document.querySelector(".trainingSwiper");

  if (trainingSwiperEl && typeof Swiper !== "undefined") {
    const trainingSwiper = new Swiper(".trainingSwiper", {
      slidesPerView: 4,
      slidesPerGroup: 1,
      spaceBetween: 28,
      speed: 700,

      /* مهم جدًا:
         loop مع grid بيغير ترتيب العناصر
         عشان كده بنستخدم rewind بدل loop */
      loop: false,
      rewind: true,

      grid: {
        rows: 2,
        fill: "row"
      },

      autoplay: {
        delay: 2200,
        disableOnInteraction: false,
        pauseOnMouseEnter: true
      },

      navigation: {
        nextEl: ".training-next",
        prevEl: ".training-prev"
      },

      breakpoints: {
        0: {
          slidesPerView: 1,
          slidesPerGroup: 1,
          grid: {
            rows: 2,
            fill: "row"
          }
        },
        576: {
          slidesPerView: 2,
          slidesPerGroup: 1,
          grid: {
            rows: 2,
            fill: "row"
          }
        },
        768: {
          slidesPerView: 3,
          slidesPerGroup: 1,
          grid: {
            rows: 2,
            fill: "row"
          }
        },
        1200: {
          slidesPerView: 4,
          slidesPerGroup: 1,
          grid: {
            rows: 2,
            fill: "row"
          }
        }
      }
    });
  }

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

<?php get_footer(); ?>