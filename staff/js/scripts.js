$(function () {
  // ヘッダー固定処理
  const header = document.querySelector('#header');
  if (header) {
    function navbarShrink() {
      if (window.scrollY === 0) {
        header.classList.remove('fixed');
      } else {
        header.classList.add('fixed');
      }
    }
    navbarShrink();
    document.addEventListener('scroll', navbarShrink);
  }

  // スクロールでヘッダーに over クラス
  let setHeader = function () {
    let sTop = $(window).scrollTop();
    if (sTop > 0) {
      $('#header').addClass('over');
    } else {
      $('#header').removeClass('over');
    }
  };
  setHeader();
  $(window).on('scroll resize', setHeader);

  // ✅ ハンバーガーメニュー開閉
  $('.h_menu_open').on('click', function () {
    $('body').addClass('open');
    $('#toggle_menu').fadeIn();
  });

  $('.h_menu_close, #toggle_menu a').on('click', function () {
    $('body').removeClass('open');
    $('#toggle_menu').fadeOut();
  });

  // Bootstrap ScrollSpy（使うなら）
  if (typeof bootstrap !== 'undefined') {
    new bootstrap.ScrollSpy(document.body, {
      target: '#header',
      rootMargin: '0px 0px -40%',
    });
  }

  // モバイルで自動クローズ（bootstrapナビ対応用）
  const navbarToggler = document.querySelector('.navbar-toggler');
  const responsiveNavItems = document.querySelectorAll('#navbarResponsive .nav-link');
  responsiveNavItems.forEach(item => {
    item.addEventListener('click', () => {
      if (window.getComputedStyle(navbarToggler).display !== 'none') {
        navbarToggler.click();
      }
    });
  });
});
