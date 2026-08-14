<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\PhoneNumber;
use PHPUnit\Framework\TestCase;

final class PhoneNumberTest extends TestCase
{
    public function test_deja_solo_digitos(): void
    {
        $this->assertSame('5512345678', PhoneNumber::normalize('55-1234-5678'));
    }

    public function test_quita_la_lada_de_mexico_cuando_el_numero_trae_12_digitos(): void
    {
        $this->assertSame('5512345678', PhoneNumber::normalize('+52 55 1234 5678'));
    }

    public function test_no_toca_un_numero_nacional_de_10_digitos(): void
    {
        $this->assertSame('5512345678', PhoneNumber::normalize('5512345678'));
    }

    public function test_no_quita_digitos_de_un_numero_que_no_trae_lada(): void
    {
        // 12 dígitos pero sin empezar con "52": no es la lada MX, se deja tal cual.
        $this->assertSame('123456789012', PhoneNumber::normalize('123456789012'));
    }

    public function test_devuelve_cadena_vacia_para_entradas_vacias(): void
    {
        $this->assertSame('', PhoneNumber::normalize(''));
        $this->assertSame('', PhoneNumber::normalize('   '));
    }
}
