<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Contact;
use App\Domain\Exception\ValidationException;
use App\Validation\ContactValidator;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryContactRepository;

final class ContactValidatorTest extends TestCase
{
    private InMemoryContactRepository $repository;
    private ContactValidator $validator;

    protected function setUp(): void
    {
        $this->repository = new InMemoryContactRepository();
        $this->validator = new ContactValidator($this->repository);
    }

    public function test_acepta_un_contacto_valido_con_varios_telefonos(): void
    {
        $input = $this->validator->validate([
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.com',
            'phones' => [
                ['type' => 'mobile', 'number' => '+52 55 1111 2222'],
                ['type' => 'work', 'number' => '5533334444'],
            ],
        ]);

        $this->assertSame('Ada', $input->firstName);
        $this->assertCount(2, $input->phones);
        $this->assertSame('5511112222', $input->phones[0]->number);
    }

    public function test_acepta_un_contacto_sin_telefonos(): void
    {
        $input = $this->validator->validate([
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.com',
        ]);

        $this->assertSame([], $input->phones);
    }

    public function test_rechaza_nombre_apellido_y_email_vacios(): void
    {
        try {
            $this->validator->validate(['first_name' => '', 'last_name' => '', 'email' => '']);
            $this->fail('Se esperaba una ValidationException.');
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
            $this->assertArrayHasKey('first_name', $errors);
            $this->assertArrayHasKey('last_name', $errors);
            $this->assertArrayHasKey('email', $errors);
        }
    }

    public function test_rechaza_un_formato_de_email_invalido(): void
    {
        $this->expectException(ValidationException::class);

        $this->validator->validate(['first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'no-es-un-correo']);
    }

    public function test_rechaza_un_email_ya_registrado_sin_importar_mayusculas(): void
    {
        $this->repository->create(Contact::createNew('Ana', 'Pérez', 'ana@correo.mx', null, false, []));

        try {
            $this->validator->validate(['first_name' => 'Otra', 'last_name' => 'Persona', 'email' => 'ANA@correo.mx']);
            $this->fail('Se esperaba una ValidationException.');
        } catch (ValidationException $exception) {
            $this->assertSame(['El correo ya está registrado.'], $exception->errors()['email']);
        }
    }

    public function test_rechaza_nombre_con_caracteres_no_permitidos(): void
    {
        try {
            $this->validator->validate(['first_name' => 'Ada123', 'last_name' => 'Lovelace', 'email' => 'ada@example.com']);
            $this->fail('Se esperaba una ValidationException.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('first_name', $exception->errors());
        }
    }

    public function test_rechaza_un_telefono_con_menos_de_10_digitos(): void
    {
        try {
            $this->validator->validate([
                'first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada@example.com',
                'phones' => [['type' => 'mobile', 'number' => '123']],
            ]);
            $this->fail('Se esperaba una ValidationException.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('phones.0.number', $exception->errors());
        }
    }

    /**
     * Regresión: un número con letras (`"+52 dsadsadsa55 1234 5678"`) se
     * normalizaba a 10 dígitos válidos y el contacto se creaba igual.
     */
    public function test_rechaza_un_telefono_con_letras_aunque_al_normalizarlo_de_10_digitos(): void
    {
        try {
            $this->validator->validate([
                'first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada@example.com',
                'phones' => [['type' => 'mobile', 'number' => '+52 dsadsadsa55 1234 5678']],
            ]);
            $this->fail('Se esperaba una ValidationException.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('phones.0.number', $exception->errors());
        }
    }

    public function test_rechaza_un_tipo_de_telefono_desconocido(): void
    {
        try {
            $this->validator->validate([
                'first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada@example.com',
                'phones' => [['type' => 'fax', 'number' => '5512345678']],
            ]);
            $this->fail('Se esperaba una ValidationException.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('phones.0.type', $exception->errors());
        }
    }

    public function test_rechaza_telefonos_duplicados_en_el_mismo_contacto(): void
    {
        try {
            $this->validator->validate([
                'first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada@example.com',
                'phones' => [
                    ['type' => 'mobile', 'number' => '5512345678'],
                    ['type' => 'work', 'number' => '+52 55 1234 5678'],
                ],
            ]);
            $this->fail('Se esperaba una ValidationException.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('phones.0.number', $exception->errors());
            $this->assertArrayHasKey('phones.1.number', $exception->errors());
        }
    }

    public function test_rechaza_mas_de_diez_telefonos(): void
    {
        $phones = array_fill(0, 11, ['type' => 'mobile', 'number' => '5512345678']);

        try {
            $this->validator->validate([
                'first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada@example.com',
                'phones' => $phones,
            ]);
            $this->fail('Se esperaba una ValidationException.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('phones', $exception->errors());
        }
    }

    public function test_normaliza_company_vacia_a_null(): void
    {
        $input = $this->validator->validate([
            'first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada@example.com', 'company' => '   ',
        ]);

        $this->assertNull($input->company);
    }

    public function test_usa_mobile_como_tipo_de_telefono_por_defecto(): void
    {
        $input = $this->validator->validate([
            'first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada@example.com',
            'phones' => [['number' => '5512345678']],
        ]);

        $this->assertSame('mobile', $input->phones[0]->type->value);
    }
}
