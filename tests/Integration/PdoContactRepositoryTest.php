<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Domain\Contact;
use App\Domain\Phone;
use App\Domain\PhoneType;
use App\Infrastructure\Database\Connection;
use App\Infrastructure\Database\Migrator;
use App\Infrastructure\Persistence\PdoContactRepository;
use PHPUnit\Framework\TestCase;

/**
 * Contra SQLite en memoria (real PDO, sin mocks): valida que el repositorio
 * persiste, hidrata y borra en cascada correctamente.
 */
final class PdoContactRepositoryTest extends TestCase
{
    private PdoContactRepository $repository;

    protected function setUp(): void
    {
        Connection::reset();
        $pdo = Connection::make();
        $this->repository = new PdoContactRepository($pdo);
        (new Migrator($pdo, $this->repository))->fresh();
    }

    public function test_crea_un_contacto_con_varios_telefonos_y_los_conserva_en_orden(): void
    {
        $contact = Contact::createNew('Ada', 'Lovelace', 'ada@example.com', 'Analytical Engine', true, [
            Phone::create(PhoneType::Mobile, '5511112222'),
            Phone::create(PhoneType::Work, '5533334444'),
        ]);

        $this->repository->create($contact);
        $found = $this->repository->find($contact->id);

        $this->assertNotNull($found);
        $this->assertSame('Ada', $found->firstName);
        $this->assertTrue($found->favorite);
        $this->assertCount(2, $found->phones);
        $this->assertSame('5511112222', $found->phones[0]->number);
        $this->assertSame('5533334444', $found->phones[1]->number);
    }

    public function test_all_devuelve_los_telefonos_de_cada_contacto_ordenados_por_nombre(): void
    {
        $this->repository->create(
            Contact::createNew('Beto', 'Uno', 'beto@example.com', null, false, [Phone::create(PhoneType::Mobile, '5511112222')]),
        );
        $this->repository->create(
            Contact::createNew('Ana', 'Dos', 'ana2@example.com', null, false, [Phone::create(PhoneType::Home, '5522223333')]),
        );

        $all = $this->repository->all();

        $this->assertCount(2, $all);
        $this->assertSame('Ana', $all[0]->firstName);
        $this->assertSame('5522223333', $all[0]->phones[0]->number);
        $this->assertSame('Beto', $all[1]->firstName);
    }

    public function test_delete_elimina_el_contacto_y_sus_telefonos_en_cascada(): void
    {
        $contact = Contact::createNew('Ada', 'Lovelace', 'ada@example.com', null, false, [
            Phone::create(PhoneType::Mobile, '5511112222'),
        ]);
        $this->repository->create($contact);

        $deleted = $this->repository->delete($contact->id);

        $this->assertTrue($deleted);
        $this->assertNull($this->repository->find($contact->id));

        $orphanPhones = (int) Connection::make()->query('SELECT COUNT(*) FROM phones')->fetchColumn();
        $this->assertSame(0, $orphanPhones);
    }

    public function test_delete_de_un_id_inexistente_devuelve_false(): void
    {
        $this->assertFalse($this->repository->delete('no-existe'));
    }

    public function test_email_exists_es_insensible_a_mayusculas(): void
    {
        $this->repository->create(Contact::createNew('Ada', 'Lovelace', 'ada@example.com', null, false, []));

        $this->assertTrue($this->repository->emailExists('ADA@EXAMPLE.COM'));
        $this->assertFalse($this->repository->emailExists('otra@example.com'));
    }

    public function test_email_exists_excluye_el_id_indicado(): void
    {
        $contact = Contact::createNew('Ada', 'Lovelace', 'ada@example.com', null, false, []);
        $this->repository->create($contact);

        $this->assertFalse($this->repository->emailExists('ada@example.com', $contact->id));
    }

    public function test_find_devuelve_null_si_el_id_no_existe(): void
    {
        $this->assertNull($this->repository->find('no-existe'));
    }
}
