<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/layout.php';

/** @var array<string, mixed> $config */
$config = require dirname(__DIR__, 2) . '/config.php';
/** @var array<string, string> $company */
$company = $config['company'];

page_head(
    title: '利用許諾の内容 — jp-invoice-pdf（商用パッケージ）',
    description: 'jp-invoice-pdf の利用許諾の要点。許諾範囲（1社1プロダクト・台数無制限）、再配布と改変の可否、委託先・退職者の扱い、アップデート受領権、第三者ソフトウェア（TCPDF・IPAexフォント）の扱いをまとめています。',
    path: '/license/',
);
?>
<header>
  <h1>利用許諾の内容</h1>
  <p class="lead">商用パッケージ <strong>jp-invoice-pdf</strong> の許諾範囲です。
  ご購入前にご確認ください。正式な契約書は納品時にお渡しします。</p>
</header>

<section>
  <h2>できること</h2>
  <ul>
    <li><strong>1社1プロダクトへの組み込み</strong>。貴社が開発・運用するシステム1つに組み込めます</li>
    <li><strong>インストール台数・稼働環境数の制限はありません</strong>。本番・ステージング・開発環境、
      サーバ台数、コンテナ数を問いません</li>
    <li><strong>ソースコードの閲覧と改変</strong>。そのプロダクトの開発・保守の目的で自由に変更できます</li>
    <li><strong>永続利用</strong>。アップデート受領権が切れても、購入時点のバージョンは使い続けられます</li>
    <li><strong>委託先・協力会社の開発者</strong>も、貴社のプロダクト開発に従事する範囲で利用できます</li>
    <li><strong>退職・入れ替え</strong>があっても再購入は不要です（人数課金ではありません）</li>
  </ul>
</section>

<section>
  <h2>できないこと</h2>
  <ul>
    <li>パッケージそのものの<strong>再配布・再販売・貸与・公開</strong>（改変したものを含む）</li>
    <li><strong>公開リポジトリへの掲載</strong>。private リポジトリでの社内管理は問題ありません</li>
    <li>これを主たる価値とする製品（帳票生成ライブラリ・SDK 等）としての提供</li>
    <li>許諾を受けたプロダクト以外への組み込み。<strong>2つ目のプロダクトには追加のライセンス</strong>が必要です</li>
  </ul>
  <p style="font-size:14px">
    SaaS に組み込んで多数のエンドユーザに提供する場合は、上記との関係を個別に協議します
    （OEM・再販ライセンスとして別途お見積り）。判断に迷う場合はご相談ください。
  </p>
</section>

<section>
  <h2>譲渡・組織変更</h2>
  <ul>
    <li>本ライセンスの<strong>第三者への譲渡・貸与はできません</strong>（合併・会社分割等により
      許諾を受けたプロダクトの事業を承継する場合は、事前にご連絡いただければ承継を認めます）</li>
    <li>グループ会社が<strong>別のプロダクト</strong>に組み込む場合は、別途ライセンスが必要です</li>
    <li>許諾を受けたプロダクトの開発・保守のために、社内で必要な数だけ複製して構いません</li>
  </ul>
</section>

<section>
  <h2>アップデートとサポート</h2>
  <ul>
    <li>ご購入から<strong>1年間</strong>、消費税法その他の関連法令の改正に対応したアップデートを提供します</li>
    <li>同期間、メールでの技術的なお問い合わせに対応します</li>
    <li>更新は年額（買い切り価格の50%）。<strong>更新しなくても、購入時点のバージョンは使い続けられます</strong></li>
  </ul>
  <p style="font-size:14px">
    経過措置の控除割合は 80% → 70% → 50% → 30% → 0% と段階的に変わり、今後も改正が見込まれます。
    アップデート受領権は、この追随を継続的に受け取るためのものです。
  </p>
</section>

<section>
  <h2>第三者ソフトウェア</h2>
  <div class="scroll">
  <table>
    <thead><tr><th>対象</th><th>ライセンス</th><th>扱い</th></tr></thead>
    <tbody>
      <tr><td>TCPDF</td><td>LGPL-3.0-or-later</td>
        <td>Composer の依存として利用。<strong>当社は改変していません</strong>ので、貴社が別バージョンに差し替えることもできます</td></tr>
      <tr><td>IPAex ゴシック</td><td>IPA フォントライセンス v1.0</td>
        <td>パッケージに同梱。PDF への埋め込みは同ライセンスで許諾されています</td></tr>
      <tr><td>foovar/jp-invoice（計算コア）</td><td>MIT</td>
        <td>無料の OSS。単体でもご利用いただけます</td></tr>
    </tbody>
  </table>
  </div>
</section>

<section>
  <h2>保証と責任</h2>
  <p>本ソフトウェアは<strong>帳票の作成と消費税額の計算を補助するもの</strong>であり、
  税務上の判断または助言を提供するものではありません。個別の取引における消費税の取扱い、および
  交付する書類が法令の要件を満たすか否かの最終的な判断は、税理士等の専門家にご確認ください。</p>
  <p>当社の損害賠償責任は、当社の故意または重過失による場合を除き、
  お支払いいただいたライセンス料の額を上限とします。逸失利益・事業機会の喪失・データの損失
  その他の間接損害については責任を負いません。</p>
</section>

<section>
  <h2>準拠法・管轄</h2>
  <p>本利用許諾は<strong>日本法</strong>に準拠します。本許諾に関する紛争は、
  <strong>東京地方裁判所</strong>を第一審の専属的合意管轄裁判所とします。</p>
  <p style="font-size:14px;color:var(--muted)">
    本ページは許諾内容の要点をまとめたものです。正式な契約書（利用許諾契約書）は納品時にお渡しします。
    貴社の様式での締結をご希望の場合もご相談ください。
  </p>
</section>

<div class="cta">
  <p><strong>ご不明な点はご購入前にお問い合わせください。</strong></p>
  <p style="font-size:14px">
    <?= h($company['email']) ?> ／ <?= h($company['name']) ?>（登録番号 <?= h($company['registration_number']) ?>）<br>
    <a href="/legal/">特定商取引法に基づく表記</a> ／ <a href="/">購入ページへ戻る</a>
  </p>
</div>
<?php page_foot(); ?>
