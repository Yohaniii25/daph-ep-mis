<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect authenticated users directly to dashboard
if (isset($_SESSION['user_id']) && !empty($_SESSION['logged_in'])) {
    header("Location: dashboard.php");
    exit();
}

require_once './config/db_connect.php';
$login_error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']); // Clear errors on reload
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department of Animal Production and Health | Eastern Province, Sri Lanka</title>
    <meta name="description" content="Official Portal of the Department of Animal Production and Health (DAPH), Eastern Provincial Council, Sri Lanka. Providing veterinary healthcare, livestock development, and digital MIS services across Ampara, Batticaloa, and Trincomalee.">

    <link rel="icon" type="image/png" href="assets/img/favicon.png">

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Core Bootstrap & Icons -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --primary-maroon: #370709;
            --primary-garnet: #820100;
            --primary-deep: #500707;
            --maroon-gradient: linear-gradient(135deg, #820100 0%, #370709 100%);
            --blue-gov: #003ddc;
            --blue-cobalt: #185dbd;
            --gold-accent: #efbe2c;
            --gold-dark: #b08723;
            --gold-amber: #d97706;
            --cream-bg: #fcfbfa;
            --stone-neutral: #d4c7b7;
            --dark-slate: #0f172a;
            --glass-bg: rgba(255, 255, 255, 0.96);
            --glass-card: rgba(255, 255, 255, 0.95);
            --glass-border: rgba(255, 255, 255, 0.7);
            --input-border: #cbd5e1;
            --radius-md: 14px;
            --radius-lg: 20px;
            --radius-xl: 26px;
            --shadow-subtle: 0 4px 20px -2px rgba(15, 23, 42, 0.08);
            --shadow-card: 0 16px 36px -8px rgba(55, 7, 9, 0.12);
        }

        * {
            box-sizing: border-box;
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            color: #1e293b;
            background-color: var(--cream-bg);
            background-image: radial-gradient(#d4c7b7 0.75px, transparent 0.75px);
            background-size: 24px 24px;
            overflow-x: hidden;
            line-height: 1.6;
        }

        /* ----------------------------------------------------
           1. TOP GOVERNMENT BAR & INFORMATION OFFICER CORNER
           ---------------------------------------------------- */
        .top-gov-bar {
            background: linear-gradient(90deg, #180204 0%, #370709 50%, #180204 100%);
            color: rgba(255, 255, 255, 0.92);
            font-size: 0.8rem;
            padding: 0.55rem 0;
            border-bottom: 2px solid var(--gold-accent);
            position: relative;
            z-index: 1050;
        }

        .gov-flag-icon {
            width: 24px;
            height: 14px;
            object-fit: cover;
            border-radius: 2px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        /* Information Officer Corner Card / Badge */
        .info-officer-corner {
            background: rgba(239, 190, 44, 0.12);
            border: 1px solid rgba(239, 190, 44, 0.38);
            border-radius: 10px;
            padding: 0.35rem 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 0.65rem;
            transition: all 0.25s ease;
        }

        .info-officer-corner:hover {
            background: rgba(239, 190, 44, 0.2);
            border-color: var(--gold-accent);
        }

        .info-officer-icon-box {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--gold-accent);
            color: #1e0305;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            flex-shrink: 0;
            font-weight: 800;
        }

        .info-officer-tag {
            font-size: 0.68rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--gold-accent);
            line-height: 1.1;
        }

        .info-officer-name {
            font-size: 0.78rem;
            font-weight: 700;
            color: #ffffff;
            line-height: 1.2;
        }

        .info-officer-tel {
            font-size: 0.74rem;
            color: rgba(255, 255, 255, 0.88);
            text-decoration: none;
            transition: color 0.2s;
        }

        .info-officer-tel:hover {
            color: var(--gold-accent);
        }

        /* ----------------------------------------------------
           2. MAIN NAVIGATION (STICKY GLASS)
           ---------------------------------------------------- */
        .main-navbar {
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.08);
            border-bottom: 1px solid rgba(212, 199, 183, 0.4);
            padding: 0.65rem 0;
            transition: all 0.3s ease;
            position: sticky;
            top: 0;
            z-index: 1040;
        }

        .navbar-brand img {
            max-height: 52px;
            width: auto;
            object-fit: contain;
        }

        .nav-link-custom {
            color: #334155 !important;
            font-weight: 600;
            font-size: 0.92rem;
            padding: 0.5rem 1rem !important;
            border-radius: 8px;
            transition: all 0.25s ease;
            position: relative;
        }

        .nav-link-custom:hover {
            color: var(--primary-garnet) !important;
            background: rgba(130, 1, 0, 0.04);
        }

        .nav-link-custom.active {
            color: var(--primary-garnet) !important;
        }

        .nav-link-custom.active::after {
            content: '';
            position: absolute;
            bottom: 2px;
            left: 1rem;
            right: 1rem;
            height: 2.5px;
            background: var(--primary-garnet);
            border-radius: 2px;
        }

        .btn-portal-login-nav {
            background: var(--maroon-gradient);
            color: #ffffff !important;
            font-weight: 700;
            font-size: 0.88rem;
            padding: 0.55rem 1.25rem;
            border-radius: 9999px;
            box-shadow: 0 4px 12px rgba(130, 1, 0, 0.28);
            border: 1px solid rgba(239, 190, 44, 0.5);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-portal-login-nav:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(130, 1, 0, 0.4);
            color: #ffffff !important;
            background: linear-gradient(135deg, #990302 0%, #450a0a 100%);
        }

        /* ----------------------------------------------------
           3. HERO SECTION (SLIDER + EMBEDDED LOGIN)
           ---------------------------------------------------- */
        .hero-section {
            position: relative;
            background: linear-gradient(135deg, rgba(30, 3, 5, 0.96) 0%, rgba(55, 7, 9, 0.92) 50%, rgba(20, 2, 4, 0.98) 100%),
                        url('assets/img/bg.png') repeat center center;
            padding: 2.75rem 0 3.5rem 0;
            color: #ffffff;
            overflow: hidden;
        }

        .hero-ambient-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(100px);
            pointer-events: none;
            opacity: 0.3;
            z-index: 1;
        }

        .hero-orb-1 {
            width: 450px;
            height: 450px;
            background: radial-gradient(circle, #b91c1c, #450a0a);
            top: -120px;
            left: -100px;
            animation: orbFloat 20s infinite alternate ease-in-out;
        }

        .hero-orb-2 {
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, #efbe2c, #78350f);
            bottom: -100px;
            right: -80px;
            animation: orbFloat 24s infinite alternate-reverse ease-in-out;
        }

        @keyframes orbFloat {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(60px, 40px) scale(1.1); }
        }

        /* Hero Slider Card */
        .hero-slider-card {
            background: rgba(255, 255, 255, 0.06);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: var(--radius-xl);
            overflow: hidden;
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.35);
            height: 100%;
            display: flex;
            flex-direction: column;
            position: relative;
            z-index: 5;
        }

        .slider-viewport {
            position: relative;
            height: 380px;
            overflow: hidden;
            border-radius: var(--radius-xl) var(--radius-xl) 0 0;
        }

        @media (min-width: 992px) {
            .slider-viewport {
                height: 430px;
            }
        }

        .hero-slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.8s cubic-bezier(0.4, 0, 0.2, 1), transform 1.2s ease-out;
            transform: scale(1.04);
            background-size: cover;
            background-position: center;
        }

        .hero-slide.active {
            opacity: 1;
            visibility: visible;
            transform: scale(1);
        }

        .hero-slide-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.25) 0%, rgba(30, 4, 6, 0.85) 60%, rgba(15, 2, 3, 0.98) 100%);
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 2.25rem 2rem;
        }

        .slide-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: var(--gold-accent);
            color: #1e0305;
            font-weight: 800;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            padding: 0.35rem 0.85rem;
            border-radius: 9999px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.25);
            margin-bottom: 0.85rem;
            width: fit-content;
        }

        .slide-title {
            font-size: 1.65rem;
            font-weight: 800;
            line-height: 1.25;
            color: #ffffff;
            margin-bottom: 0.6rem;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.5);
        }

        @media (min-width: 768px) {
            .slide-title {
                font-size: 2rem;
            }
        }

        .slide-desc {
            font-size: 0.92rem;
            color: rgba(255, 255, 255, 0.88);
            line-height: 1.5;
            margin-bottom: 1.2rem;
            max-width: 90%;
        }

        .slide-meta-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .slide-pill {
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.22);
            color: rgba(255, 255, 255, 0.95);
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.76rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        /* Slider Controls Footer */
        .slider-controls-bar {
            padding: 1rem 1.75rem;
            background: rgba(15, 23, 42, 0.6);
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .slider-dots {
            display: flex;
            gap: 0.5rem;
        }

        .slider-dot {
            width: 28px;
            height: 6px;
            border-radius: 3px;
            background: rgba(255, 255, 255, 0.3);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .slider-dot.active {
            width: 44px;
            background: var(--gold-accent);
        }

        .slider-arrow-btn {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .slider-arrow-btn:hover {
            background: var(--gold-accent);
            color: #1e0305;
            border-color: var(--gold-accent);
        }

        /* ----------------------------------------------------
           EMBEDDED LOGIN PORTAL CARD
           ---------------------------------------------------- */
        .embedded-login-card {
            background: var(--glass-card);
            backdrop-filter: blur(28px);
            -webkit-backdrop-filter: blur(28px);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-xl);
            padding: 2.25rem 2rem;
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.55),
                        0 10px 25px -5px rgba(55, 7, 9, 0.25),
                        inset 0 1px 0 rgba(255, 255, 255, 0.9);
            position: relative;
            z-index: 10;
            color: #1e293b;
        }

        .login-card-badge {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            padding: 0.3rem 0.85rem;
            border-radius: 9999px;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .login-card-title {
            font-size: 1.45rem;
            font-weight: 800;
            color: var(--primary-maroon);
            margin-top: 0.5rem;
            margin-bottom: 0.25rem;
            letter-spacing: -0.02em;
        }

        .login-card-subtitle {
            font-size: 0.84rem;
            color: #64748b;
            margin-bottom: 1.25rem;
        }

        .form-label-custom {
            font-size: 0.76rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #475569;
            margin-bottom: 0.35rem;
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .form-label-custom i {
            color: var(--primary-garnet);
            font-size: 0.85rem;
        }

        .input-group-custom {
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            transition: all 0.25s ease;
        }

        .input-group-custom .input-group-text {
            background-color: #f8fafc;
            border: 1.5px solid var(--input-border);
            border-right: none;
            border-top-left-radius: 12px;
            border-bottom-left-radius: 12px;
            color: #64748b;
            padding: 0.65rem 0.9rem;
            font-size: 0.95rem;
        }

        .input-group-custom .form-control,
        .input-group-custom .form-select {
            border: 1.5px solid var(--input-border);
            border-left: none;
            border-top-right-radius: 12px;
            border-bottom-right-radius: 12px;
            padding: 0.65rem 0.95rem;
            font-size: 0.92rem;
            font-weight: 500;
            color: #1e293b;
            background-color: #ffffff;
            transition: all 0.25s ease;
        }

        .input-group-custom .form-control:focus,
        .input-group-custom .form-select:focus {
            border-color: var(--primary-garnet);
            background-color: #ffffff;
            box-shadow: none;
        }

        .input-group-custom:focus-within {
            box-shadow: 0 0 0 3px rgba(130, 1, 0, 0.15);
        }

        .input-group-custom:focus-within .input-group-text {
            border-color: var(--primary-garnet);
            background-color: #fff1f2;
            color: var(--primary-garnet);
        }

        .input-group-custom:focus-within .form-control,
        .input-group-custom:focus-within .form-select {
            border-color: var(--primary-garnet);
        }

        .btn-toggle-password {
            background-color: #ffffff;
            border: 1.5px solid var(--input-border);
            border-left: none;
            border-top-right-radius: 12px;
            border-bottom-right-radius: 12px;
            color: #64748b;
            padding: 0 0.9rem;
            font-size: 0.95rem;
        }

        .btn-portal-submit {
            background: var(--maroon-gradient);
            color: #ffffff;
            font-weight: 700;
            font-size: 0.95rem;
            border: 1px solid rgba(239, 190, 44, 0.4);
            border-radius: 12px;
            padding: 0.8rem 1.25rem;
            box-shadow: 0 6px 18px rgba(130, 1, 0, 0.32);
            transition: all 0.3s ease;
        }

        .btn-portal-submit:hover {
            background: linear-gradient(135deg, #a30807 0%, #4a090b 100%);
            box-shadow: 0 10px 24px rgba(130, 1, 0, 0.45);
            transform: translateY(-1.5px);
            color: #ffffff;
        }

        .portal-alert-danger {
            background: #fff1f2;
            color: #991b1b;
            border: 1.5px solid #fecdd3;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 0.75rem 1rem;
            margin-bottom: 1.25rem;
            animation: alertShake 0.4s ease-in-out;
        }

        @keyframes alertShake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-5px); }
            40%, 80% { transform: translateX(5px); }
        }

        .dynamic-field-group {
            display: none;
        }

        /* ----------------------------------------------------
           4. STATS COUNTER STRIP
           ---------------------------------------------------- */
        .stats-counter-strip {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            position: relative;
            z-index: 20;
            margin-top: -24px;
            border-radius: var(--radius-lg);
            max-width: 1280px;
            margin-left: auto;
            margin-right: auto;
            padding: 1.5rem 1rem;
        }

        .stat-item {
            text-align: center;
            padding: 0.75rem 1rem;
            border-right: 1px solid #f1f5f9;
        }

        .stat-item:last-child {
            border-right: none;
        }

        .stat-num {
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--primary-maroon);
            line-height: 1;
            margin-bottom: 0.35rem;
            font-feature-settings: "tnum";
        }

        .stat-label {
            font-size: 0.82rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748b;
        }

        .stat-sub {
            font-size: 0.74rem;
            color: #94a3b8;
        }

        /* ----------------------------------------------------
           5. SECTION COMMONS & VISION / MISSION
           ---------------------------------------------------- */
        .section-padding {
            padding: 4.5rem 0;
        }

        .section-badge {
            background: rgba(130, 1, 0, 0.08);
            color: var(--primary-garnet);
            font-weight: 800;
            font-size: 0.78rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 0.35rem 0.95rem;
            border-radius: 9999px;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            margin-bottom: 0.75rem;
            border: 1px solid rgba(130, 1, 0, 0.15);
        }

        .section-title {
            font-size: 2.15rem;
            font-weight: 800;
            color: var(--primary-maroon);
            letter-spacing: -0.03em;
            line-height: 1.25;
            margin-bottom: 0.75rem;
        }

        .section-subtitle {
            font-size: 1.05rem;
            color: #64748b;
            max-width: 720px;
            margin: 0 auto 2.5rem auto;
            line-height: 1.6;
        }

        .vision-mission-card {
            background: #ffffff;
            border-radius: var(--radius-xl);
            padding: 2.5rem 2.25rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
            height: 100%;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .vision-mission-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 18px 40px rgba(55, 7, 9, 0.1);
        }

        .vision-mission-card.vision-card {
            border-top: 5px solid var(--gold-accent);
        }

        .vision-mission-card.mission-card {
            border-top: 5px solid var(--primary-garnet);
        }

        .vm-icon-box {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
        }

        .vision-card .vm-icon-box {
            background: #fefce8;
            color: #ca8a04;
            border: 1px solid #fef08a;
        }

        .mission-card .vm-icon-box {
            background: #fff1f2;
            color: var(--primary-garnet);
            border: 1px solid #fecdd3;
        }

        /* ----------------------------------------------------
           6. DAPH PHOTO GALLERY (PURE IMAGES ONLY)
           ---------------------------------------------------- */
        .gallery-photo-card {
            border-radius: var(--radius-lg);
            overflow: hidden;
            background-color: #0f172a;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
            transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
            cursor: pointer;
            position: relative;
            height: 250px;
        }

        .gallery-photo-card:hover {
            transform: translateY(-6px) scale(1.02);
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.2);
        }

        .gallery-photo-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .gallery-photo-card:hover .gallery-photo-img {
            transform: scale(1.08);
        }

        .gallery-photo-zoom-overlay {
            position: absolute;
            inset: 0;
            background: rgba(55, 7, 9, 0.45);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .gallery-photo-zoom-overlay i {
            color: #ffffff;
            font-size: 2rem;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(8px);
            padding: 0.75rem;
            border-radius: 50%;
            border: 1px solid rgba(255, 255, 255, 0.4);
            transform: scale(0.8);
            transition: transform 0.3s ease;
        }

        .gallery-photo-card:hover .gallery-photo-zoom-overlay {
            opacity: 1;
        }

        .gallery-photo-card:hover .gallery-photo-zoom-overlay i {
            transform: scale(1);
        }

        /* ----------------------------------------------------
           7. LEADERSHIP SECTION (EXPANDED TO 4 OFFICERS)
           ---------------------------------------------------- */
        .leadership-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.06);
            transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
            height: 100%;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .leadership-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px -10px rgba(55, 7, 9, 0.18);
            border-color: rgba(130, 1, 0, 0.3);
        }

        .leadership-card.director-highlight {
            border: 2px solid var(--gold-accent);
            box-shadow: 0 16px 36px -8px rgba(130, 1, 0, 0.18);
        }

        .director-top-ribbon {
            background: linear-gradient(90deg, #b08723 0%, #efbe2c 50%, #b08723 100%);
            color: #1e0305;
            font-weight: 800;
            font-size: 0.72rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            padding: 0.3rem 0.5rem;
            text-align: center;
        }

        .leadership-img-wrapper {
            position: relative;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            height: 210px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid #f1f5f9;
        }

        .leadership-avatar-svg {
            width: 105px;
            height: 105px;
            border-radius: 50%;
            padding: 6px;
            background: #ffffff;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            border: 2px solid var(--stone-neutral);
            transition: transform 0.3s ease;
        }

        .leadership-card:hover .leadership-avatar-svg {
            transform: scale(1.08);
            border-color: var(--primary-garnet);
        }

        .leadership-badge-role {
            position: absolute;
            bottom: 12px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(8px);
            color: #ffffff;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            white-space: nowrap;
            letter-spacing: 0.02em;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .director-highlight .leadership-badge-role {
            background: var(--maroon-gradient);
            border-color: var(--gold-accent);
            color: #ffffff;
        }

        .leadership-body {
            padding: 1.5rem 1.25rem;
            flex: 1 0 auto;
            display: flex;
            flex-direction: column;
            text-align: center;
        }

        .leadership-name {
            font-size: 1.12rem;
            font-weight: 800;
            color: var(--primary-maroon);
            margin-bottom: 0.25rem;
        }

        .leadership-title {
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--primary-garnet);
            margin-bottom: 0.75rem;
            line-height: 1.4;
        }

        .leadership-desc {
            font-size: 0.84rem;
            color: #64748b;
            line-height: 1.5;
            margin-bottom: 1.25rem;
            flex-grow: 1;
        }

        .leadership-footer-meta {
            padding-top: 0.85rem;
            border-top: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            font-size: 0.78rem;
            color: #64748b;
        }

        .leadership-footer-meta i {
            color: var(--gold-amber);
        }

        /* ----------------------------------------------------
           8. FOOTER & COPYRIGHT
           ---------------------------------------------------- */
        .main-portal-footer {
            background: linear-gradient(180deg, #180305 0%, #0d0102 100%);
            color: rgba(255, 255, 255, 0.78);
            border-top: 3px solid var(--gold-accent);
            padding: 3.5rem 0 2rem 0;
            font-size: 0.88rem;
        }

        .footer-logo-title {
            color: #ffffff;
            font-size: 1.15rem;
            font-weight: 800;
            margin-bottom: 0.35rem;
        }

        .footer-logo-sub {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.65);
            line-height: 1.5;
            margin-bottom: 1rem;
        }

        .footer-heading {
            color: #ffffff;
            font-size: 0.92rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 1rem;
            position: relative;
            padding-bottom: 0.4rem;
        }

        .footer-heading::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 28px;
            height: 2px;
            background: var(--gold-accent);
        }

        .footer-links-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-links-list li {
            margin-bottom: 0.55rem;
        }

        .footer-links-list a {
            color: rgba(255, 255, 255, 0.75);
            text-decoration: none;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .footer-links-list a:hover {
            color: var(--gold-accent);
            transform: translateX(3px);
        }

        .footer-bottom-bar {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: 2.5rem;
            padding-top: 1.5rem;
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.6);
        }

        /* ----------------------------------------------------
           9. LIGHTBOX MODAL
           ---------------------------------------------------- */
        .modal-gallery-preview .modal-content {
            background: #0f172a;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: var(--radius-lg);
            overflow: hidden;
            color: #ffffff;
        }

        .modal-gallery-img {
            max-height: 80vh;
            width: 100%;
            object-fit: contain;
            background-color: #020617;
        }

        .floating-login-fab {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 999;
            background: var(--maroon-gradient);
            color: #ffffff;
            border: 2px solid var(--gold-accent);
            border-radius: 9999px;
            padding: 0.75rem 1.4rem;
            font-size: 0.9rem;
            font-weight: 700;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.35);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .floating-login-fab:hover {
            transform: translateY(-4px) scale(1.02);
            color: #ffffff;
            box-shadow: 0 16px 32px rgba(130, 1, 0, 0.5);
        }

        @media (max-width: 991px) {
            .embedded-login-card {
                margin-top: 2rem;
            }
        }
    </style>
</head>

<body>



    <!-- 2. MAIN NAVIGATION (Cleaned up: Home, Vision & Mission, Gallery, Leadership, Staff Login) -->
    <nav class="navbar navbar-expand-lg main-navbar">
        <div class="container-fluid px-lg-5">
            <a class="navbar-brand d-flex align-items-center gap-2" href="#home">
                <img src="assets/img/Government Department of Animal Production and Health – Eastern Province, Sri Lanka(black).png" alt="Department of Animal Production & Health - Eastern Province" class="img-fluid">
            </a>

            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#landingNavbar">
                <i class="bi bi-list fs-2 text-dark"></i>
            </button>

            <div class="collapse navbar-collapse" id="landingNavbar">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0 align-items-lg-center">
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom active" href="#home"><i class="bi bi-house-door me-1"></i> Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom" href="#vision-mission"><i class="bi bi-compass me-1"></i> Vision & Mission</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom" href="#gallery"><i class="bi bi-images me-1"></i> Gallery</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom" href="#leadership"><i class="bi bi-people me-1"></i> Leadership</a>
                    </li>
                </ul>

            </div>
        </div>
    </nav>

    <!-- 3. HERO SECTION WITH DYNAMIC SLIDER & INTEGRATED LOGIN PORTAL -->
    <section class="hero-section" id="home">
        <div class="hero-ambient-orb hero-orb-1"></div>
        <div class="hero-ambient-orb hero-orb-2"></div>

        <div class="container-fluid px-lg-5 position-relative" style="z-index: 10;">
            <div class="row g-4 align-items-center">

                <!-- LEFT COLUMN: TOP INITIATIVES SLIDER -->
                <div class="col-lg-7">
                    <div class="mb-3 d-flex align-items-center gap-2">
                        <span class="badge bg-warning text-dark fw-bold px-3 py-1.5 rounded-pill text-uppercase">
                            <i class="bi bi-award-fill me-1"></i> DAPH Eastern Province
                        </span>
                        <span class="text-white-50 small d-none d-sm-inline">
                            Empowering Livestock Health & Rural Prosperity
                        </span>
                    </div>

                    <h1 class="display-6 fw-extrabold text-white mb-3" style="letter-spacing: -0.02em;">
                        Provincial Department of Animal Production & Health
                    </h1>
                    <p class="text-white-50 mb-4 fs-6" style="max-width: 650px;">
                        Serving Ampara, Batticaloa, and Trincomalee with 45 Veterinary Ranges, regional farms, and automated clinical MIS services.
                    </p>

                    <!-- Interactive Slider Card -->
                    <div class="hero-slider-card">
                        <div class="slider-viewport" id="heroSliderViewport">

                            <!-- Slide 1: Veterinary Clinical Healthcare -->
                            <div class="hero-slide active" style="background-image: url('assets/img/slide01.jpg');">
                                <div class="hero-slide-overlay">
                                    <span class="slide-badge"><i class="bi bi-heart-pulse-fill"></i> Veterinary Healthcare</span>
                                    <h2 class="slide-title">45 Range Offices & Mobile Clinical Operations</h2>
                                    
                                </div>
                            </div>

                            <!-- Slide 2: Modern Laboratory Diagnostics -->
                            <div class="hero-slide" style="background-image: url('assets/img/slide02.jpg');">
                                <div class="hero-slide-overlay">
                                    <span class="slide-badge"><i class="bi bi-search"></i> Disease Surveillance</span>
                                    <h2 class="slide-title">Provincial Epidemiology & Clinical Diagnostics</h2>
                                    
                                </div>
                            </div>

                            <!-- Slide 3: Livestock Breeding & Dairy Development -->
                            <div class="hero-slide" style="background-image: url('assets/img/slide03.jpg');">
                                <div class="hero-slide-overlay">
                                    <span class="slide-badge"><i class="bi bi-droplet-fill"></i> Dairy Productivity</span>
                                    <h2 class="slide-title">Artificial Insemination & Breed Improvement</h2>
                                    
                                </div>
                            </div>

                            <!-- Slide 4: Farmer Training & Extension -->
                            <div class="hero-slide" style="background-image: url('assets/img/slide04.jpg');">
                                <div class="hero-slide-overlay">
                                    <span class="slide-badge"><i class="bi bi-mortarboard-fill"></i> Capacity Building</span>
                                    <h2 class="slide-title">Vaccination & Disease Control</h2>
                                    
                                </div>
                            </div>

                            <!-- Slide 5: Digital MIS Portal Infrastructure -->
                            <div class="hero-slide" style="background-image: url('assets/img/slide05.jpg');">
                                <div class="hero-slide-overlay">
                                    <span class="slide-badge"><i class="bi bi-laptop"></i> E-Governance</span>
                                    <h2 class="slide-title">Integrated Digital MIS Platform</h2>
                                    
                                </div>
                            </div>

                        </div>

                        <!-- Slider Controls & Progress -->
                        <div class="slider-controls-bar">
                            <div class="slider-dots" id="heroSliderDots">
                                <div class="slider-dot active" data-index="0"></div>
                                <div class="slider-dot" data-index="1"></div>
                                <div class="slider-dot" data-index="2"></div>
                                <div class="slider-dot" data-index="3"></div>
                                <div class="slider-dot" data-index="4"></div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="slider-arrow-btn" id="btnPrevSlide" title="Previous Slide">
                                    <i class="bi bi-chevron-left"></i>
                                </button>
                                <button type="button" class="slider-arrow-btn" id="btnNextSlide" title="Next Slide">
                                    <i class="bi bi-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN: INTEGRATED STAFF LOGIN PORTAL -->
                <div class="col-lg-5" id="loginPortal">
                    <div class="embedded-login-card shadow-lg" id="loginCard">

                        <!-- Header & Brand -->
                        <div class="text-center mb-3">
                            <img src="assets/img/Government Department of Animal Production and Health – Eastern Province, Sri Lanka(black).png" alt="DAPH Eastern Province" class="img-fluid mb-2" style="max-height: 54px;">
                            <div>
                                <span class="login-card-badge">
                                    <i class="bi bi-shield-lock-fill"></i> Staff Authentication Portal
                                </span>
                            </div>
                            <h2 class="login-card-title">MIS Portal Sign In</h2>
                            <p class="login-card-subtitle">Secure access for veterinary, farm, and administrative personnel</p>
                        </div>

                        <!-- Error Banner (Preserved) -->
                        <?php if (!empty($login_error)): ?>
                            <div class="alert portal-alert-danger alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
                                <i class="bi bi-exclamation-triangle-fill flex-shrink-0 fs-5"></i>
                                <div><?= htmlspecialchars($login_error) ?></div>
                                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Login Form (Direct System Integration) -->
                        <form action="auth/login_process.php" method="POST" autocomplete="off" id="portalLoginForm">

                            <!-- User Category Selection -->
                            <div class="mb-3">
                                <label class="form-label-custom" for="userCategory">
                                    <i class="bi bi-person-badge-fill"></i> User Category <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-custom">
                                    <span class="input-group-text"><i class="bi bi-grid-fill"></i></span>
                                    <select name="user_category" id="userCategory" class="form-select" required>
                                        <option value="">-- Select User Category --</option>
                                        <option value="provincial_director">1. Provincial Director</option>
                                        <option value="additional_provincial_director">2. Additional Provincial Director</option>
                                        <option value="subject_matter_specialist">3. Subject Matter Specialist</option>
                                        <option value="deputy_director_hq_1">4. Deputy Director - H/Q-1</option>
                                        <option value="deputy_director_hq_2">5. Deputy Director - H/Q-2</option>
                                        <option value="deputy_director_district">6. Deputy Director - District</option>
                                        <option value="administrator">7. Administrator</option>
                                        <option value="accounts_branch">8. Accounts Branch</option>
                                        <option value="range_veterinary_officer">9. Range Veterinary Officer</option>
                                        <option value="training_centers">10. Training Centers</option>
                                        <option value="regional_farms">11. Regional Farms</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Dynamic Group: District Dropdown -->
                            <div id="districtGroup" class="mb-3 dynamic-field-group">
                                <label class="form-label-custom" for="districtSelect">
                                    <i class="bi bi-geo-alt-fill"></i> Select District <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-custom">
                                    <span class="input-group-text"><i class="bi bi-map-fill"></i></span>
                                    <select name="district_id" id="districtSelect" class="form-select">
                                        <option value="">-- Choose District --</option>
                                        <?php
                                        $dist_res = $mysqli->query("SELECT id, name FROM districts ORDER BY name ASC");
                                        if ($dist_res) {
                                            while ($row = $dist_res->fetch_assoc()) {
                                                echo "<option value='" . $row['id'] . "'>" . htmlspecialchars($row['name']) . "</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Dynamic Group: VS Range Office Dropdown -->
                            <div id="rangeGroup" class="mb-3 dynamic-field-group">
                                <label class="form-label-custom" for="rangeSelect">
                                    <i class="bi bi-hospital-fill"></i> VS Range Office <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-custom">
                                    <span class="input-group-text"><i class="bi bi-building"></i></span>
                                    <select name="range_id" id="rangeSelect" class="form-select">
                                        <option value="">-- Select Range Office --</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Dynamic Group: Training Center Location -->
                            <div id="trainingCenterLocationGroup" class="mb-3 dynamic-field-group">
                                <label class="form-label-custom" for="trainingCenterLocation">
                                    <i class="bi bi-pin-map-fill"></i> Training Center Location <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-custom">
                                    <span class="input-group-text"><i class="bi bi-geo"></i></span>
                                    <select name="training_center_location" id="trainingCenterLocation" class="form-select">
                                        <option value="">-- Select Center Location --</option>
                                        <?php
                                        $tc_loc_res = $mysqli->query("SELECT DISTINCT location FROM training_centers WHERE is_active = 1 AND location IS NOT NULL AND location <> '' ORDER BY location ASC");
                                        if ($tc_loc_res) {
                                            while ($row = $tc_loc_res->fetch_assoc()) {
                                                echo "<option value='" . htmlspecialchars($row['location'], ENT_QUOTES, 'UTF-8') . "'>" . htmlspecialchars($row['location']) . "</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Dynamic Group: Training Center Name -->
                            <div id="trainingCenterGroup" class="mb-3 dynamic-field-group">
                                <label class="form-label-custom" for="trainingCenterSelect">
                                    <i class="bi bi-mortarboard-fill"></i> Center Name <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-custom">
                                    <span class="input-group-text"><i class="bi bi-award-fill"></i></span>
                                    <select name="training_center_id" id="trainingCenterSelect" class="form-select">
                                        <option value="">-- Select Training Center --</option>
                                        <?php
                                        $tc_res = $mysqli->query("SELECT id, center_name, location FROM training_centers WHERE is_active = 1 ORDER BY id ASC");
                                        if ($tc_res) {
                                            while ($row = $tc_res->fetch_assoc()) {
                                                echo "<option value='" . $row['id'] . "' data-location='" . htmlspecialchars($row['location'], ENT_QUOTES, 'UTF-8') . "'>" . htmlspecialchars($row['center_name']) . "</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Dynamic Group: Regional Farm -->
                            <div id="farmGroup" class="mb-3 dynamic-field-group">
                                <label class="form-label-custom" for="farmSelect">
                                    <i class="bi bi-tree-fill"></i> Regional Farm Name <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-custom">
                                    <span class="input-group-text"><i class="bi bi-flower1"></i></span>
                                    <select name="farm_id" id="farmSelect" class="form-select">
                                        <option value="">-- Select Farm Name --</option>
                                        <?php
                                        $farm_res = $mysqli->query("SELECT id, farm_name FROM regional_farms WHERE is_active = 1 ORDER BY id ASC");
                                        if ($farm_res) {
                                            while ($row = $farm_res->fetch_assoc()) {
                                                echo "<option value='" . $row['id'] . "'>" . htmlspecialchars($row['farm_name']) . "</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Username or Email -->
                            <div class="mb-3">
                                <label class="form-label-custom" for="loginId">
                                    <i class="bi bi-person-fill"></i> Username or Email <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-custom">
                                    <span class="input-group-text"><i class="bi bi-envelope-at-fill"></i></span>
                                    <input type="text" name="login_id" id="loginId" class="form-control" placeholder="Enter username or email" required>
                                </div>
                            </div>

                            <!-- Password with Show/Hide Toggle -->
                            <div class="mb-4">
                                <label class="form-label-custom" for="loginPassword">
                                    <i class="bi bi-key-fill"></i> Password <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-custom">
                                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                    <input type="password" name="password" id="loginPassword" class="form-control" placeholder="Enter password" required>
                                    <button type="button" class="btn btn-toggle-password" id="btnTogglePassword" title="Show or hide password" tabindex="-1">
                                        <i class="bi bi-eye-fill" id="togglePasswordIcon"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" class="btn btn-portal-submit w-100 d-flex align-items-center justify-content-center gap-2">
                                <i class="bi bi-box-arrow-in-right fs-5"></i>
                                <span>Authenticate & Enter MIS</span>
                            </button>

                        </form>

                        <div class="text-center mt-3 pt-3 border-top">
                            <span class="text-muted small">
                                <i class="bi bi-shield-check text-success me-1"></i> SLCERT & ISO 27001 Standard Compliant Session
                            </span>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- 5. VISION & MISSION SECTION (About details removed as requested) -->
    <section class="section-padding" id="vision-mission">
        <div class="container-fluid px-lg-5">
            <div class="text-center">
                <span class="section-badge"><i class="bi bi-compass-fill"></i> Strategic Direction</span>
                <h2 class="section-title">Vision & Mission</h2>
            </div>

            <div class="row g-4 mt-2 justify-content-center">
                <!-- Vision Card -->
                <div class="col-lg-6 col-md-6">
                    <div class="vision-mission-card vision-card">
                        <div class="vm-icon-box">
                            <i class="bi bi-eye-fill"></i>
                        </div>
                        <h3 class="h3 fw-bold text-dark mb-3">Our Vision</h3>
                        <p class="text-muted fs-6 mb-0 leading-relaxed">
                            To achieve self-reliance and excellence in livestock production, animal health security, and economic prosperity for the farming communities of the Eastern Province.
                        </p>
                    </div>
                </div>

                <!-- Mission Card -->
                <div class="col-lg-6 col-md-6">
                    <div class="vision-mission-card mission-card">
                        <div class="vm-icon-box">
                            <i class="bi bi-bullseye"></i>
                        </div>
                        <h3 class="h3 fw-bold text-dark mb-3">Our Mission</h3>
                        <p class="text-muted fs-6 mb-0 leading-relaxed">
                            Deliver exemplary veterinary clinical healthcare, maintain robust animal disease surveillance, spearhead artificial breeding technologies, and extend farmer education through sustainable digital governance.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 6. DAPH PHOTO GALLERY (ONLY IMAGES, NO EXTRA CARDS OR CATEGORIES) -->
    <section class="section-padding" id="gallery" style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0;">
        <div class="container-fluid px-lg-5">
            <div class="text-center">
                <span class="section-badge"><i class="bi bi-camera-fill"></i> Photographic Showcase</span>
                <h2 class="section-title">DAPH Photo Gallery</h2>
            </div>

            <!-- Image-only grid (no filters, no titles/text items) -->
            <div class="row g-4 mt-1">

                <!-- Photo 1: Diagnostics Lab -->
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                    <div class="gallery-photo-card" onclick="openLightbox('assets/uploads/profile_images/profile_20_1777526035.png')">
                        <img src="assets/uploads/profile_images/profile_20_1777526035.png" alt="DAPH Veterinary Diagnostics Lab" class="gallery-photo-img">
                        <div class="gallery-photo-zoom-overlay"><i class="bi bi-arrows-fullscreen"></i></div>
                    </div>
                </div>

                <!-- Photo 2: Field Veterinary Care -->
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                    <div class="gallery-photo-card" onclick="openLightbox('https://images.unsplash.com/photo-1628088062854-d1870b4553da?auto=format&fit=crop&w=1200&q=80')">
                        <img src="https://images.unsplash.com/photo-1628088062854-d1870b4553da?auto=format&fit=crop&w=800&q=80" alt="DAPH Veterinary Clinical Outreach" class="gallery-photo-img">
                        <div class="gallery-photo-zoom-overlay"><i class="bi bi-arrows-fullscreen"></i></div>
                    </div>
                </div>

                <!-- Photo 3: Cattle Herd Care -->
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                    <div class="gallery-photo-card" onclick="openLightbox('https://images.unsplash.com/photo-1570042225831-d98fa7577f1e?auto=format&fit=crop&w=1200&q=80')">
                        <img src="https://images.unsplash.com/photo-1570042225831-d98fa7577f1e?auto=format&fit=crop&w=800&q=80" alt="DAPH Dairy Cattle Management" class="gallery-photo-img">
                        <div class="gallery-photo-zoom-overlay"><i class="bi bi-arrows-fullscreen"></i></div>
                    </div>
                </div>

                <!-- Photo 4: Artificial Insemination & Pasture -->
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                    <div class="gallery-photo-card" onclick="openLightbox('https://images.unsplash.com/photo-1500595046743-cd271d694d30?auto=format&fit=crop&w=1200&q=80')">
                        <img src="https://images.unsplash.com/photo-1500595046743-cd271d694d30?auto=format&fit=crop&w=800&q=80" alt="DAPH Pasture and Dairy Cattle" class="gallery-photo-img">
                        <div class="gallery-photo-zoom-overlay"><i class="bi bi-arrows-fullscreen"></i></div>
                    </div>
                </div>

                <!-- Photo 5: Farmer Training Session -->
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                    <div class="gallery-photo-card" onclick="openLightbox('https://images.unsplash.com/photo-1544717305-2782549b5136?auto=format&fit=crop&w=1200&q=80')">
                        <img src="https://images.unsplash.com/photo-1544717305-2782549b5136?auto=format&fit=crop&w=800&q=80" alt="DAPH Farmer Training Session" class="gallery-photo-img">
                        <div class="gallery-photo-zoom-overlay"><i class="bi bi-arrows-fullscreen"></i></div>
                    </div>
                </div>

                <!-- Photo 6: Regional Poultry Hatcheries -->
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                    <div class="gallery-photo-card" onclick="openLightbox('https://images.unsplash.com/photo-1548550023-2bdb3c5beed7?auto=format&fit=crop&w=1200&q=80')">
                        <img src="https://images.unsplash.com/photo-1548550023-2bdb3c5beed7?auto=format&fit=crop&w=800&q=80" alt="DAPH Poultry Hatchery" class="gallery-photo-img">
                        <div class="gallery-photo-zoom-overlay"><i class="bi bi-arrows-fullscreen"></i></div>
                    </div>
                </div>

                <!-- Photo 7: Fodder and Pasture Lands -->
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                    <div class="gallery-photo-card" onclick="openLightbox('https://images.unsplash.com/photo-1500382017468-9049fed747ef?auto=format&fit=crop&w=1200&q=80')">
                        <img src="https://images.unsplash.com/photo-1500382017468-9049fed747ef?auto=format&fit=crop&w=800&q=80" alt="DAPH Fodder Plots" class="gallery-photo-img">
                        <div class="gallery-photo-zoom-overlay"><i class="bi bi-arrows-fullscreen"></i></div>
                    </div>
                </div>

                <!-- Photo 8: Secure MIS Operations -->
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                    <div class="gallery-photo-card" onclick="openLightbox('assets/uploads/profile_images/profile_50_1786605206.jpg')">
                        <img src="assets/uploads/profile_images/profile_50_1786605206.jpg" alt="DAPH Digital MIS Central Server" class="gallery-photo-img">
                        <div class="gallery-photo-zoom-overlay"><i class="bi bi-arrows-fullscreen"></i></div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- 7. LEADERSHIP SECTION (PROVINCIAL DIRECTOR + 3 ADDITIONAL OFFICERS) -->
    <section class="section-padding bg-white" id="leadership">
        <div class="container-fluid px-lg-5">
            <div class="text-center">
                <span class="section-badge"><i class="bi bi-person-workspace"></i> Department Directorate</span>
                <h2 class="section-title">Executive Leadership</h2>
                <p class="section-subtitle">
                    Guiding animal production, disease prevention frameworks, and digital administration across the Eastern Province.
                </p>
            </div>

            <!-- 4 Officers in a unified row/grid -->
            <div class="row g-4">

                <!-- 1. Provincial Director -->
                <div class="col-xl-3 col-md-6">
                    <div class="leadership-card director-highlight">

                        <div class="leadership-img-wrapper" style="background: radial-gradient(circle, #fff1f2 0%, #fee2e2 100%);">
                            <svg class="leadership-avatar-svg" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="50" cy="50" r="48" fill="#370709" stroke="#efbe2c" stroke-width="4"/>
                                <circle cx="50" cy="36" r="18" fill="#fde047"/>
                                <path d="M22 84C22 66 34 58 50 58C66 58 78 66 78 84" fill="#efbe2c"/>
                                <circle cx="50" cy="35" r="14" fill="#370709"/>
                            </svg>
                            <span class="leadership-badge-role">
                                <i class="bi bi-award-fill text-warning me-1"></i> Provincial Director
                            </span>
                        </div>
                        <div class="leadership-body">
                            <h3 class="leadership-name">Dr. (Mrs.) M. A. Nihal</h3>
                            <div class="leadership-title">Provincial Director of Animal Production & Health</div>

                        </div>
                    </div>
                </div>

                <!-- 2. Deputy Director - H/Q-1 (Animal Health & Extension) -->
                <div class="col-xl-3 col-md-6">
                    <div class="leadership-card">
                        <div class="leadership-img-wrapper" style="background: radial-gradient(circle, #f8fafc 0%, #e2e8f0 100%);">
                            <svg class="leadership-avatar-svg" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="50" cy="50" r="48" fill="#003ddc" stroke="#cbd5e1" stroke-width="3"/>
                                <circle cx="50" cy="36" r="18" fill="#93c5fd"/>
                                <path d="M22 84C22 66 34 58 50 58C66 58 78 66 78 84" fill="#93c5fd"/>
                                <circle cx="50" cy="35" r="14" fill="#003ddc"/>
                            </svg>
                            <span class="leadership-badge-role">
                                <i class="bi bi-heart-pulse-fill text-danger me-1"></i> Deputy Director H/Q-1
                            </span>
                        </div>
                        <div class="leadership-body">
                            <h3 class="leadership-name">Dr. K. Pushpakumaran</h3>
                            <div class="leadership-title">Deputy Director (Animal Health & Veterinary Services)</div>

                        </div>
                    </div>
                </div>

                <!-- 3. Deputy Director - H/Q-2 (Planning & Development) -->
                <div class="col-xl-3 col-md-6">
                    <div class="leadership-card">
                        <div class="leadership-img-wrapper" style="background: radial-gradient(circle, #f8fafc 0%, #e2e8f0 100%);">
                            <svg class="leadership-avatar-svg" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="50" cy="50" r="48" fill="#1e293b" stroke="#cbd5e1" stroke-width="3"/>
                                <circle cx="50" cy="36" r="18" fill="#cbd5e1"/>
                                <path d="M22 84C22 66 34 58 50 58C66 58 78 66 78 84" fill="#cbd5e1"/>
                                <circle cx="50" cy="35" r="14" fill="#1e293b"/>
                            </svg>
                            <span class="leadership-badge-role">
                                <i class="bi bi-graph-up text-info me-1"></i> Deputy Director H/Q-2
                            </span>
                        </div>
                        <div class="leadership-body">
                            <h3 class="leadership-name">Mr. R. Jeyakanth</h3>
                            <div class="leadership-title">Deputy Director (Planning & Development)</div>

                        </div>
                    </div>
                </div>

                <!-- 4. Subject Matter Specialist (Veterinary Epidemiology & Production) -->
                <div class="col-xl-3 col-md-6">
                    <div class="leadership-card">
                        <div class="leadership-img-wrapper" style="background: radial-gradient(circle, #f8fafc 0%, #e2e8f0 100%);">
                            <svg class="leadership-avatar-svg" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="50" cy="50" r="48" fill="#065f46" stroke="#cbd5e1" stroke-width="3"/>
                                <circle cx="50" cy="36" r="18" fill="#6ee7b7"/>
                                <path d="M22 84C22 66 34 58 50 58C66 58 78 66 78 84" fill="#6ee7b7"/>
                                <circle cx="50" cy="35" r="14" fill="#065f46"/>
                            </svg>
                            <span class="leadership-badge-role">
                                <i class="bi bi-mortarboard text-success me-1"></i> Subject Matter Specialist
                            </span>
                        </div>
                        <div class="leadership-body">
                            <h3 class="leadership-name">Dr. T. Vigneswaran</h3>
                            <div class="leadership-title">Senior Subject Matter Specialist (Epidemiology)</div>

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- 8. OFFICIAL FOOTER -->
    <footer class="main-portal-footer">
        <div class="container-fluid px-lg-5">
            <div class="row g-4 justify-content-between">
                <div class="col-lg-5 mb-2">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <img src="assets/img/top-sl-flog.png.png" alt="Sri Lanka" class="gov-flag-icon">
                        <span class="footer-logo-title">DAPH Eastern Province</span>
                    </div>
                    <p class="footer-logo-sub">
                        Department of Animal Production & Health • Eastern Provincial Council, Sri Lanka.<br>
                        Inner Harbour Road, Trincomalee.
                    </p>
                    <div class="d-flex gap-2">
                        <span class="badge bg-secondary bg-opacity-25 text-white border border-light border-opacity-25 py-2 px-3">
                            <i class="bi bi-cpu-fill text-warning me-1"></i> Centralized MIS
                        </span>
                        <span class="badge bg-secondary bg-opacity-25 text-white border border-light border-opacity-25 py-2 px-3">
                            <i class="bi bi-shield-lock-fill text-success me-1"></i> SSL 256-Bit
                        </span>
                    </div>
                </div>

                <div class="col-lg-3 col-md-5">
                    <h5 class="footer-heading">Navigation</h5>
                    <ul class="footer-links-list">
                        <li><a href="#home"><i class="bi bi-chevron-right"></i> Home</a></li>
                        <li><a href="#vision-mission"><i class="bi bi-chevron-right"></i> Vision & Mission</a></li>
                        <li><a href="#gallery"><i class="bi bi-chevron-right"></i> Photo Gallery</a></li>
                        <li><a href="#leadership"><i class="bi bi-chevron-right"></i> Leadership</a></li>
                        <li><a href="#loginPortal"><i class="bi bi-chevron-right"></i> Staff MIS Login</a></li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-5">
                    <h5 class="footer-heading">Information Officer</h5>
                    <p class="small text-white-50 mb-1">
                        Right to Information (RTI) Inquiries:
                    </p>
                    <div class="fw-bold text-white small mb-1">Dr. (Mr) M.A. Nadheer</div>
                    <a href="tel:0262225217" class="text-warning text-decoration-none small d-inline-block">
                        <i class="bi bi-telephone-fill me-1"></i>026-2225217
                    </a>
                </div>
            </div>

            <div class="footer-bottom-bar d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                <div>
                    © <?= date('Y') ?> <strong>Department of Animal Production & Health</strong> • Eastern Provincial Council, Sri Lanka.
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-warning fw-semibold">Powered By SLT-DIGITAL</span>
                    <span class="text-white-50">•</span>
                    <span>All Rights Reserved</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- LIGHTBOX PREVIEW MODAL -->
    <div class="modal fade modal-gallery-preview" id="galleryLightboxModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3 text-center">
                    <img src="" id="lightboxImg" class="modal-gallery-img rounded-3" alt="Preview">
                </div>
            </div>
        </div>
    </div>

    <!-- FLOATING QUICK LOGIN FAB -->
    <a href="#loginPortal" class="floating-login-fab d-lg-none" title="Quick Jump to MIS Staff Sign In">
        <i class="bi bi-shield-lock-fill"></i>
        <span>Staff Login</span>
    </a>

    <!-- SCRIPTS -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {

            // ==========================================
            // 1. HERO SLIDER LOGIC
            // ==========================================
            const slides = $('.hero-slide');
            const dots = $('.slider-dot');
            let currentSlide = 0;
            const slideCount = slides.length;
            let slideInterval = null;

            function goToSlide(index) {
                if (index < 0) index = slideCount - 1;
                if (index >= slideCount) index = 0;

                slides.removeClass('active').eq(index).addClass('active');
                dots.removeClass('active').eq(index).addClass('active');
                currentSlide = index;
            }

            function nextSlide() {
                goToSlide(currentSlide + 1);
            }

            function prevSlide() {
                goToSlide(currentSlide - 1);
            }

            function startSlideTimer() {
                stopSlideTimer();
                slideInterval = setInterval(nextSlide, 5500);
            }

            function stopSlideTimer() {
                if (slideInterval) {
                    clearInterval(slideInterval);
                    slideInterval = null;
                }
            }

            $('#btnNextSlide').on('click', function() {
                nextSlide();
                startSlideTimer();
            });

            $('#btnPrevSlide').on('click', function() {
                prevSlide();
                startSlideTimer();
            });

            dots.on('click', function() {
                const targetIndex = parseInt($(this).data('index'));
                goToSlide(targetIndex);
                startSlideTimer();
            });

            // Pause autoplay on mouse hover over the slider card
            $('.hero-slider-card').hover(stopSlideTimer, startSlideTimer);
            startSlideTimer();

            // ==========================================
            // 2. EMBEDDED LOGIN FORM LOGIC (PRESERVED)
            // ==========================================
            // Password Show/Hide Toggle
            $('#btnTogglePassword').on('click', function(e) {
                e.preventDefault();
                const passwordInput = $('#loginPassword');
                const icon = $('#togglePasswordIcon');
                
                if (passwordInput.attr('type') === 'password') {
                    passwordInput.attr('type', 'text');
                    icon.removeClass('bi-eye-fill').addClass('bi-eye-slash-fill');
                } else {
                    passwordInput.attr('type', 'password');
                    icon.removeClass('bi-eye-slash-fill').addClass('bi-eye-fill');
                }
            });

            // Listen for user category selection updates
            $('#userCategory').change(function() {
                const selectedVal = $(this).val();

                // Reset and hide all contextual container blocks initially
                $('.dynamic-field-group').hide().find('select').val('').prop('required', false);

                if (selectedVal === 'deputy_director_district') {
                    $('#districtGroup').fadeIn(250).find('select').prop('required', true);
                } else if (selectedVal === 'range_veterinary_officer') {
                    $('#districtGroup').fadeIn(250).find('select').prop('required', true);
                    $('#rangeGroup').fadeIn(250).find('select').prop('required', true);
                } else if (selectedVal === 'training_centers') {
                    $('#trainingCenterLocationGroup').fadeIn(250).find('select').prop('required', true);
                    $('#trainingCenterGroup').fadeIn(250).find('select').prop('required', true);
                } else if (selectedVal === 'regional_farms') {
                    $('#farmGroup').fadeIn(250).find('select').prop('required', true);
                }
            });

            // Training center auto-location sync
            $('#trainingCenterSelect').change(function() {
                const selectedOption = $(this).find('option:selected');
                const location = selectedOption.data('location');
                if (location) {
                    $('#trainingCenterLocation').val(location);
                } else {
                    $('#trainingCenterLocation').val('');
                }
            });

            // Handle Dependent Selection filtering for VS Ranges based on District
            $('#districtSelect').change(function() {
                const districtId = $(this).val();
                const rangeSelect = $('#rangeSelect');

                // Clear previous options
                rangeSelect.html('<option value="">-- Select Range Office --</option>');

                if (districtId && $('#userCategory').val() === 'range_veterinary_officer') {
                    $.ajax({
                        url: 'auth/get_ranges_by_district.php',
                        type: 'GET',
                        data: { district_id: districtId },
                        dataType: 'json',
                        success: function(data) {
                            $.each(data, function(index, range) {
                                rangeSelect.append('<option value="' + range.id + '">' + range.name + '</option>');
                            });
                        }
                    });
                }
            });

            // ==========================================
            // 3. NAV SCROLL SPY & SMOOTH BEHAVIOR
            // ==========================================
            $(window).on('scroll', function() {
                const scrollPos = $(document).scrollTop();
                $('section').each(function() {
                    const top = $(this).offset().top - 120;
                    const bottom = top + $(this).outerHeight();
                    const id = $(this).attr('id');

                    if (scrollPos >= top && scrollPos <= bottom) {
                        $('.nav-link-custom').removeClass('active');
                        $('.nav-link-custom[href="#' + id + '"]').addClass('active');
                    }
                });
            });

        });

        // Global Lightbox preview helper
        function openLightbox(src) {
            $('#lightboxImg').attr('src', src);
            const modal = new bootstrap.Modal(document.getElementById('galleryLightboxModal'));
            modal.show();
        }
    </script>
</body>

</html>