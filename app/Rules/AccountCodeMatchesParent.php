<?php

namespace App\Rules;

use App\Models\Account;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Laporan mengelompokkan akun menurut awalan kode, jadi kode harus sesuai kelompok induknya.
 */
class AccountCodeMatchesParent implements ValidationRule
{
    public function __construct(private ?int $parentId, private ?string $name) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $parent = $this->parentId ? Account::find($this->parentId) : null;

        if (! $parent) {
            return;
        }

        // Kelompok 1, 2, dan 7 dibedakan dua digit di laporan (11/12, 21/22, 71/72).
        $length = in_array($parent->code[0], ['1', '2', '7'], true) ? 2 : 1;
        $prefix = substr($parent->code, 0, $length);

        if (! str_starts_with((string) $value, $prefix)) {
            $fail("Kode akun di bawah {$parent->code} harus berawalan {$prefix}.");

            return;
        }

        // Arus Kas mengenali kas dan bank dari kode 111x dan 112x.
        if (preg_match('/^(kas|bank)\b/i', (string) $this->name) && ! preg_match('/^11[12]/', (string) $value)) {
            $fail('Akun kas dan bank harus memakai kode 111x atau 112x.');
        }
    }
}
