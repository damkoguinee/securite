<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260306093952 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE avance_salaire ADD contrat_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE avance_salaire ADD CONSTRAINT FK_6DA8D1051823061F FOREIGN KEY (contrat_id) REFERENCES contrat_surveillance (id)');
        $this->addSql('CREATE INDEX IDX_6DA8D1051823061F ON avance_salaire (contrat_id)');
        $this->addSql('ALTER TABLE paiement_salaire_personnel ADD contrat_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE paiement_salaire_personnel ADD CONSTRAINT FK_B221A0971823061F FOREIGN KEY (contrat_id) REFERENCES contrat_surveillance (id)');
        $this->addSql('CREATE INDEX IDX_B221A0971823061F ON paiement_salaire_personnel (contrat_id)');
        $this->addSql('ALTER TABLE personel CHANGE statut_planning statut_planning VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_REFERENCE ON user (reference)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE avance_salaire DROP FOREIGN KEY FK_6DA8D1051823061F');
        $this->addSql('DROP INDEX IDX_6DA8D1051823061F ON avance_salaire');
        $this->addSql('ALTER TABLE avance_salaire DROP contrat_id');
        $this->addSql('ALTER TABLE paiement_salaire_personnel DROP FOREIGN KEY FK_B221A0971823061F');
        $this->addSql('DROP INDEX IDX_B221A0971823061F ON paiement_salaire_personnel');
        $this->addSql('ALTER TABLE paiement_salaire_personnel DROP contrat_id');
        $this->addSql('ALTER TABLE personel CHANGE statut_planning statut_planning VARCHAR(100) DEFAULT \'actif\'');
        $this->addSql('DROP INDEX UNIQ_IDENTIFIER_REFERENCE ON user');
        $this->addSql('ALTER TABLE user CHANGE roles roles LONGTEXT NOT NULL COLLATE `utf8mb4_bin`');
    }
}
