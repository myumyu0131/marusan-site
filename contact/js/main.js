$(document).ready(function(){
  $('.slide').slick({
    autoplay: true,         // 自動再生
    autoplaySpeed: 3000,    // 次の画像までの時間（ミリ秒）
    dots: true,             // 下のドット表示
    arrows: false,          // 左右の矢印なし
    fade: true,             // フェード切り替え
    speed: 1000             // 切り替えスピード
  });
});
