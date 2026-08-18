<?php

declare(strict_types=1);

namespace Foovar\JpInvoice;

use Foovar\JpInvoice\Enum\RoundingMode;
use Foovar\JpInvoice\Exception\InvalidRegistrationNumberException;
use Foovar\JpInvoice\Validation\RegistrationNumber;

/**
 * 適格請求書発行事業者（記載事項1）。
 *
 * 端数処理の方法は継続適用が求められるため、請求書ごとではなく発行事業者が保持する
 * （要件定義書 §3.4）。同じ事業者が出す請求書の間で丸めがブレない構造にするのが目的。
 */
final class Issuer
{
    public readonly ?RegistrationNumber $registrationNumber;

    /**
     * @param string|RegistrationNumber|null $registrationNumber
     *        登録番号。空文字・null は「未登録」として保持し、validate() 時に記載事項不足として扱う。
     *        形式が不正な文字列は、この時点で例外にする（気付くのが早いほうがよいため）。
     *
     * @throws InvalidRegistrationNumberException 登録番号の形式が不正なとき
     */
    public function __construct(
        public readonly string $name,
        string|RegistrationNumber|null $registrationNumber = null,
        public readonly RoundingMode $roundingMode = RoundingMode::FLOOR,
    ) {
        if ($registrationNumber instanceof RegistrationNumber) {
            $this->registrationNumber = $registrationNumber;
        } elseif ($registrationNumber === null || trim($registrationNumber) === '') {
            $this->registrationNumber = null;
        } else {
            $this->registrationNumber = RegistrationNumber::fromString($registrationNumber);
        }
    }
}
