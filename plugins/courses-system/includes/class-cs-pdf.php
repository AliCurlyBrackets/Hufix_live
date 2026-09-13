<?php
/**
 * Feature: Auto-Generated Course Outline PDF
 *
 * بيولّد ملف PDF أوتوماتيك لأي "Session" من بيانات الكورس نفسها
 * (نفس المحتوى اللي ظاهر في صفحة single-course.php) — من غير ما حد
 * يرفع PDF يدوي. الشكل مبني على نفس ستايل بروشور "Hufix HR Services".
 *
 * الاستخدام:
 *   الرابط: /?cs_pdf=SESSION_ID
 *   ( مربوط أوتوماتيك في زرار "Download Outline PDF" في templates/single-course.php )
 *
 * ------------------------------------------------------------------
 *  دعم العربي: بنستخدم مكتبة TCPDF (بدل FPDF القديمة). النصوص
 *  الإنجليزية بخط "Cairo" (خط عصري، Google Fonts، رخصة حرة OFL)،
 *  والنصوص العربية بخط "Amiri" (مصمم خصيصاً للطباعة العربية ومغطّي
 *  بالكامل جدول "أشكال العرض العربي" اللي محرك التشكيل في TCPDF محتاجه
 *  -- جرّبنا Cairo على العربي وطلعت حروف ناقصة لأنه زي أغلب خطوط الـ UI
 *  الحديثة مالوش التغطية دي، فبنستخدمه للإنجليزي بس). TCPDF بيعمل
 *  "إعادة تشكيل" للحروف العربية (كل حرف بيتغيّر شكله حسب موقعه في
 *  الكلمة) واتجاه الكتابة (RTL) تلقائي -- ده اللي كان ناقص في FPDF
 *  القديمة وبيسبب ظهور علامات استفهام (؟؟؟؟) بدل الحروف العربية.
 *
 *  إزاي بيقرر الـ PDF إن الكورس ده "عربي": بيسأل WPML عن لغة السيشن
 *  (post language)، ولو WPML مش شغّال أو مفيش لغة متحددة، بيفحص لو
 *  عنوان الكورس نفسه فيه حروف عربية. في الحالتين، لو الكورس عربي:
 *  الاتجاه بيبقى RTL، وكل نصوص العلامة التجارية (اسم الشركة، الشعار،
 *  العنوان، الإيميل، التليفون) بتتبدّل للنسخة العربية بتاعتها كمان.
 * ------------------------------------------------------------------
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once CS_PATH . 'includes/lib/tcpdf/tcpdf.php';

/**
 * ------------------------------------------------------------------
 *  بيانات الشركة (عربي/إنجليزي) -- عدّل هنا لو اتغيرت.
 *  القيم دي بتتبدّل تلقائي حسب لغة الكورس اللي بيتولّدله الـ PDF.
 * ------------------------------------------------------------------
 */
if ( ! defined( 'CS_PDF_COMPANY_NAME_EN' ) )    { define( 'CS_PDF_COMPANY_NAME_EN', 'Hufix HR Professional Services' ); }
if ( ! defined( 'CS_PDF_COMPANY_NAME_AR' ) )    { define( 'CS_PDF_COMPANY_NAME_AR', 'هوفيكس لخدمات الموارد البشرية الاحترافية' ); }
if ( ! defined( 'CS_PDF_COMPANY_TAGLINE_EN' ) ) { define( 'CS_PDF_COMPANY_TAGLINE_EN', 'Training & Development' ); }
if ( ! defined( 'CS_PDF_COMPANY_TAGLINE_AR' ) ) { define( 'CS_PDF_COMPANY_TAGLINE_AR', 'التدريب والتطوير' ); }

if ( ! defined( 'CS_PDF_LOCATION_EN' ) ) { define( 'CS_PDF_LOCATION_EN', '123 Innovation Way, Tech District, Netherlands' ); }
if ( ! defined( 'CS_PDF_LOCATION_AR' ) ) { define( 'CS_PDF_LOCATION_AR', '123 طريق الابتكار، الحي التكنولوجي، هولندا' ); }
if ( ! defined( 'CS_PDF_EMAIL' ) )       { define( 'CS_PDF_EMAIL', 'info@hufix.com' ); }
if ( ! defined( 'CS_PDF_PHONE' ) )       { define( 'CS_PDF_PHONE', '+31 (0) 123 456 789' ); }
if ( ! defined( 'CS_PDF_WEBSITE' ) )     { define( 'CS_PDF_WEBSITE', 'hufix.eu' ); }

if ( ! defined( 'CS_PDF_FOOTER_TAGLINE_EN' ) ) { define( 'CS_PDF_FOOTER_TAGLINE_EN', 'Your Strategic Partner in Professional Development' ); }
if ( ! defined( 'CS_PDF_FOOTER_TAGLINE_AR' ) ) { define( 'CS_PDF_FOOTER_TAGLINE_AR', 'شريككم الاستراتيجي في التطوير المهني' ); }

// ألوان البراند (نفس تيل الموقع).
if ( ! defined( 'CS_PDF_COLOR_TEAL_DARK' ) ) { define( 'CS_PDF_COLOR_TEAL_DARK', array( 22, 58, 63 ) ); }
if ( ! defined( 'CS_PDF_COLOR_TEAL' ) )      { define( 'CS_PDF_COLOR_TEAL', array( 80, 192, 175 ) ); }
if ( ! defined( 'CS_PDF_COLOR_GREY' ) )      { define( 'CS_PDF_COLOR_GREY', array( 110, 110, 110 ) ); }
if ( ! defined( 'CS_PDF_COLOR_LIGHT' ) )     { define( 'CS_PDF_COLOR_LIGHT', array( 244, 247, 246 ) ); }

class WeDo_Course_PDF extends TCPDF {

	/** true = المستند ده عربي (اتجاه RTL + نصوص العلامة التجارية بالعربي). */
	public $is_ar = false;

	public function __construct() {
		parent::__construct( 'P', 'mm', 'A4', true, 'UTF-8', false );
		// الهامش العلوي 26 (مش 12) عشان عنوان الكورس ميتراكبش فوق شريط
		// الهيدر التيل (ارتفاعه 16مم) -- TCPDF بيرجّع مكان الكتابة لقيمة
		// الهامش العلوي دي تلقائي بعد ما يخلص من رسم Header() في كل صفحة.
		$this->SetMargins( 16, 26, 16 );
		$this->SetAutoPageBreak( true, 20 );
		$this->SetPrintHeader( true );
		$this->SetPrintFooter( true );
		$this->SetCreator( CS_PDF_COMPANY_NAME_EN );
		$this->setFontSubsetting( true );
	}

	/**
	 * فعّل وضع العربي (لازم تتنادى قبل AddPage()).
	 */
	public function set_arabic( $is_ar ) {
		$this->is_ar = (bool) $is_ar;
		$this->setRTL( $this->is_ar );
	}

	/**
	 * اسم عائلة الخط.
	 *  - الإنجليزي: Cairo (خط عصري، Google Fonts، رخصة OFL حرة).
	 *  - العربي: Amiri (يدعم التشكيل العربي كاملاً).
	 */
	public function base_font() {
		return $this->is_ar ? 'amiri' : 'cairo';
	}

	/** نسخة الخط الغامق (Bold) بنفس منطق base_font(). */
	public function bold_font() {
		return $this->is_ar ? 'amirib' : 'cairob';
	}

	/** المحاذاة الطبيعية للاتجاه الحالي. */
	public function dir_align() {
		return $this->is_ar ? 'R' : 'L';
	}

	/** اسم الشركة بلغة المستند الحالية. */
	public function company_name() {
		return $this->is_ar ? CS_PDF_COMPANY_NAME_AR : CS_PDF_COMPANY_NAME_EN;
	}

	/** شعار/تاجلاين الشركة (جنب الاسم في الهيدر) بلغة المستند الحالية. */
	public function company_tagline() {
		return $this->is_ar ? CS_PDF_COMPANY_TAGLINE_AR : CS_PDF_COMPANY_TAGLINE_EN;
	}

	/** سطر التاجلاين التاني (تحت بيانات التواصل في الفوتر). */
	public function footer_tagline() {
		return $this->is_ar ? CS_PDF_FOOTER_TAGLINE_AR : CS_PDF_FOOTER_TAGLINE_EN;
	}

	/**
	 * سطر بيانات التواصل بلغة المستند الحالية.
	 * - العنوان/الموقع: استُبدل بالـ "hufix.eu" (Website)
	 * - البريد والتليفون: بنستخدم Unicode bidi isolate (LRI/PDI)
	 *   عشان يطلعوا بالترتيب الصح داخل النص العربي (RTL).
	 */
	public function contact_line() {
		// LRI = U+2066, PDI = U+2069
		$ltr_email   = "\u{2066}" . CS_PDF_EMAIL . "\u{2069}";
		$ltr_phone   = "\u{2066}" . CS_PDF_PHONE . "\u{2069}";
		$ltr_website = "\u{2066}" . CS_PDF_WEBSITE . "\u{2069}";

		if ( $this->is_ar ) {
			return 'الموقع: ' . $ltr_website . '   |   ' . 'الهاتف: ' . $ltr_phone . '   |   ' . 'البريد الإلكتروني: ' . $ltr_email;
		}
		return 'Location: ' . CS_PDF_LOCATION_EN . '   |   ' . 'Email: ' . CS_PDF_EMAIL . '   |   ' . 'Phone: ' . CS_PDF_PHONE;
	}

	public function t( $text ) {
		// المحتوى ممكن يوصل من ووردبريس مع HTML entities زي &#038; أو &amp;
		// (مثلاً لو اسم الكورس فيه "&")، فبنفكّها لحروفها الحقيقية.
		// TCPDF (على عكس FPDF القديمة) بيشتغل UTF-8 مباشرة فمحتاجينش أي
		// تحويل ترميز تاني.
		return html_entity_decode( (string) $text, ENT_QUOTES, 'UTF-8' );
	}

	public function up( $text ) {
		// تكبير الحروف (UPPERCASE) مفهوم لاتيني بس -- العربي مالوش "حالة كبيرة/صغيرة".
		if ( $this->is_ar ) {
			return $text;
		}
		return function_exists( 'mb_strtoupper' ) ? mb_strtoupper( (string) $text, 'UTF-8' ) : strtoupper( (string) $text );
	}

	public function Header() { // phpcs:ignore
		// شريط الهيدر: للعربي بنعكس الجهات (اسم الشركة يمين، الشعار شمال)
		// عشان يتماشى مع اتجاه القراءة، من غير ما نفعّل RTL نفسها (مواقع
		// العناصر ثابتة بالإحداثيات في الحالتين).
		$was_rtl = $this->rtl;
		$this->setRTL( false );

		list( $r, $g, $b ) = CS_PDF_COLOR_TEAL_DARK;
		$this->SetFillColor( $r, $g, $b );
		$this->Rect( 0, 0, 210, 16, 'F' );
		$this->SetTextColor( 255, 255, 255 );

		if ( $this->is_ar ) {
			$this->SetFont( $this->base_font(), '', 8 );
			$this->SetXY( 16, 5 );
			$this->Cell( 64, 6, $this->t( $this->company_tagline() ), 0, 0, 'L' );
			$this->SetFont( $this->bold_font(), '', 11 );
			$this->SetXY( -96, 4 );
			$this->Cell( 80, 8, $this->t( $this->company_name() ), 0, 0, 'R' );
		} else {
			$this->SetFont( $this->bold_font(), '', 11 );
			$this->SetXY( 16, 4 );
			$this->Cell( 0, 8, $this->t( $this->company_name() ), 0, 0, 'L' );
			$this->SetFont( $this->base_font(), '', 8 );
			$this->SetXY( -80, 5 );
			$this->Cell( 64, 6, $this->t( $this->company_tagline() ), 0, 0, 'R' );
		}

		$this->SetY( 22 );
		$this->SetTextColor( 30, 30, 30 );

		$this->setRTL( $was_rtl );
	}

	public function Footer() { // phpcs:ignore
		$was_rtl = $this->rtl;
		$this->setRTL( $this->is_ar );

		$this->SetY( -20 );
		list( $r, $g, $b ) = CS_PDF_COLOR_GREY;
		$this->SetDrawColor( $r, $g, $b );
		$this->Line( 16, $this->GetY(), 194, $this->GetY() );
		$this->SetY( -17 );
		$this->SetTextColor( $r, $g, $b );
		$this->SetFont( $this->base_font(), '', 7.5 );
		$this->Cell( 0, 5, $this->t( $this->contact_line() ), 0, 1, 'C' );
		$this->SetFont( $this->base_font(), '', 7 );
		$this->Cell( 0, 4, $this->t( $this->footer_tagline() ), 0, 0, 'C' );

		$this->setRTL( false );
		$this->SetFont( $this->base_font(), '', 7.5 );
		$this->SetXY( -30, -10 );
		$this->Cell( 14, 5, $this->t( cs__( 'Page', 'pdf_page_word' ) ) . ' ' . $this->PageNo(), 0, 0, 'R' );

		$this->setRTL( $was_rtl );
	}

	/** عنوان قسم (Section heading) بستايل موحد. */
	public function section_title( $label ) {
		$this->Ln( 3 );
		list( $r, $g, $b ) = CS_PDF_COLOR_TEAL_DARK;
		$this->SetTextColor( $r, $g, $b );
		$this->SetFont( $this->bold_font(), '', 12 );
		$this->Cell( 0, 8, $this->t( $this->up( $label ) ), 0, 1, $this->dir_align() );
		list( $tr, $tg, $tb ) = CS_PDF_COLOR_TEAL;
		$this->SetDrawColor( $tr, $tg, $tb );
		$this->SetLineWidth( 0.6 );
		if ( $this->is_ar ) {
			$this->Line( 194 - 30, $this->GetY(), 194, $this->GetY() );
		} else {
			$this->Line( $this->GetX(), $this->GetY(), $this->GetX() + 30, $this->GetY() );
		}
		$this->SetLineWidth( 0.2 );
		$this->Ln( 4 );
		$this->SetTextColor( 30, 30, 30 );
	}

	/** بند بولت واحد. */
	public function bullet( $text, $indent = 0 ) {
		$this->SetFont( $this->base_font(), '', 10 );
		$w = 178 - $indent;
		if ( $this->is_ar ) {
			$this->SetX( 16 );
			$this->MultiCell( $w, 5.6, '• ' . $this->t( $text ), 0, 'R' );
		} else {
			$x = $this->GetX() + $indent;
			$this->SetX( $x );
			$this->Cell( 5, 5.6, '-', 0, 0 );
			$this->SetX( $x + 5 );
			$this->MultiCell( $w, 5.6, $this->t( $text ), 0, 'L' );
		}
	}
}

class CS_PDF {

	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'maybe_output' ) );
	}

	/**
	 * لو الرابط فيه ?cs_pdf=ID بنولّد الـ PDF ونطلعه ونوقف التنفيذ.
	 */
	public static function maybe_output() {
		if ( empty( $_GET['cs_pdf'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		$session_id = absint( wp_unslash( $_GET['cs_pdf'] ) ); // phpcs:ignore WordPress.Security.NonceVerification

		if ( ! $session_id || get_post_type( $session_id ) !== CS_CPT || get_post_status( $session_id ) !== 'publish' ) {
			wp_die( esc_html__( 'Course not found.', 'courses-system' ), '', array( 'response' => 404 ) );
		}

		self::stream( $session_id );
		exit;
	}

	/**
	 * هل السيشن/الكورس ده "عربي"؟ نتحقق من عنوان URL (/ar)،
	 * وإذا مفيش نتيجة نسأل WPML ونفحص العنوان.
	 */
	public static function is_arabic_session( $session_id, $master_id, $title ) {
		// 1) أولاً: تحقق من /ar في الـ URL (مطلوب من الموقع)
		$request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
		if ( strpos( $request_uri, '/ar' ) !== false ) {
			return true;
		}

		// 2) تحقق من WPML للغة اللي انحرفتها على الموقع الأصلي
		$wpml_lang = CS_WPML::post_language( $session_id );
		if ( ! $wpml_lang ) {
			$wpml_lang = CS_WPML::post_language( $master_id );
		}
		if ( $wpml_lang ) {
			return 'ar' === $wpml_lang;
		}

		// 3) فحص أخير: فيه حروف عربية في العنوان؟
		return (bool) preg_match( '/[\x{0600}-\x{06FF}]/u', (string) $title );
	}

	/**
	 * بيبني الـ PDF من نفس البيانات اللي بتتعرض في single-course.php ويطلعه للمتصفح.
	 */
	public static function stream( $session_id ) {

		$master_id = CS_CPT::master_id( $session_id );

		$country = get_post_meta( $session_id, CS_META_COUNTRY, true );
		$s_date  = get_post_meta( $session_id, CS_META_DATE, true );
		$s_end   = get_post_meta( $session_id, '_cs_session_end', true );
		$loc     = $country ? CS_Countries::name( $country ) : '';

		$title    = get_the_title( $master_id );
		$summary  = cs_field( 'cs_summary', $session_id );
		$mode     = CS_Listing::label_mode( cs_field( 'cs_delivery_mode', $session_id ) );
		$lang     = CS_Listing::label_lang( cs_field( 'cs_language', $session_id ) );
		$days     = cs_duration_days( $session_id );
		$hours    = (int) cs_field( 'cs_duration_hours', $session_id );
		$loc_index = get_post_meta( $session_id, CS_META_LOC_INDEX, true );
	$pc       = CS_Listing::country_price( $master_id, $country, $loc_index );
		$price    = $pc['price'];
		$curr     = $pc['currency'];
		$objs     = cs_field( 'cs_objectives', $session_id );
		$schedule = cs_field( 'cs_schedule', $session_id );

		$date_long = $s_date ? date_i18n( 'd F Y', strtotime( $s_date ) ) : '';
		$end_long  = $s_end ? date_i18n( 'd F Y', strtotime( $s_end ) ) : '';
		$date_disp = trim( $date_long . ( $end_long ? ' - ' . $end_long : '' ) );

		$is_ar = self::is_arabic_session( $session_id, $master_id, $title );

		// اتأكد إن WPML شايف اللغة الصحيحة وهو بيترجم عناوين الأقسام
		// (Course Overview, Detailed Course Outline...) عن طريق cs__()
		// تحت. من غير الخطوة دي، لو حد فتح رابط الـ PDF مباشرة (مش من
		// جوه صفحة الموقع بلغتها)، ممكن WPML يفضل شايف "لغة الموقع
		// الافتراضية" فترجع العناوين إنجليزي حتى لو الكورس عربي.
		if ( CS_WPML::active() ) {
			$pdf_lang = CS_WPML::post_language( $session_id );
			if ( ! $pdf_lang ) {
				$pdf_lang = CS_WPML::post_language( $master_id );
			}
			if ( ! $pdf_lang ) {
				$pdf_lang = $is_ar ? 'ar' : CS_WPML::default_language();
			}
			do_action( 'wpml_switch_language', $pdf_lang );
		}

		$pdf = new WeDo_Course_PDF();
		$pdf->set_arabic( $is_ar );
		$pdf->AddPage();

		// عنوان الكورس.
		$pdf_font      = $pdf->base_font();
		$pdf_font_bold = $pdf->bold_font();
		$pdf->SetFont( $pdf_font_bold, '', 18 );
		$pdf->SetTextColor( 20, 20, 20 );
		$pdf->MultiCell( 0, 8, $pdf->t( $title ), 0, $is_ar ? 'R' : 'L' );
		$pdf->Ln( 2 );

		// شريط الـ Quick Facts (Duration / Delivery / Language / Dates / Investment).
		$facts = array(
			cs__( 'Duration', 'pdf_fact_duration' )   => $days ? ( $days . ' ' . cs__( 'Days', 'pdf_unit_days' ) . ' (' . $hours . ' ' . cs__( 'Hours', 'pdf_unit_hours' ) . ')' ) : '',
			cs__( 'Delivery', 'pdf_fact_delivery' )   => $mode,
			cs__( 'Language', 'pdf_fact_language' )   => $lang,
			cs__( 'Dates', 'pdf_fact_dates' )         => $date_disp,
			cs__( 'Location', 'pdf_fact_location' )   => $loc,
			cs__( 'Investment', 'pdf_fact_investment' ) => $curr ? trim( $curr . ' ' . ( $price ? number_format( (float) $price ) : '' ) ) : '',
		);
		$facts = array_filter( $facts );

		if ( $facts ) {
			$count = count( $facts );
			$colw  = 178 / $count;
			$y0    = $pdf->GetY();
			list( $lr, $lg, $lb ) = CS_PDF_COLOR_LIGHT;
			$pdf->SetFillColor( $lr, $lg, $lb );
			$pdf->Rect( 16, $y0, 178, 16, 'F' );
			// الصناديق دي متمركزة (align 'C') فمش محتاجة انعكاس للعربي.
			$x = 16;
			foreach ( $facts as $label => $value ) {
				$pdf->SetXY( $x, $y0 + 2 );
				$pdf->SetFont( $pdf_font, '', 7.5 );
				list( $gr, $gg, $gb ) = CS_PDF_COLOR_GREY;
				$pdf->SetTextColor( $gr, $gg, $gb );
				$pdf->Cell( $colw, 4, $pdf->t( $pdf->up( $label ) ), 0, 0, 'C' );
				$pdf->SetXY( $x, $y0 + 7 );
				$pdf->SetFont( $pdf_font_bold, '', 9 );
				$pdf->SetTextColor( 20, 20, 20 );
				$pdf->MultiCell( $colw, 4, $pdf->t( $value ), 0, 'C' );
				$x += $colw;
			}
			$pdf->SetY( $y0 + 20 );
		}

		// COURSE OVERVIEW.
		if ( $summary ) {
			$pdf->section_title( cs__( 'Course Overview', 'pdf_section_overview' ) );
			$pdf->SetFont( $pdf_font, '', 10 );
			$pdf->MultiCell( 0, 5.6, $pdf->t( $summary ) );
		}

		// LEARNING OBJECTIVES.
		if ( ! empty( $objs ) ) {
			$pdf->section_title( cs__( 'Learning Objectives', 'pdf_section_objectives' ) );
			foreach ( $objs as $o ) {
				if ( ! empty( $o['text'] ) ) {
					$pdf->bullet( $o['text'] );
				}
			}
		}

		// DETAILED COURSE OUTLINE (بنفس اليومية اللي في الصفحة).
		if ( ! empty( $schedule ) ) {
			$pdf->section_title( cs__( 'Detailed Course Outline', 'pdf_section_outline' ) );
			$dn = 0;
			foreach ( $schedule as $day ) {
				$dn++;
				$day_title = ! empty( $day['day_title'] ) ? $day['day_title'] : ( cs__( 'Day', 'pdf_day_word' ) . ' ' . $dn );
				$pdf->SetFont( $pdf_font_bold, '', 10.5 );
				list( $tr, $tg, $tb ) = CS_PDF_COLOR_TEAL_DARK;
				$pdf->SetTextColor( $tr, $tg, $tb );
				$pdf->Ln( 1 );
				$pdf->Cell( 0, 6.5, $pdf->t( $day_title ), 0, 1, $pdf->dir_align() );
				$pdf->SetTextColor( 30, 30, 30 );
				if ( ! empty( $day['points'] ) ) {
					foreach ( $day['points'] as $p ) {
						if ( ! empty( $p['text'] ) ) {
							$pdf->bullet( $p['text'] );
						}
					}
				}
				$pdf->Ln( 1 );
			}
		}

		$filename = sanitize_title( $title ) . '-course-outline.pdf';

		// امسح أي auto-output اتحصل قبل كده عشان الـ PDF يطلع نضيف.
		if ( ! headers_sent() ) {
			while ( ob_get_level() ) {
				ob_end_clean();
			}
		}

		$pdf->Output( $filename, 'D' );
	}
}

CS_PDF::init();
