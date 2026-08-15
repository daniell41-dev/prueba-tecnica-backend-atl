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

    /**
     * @dataProvider formatosValidos
     */
    public function test_acepta_los_separadores_habituales_de_formato(string $raw): void
    {
        $this->assertTrue(PhoneNumber::isWellFormed($raw));
    }

    /** @return array<string, array{string}> */
    public static function formatosValidos(): array
    {
        return [
            'lada con +' => ['+52 55 1234 5678'],
            'guiones' => ['55-1234-5678'],
            'paréntesis' => ['(55) 1234 5678'],
            'puntos' => ['55.1234.5678'],
            'solo dígitos' => ['5512345678'],
            'con espacios alrededor' => ['  5512345678  '],
        ];
    }

    /**
     * Regresión: `normalize()` descarta todo lo que no sea dígito, así que sin
     * `isWellFormed()` un número con letras se convertía en uno de 10 dígitos
     * válido y se guardaba como tal.
     *
     * @dataProvider formatosInvalidos
     */
    public function test_rechaza_texto_que_no_es_un_telefono(string $raw): void
    {
        $this->assertFalse(PhoneNumber::isWellFormed($raw));
    }

    /** @return array<string, array{string}> */
    public static function formatosInvalidos(): array
    {
        return [
            'letras en medio' => ['+52 dsadsadsa55 1234 5678'],
            'letras al inicio' => ['abc5512345678'],
            'texto alrededor' => ['hola mundo 5512345678 xyz'],
            'signo + fuera del inicio' => ['55+1234567890'],
            'símbolos' => ['5512345678;DROP'],
        ];
    }
}
