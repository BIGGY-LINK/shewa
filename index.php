<?php
require_once __DIR__ . '/includes/bootstrap.php';

$databaseReady = true;
try {
    $products = db()->query(
        'SELECT id, name, category, price, stock, tone FROM products WHERE is_active = 1 ORDER BY id'
    )->fetchAll();
} catch (Throwable $exception) {
    error_log('Storefront database error: ' . $exception->getMessage());
    $products = [];
    $databaseReady = false;
}

$currentUser = current_user();
$csrfToken = csrf_token();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#f5f0e8" id="theme-color-meta">
  <meta name="description" content="Ewa_luxe Closet — affordable luxury, considered style. Elegance in Every Stitch.">
  <title>Ewa_luxe Closet — Shop &amp; Brand</title>
  <script>
  (() => {
    try {
      const savedTheme = localStorage.getItem("ewa-luxe-theme");
      const theme = savedTheme === "light" || savedTheme === "dark"
        ? savedTheme
        : (window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light");
      document.documentElement.dataset.theme = theme;
    } catch {
      document.documentElement.dataset.theme = "light";
    }
  })();
  </script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Playfair+Display:wght@400;500;600&display=swap');

    :root {
      --ivory: #f5f0e8;
      --paper: #fbf9f5;
      --ink: #191714;
      --muted: #827a70;
      --gold: #aa8959;
      --gold-light: #c9b18a;
      --nude: #d7c2ae;
      --line: #dfd7ca;
      --surface-muted: #eee8df;
      --surface-input: #ffffff;
      --text-soft: #71695f;
      --footer-text: #847c71;
      --sans: "DM Sans", "Helvetica Neue", Arial, sans-serif;
      --serif: "Playfair Display", "Times New Roman", serif;
    }

    :root[data-theme="dark"] {
      color-scheme: dark;
      --ivory: #24211d;
      --paper: #191816;
      --ink: #f3eee6;
      --muted: #b1a89b;
      --gold: #c3a778;
      --gold-light: #d3bd96;
      --line: #474138;
      --surface-muted: #22201c;
      --surface-input: #24221e;
      --text-soft: #c2b9ad;
      --footer-text: #b1a89b;
    }
    :root[data-theme="light"] { color-scheme: light; }

    * { box-sizing: border-box; }
    html { scroll-behavior: smooth; }
    body {
      margin: 0;
      color: var(--ink);
      background: var(--paper);
      font-family: var(--sans);
      font-weight: 300;
      -webkit-font-smoothing: antialiased;
    }
    .topbar {
      height: 66px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 6.4vw;
      border-bottom: 1px solid rgba(25, 23, 20, .09);
      background: var(--paper);
    }
    .topbar-brand {
      color: var(--ink);
      font-family: var(--serif);
      font-size: 15px;
      letter-spacing: .025em;
      text-decoration: none;
    }
    .topbar-brand span { color: var(--gold); }
    .topbar-note, .eyebrow {
      color: var(--muted);
      font-size: 9px;
      font-weight: 500;
      letter-spacing: .22em;
      text-transform: uppercase;
    }
    .topbar { gap: 20px; }
    .site-nav { display: flex; align-items: center; gap: 22px; }
    .site-nav a {
      color: var(--ink);
      font-size: 10px;
      font-weight: 500;
      letter-spacing: .08em;
      text-decoration: none;
    }
    .site-nav .nav-admin { color: var(--gold); }
    .nav-logout { display: inline; margin: 0; }
    .nav-logout button {
      padding: 8px 0;
      border: 0;
      background: transparent;
      color: var(--ink);
      font: 500 10px var(--sans);
      cursor: pointer;
    }
    .theme-toggle {
      min-height: 34px;
      padding: 7px 10px;
      border: 1px solid var(--line);
      background: transparent;
      color: var(--ink);
      font: 500 9px var(--sans);
      letter-spacing: .07em;
      cursor: pointer;
    }
    .theme-toggle:hover { border-color: var(--gold); color: var(--gold); }
    main { overflow: hidden; }
    .hero {
      min-height: 640px;
      height: min(760px, calc(100vh - 66px));
      display: grid;
      grid-template-columns: 1fr minmax(330px, 1fr);
      background: var(--ivory);
    }
    .hero-copy {
      padding: 11% 6vw 10% 12.8vw;
      align-self: center;
    }
    .hero-copy .eyebrow { margin: 0 0 30px; }
    h1 {
      max-width: 470px;
      margin: 0;
      font-family: var(--serif);
      font-size: clamp(42px, 5.2vw, 72px);
      font-weight: 400;
      letter-spacing: -.045em;
      line-height: 1.07;
    }
    h1 em { color: var(--gold); font-weight: 400; }
    .hero-description {
      max-width: 370px;
      margin: 24px 0 0;
      color: #71695f;
      font-size: 13px;
      line-height: 1.9;
    }
    .hero-rule {
      display: flex;
      align-items: center;
      gap: 14px;
      margin-top: 40px;
      color: var(--gold);
      font-size: 9px;
      font-weight: 500;
      letter-spacing: .2em;
      text-transform: uppercase;
    }
    .hero-rule::before { width: 35px; height: 1px; background: var(--gold); content: ""; }
    .hero-display {
      position: relative;
      display: grid;
      place-items: center;
      margin: 48px 6.4vw 48px 0;
      background:
        linear-gradient(rgba(245, 240, 232, .34), rgba(245, 240, 232, .24)),
        url("assets/clothing-rack.jpg") center 56% / cover no-repeat;
    }
    .hero-display::after {
      position: absolute;
      right: 12%;
      bottom: 12%;
      left: 12%;
      height: 25px;
      border-radius: 50%;
      background: rgba(67, 52, 33, .09);
      filter: blur(18px);
      content: "";
    }
    .hero-logo-card {
      position: relative;
      z-index: 1;
      width: min(78%, 470px);
      aspect-ratio: 1.04;
      display: grid;
      place-items: center;
      background: rgba(251,249,245,.93);
      box-shadow: 0 22px 65px rgba(62, 48, 30, .11);
    }
    .logo-lockup { text-align: center; }
    .logo-main {
      color: var(--ink);
      font-family: var(--serif);
      font-size: clamp(39px, 5.1vw, 68px);
      font-weight: 400;
      letter-spacing: -.055em;
      line-height: 1;
      white-space: nowrap;
    }
    .logo-main .underscore { color: var(--gold); }
    .logo-sub {
      margin-top: 16px;
      color: var(--gold);
      font-size: 10px;
      font-weight: 500;
      letter-spacing: .48em;
      text-indent: .48em;
    }
    .logo-flourish {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 9px;
      margin-top: 23px;
      color: var(--gold-light);
      font-size: 8px;
    }
    .logo-flourish::before, .logo-flourish::after {
      width: 25px;
      height: 1px;
      background: var(--gold-light);
      content: "";
    }
    .display-caption {
      position: absolute;
      z-index: 2;
      right: 20px;
      bottom: 18px;
      color: #9a9186;
      font-size: 8px;
      letter-spacing: .18em;
      text-transform: uppercase;
    }
    .photo-credit {
      position: absolute;
      z-index: 2;
      top: 17px;
      left: 20px;
      color: #5e5549;
      font-size: 8px;
      letter-spacing: .08em;
      text-decoration: underline;
      text-decoration-color: rgba(94, 85, 73, .5);
      text-underline-offset: 3px;
    }
    .section {
      padding: 116px 9vw;
    }
    .section-heading {
      display: flex;
      align-items: end;
      justify-content: space-between;
      gap: 30px;
      margin-bottom: 48px;
    }
    .section-heading .eyebrow { margin: 0 0 14px; }
    h2 {
      margin: 0;
      font-family: var(--serif);
      font-size: clamp(34px, 4vw, 48px);
      font-weight: 400;
      letter-spacing: -.035em;
    }
    .section-intro {
      max-width: 340px;
      margin: 0 0 4px;
      color: var(--muted);
      font-size: 12px;
      line-height: 1.8;
    }
    .identity-grid {
      display: grid;
      grid-template-columns: 1.45fr 1fr;
      grid-template-rows: 270px 260px;
      gap: 16px;
    }
    .mockup {
      position: relative;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
    }
    .mockup-label {
      position: absolute;
      z-index: 4;
      bottom: 17px;
      left: 19px;
      color: rgba(31, 28, 24, .55);
      font-size: 8px;
      font-weight: 500;
      letter-spacing: .18em;
      text-transform: uppercase;
    }
    .primary-panel { background: #eee7dc; }
    .primary-paper {
      width: 75%;
      height: 72%;
      display: grid;
      place-items: center;
      background: #faf8f3;
      box-shadow: 0 15px 32px rgba(55, 42, 27, .10);
    }
    .primary-paper .logo-main { font-size: clamp(34px, 4.2vw, 54px); }
    .primary-paper .logo-sub { margin-top: 12px; font-size: 8px; }
    .dark-panel {
      background: #201e1a;
      background-image: radial-gradient(ellipse at center, #302a22 0%, #171614 75%);
    }
    .dark-panel .logo-main { color: #f2ece2; font-size: clamp(28px, 3.2vw, 41px); }
    .dark-panel .logo-sub { color: #c3a778; font-size: 8px; }
    .dark-panel .logo-main .underscore { color: #c3a778; }
    .dark-panel .logo-flourish { color: #c3a778; }
    .dark-panel .logo-flourish::before, .dark-panel .logo-flourish::after { background: #c3a778; }
    .tag-panel {
      gap: 23px;
      background: #e8dfd3;
      perspective: 700px;
    }
    .tag {
      position: relative;
      width: 112px;
      height: 172px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-direction: column;
      background: #f7f3eb;
      box-shadow: 4px 10px 18px rgba(47, 36, 25, .16);
      transform: rotate(-6deg);
    }
    .tag::before {
      position: absolute;
      top: 10px;
      left: 50%;
      width: 6px;
      height: 6px;
      border: 1px solid var(--gold);
      border-radius: 50%;
      content: "";
      transform: translateX(-50%);
    }
    .tag::after {
      position: absolute;
      top: 0;
      left: 50%;
      width: 1px;
      height: 23px;
      background: #aa9b84;
      content: "";
    }
    .tag .monogram {
      width: 42px;
      height: 42px;
      margin-bottom: 12px;
      font-size: 19px;
    }
    .tag-wordmark { font-family: var(--serif); font-size: 13px; letter-spacing: -.04em; }
    .tag-caption { margin-top: 6px; color: var(--gold); font-size: 6px; letter-spacing: .26em; }
    .tag-back {
      width: 103px;
      height: 157px;
      display: grid;
      place-items: center;
      background: #c9b49c;
      box-shadow: 4px 10px 18px rgba(47, 36, 25, .13);
      transform: rotate(7deg) translateY(7px);
    }
    .tag-back .monogram { color: #f7f3eb; border-color: #e7d8c0; }
    .bag-panel {
      background: #e9e1d6;
      background-image: radial-gradient(ellipse at 50% 75%, rgba(255,255,255,.65), transparent 56%);
    }
    .bag {
      position: relative;
      width: 166px;
      height: 184px;
      display: grid;
      place-items: center;
      margin-top: 23px;
      background: #f7f4ed;
      box-shadow: 8px 16px 23px rgba(63, 48, 30, .15);
    }
    .bag::before {
      position: absolute;
      top: -31px;
      left: 48px;
      width: 68px;
      height: 46px;
      border: 2px solid #aa8959;
      border-bottom: 0;
      border-radius: 45px 45px 0 0;
      content: "";
    }
    .bag .logo-main { font-size: 22px; }
    .bag .logo-sub { margin-top: 9px; font-size: 6px; }
    .bag .logo-flourish { margin-top: 13px; }
    .bag .logo-flourish::before, .bag .logo-flourish::after { width: 14px; }
    .business-panel {
      grid-column: 1 / -1;
      min-height: 260px;
      gap: 20px;
      background: #f0ebe3;
    }
    .business-card {
      position: relative;
      width: 270px;
      height: 156px;
      padding: 27px 28px;
      box-shadow: 0 12px 25px rgba(53, 42, 30, .13);
    }
    .business-card.front {
      display: grid;
      place-items: center;
      background: #f9f7f2;
      transform: rotate(-3deg);
    }
    .business-card.front .logo-main { font-size: 27px; }
    .business-card.front .logo-sub { margin-top: 9px; font-size: 7px; }
    .business-card.back {
      display: flex;
      flex-direction: column;
      justify-content: center;
      background: #24211d;
      color: #f1e9dd;
      transform: rotate(3deg);
    }
    .business-card.back .card-name { font-family: var(--serif); font-size: 20px; }
    .business-card.back .card-detail {
      margin-top: 13px;
      color: #c7b18b;
      font-size: 8px;
      letter-spacing: .08em;
      line-height: 1.8;
    }
    .identity-note {
      grid-column: 1 / -1;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding-top: 20px;
      color: var(--muted);
      font-size: 10px;
    }
    .identity-note strong { color: var(--ink); font-weight: 500; }
    .social-section {
      padding-top: 94px;
      background: #282520;
      color: #f5f0e8;
    }
    .social-section .section-heading { margin-bottom: 56px; }
    .social-section .eyebrow { color: #b6a17f; }
    .social-section .section-intro { color: #b7b0a5; }
    .social-layout {
      display: grid;
      grid-template-columns: 1fr 1.1fr;
      align-items: center;
      gap: 10vw;
    }
    .social-copy h2 { max-width: 420px; }
    .social-copy p {
      max-width: 400px;
      margin: 20px 0 0;
      color: #b7b0a5;
      font-size: 12px;
      line-height: 1.9;
    }
    .social-copy .slogan {
      margin-top: 34px;
      color: #c7b18b;
      font-family: var(--serif);
      font-size: 20px;
      font-style: italic;
    }
    .social-stage {
      min-height: 350px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 32px;
      background: radial-gradient(ellipse at center, #3a362e 0%, #282520 72%);
    }
    .profile-icon {
      width: 156px;
      height: 156px;
      display: grid;
      place-items: center;
      border: 1px solid rgba(199, 177, 139, .72);
      border-radius: 50%;
      background: #f5f0e8;
      box-shadow: 0 15px 38px rgba(0,0,0,.24);
    }
    .monogram {
      display: grid;
      place-items: center;
      width: 62px;
      height: 62px;
      border: 1px solid var(--gold);
      border-radius: 50%;
      color: var(--gold);
      font-family: var(--serif);
      font-size: 27px;
      letter-spacing: -.1em;
      text-indent: -.1em;
    }
    .social-post {
      width: 202px;
      height: 252px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-direction: column;
      background:
        linear-gradient(145deg, rgba(255,255,255,.18), transparent 44%),
        #d8c5b2;
      color: #28231d;
      box-shadow: 0 15px 35px rgba(0,0,0,.24);
      text-align: center;
    }
    .social-post .post-eyebrow { font-size: 7px; letter-spacing: .25em; }
    .social-post .post-title {
      margin-top: 19px;
      font-family: var(--serif);
      font-size: 28px;
      line-height: 1.12;
    }
    .social-post .post-rule { width: 24px; height: 1px; margin: 18px 0 12px; background: var(--gold); }
    .social-post .post-slogan { font-family: var(--serif); font-size: 11px; font-style: italic; }
    .social-post .post-brand { margin-top: 23px; color: #826d4d; font-size: 7px; letter-spacing: .17em; }
    .palette-section { padding-top: 100px; padding-bottom: 110px; }
    .palette-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 14px;
    }
    .swatch-color { height: 112px; }
    .swatch-caption {
      display: flex;
      justify-content: space-between;
      gap: 10px;
      padding-top: 13px;
      color: #746c62;
      font-size: 9px;
      letter-spacing: .05em;
    }
    .swatch-caption strong { color: var(--ink); font-weight: 500; }
    .swatch-ivory { background: #f5f0e8; border: 1px solid #e8e1d7; }
    .swatch-gold { background: #aa8959; }
    .swatch-black { background: #191714; }
    .swatch-nude { background: #d7c2ae; }
    .shop-section { padding-top: 100px; background: #fbf9f5; }
    .shop-layout { display: grid; grid-template-columns: minmax(0, 1fr) 290px; gap: 28px; align-items: start; }
    .shop-filters { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 23px; }
    .filter-button, .outline-button, .primary-button, .danger-button {
      min-height: 38px;
      padding: 10px 15px;
      border: 1px solid var(--line);
      background: transparent;
      color: var(--ink);
      font: 500 10px var(--sans);
      letter-spacing: .08em;
      cursor: pointer;
    }
    .filter-button.is-active, .primary-button {
      border-color: var(--ink);
      background: var(--ink);
      color: var(--paper);
    }
    .primary-button:hover, .filter-button:hover { border-color: var(--gold); }
    .outline-button:hover { border-color: var(--gold); color: var(--gold); }
    .danger-button { border-color: #b87167; color: #8d3930; }
    .product-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 24px 16px; }
    .product-card { min-width: 0; }
    .product-art {
      position: relative;
      height: 245px;
      display: grid;
      place-items: center;
      overflow: hidden;
      background: linear-gradient(145deg, #eee5da, #d6c3b0);
    }
    .product-art::before {
      position: absolute;
      width: 52%;
      height: 76%;
      border-radius: 48% 48% 12% 12%;
      background: linear-gradient(120deg, rgba(255,255,255,.48), transparent 40%), var(--garment, #a8856f);
      box-shadow: 12px 19px 24px rgba(58, 43, 30, .15);
      clip-path: polygon(25% 0, 38% 8%, 62% 8%, 75% 0, 100% 14%, 83% 36%, 76% 28%, 79% 100%, 21% 100%, 24% 28%, 17% 36%, 0 14%);
      content: "";
    }
    .product-art::after {
      position: absolute;
      right: -25px;
      bottom: -60px;
      width: 170px;
      height: 170px;
      border: 1px solid rgba(255,255,255,.44);
      border-radius: 50%;
      content: "";
    }
    .product-art.tone-rose { --garment: #a77770; background: linear-gradient(145deg, #f0e5df, #d9c1b6); }
    .product-art.tone-olive { --garment: #656a52; background: linear-gradient(145deg, #ece9dd, #c8c8b3); }
    .product-art.tone-black { --garment: #292725; background: linear-gradient(145deg, #e6e1d8, #bdb4a7); }
    .product-art.tone-nude { --garment: #c6a98f; background: linear-gradient(145deg, #f0e8dd, #d8c8b6); }
    .product-art.tone-blue { --garment: #657587; background: linear-gradient(145deg, #e2e8e8, #bdc9ca); }
    .product-copy { display: flex; align-items: start; justify-content: space-between; gap: 12px; padding-top: 13px; }
    .product-copy h3 { margin: 0; font: 400 17px var(--serif); }
    .product-category { margin: 6px 0 0; color: var(--muted); font-size: 9px; letter-spacing: .12em; text-transform: uppercase; }
    .product-price { flex: 0 0 auto; color: var(--gold); font-size: 12px; font-weight: 500; }
    .product-card .outline-button { width: 100%; margin-top: 12px; }
    .product-card .outline-button:disabled { cursor: not-allowed; opacity: .48; }
    .cart-panel {
      position: sticky;
      top: 20px;
      padding: 23px;
      border: 1px solid var(--line);
      background: #f5f0e8;
    }
    .cart-panel h3 { margin: 0; font: 400 24px var(--serif); }
    .cart-caption { margin: 7px 0 19px; color: var(--muted); font-size: 10px; line-height: 1.7; }
    .cart-lines { display: grid; gap: 12px; }
    .cart-line { display: grid; grid-template-columns: 1fr auto; gap: 4px 10px; padding-bottom: 11px; border-bottom: 1px solid var(--line); font-size: 11px; }
    .cart-line small { color: var(--muted); }
    .cart-quantity { display: flex; align-items: center; gap: 8px; }
    .quantity-button { width: 24px; height: 24px; border: 1px solid var(--line); background: transparent; cursor: pointer; }
    .cart-total { display: flex; justify-content: space-between; margin: 18px 0; font-size: 12px; font-weight: 500; }
    .cart-panel .primary-button { width: 100%; }
    .empty-note { color: var(--muted); font-size: 11px; line-height: 1.7; }
    .demo-notice {
      margin-top: 13px;
      padding: 11px;
      background: #eee5d9;
      color: #655641;
      font-size: 9px;
      line-height: 1.7;
    }
    .store-status { min-height: 20px; margin: 10px 0 0; color: #705f42; font-size: 10px; }
    .admin-section { padding-top: 92px; background: #eee8df; }
    .admin-banner {
      margin-bottom: 25px;
      padding: 15px 18px;
      border-left: 3px solid var(--gold);
      background: #f8f4ec;
      color: #655641;
      font-size: 11px;
      line-height: 1.75;
    }
    .admin-banner strong { color: var(--ink); }
    .admin-tabs { display: flex; gap: 9px; margin-bottom: 20px; }
    .admin-tab[aria-selected="true"] { border-color: var(--ink); background: var(--ink); color: white; }
    .admin-panel { padding: 22px; border: 1px solid var(--line); background: var(--paper); }
    .admin-panel[hidden] { display: none; }
    .admin-panel h3 { margin: 0 0 17px; font: 400 23px var(--serif); }
    .admin-tools { display: flex; align-items: center; justify-content: space-between; gap: 15px; margin-bottom: 16px; }
    .admin-tools p { margin: 0; color: var(--muted); font-size: 10px; line-height: 1.7; }
    .admin-table-wrap { overflow-x: auto; }
    .admin-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 10px; }
    .admin-table th, .admin-table td { padding: 12px 9px; border-bottom: 1px solid var(--line); white-space: nowrap; }
    .admin-table th { color: var(--muted); font-size: 8px; font-weight: 500; letter-spacing: .13em; text-transform: uppercase; }
    .admin-table td .outline-button, .admin-table td .danger-button { min-height: 30px; padding: 6px 9px; font-size: 8px; }
    .product-form { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; max-width: 640px; }
    .product-form[hidden] { display: none; }
    .product-form label, .checkout-form label { display: grid; gap: 6px; color: var(--muted); font-size: 9px; font-weight: 500; letter-spacing: .1em; text-transform: uppercase; }
    .product-form input, .product-form select, .checkout-form input {
      width: 100%;
      min-height: 39px;
      padding: 9px 10px;
      border: 1px solid var(--line);
      border-radius: 0;
      background: white;
      color: var(--ink);
      font: 12px var(--sans);
    }
    .product-form .form-wide { grid-column: 1 / -1; }
    .currency-control { display: flex; align-items: center; gap: 10px; color: var(--muted); font-size: 10px; }
    .currency-control select { min-height: 34px; padding: 5px 8px; border: 1px solid var(--line); background: white; color: var(--ink); }
    dialog { width: min(440px, calc(100% - 28px)); padding: 28px; border: 1px solid var(--line); background: var(--paper); color: var(--ink); }
    dialog::backdrop { background: rgba(25, 23, 20, .62); }
    dialog h2 { font-size: 30px; }
    dialog p { color: var(--muted); font-size: 11px; line-height: 1.75; }
    .checkout-form { display: grid; gap: 16px; }
    .dialog-actions { display: flex; justify-content: end; gap: 8px; margin-top: 20px; }
    :root[data-theme="dark"] body { background: var(--paper); color: var(--ink); }
    :root[data-theme="dark"] .topbar { border-color: var(--line); }
    :root[data-theme="dark"] .hero-description { color: var(--text-soft); }
    :root[data-theme="dark"] .hero-display {
      background:
        linear-gradient(rgba(25, 24, 22, .48), rgba(25, 24, 22, .4)),
        url("assets/clothing-rack.jpg") center 56% / cover no-repeat;
    }
    :root[data-theme="dark"] .hero-logo-card {
      background: #f5f0e8;
      box-shadow: 0 22px 65px rgba(0, 0, 0, .35);
    }
    :root[data-theme="dark"] .hero-logo-card .logo-main { color: #191714; }
    :root[data-theme="dark"] .hero-logo-card .logo-sub { color: #aa8959; }
    :root[data-theme="dark"] .display-caption { color: #d1c5b3; }
    :root[data-theme="dark"] .photo-credit {
      color: #f0e5d3;
      text-decoration-color: rgba(240, 229, 211, .65);
    }
    :root[data-theme="dark"] .shop-section { background: var(--paper); }
    :root[data-theme="dark"] .admin-section { background: var(--surface-muted); }
    :root[data-theme="dark"] .cart-panel,
    :root[data-theme="dark"] .admin-panel { background: #191816; }
    :root[data-theme="dark"] .admin-banner { background: #312b22; color: #e2d2b7; }
    :root[data-theme="dark"] .admin-banner strong { color: #f3eee6; }
    :root[data-theme="dark"] .outline-button,
    :root[data-theme="dark"] .filter-button,
    :root[data-theme="dark"] .quantity-button,
    :root[data-theme="dark"] .theme-toggle { color: var(--ink); }
    :root[data-theme="dark"] .filter-button.is-active,
    :root[data-theme="dark"] .primary-button,
    :root[data-theme="dark"] .admin-tab[aria-selected="true"] { background: #c3a778; border-color: #c3a778; color: #191714; }
    :root[data-theme="dark"] .outline-button:hover,
    :root[data-theme="dark"] .theme-toggle:hover { color: #e0c89d; }
    :root[data-theme="dark"] .product-form input,
    :root[data-theme="dark"] .product-form select,
    :root[data-theme="dark"] .checkout-form input,
    :root[data-theme="dark"] .currency-control select {
      border-color: var(--line);
      background: var(--surface-input);
      color: var(--ink);
    }
    :root[data-theme="dark"] .demo-notice { background: #312b22; color: #e2d2b7; }
    :root[data-theme="dark"] .empty-note { color: var(--muted); }
    :root[data-theme="dark"] footer { color: var(--footer-text); }
    :root[data-theme="dark"] dialog { background: #201e1a; color: var(--ink); }
    :root[data-theme="dark"] .swatch-caption { color: var(--muted); }
    :root[data-theme="dark"] .swatch-caption strong { color: var(--ink); }
    footer {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 27px 9vw;
      border-top: 1px solid var(--line);
      color: #847c71;
      font-size: 9px;
      letter-spacing: .13em;
      text-transform: uppercase;
    }
    footer .footer-name {
      color: var(--ink);
      font-family: var(--serif);
      font-size: 14px;
      letter-spacing: 0;
      text-transform: none;
    }
    @media (max-width: 780px) {
      .shop-layout { grid-template-columns: 1fr; }
      .cart-panel { position: static; }
      .admin-tools { align-items: start; flex-direction: column; }
      .hero {
        height: auto;
        grid-template-columns: 1fr;
      }
      .hero-copy { padding: 72px 9vw 35px; }
      .hero-display { min-height: 430px; margin: 10px 7vw 45px; }
      .hero-logo-card { width: min(76%, 390px); }
      .logo-main { font-size: clamp(39px, 9vw, 62px); }
      .section { padding: 82px 7vw; }
      .identity-grid {
        grid-template-columns: 1fr 1fr;
        grid-template-rows: 240px 230px 230px 220px;
      }
      .primary-panel { grid-column: 1 / -1; }
      .tag-panel, .bag-panel { grid-column: span 1; }
      .business-panel { min-height: 220px; }
      .business-card { width: min(42%, 270px); height: 145px; padding: 18px; }
      .business-card.front .logo-main { font-size: clamp(16px, 3.6vw, 26px); }
      .business-card.back .card-name { font-size: 17px; }
      .social-layout { grid-template-columns: 1fr; gap: 30px; }
      .social-stage { min-height: 310px; }
      .social-section { padding-top: 80px; }
    }
    @media (max-width: 520px) {
      .topbar { height: 58px; padding: 0 6vw; }
      .topbar-note { display: none; }
      .site-nav { gap: 8px; }
      .site-nav a { font-size: 9px; }
      .theme-toggle { min-height: 30px; padding: 6px 7px; font-size: 8px; }
      .hero-copy { padding-top: 58px; }
      .hero-display { min-height: 350px; }
      .section-heading { align-items: start; flex-direction: column; gap: 14px; }
      .identity-grid {
        grid-template-columns: 1fr 1fr;
        grid-template-rows: 220px 205px 200px 215px;
        gap: 10px;
      }
      .primary-paper { width: 86%; height: 70%; }
      .primary-paper .logo-main { font-size: clamp(29px, 7vw, 38px); }
      .tag-panel { gap: 11px; }
      .tag { width: 88px; height: 142px; }
      .tag-back { width: 80px; height: 128px; }
      .tag .monogram { width: 35px; height: 35px; font-size: 16px; }
      .tag-wordmark { font-size: 11px; }
      .bag { transform: scale(.82); }
      .business-panel { gap: 12px; }
      .business-card { width: 44%; height: 130px; }
      .business-card.front .logo-main { font-size: 17px; }
      .business-card.front .logo-sub { font-size: 6px; }
      .business-card.back .card-name { font-size: 14px; }
      .business-card.back .card-detail { font-size: 6px; }
      .identity-note { align-items: start; flex-direction: column; gap: 7px; }
      .social-stage { gap: 15px; min-height: 280px; }
      .profile-icon { width: 112px; height: 112px; }
      .social-post { width: 155px; height: 210px; }
      .social-post .post-title { font-size: 23px; }
      .palette-grid { grid-template-columns: 1fr 1fr; row-gap: 24px; }
      .swatch-color { height: 90px; }
      .shop-section { padding-top: 66px; }
      .product-grid { gap: 18px 10px; }
      .product-art { height: 190px; }
      .product-copy { flex-direction: column; gap: 6px; }
      .product-copy h3 { font-size: 15px; }
      .product-card .outline-button { padding: 9px 7px; font-size: 9px; }
      .admin-section { padding-top: 66px; }
      .admin-panel { padding: 15px; }
      .product-form { grid-template-columns: 1fr; }
      .product-form .form-wide { grid-column: auto; }
      footer { align-items: start; flex-direction: column; gap: 10px; padding: 23px 7vw; }
    }
    @media (prefers-reduced-motion: reduce) {
      html { scroll-behavior: auto; }
    }

    :root {
      --gold: #a45b48;
      --gold-light: #c28a72;
      --ivory: #eee5dc;
      --paper: #fbf8f3;
      --line: #ded1c5;
      --surface-muted: #eee5dc;
      --text-soft: #6f6259;
      --footer-text: #76695f;
      --serif: "Playfair Display", Georgia, serif;
    }
    :root[data-theme="dark"] {
      --gold: #d29a7d;
      --gold-light: #ddb49c;
      --ivory: #28221f;
      --paper: #191716;
      --line: #493c36;
      --surface-muted: #241e1b;
      --text-soft: #c9b9ae;
      --footer-text: #b7a69a;
    }
    body {
      background-image: radial-gradient(ellipse at 10% 12%, rgba(216, 189, 168, .12), transparent 34%);
      letter-spacing: .005em;
    }
    .topbar {
      position: sticky;
      z-index: 20;
      top: 0;
      height: 72px;
      border-bottom: 1px solid var(--line);
      box-shadow: 0 4px 18px rgba(48, 34, 26, .04);
    }
    .topbar-brand { font-size: 17px; }
    .topbar-brand::after {
      display: block;
      margin-top: 3px;
      color: var(--muted);
      content: "ELEGANCE IN EVERY STITCH";
      font: 500 6px var(--sans);
      letter-spacing: .22em;
    }
    .site-nav { gap: clamp(13px, 2vw, 25px); }
    .site-nav a { position: relative; padding: 8px 0; }
    .site-nav a:not(.nav-admin)::after, .site-nav .nav-admin::after {
      position: absolute;
      right: 0;
      bottom: 1px;
      left: 0;
      height: 1px;
      background: var(--gold);
      content: "";
      transform: scaleX(0);
      transform-origin: left;
      transition: transform .2s ease;
    }
    .site-nav a:hover::after { transform: scaleX(1); }
    .theme-toggle {
      border-radius: 999px;
      padding-right: 14px;
      padding-left: 14px;
    }
    .hero {
      position: relative;
      min-height: 690px;
      grid-template-columns: .92fr 1.08fr;
      background:
        linear-gradient(110deg, var(--ivory) 0%, var(--ivory) 48%, #e6d9cd 100%);
    }
    .hero-copy {
      position: relative;
      z-index: 2;
      padding-left: max(9vw, calc((100vw - 1200px) / 2));
    }
    .hero-copy::before {
      position: absolute;
      top: 50%;
      left: 2vw;
      color: rgba(164, 91, 72, .07);
      content: "E";
      font: italic 400 clamp(250px, 34vw, 450px)/.8 var(--serif);
      pointer-events: none;
      transform: translateY(-53%);
    }
    .hero-copy .eyebrow {
      position: relative;
      display: inline-flex;
      align-items: center;
      gap: 9px;
      margin-bottom: 25px;
      padding: 8px 12px;
      border: 1px solid rgba(164, 91, 72, .34);
      color: var(--gold);
      font-size: 8px;
      letter-spacing: .18em;
    }
    .hero-copy .eyebrow::before {
      width: 5px;
      height: 5px;
      border-radius: 50%;
      background: currentColor;
      content: "";
    }
    h1 {
      position: relative;
      max-width: 520px;
      font-size: clamp(54px, 6.7vw, 91px);
      letter-spacing: -.065em;
      line-height: .96;
    }
    h1 em {
      display: inline-block;
      color: var(--gold);
      font-style: italic;
      transform: translateX(13px);
    }
    .hero-description {
      position: relative;
      max-width: 350px;
      margin-top: 28px;
      color: var(--text-soft);
      font-size: 13px;
    }
    .hero-rule { position: relative; }
    .hero-display {
      min-width: 0;
      margin: 34px 5vw 34px 0;
      border: 9px solid rgba(251, 248, 243, .76);
      background-position: 55% 56%;
      box-shadow: 0 24px 70px rgba(67, 45, 33, .13);
    }
    .hero-logo-card {
      width: min(74%, 430px);
      aspect-ratio: 1.12;
      border: 1px solid rgba(164, 91, 72, .32);
      outline: 1px solid rgba(251, 248, 243, .9);
      outline-offset: 7px;
      background: rgba(251, 248, 243, .94);
      box-shadow: 0 22px 65px rgba(62, 48, 30, .2);
      transform: rotate(-2deg);
    }
    .hero-logo-card .logo-main { font-size: clamp(38px, 4.8vw, 65px); }
    .hero-logo-card .logo-sub { letter-spacing: .55em; }
    .display-caption {
      right: 15px;
      bottom: 13px;
      padding: 7px 9px;
      background: rgba(251, 248, 243, .84);
      color: #57463d;
    }
    .photo-credit {
      top: 15px;
      left: 15px;
      padding: 7px 9px;
      background: rgba(251, 248, 243, .84);
      color: #57463d;
    }
    .section { padding-top: 104px; padding-bottom: 104px; }
    .section-heading {
      align-items: end;
      margin-bottom: 37px;
      padding-bottom: 20px;
      border-bottom: 1px solid var(--line);
    }
    .section-heading .eyebrow { color: var(--gold); }
    h2 {
      font-size: clamp(37px, 4.5vw, 57px);
      letter-spacing: -.05em;
    }
    .section-intro { max-width: 310px; }
    .shop-section { position: relative; }
    .shop-section::before {
      position: absolute;
      top: 0;
      left: 9vw;
      width: 54px;
      height: 3px;
      background: var(--gold);
      content: "";
    }
    .product-grid { gap: 32px 18px; }
    .product-card {
      padding: 0 0 15px;
      border-bottom: 1px solid var(--line);
    }
    .product-art {
      height: clamp(230px, 25vw, 340px);
      background-image:
        radial-gradient(circle at 16% 18%, rgba(255,255,255,.55), transparent 30%),
        linear-gradient(145deg, #eee3d8, #d6c1b2);
      transition: background-size .35s ease, box-shadow .25s ease;
    }
    .product-art::before {
      width: 48%;
      height: 78%;
      filter: drop-shadow(12px 16px 9px rgba(53, 38, 29, .16));
      transition: transform .35s ease;
    }
    .product-card:hover .product-art::before { transform: translateY(-5px) scale(1.025); }
    .product-art::after {
      right: -44px;
      bottom: -96px;
      width: 205px;
      height: 205px;
      border-color: rgba(164, 91, 72, .32);
    }
    .product-copy { padding-top: 16px; }
    .product-copy h3 { font-size: 19px; }
    .product-category { letter-spacing: .16em; }
    .product-price { color: var(--gold); }
    .product-card .outline-button { border-color: var(--ink); }
    .product-card .outline-button:hover { background: var(--gold); border-color: var(--gold); color: #fffaf5; }
    .cart-panel {
      border: 0;
      border-top: 3px solid var(--gold);
      background: var(--ivory);
      box-shadow: 0 15px 40px rgba(48, 34, 26, .07);
    }
    .cart-panel h3 { font-size: 28px; }
    .admin-section {
      background:
        radial-gradient(ellipse at 90% 5%, rgba(164, 91, 72, .10), transparent 36%),
        var(--surface-muted);
    }
    .admin-banner {
      border-left-width: 4px;
      border-left-color: var(--gold);
    }
    .admin-panel { box-shadow: 0 12px 35px rgba(48, 34, 26, .05); }
    .admin-panel h3 { font-size: 27px; }
    .admin-table th { color: var(--gold); }
    .filter-button, .outline-button, .primary-button, .danger-button {
      transition: color .18s ease, background-color .18s ease, border-color .18s ease, transform .18s ease;
    }
    .filter-button:hover, .outline-button:hover, .primary-button:hover, .danger-button:hover {
      transform: translateY(-1px);
    }
    .social-section {
      background:
        radial-gradient(ellipse at 80% 8%, rgba(164, 91, 72, .18), transparent 36%),
        #28221f;
    }
    .social-section h2 { color: #f5f0e8; }
    .social-stage {
      border: 1px solid rgba(210, 154, 125, .22);
      background: radial-gradient(ellipse at center, #46352e 0%, #28221f 72%);
    }
    .palette-section { background: var(--paper); }
    .swatch-color { position: relative; overflow: hidden; }
    .swatch-color::after {
      position: absolute;
      top: 0;
      right: 0;
      bottom: 0;
      left: 0;
      background: linear-gradient(135deg, rgba(255,255,255,.15), transparent 52%);
      content: "";
    }
    footer { border-top-color: var(--line); }
    :root[data-theme="dark"] body {
      background-image: radial-gradient(ellipse at 10% 12%, rgba(210, 154, 125, .08), transparent 34%);
    }
    :root[data-theme="dark"] .hero {
      background: linear-gradient(110deg, #28221f 0%, #28221f 48%, #332923 100%);
    }
    :root[data-theme="dark"] .hero-copy::before { color: rgba(210, 154, 125, .08); }
    :root[data-theme="dark"] .hero-display {
      border-color: rgba(25, 23, 22, .76);
      box-shadow: 0 24px 70px rgba(0, 0, 0, .35);
    }
    :root[data-theme="dark"] .display-caption,
    :root[data-theme="dark"] .photo-credit {
      background: rgba(25, 23, 22, .86);
      color: #f0e5d3;
    }
    :root[data-theme="dark"] .product-art {
      background-image:
        radial-gradient(circle at 16% 18%, rgba(255,255,255,.18), transparent 30%),
        linear-gradient(145deg, #41362f, #302722);
    }
    :root[data-theme="dark"] .product-art.tone-rose { background: linear-gradient(145deg, #4a3331, #352825); }
    :root[data-theme="dark"] .product-art.tone-olive { background: linear-gradient(145deg, #3e4033, #292b24); }
    :root[data-theme="dark"] .product-art.tone-black { background: linear-gradient(145deg, #3c3731, #262320); }
    :root[data-theme="dark"] .product-art.tone-nude { background: linear-gradient(145deg, #48392f, #322923); }
    :root[data-theme="dark"] .product-art.tone-blue { background: linear-gradient(145deg, #34404a, #252b30); }
    :root[data-theme="dark"] .cart-panel { background: #28221f; }
    :root[data-theme="dark"] .admin-section {
      background:
        radial-gradient(ellipse at 90% 5%, rgba(210, 154, 125, .09), transparent 36%),
        var(--surface-muted);
    }
    :root[data-theme="dark"] .social-section {
      background:
        radial-gradient(ellipse at 80% 8%, rgba(210, 154, 125, .15), transparent 36%),
        #211d1a;
    }
    @media (max-width: 780px) {
      .hero { min-height: 0; }
      .hero-copy { padding-left: 9vw; }
      .hero-copy::before { left: 2vw; }
      .hero-display { margin-right: 7vw; }
      .product-art { height: 260px; }
    }
    @media (max-width: 520px) {
      .topbar { height: 62px; }
      .topbar-brand { font-size: 14px; }
      .topbar-brand::after { font-size: 5px; }
      .site-nav { gap: 7px; }
      .site-nav a { font-size: 8px; }
      .theme-toggle { padding-right: 8px; padding-left: 8px; }
      .hero-copy { padding-left: 8vw; }
      .hero-copy::before { left: -3vw; font-size: 245px; }
      h1 { font-size: clamp(52px, 13vw, 70px); }
      .hero-display {
        min-height: 350px;
        margin-right: 6vw;
        border-width: 5px;
      }
      .hero-logo-card { width: 77%; }
      .section-heading { padding-bottom: 15px; }
      .section { padding-top: 70px; padding-bottom: 70px; }
      .product-art { height: 205px; }
      .product-copy h3 { font-size: 16px; }
      .shop-section::before { left: 7vw; }
    }
    @media (prefers-reduced-motion: reduce) {
      *, *::before, *::after { transition-duration: .01ms !important; }
    }
  </style>
</head>
<body>
  <header class="topbar">
    <a class="topbar-brand" href="#" aria-label="Ewa_luxe Closet home">Ewa<span>_</span>luxe Closet</a>
    <nav class="site-nav" aria-label="Main navigation">
      <a href="#shop">Shop</a>
      <a href="#identity">Our identity</a>
      <a href="account.php"><?= $currentUser ? 'My account' : 'Sign in' ?></a>
      <a class="nav-admin" href="<?= e($currentUser && $currentUser['role'] === 'admin' ? 'admin/index.php' : 'admin/login.php') ?>">Admin</a>
      <?php if ($currentUser): ?>
        <form action="logout.php" method="post" class="nav-logout">
          <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
          <button type="submit">Sign out</button>
        </form>
      <?php endif; ?>
      <button class="theme-toggle" id="theme-toggle" type="button" aria-label="Switch to dark mode" aria-pressed="false">☾ Dark</button>
    </nav>
  </header>

  <main>
    <section class="hero" aria-labelledby="brand-title">
      <div class="hero-copy">
        <p class="eyebrow">A considered kind of luxury</p>
        <h1 id="brand-title">Style that feels like <em>you.</em></h1>
        <p class="hero-description">A modern wardrobe, made more meaningful. Ewa_luxe Closet brings a little everyday elegance within reach—with thoughtful details and confidence in every look.</p>
        <div class="hero-rule">The Ewa_luxe identity</div>
      </div>
      <div class="hero-display" aria-label="Primary Ewa_luxe Closet logo on warm ivory">
        <div class="hero-logo-card">
          <div class="logo-lockup">
            <div class="logo-main">Ewa<span class="underscore">_</span>luxe</div>
            <div class="logo-sub">CLOSET</div>
            <div class="logo-flourish" aria-hidden="true">✦</div>
          </div>
        </div>
        <div class="display-caption">Primary mark · warm ivory</div>
        <a class="photo-credit" href="https://unsplash.com/license" target="_blank" rel="noreferrer">Background photo via Unsplash · free commercial use</a>
      </div>
    </section>

    <section class="section shop-section" id="shop" aria-labelledby="shop-title">
      <div class="section-heading">
        <div>
          <p class="eyebrow">The Ewa_luxe edit</p>
          <h2 id="shop-title">Find your feeling.</h2>
        </div>
        <p class="section-intro">Considered pieces for the way you want to feel. Browse the edit, add a favorite to your bag, and sign in to submit an order.</p>
      </div>
      <div class="shop-layout">
        <div>
          <div class="shop-filters" id="shop-filters" aria-label="Filter products"></div>
          <div class="product-grid" id="product-grid" aria-live="polite"></div>
          <?php if (!$databaseReady): ?>
            <p class="store-status" role="status">The catalog database is not ready. Check the hosted MySQL settings in Vercel and complete <a href="setup.php">store setup</a>.</p>
          <?php endif; ?>
        </div>
        <aside class="cart-panel" aria-labelledby="cart-title">
          <h3 id="cart-title">Your bag <span id="cart-count">(0)</span></h3>
          <p class="cart-caption">A little something, just for you.</p>
          <div class="cart-lines" id="cart-lines"></div>
          <div class="cart-total"><span>Subtotal</span><span id="cart-total">—</span></div>
          <button class="primary-button" id="checkout-button" type="button" disabled>Continue to checkout</button>
          <div class="demo-notice">Orders are recorded as awaiting payment. No payment is processed until a payment provider is configured.</div>
          <p class="store-status" id="store-status" role="status" aria-live="polite"></p>
        </aside>
      </div>
    </section>

    <section class="section" id="identity" aria-labelledby="applications-title">
      <div class="section-heading">
        <div>
          <p class="eyebrow">One identity, many moments</p>
          <h2 id="applications-title">Made to be remembered.</h2>
        </div>
        <p class="section-intro">Quiet, confident typography and considered materials carry the brand from first impression to the details she keeps.</p>
      </div>

      <div class="identity-grid">
        <div class="mockup primary-panel" aria-label="Primary logo printed on cream stationery">
          <div class="primary-paper">
            <div class="logo-lockup">
              <div class="logo-main">Ewa<span class="underscore">_</span>luxe</div>
              <div class="logo-sub">CLOSET</div>
              <div class="logo-flourish" aria-hidden="true">✦</div>
            </div>
          </div>
          <div class="mockup-label">01 / Primary · warm ivory</div>
        </div>
        <div class="mockup dark-panel" aria-label="Champagne gold logo on a rich black background">
          <div class="logo-lockup">
            <div class="logo-main">Ewa<span class="underscore">_</span>luxe</div>
            <div class="logo-sub">CLOSET</div>
            <div class="logo-flourish" aria-hidden="true">✦</div>
          </div>
          <div class="mockup-label" style="color:#c3b9a8">02 / Black &amp; champagne</div>
        </div>
        <div class="mockup tag-panel" aria-label="Clothing tags featuring the Ewa_luxe Closet mark and EL monogram">
          <div class="tag">
            <div class="tag-wordmark">Ewa_luxe</div>
            <div class="tag-caption">CLOSET</div>
          </div>
          <div class="tag-back"><div class="monogram">EL</div></div>
          <div class="mockup-label">03 / Garment tags &amp; monogram</div>
        </div>
        <div class="mockup bag-panel" aria-label="Ivory luxury shopping bag with champagne gold details">
          <div class="bag">
            <div class="logo-lockup">
              <div class="logo-main">Ewa<span class="underscore">_</span>luxe</div>
              <div class="logo-sub">CLOSET</div>
              <div class="logo-flourish" aria-hidden="true">✦</div>
            </div>
          </div>
          <div class="mockup-label">04 / Signature shopping bag</div>
        </div>
        <div class="mockup business-panel" aria-label="Front and back of Ewa_luxe Closet business cards">
          <div class="business-card front">
            <div class="logo-lockup">
              <div class="logo-main">Ewa<span class="underscore">_</span>luxe</div>
              <div class="logo-sub">CLOSET</div>
            </div>
          </div>
          <div class="business-card back">
            <div class="card-name">Ewa_luxe Closet</div>
            <div class="card-detail">ELEGANCE IN EVERY STITCH<br>STYLE · CONFIDENCE · YOU</div>
          </div>
          <div class="mockup-label">05 / Fashion business cards</div>
        </div>
        <div class="identity-note">
          <span><strong>Signature detail</strong> &nbsp; A fine-line EL seal for labels, profiles and small-scale branding.</span>
          <span>Refined. Repeatable. Recognizable.</span>
        </div>
      </div>
    </section>

    <section class="section social-section" aria-labelledby="social-title">
      <div class="section-heading">
        <div>
          <p class="eyebrow">The brand, out in the world</p>
          <h2 id="social-title">A little luxury, everywhere.</h2>
        </div>
        <p class="section-intro">An adaptable visual language for the boutique, the parcel at her door, and the next saved look.</p>
      </div>
      <div class="social-layout">
        <div class="social-copy">
          <h2>Every detail, thoughtfully dressed.</h2>
          <p>Warm neutrals, a subtle metallic accent and editorial type make a cohesive impression online and in person. The EL seal stays distinct—even at profile size.</p>
          <p class="slogan">“Elegance in Every Stitch.”</p>
        </div>
        <div class="social-stage" aria-label="Instagram profile icon and branded social media post">
          <div class="profile-icon" aria-label="EL monogram profile icon"><div class="monogram">EL</div></div>
          <div class="social-post">
            <div class="post-eyebrow">THE EWA_LUXE EDIT</div>
            <div class="post-title">Feel beautiful.<br>Be yourself.</div>
            <div class="post-rule"></div>
            <div class="post-slogan">Elegance in Every Stitch.</div>
            <div class="post-brand">EWA_LUXE CLOSET</div>
          </div>
        </div>
      </div>
    </section>

    <section class="section palette-section" aria-labelledby="palette-title">
      <div class="section-heading">
        <div>
          <p class="eyebrow">The considered palette</p>
          <h2 id="palette-title">Softly distinctive.</h2>
        </div>
        <p class="section-intro">A grounded, versatile palette balances the warmth of natural neutrals with the clarity of black.</p>
      </div>
      <div class="palette-grid">
        <div>
          <div class="swatch-color swatch-ivory"></div>
          <div class="swatch-caption"><strong>Warm Ivory</strong><span>#F5F0E8</span></div>
        </div>
        <div>
          <div class="swatch-color swatch-gold"></div>
          <div class="swatch-caption"><strong>Champagne Gold</strong><span>#AA8959</span></div>
        </div>
        <div>
          <div class="swatch-color swatch-black"></div>
          <div class="swatch-caption"><strong>Rich Black</strong><span>#191714</span></div>
        </div>
        <div>
          <div class="swatch-color swatch-nude"></div>
          <div class="swatch-caption"><strong>Soft Nude</strong><span>#D7C2AE</span></div>
        </div>
      </div>
    </section>
  </main>

  <footer>
    <div class="footer-name">Ewa_luxe Closet</div>
    <div>Elegance in Every Stitch.</div>
    <div>Style, made yours.</div>
  </footer>
  <dialog id="checkout-dialog" aria-labelledby="checkout-title">
    <h2 id="checkout-title">Submit your order</h2>
    <p>Your order will be saved as awaiting payment. No payment is collected until a payment provider is configured.</p>
    <p id="checkout-summary"></p>
    <div class="dialog-actions">
      <button class="outline-button" id="close-checkout-button" type="button">Keep browsing</button>
      <button class="primary-button" id="place-demo-order-button" type="button">Submit order</button>
    </div>
  </dialog>
  <script>
    window.SHEWA_STORE = <?= json_encode([
        'products' => $products,
        'user' => $currentUser ? ['name' => $currentUser['name'], 'role' => $currentUser['role']] : null,
        'csrfToken' => $csrfToken,
        'databaseReady' => $databaseReady,
    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?>;
  </script>
  <script>
    (() => {
      const STORAGE_KEY = "ewa-luxe-cart-v1";
      const store = window.SHEWA_STORE;
      const seedProducts = store.products.map(product => ({
        ...product,
        id: Number(product.id),
        price: Number(product.price),
        stock: Number(product.stock)
      }));
      const elements = {
        filters: document.querySelector("#shop-filters"),
        grid: document.querySelector("#product-grid"),
        cartLines: document.querySelector("#cart-lines"),
        cartCount: document.querySelector("#cart-count"),
        cartTotal: document.querySelector("#cart-total"),
        checkoutButton: document.querySelector("#checkout-button"),
        status: document.querySelector("#store-status"),
        checkoutDialog: document.querySelector("#checkout-dialog"),
        checkoutSummary: document.querySelector("#checkout-summary")
      };
      const themeButton = document.querySelector("#theme-toggle");
      const themeColorMeta = document.querySelector("#theme-color-meta");
      function updateTheme(theme) {
        document.documentElement.dataset.theme = theme;
        const isDark = theme === "dark";
        themeButton.setAttribute("aria-pressed", String(isDark));
        themeButton.setAttribute("aria-label", `Switch to ${isDark ? "light" : "dark"} mode`);
        themeButton.textContent = isDark ? "☀ Light" : "☾ Dark";
        themeColorMeta.setAttribute("content", isDark ? "#191816" : "#f5f0e8");
      }
      updateTheme(document.documentElement.dataset.theme === "dark" ? "dark" : "light");
      themeButton.addEventListener("click", () => {
        const nextTheme = document.documentElement.dataset.theme === "dark" ? "light" : "dark";
        updateTheme(nextTheme);
        try {
          localStorage.setItem("ewa-luxe-theme", nextTheme);
        } catch {
          setStatus("Theme changed for this visit, but the browser could not save your preference.");
        }
      });
      let state;
      let activeCategory = "All";
      let storageError = "";
      try {
        const savedCart = JSON.parse(localStorage.getItem(STORAGE_KEY) || "{}");
        const cart = {};
        if (savedCart && typeof savedCart === "object" && !Array.isArray(savedCart)) {
          Object.entries(savedCart).forEach(([id, quantity]) => {
            const product = seedProducts.find(item => item.id === Number(id));
            if (product && Number.isInteger(quantity) && quantity > 0) {
              cart[product.id] = Math.min(quantity, product.stock);
            }
          });
        }
        state = { products: seedProducts, cart };
      } catch {
        state = { products: seedProducts, cart: {} };
        storageError = "Your saved bag could not be read. Add the items again to continue.";
      }

      function money(amount, currency = "USD") {
        return new Intl.NumberFormat(undefined, { style: "currency", currency }).format(amount);
      }

      function saveState() {
        try {
          localStorage.setItem(STORAGE_KEY, JSON.stringify(state.cart));
          storageError = "";
          return true;
        } catch {
          storageError = "Browser storage is unavailable or full. Changes may not be saved.";
          return false;
        }
      }

      function setStatus(message) {
        elements.status.textContent = message || storageError;
      }

      function renderFilters() {
        const categories = ["All", ...new Set(state.products.map(product => product.category))];
        elements.filters.replaceChildren();
        categories.forEach(category => {
          const button = document.createElement("button");
          button.type = "button";
          button.className = `filter-button${activeCategory === category ? " is-active" : ""}`;
          button.textContent = category;
          button.setAttribute("aria-pressed", String(activeCategory === category));
          button.addEventListener("click", () => {
            activeCategory = category;
            renderFilters();
            renderProducts();
          });
          elements.filters.append(button);
        });
      }

      function renderProducts() {
        elements.grid.replaceChildren();
        const products = state.products.filter(product => activeCategory === "All" || product.category === activeCategory);
        products.forEach(product => {
          const card = document.createElement("article");
          card.className = "product-card";
          const art = document.createElement("div");
          art.className = `product-art ${product.tone}`;
          art.setAttribute("aria-hidden", "true");
          const copy = document.createElement("div");
          copy.className = "product-copy";
          const nameAndCategory = document.createElement("div");
          const name = document.createElement("h3");
          name.textContent = product.name;
          const category = document.createElement("p");
          category.className = "product-category";
          category.textContent = `${product.category} · ${product.stock} in stock`;
          const price = document.createElement("div");
          price.className = "product-price";
          price.textContent = money(product.price);
          nameAndCategory.append(name, category);
          copy.append(nameAndCategory, price);
          const button = document.createElement("button");
          button.type = "button";
          button.className = "outline-button";
          button.disabled = product.stock < 1;
          button.textContent = product.stock ? "Add to bag" : "Sold out";
          button.addEventListener("click", () => addToCart(product.id));
          card.append(art, copy, button);
          elements.grid.append(card);
        });
        if (!products.length) {
          const empty = document.createElement("p");
          empty.className = "empty-note";
          empty.textContent = "No products are available in this collection yet.";
          elements.grid.append(empty);
        }
      }

      function renderCart() {
        elements.cartLines.replaceChildren();
        const cartEntries = Object.entries(state.cart).filter(([, quantity]) => quantity > 0);
        let itemCount = 0;
        let total = 0;
        cartEntries.forEach(([id, quantity]) => {
          const product = state.products.find(item => item.id === Number(id));
          if (!product) return;
          itemCount += quantity;
          total += product.price * quantity;
          const line = document.createElement("div");
          line.className = "cart-line";
          const name = document.createElement("span");
          name.textContent = product.name;
          const linePrice = document.createElement("span");
          linePrice.textContent = money(product.price * quantity);
          const controls = document.createElement("div");
          controls.className = "cart-quantity";
          const minus = document.createElement("button");
          minus.type = "button";
          minus.className = "quantity-button";
          minus.setAttribute("aria-label", `Remove one ${product.name}`);
          minus.textContent = "−";
          minus.addEventListener("click", () => changeQuantity(id, -1));
          const count = document.createElement("small");
          count.textContent = String(quantity);
          const plus = document.createElement("button");
          plus.type = "button";
          plus.className = "quantity-button";
          plus.setAttribute("aria-label", `Add one ${product.name}`);
          plus.textContent = "+";
          plus.disabled = quantity >= product.stock;
          plus.addEventListener("click", () => changeQuantity(id, 1));
          controls.append(minus, count, plus);
          const spacer = document.createElement("span");
          line.append(name, linePrice, controls, spacer);
          elements.cartLines.append(line);
        });
        if (!cartEntries.length) {
          const empty = document.createElement("p");
          empty.className = "empty-note";
          empty.textContent = "Your bag is waiting for something lovely.";
          elements.cartLines.append(empty);
        }
        elements.cartCount.textContent = `(${itemCount})`;
        elements.cartTotal.textContent = money(total);
        elements.checkoutButton.disabled = itemCount === 0;
      }

      function render() {
        renderFilters();
        renderProducts();
        renderCart();
        setStatus(storageError);
      }

      function addToCart(id) {
        const product = state.products.find(item => item.id === Number(id));
        const quantity = state.cart[id] || 0;
        if (!product || quantity >= product.stock) {
          setStatus("That item is no longer available in the selected quantity.");
          return;
        }
        state.cart[id] = quantity + 1;
        saveState();
        render();
        setStatus(`${product.name} added to your bag.`);
      }

      function changeQuantity(id, amount) {
        const product = state.products.find(item => item.id === Number(id));
        const nextQuantity = (state.cart[id] || 0) + amount;
        if (nextQuantity > (product?.stock || 0)) {
          setStatus("There is not enough stock for that quantity.");
          return;
        }
        if (nextQuantity <= 0) delete state.cart[id];
        else state.cart[id] = nextQuantity;
        saveState();
        render();
      }

      elements.checkoutButton.addEventListener("click", () => {
        if (!store.user) {
          window.location.href = "account.php?action=login";
          return;
        }
        const total = Object.entries(state.cart).reduce((sum, [id, quantity]) => {
          const product = state.products.find(item => item.id === Number(id));
          return sum + (product ? product.price * quantity : 0);
        }, 0);
        elements.checkoutSummary.textContent = `Order total: ${money(total)}. This will be saved as awaiting payment; no payment is taken.`;
        elements.checkoutDialog.showModal();
      });
      document.querySelector("#close-checkout-button").addEventListener("click", () => elements.checkoutDialog.close());
      document.querySelector("#place-demo-order-button").addEventListener("click", async event => {
        const button = event.currentTarget;
        if (!Object.keys(state.cart).length) {
          elements.checkoutDialog.close();
          setStatus("Your bag is empty.");
          return;
        }
        button.disabled = true;
        try {
          const response = await fetch("checkout.php", {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              "X-CSRF-Token": store.csrfToken
            },
            body: JSON.stringify({ cart: state.cart })
          });
          const result = await response.json();
          if (!response.ok) {
            if (response.status === 401) {
              window.location.href = "account.php?action=login";
              return;
            }
            throw new Error(result.error || "The order could not be submitted.");
          }
          state.cart = {};
          saveState();
          elements.checkoutDialog.close();
          render();
          setStatus(`Order ${result.orderNumber} saved. ${result.message}`);
        } catch (error) {
          setStatus(error instanceof Error ? error.message : "The order could not be submitted.");
        } finally {
          button.disabled = false;
        }
      });

      render();
    })();
  </script>
</body>
</html>
