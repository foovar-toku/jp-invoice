<?php

declare(strict_types=1);

/**
 * 登録番号チェッカー。
 *
 * インボイスの登録番号（T + 13桁）の形式を検証し、法人番号のチェックディジット規則と
 * 整合するかを計算過程つきで表示する。計算はライブラリ（foovar/jp-invoice）に任せる。
 *
 * 注意: 実在確認ではない。国税庁の公表サイトへの照会は行っていない。
 */

require dirname(__DIR__, 3) . '/autoload.php';
require dirname(__DIR__, 3) . '/layout.php';

use Foovar\JpInvoice\Exception\InvalidRegistrationNumberException;
use Foovar\JpInvoice\Validation\RegistrationNumber;

$input = trim((string) ($_POST['number'] ?? ''));
$error = null;
$number = null;
$steps = null;

if ($input !== '') {
    try {
        $number = RegistrationNumber::fromString($input);

        // チェックディジットの計算過程を表示用に組み立てる
        $digits = $number->digits();
        $base = substr($digits, 1);
        $length = strlen($base);
        $odd = 0;
        $even = 0;
        for ($n = 1; $n <= $length; $n++) {
            $digit = (int) $base[$length - $n];
            if ($n % 2 === 1) {
                $odd += $digit;
            } else {
                $even += $digit;
            }
        }
        $sum = $even * 2 + $odd;
        $steps = [
            'base' => $base,
            'odd' => $odd,
            'even' => $even,
            'sum' => $sum,
            'mod' => $sum % 9,
            'expected' => RegistrationNumber::calculateCheckDigit($base),
            'actual' => (int) $digits[0],
        ];
    } catch (InvalidRegistrationNumberException $e) {
        $error = $e->getMessage();
    }
}

page_head(
    title: '登録番号チェッカー — インボイスの登録番号と法人番号チェックデジットを確認',
    description: 'インボイスの登録番号（T+13桁）の形式を検証し、法人番号のチェックディジット規則と整合するかを計算過程つきで表示します。全角・ハイフン・小文字も正規化。個人事業者の登録番号は法人番号規則に従わない点も解説。',
    path: '/tools/registration-number/',
);
?>
<header>
  <h1>登録番号チェッカー</h1>
  <p class="lead">インボイスの登録番号（<code>T</code> + 13桁）の形式と、法人番号のチェックディジットを確認します。
  全角数字・ハイフン・小文字の <code>t</code> は自動で正規化します。</p>
</header>

<section>
  <form method="post">
    <label style="font-size:14px">登録番号
      <input type="text" name="number" value="<?= h($input) ?>" placeholder="T8700110005901"
             style="width:100%;max-width:360px;font-size:18px;letter-spacing:.05em" autofocus>
    </label>
    <button type="submit">確認する</button>
  </form>

<?php if ($error !== null): ?>
  <p class="err" style="margin-top:18px">形式エラー: <?= h($error) ?></p>
  <p style="font-size:14px;color:var(--muted)">登録番号は <code>T</code> に続けて数字13桁です（合計14文字）。</p>
<?php elseif ($number !== null && $steps !== null): ?>
  <div class="verdict" style="margin-top:22px">
    <div class="card good">
      <div class="tag">形式</div>
      <div class="amount" style="font-size:22px">有効</div>
      <div class="tag">正規化後: <strong><?= h($number->value) ?></strong></div>
    </div>
    <div class="card <?= $number->matchesCorporateNumberRule() ? 'good' : 'bad' ?>">
      <div class="tag">法人番号のチェックディジット</div>
      <div class="amount" style="font-size:22px"><?= $number->matchesCorporateNumberRule() ? '一致' : '不一致' ?></div>
      <div class="tag"><?= $number->matchesCorporateNumberRule()
          ? '法人番号として整合します'
          : '個人事業者の登録番号の可能性があります（後述）' ?></div>
    </div>
  </div>

  <h2>チェックディジットの計算過程</h2>
  <div class="scroll">
  <table>
    <tbody>
      <tr><td>基礎番号（下12桁）</td><td class="num"><code><?= h($steps['base']) ?></code></td></tr>
      <tr><td>最下位から奇数桁の和</td><td class="num"><?= $steps['odd'] ?></td></tr>
      <tr><td>最下位から偶数桁の和</td><td class="num"><?= $steps['even'] ?></td></tr>
      <tr><td>偶数桁の和 × 2 + 奇数桁の和</td><td class="num"><?= $steps['even'] ?> × 2 + <?= $steps['odd'] ?> = <strong><?= $steps['sum'] ?></strong></td></tr>
      <tr><td><?= $steps['sum'] ?> ÷ 9 の余り</td><td class="num"><?= $steps['mod'] ?></td></tr>
      <tr><td>9 − 余り＝チェックディジット</td><td class="num">9 − <?= $steps['mod'] ?> = <strong><?= $steps['expected'] ?></strong></td></tr>
      <tr><td>入力された先頭1桁</td><td class="num"><strong><?= $steps['actual'] ?></strong>
        <?= $steps['actual'] === $steps['expected'] ? '（一致）' : '（不一致）' ?></td></tr>
    </tbody>
  </table>
  </div>
<?php else: ?>
  <p style="font-size:14px;color:var(--muted);margin-top:16px">
    例: <code>T8700110005901</code>（国税庁の公表資料に載っている検算例）
  </p>
<?php endif; ?>
</section>

<section>
  <h2>チェックディジットが「不一致」でも無効とは限りません</h2>
  <p>登録番号は <code>T</code> + 13桁で、<strong>法人の場合は</strong>13桁が法人番号と一致します。
  法人番号にはチェックディジットがあるため検証できますが、
  <strong>個人事業者の登録番号は法人番号ではなく、この規則に従いません</strong>。</p>
  <p>したがって実装では、</p>
  <ul>
    <li><strong>形式検証（T + 13桁）はエラー</strong>として扱う</li>
    <li><strong>チェックディジット検証は参考情報</strong>にとどめ、不一致でも受け付ける</li>
  </ul>
  <p>と分けるのが安全です。不一致を理由に登録を弾く実装は、個人事業者を排除してしまいます。</p>
  <p class="related">なお本ツールは<strong>実在確認ではありません</strong>。番号が実際に登録されているかは、
  国税庁「適格請求書発行事業者公表サイト」でご確認ください。</p>
</section>

<section>
  <h2>計算式（国税庁 法人番号公表サイト）</h2>
  <pre><code>下12桁を P1..P12（最下位が P1）とする
Qn = 1（n が奇数）／ 2（n が偶数）
チェックディジット = 9 −（Σ(n=1..12) Pn × Qn） mod 9</code></pre>
  <p>公表資料の検算例: 基礎番号 <code>700110005901</code> → 偶数桁の和 13、奇数桁の和 11、
  13 × 2 + 11 = 37、37 mod 9 = 1、9 − 1 = 8 → 法人番号 <code>8700110005901</code>。</p>
</section>

<section>
  <h2>プログラムから使う</h2>
  <pre><code>use Foovar\JpInvoice\Validation\RegistrationNumber;

$number = RegistrationNumber::fromString('t8700110-005901');  // 小文字・ハイフンも正規化
$number-&gt;value;                        // 'T8700110005901'
$number-&gt;matchesCorporateNumberRule();  // true

RegistrationNumber::calculateCheckDigit('700110005901');  // 8</code></pre>
</section>

<div class="cta">
  <p><strong>foovar/jp-invoice</strong> — インボイス対応の消費税計算ライブラリ（MIT・無料）</p>
  <p style="font-size:14px"><code>composer require foovar/jp-invoice</code><br>
  登録番号の検証のほか、端数処理・記載事項の検証・返還インボイス・経過措置に対応。
  <a href="https://github.com/foovar-toku/jp-invoice">GitHub</a> ／
  <a href="/">端数処理のデモ</a> ／ <a href="/guide/">実装ガイド</a></p>
</div>
<?php page_foot(); ?>
