<?php
// --- 基本設定（サイトに合わせて変更してください） ---
$TO_ADDRESS   = 'info@marusam.com';      // 受信先（会社側の実アドレス）
$FROM_ADDRESS = 'info@marusam.com';      // 送信元（実在アドレスが推奨）
$SITE_NAME    = 'マルサン株式会社';
$THANKS_SUBJECT = '【'.$SITE_NAME.'】お問い合わせありがとうございます';
$ADMIN_SUBJECT  = '【お問い合わせ】'.$SITE_NAME;

// --- 文字化け対策 ---
mb_language('Japanese');
mb_internal_encoding('UTF-8');
date_default_timezone_set('Asia/Tokyo');

// --- セッション開始（CSRF対策用） ---
session_start();

// --- POSTのみ許可 ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit('Method Not Allowed');
}

// --- CSRFトークン検証 ---
if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token'])
    || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
  http_response_code(400);
  exit('Bad Request (invalid CSRF token)');
}
// 使い捨て（再送防止）
unset($_SESSION['csrf_token']);

// --- ハニーポット（ボット対策） ---
if (!empty($_POST['website'])) {
  header('Location: ./thanks.html');
  exit;
}

// --- 入力取得 ---
$name1   = trim((string)($_POST['name1'] ?? ''));
$name2   = trim((string)($_POST['name2'] ?? ''));
$kana1   = trim((string)($_POST['kana1'] ?? ''));
$kana2   = trim((string)($_POST['kana2'] ?? ''));
$tel     = trim((string)($_POST['tel'] ?? ''));
$fax     = trim((string)($_POST['fax'] ?? ''));
$email   = trim((string)($_POST['email'] ?? ''));
$subject = trim((string)($_POST['subject'] ?? ''));
$comment = trim((string)($_POST['comment'] ?? ''));
$doui    = $_POST['doui'] ?? '';

// --- バリデーション ---
$errors = [];
if ($name1 === '' && $name2 === '') { $errors[] = 'お名前は必須です。'; }
if ($kana1 === '' && $kana2 === '') { $errors[] = 'ふりがなは必須です。'; }
if ($tel === '') { $errors[] = '電話番号は必須です。'; }
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  $errors[] = 'メールアドレスの形式が正しくありません。';
}
if ($subject === '') { $errors[] = 'お問い合わせ種別を選択してください。'; }
if ($comment === '') { $errors[] = 'お問い合わせ内容は必須です。'; }
if ($doui !== '1') { $errors[] = 'プライバシーポリシーへの同意が必要です。'; }

if (!empty($errors)) {
  http_response_code(400);
  header('Content-Type: text/plain; charset=UTF-8');
  echo "入力エラー:\n- " . implode("\n- ", $errors);
  exit;
}

// --- 整形 ---
$fullname = $name1 . ' ' . $name2;
$furigana = $kana1 . ' ' . $kana2;
$datetime = date('Y-m-d H:i:s');
$ip       = $_SERVER['REMOTE_ADDR'] ?? '';
$ua       = $_SERVER['HTTP_USER_AGENT'] ?? '';

// --- 管理者向けメール本文 ---
$admin_body = "以下の内容でお問い合わせを受け付けました。\n"
  ."--------------------------------------------------\n"
  ."お名前: {$fullname}\n"
  ."ふりがな: {$furigana}\n"
  ."電話: {$tel}\n"
  ."FAX: {$fax}\n"
  ."メール: {$email}\n"
  ."種別: {$subject}\n"
  ."日時: {$datetime}\n"
  ."IP: {$ip}\n"
  ."UA: {$ua}\n"
  ."--------------------------------------------------\n\n"
  .$comment."\n";

// --- 自動返信メール本文（お客様宛） ---
$thanks_body  = "{$fullname} 様\n\n";
$thanks_body .= "このたびは『{$SITE_NAME}』へお問い合わせいただきありがとうございます。\n";
$thanks_body .= "下記の内容で受け付けいたしました。担当者より折り返しご連絡いたします。\n\n";
$thanks_body .= "――――――――――――――――――――――――――――\n";
$thanks_body .= "お名前: {$fullname}\n";
$thanks_body .= "ふりがな: {$furigana}\n";
$thanks_body .= "電話: {$tel}\n";
if ($fax !== '') { $thanks_body .= "FAX: {$fax}\n"; }
$thanks_body .= "メール: {$email}\n";
$thanks_body .= "種別: {$subject}\n";
$thanks_body .= "――――――――――――――――――――――――――――\n\n";
$thanks_body .= $comment . "\n\n";
$thanks_body .= "※本メールは送信専用です。心当たりが無い場合は破棄してください。\n";
$thanks_body .= "{$SITE_NAME}\n";

// --- 共通ヘッダ ---
$from_name_encoded = mb_encode_mimeheader($SITE_NAME, 'UTF-8');
$headers_common = [
  'MIME-Version: 1.0',
  'Content-Type: text/plain; charset=UTF-8',
  'Content-Transfer-Encoding: 8bit',
  'From: '.$from_name_encoded.' <'.$FROM_ADDRESS.'>'
];
$headers_admin = $headers_common;
$headers_admin[] = 'Reply-To: '.$fullname.' <'.$email.'>';

$headers_user = $headers_common;
$headers_user[] = 'Reply-To: '.$from_name_encoded.' <'.$TO_ADDRESS.'>';

// Return-Path対策
$additional_params = '-f ' . escapeshellarg($FROM_ADDRESS);

// --- メール送信 ---
$ok_admin = @mb_send_mail($TO_ADDRESS, $ADMIN_SUBJECT, $admin_body, implode("\r\n", $headers_admin), $additional_params);

$ok_user = false;
if ($ok_admin) {
  $ok_user = @mb_send_mail($email, $THANKS_SUBJECT, $thanks_body, implode("\r\n", $headers_user), $additional_params);
}

// --- 遷移 ---
if ($ok_admin) {
  header('Location: ./thanks.html');
  exit;
} else {
  http_response_code(500);
  echo "メール送信に失敗しました。サーバ設定（Fromアドレス/SPFなど）をご確認ください。";
  exit;
}
