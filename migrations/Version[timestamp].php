<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version[timestamp] extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE games MODIFY player1_card_id INT NULL');
        $this->addSql('ALTER TABLE games MODIFY player2_card_id INT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE games MODIFY player1_card_id INT NOT NULL');
        $this->addSql('ALTER TABLE games MODIFY player2_card_id INT NOT NULL');
    }
} 